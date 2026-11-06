package main

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"strings"
	"time"
)

type WorkoutItem struct {
	ExerciseName string  `json:"exercise_name"`
	TargetMuscle string  `json:"target_muscle"`
	Weight       float64 `json:"weight"`
	Reps         int     `json:"reps"`
	Sets         int     `json:"sets"`
	Notes        string  `json:"notes"`
}

type ParseWorkoutRequest struct {
	Text string `json:"text"`
}

type ParseWorkoutResponse struct {
	Workouts []WorkoutItem `json:"workouts"`
}

type GoalInfo struct {
	Height             float64 `json:"height"`
	Age                int     `json:"age"`
	Gender             string  `json:"gender"`
	WorkoutEnvironment string  `json:"workout_environment"`
	TargetWeight       float64 `json:"target_weight"`
	TargetBodyFat      float64 `json:"target_body_fat"`
	StartWeight        float64 `json:"start_weight"`
	TargetDate         string  `json:"target_date"`
	Objective          string  `json:"objective"`
}

type MetricInfo struct {
	RecordDate string        `json:"record_date"`
	Weight     float64       `json:"weight"`
	BodyFat    float64       `json:"body_fat"`
	Memo       string        `json:"memo"`
	Workouts   []WorkoutItem `json:"workouts"`
}

type GenerateAdviceRequest struct {
	Goal          GoalInfo     `json:"goal"`
	LatestMetric  MetricInfo   `json:"latest_metric"`
	RecentHistory []MetricInfo `json:"recent_history"`
}

type RecommendedWorkout struct {
	TargetMuscle    string `json:"target_muscle"`
	Exercise        string `json:"exercise"`
	SuggestedWeight string `json:"suggested_weight"`
	SuggestedSets   string `json:"suggested_sets"`
	Point           string `json:"point"`
}

type AdviceResponse struct {
	PaceStatus         string             `json:"pace_status"`
	PaceStatusLabel    string             `json:"pace_status_label"`
	PaceMessage        string             `json:"pace_message"`
	TimelineGap        string             `json:"timeline_gap"`
	NextAction         string             `json:"next_action"`
	RecommendedWorkout RecommendedWorkout `json:"recommended_workout"`
	LifestyleTip       string             `json:"lifestyle_tip"`
}

var (
	apiKey = ""
	model  = "gemini-3.5-flash"
)

func loadEnvFile(path string) {
	data, err := os.ReadFile(path)
	if err != nil {
		return
	}
	lines := strings.Split(string(data), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}
		parts := strings.SplitN(line, "=", 2)
		if len(parts) == 2 {
			k := strings.TrimSpace(parts[0])
			v := strings.Trim(strings.TrimSpace(parts[1]), `"'`)
			os.Setenv(k, v)
		}
	}
}

func callGemini(prompt string) (string, error) {
	if apiKey == "" {
		return "", fmt.Errorf("GEMINI_API_KEY is not configured")
	}

	url := fmt.Sprintf("https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s", model, apiKey)

	reqBody := map[string]interface{}{
		"contents": []map[string]interface{}{
			{
				"parts": []map[string]interface{}{
					{"text": prompt},
				},
			},
		},
		"generationConfig": map[string]interface{}{
			"temperature":     0.2,
			"responseMimeType": "application/json",
		},
	}

	jsonBytes, err := json.Marshal(reqBody)
	if err != nil {
		return "", err
	}

	ctx, cancel := context.WithTimeout(context.Background(), 25*time.Second)
	defer cancel()

	httpReq, err := http.NewRequestWithContext(ctx, "POST", url, bytes.NewBuffer(jsonBytes))
	if err != nil {
		return "", err
	}
	httpReq.Header.Set("Content-Type", "application/json")

	resp, err := http.DefaultClient.Do(httpReq)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	bodyBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", err
	}

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("Gemini API error (status %d): %s", resp.StatusCode, string(bodyBytes))
	}

	var geminiResp struct {
		Candidates []struct {
			Content struct {
				Parts []struct {
					Text string `json:"text"`
				} `json:"parts"`
			} `json:"content"`
		} `json:"candidates"`
	}

	if err := json.Unmarshal(bodyBytes, &geminiResp); err != nil {
		return "", err
	}

	if len(geminiResp.Candidates) == 0 || len(geminiResp.Candidates[0].Content.Parts) == 0 {
		return "", fmt.Errorf("empty response from Gemini API")
	}

	return geminiResp.Candidates[0].Content.Parts[0].Text, nil
}

func handleParseWorkout(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req ParseWorkoutRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}

	if strings.TrimSpace(req.Text) == "" {
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(ParseWorkoutResponse{Workouts: []WorkoutItem{}})
		return
	}

	prompt := fmt.Sprintf(`あなたは筋トレ・フィットネスデータの高精度自然言語パーサーです。
以下のユーザー入力メモから、筋トレの種目、対象部位、重量(kg)、回数(レップ数)、セット数、補足メモを抽出してください。
筋トレの記述がない場合や、単なる体調・食事メモの場合は空配列 "workouts": [] を返してください。

【ユーザー入力】
%s

【対象部位の選択肢】
"胸", "背中", "脚", "肩", "腕", "腹", "有酸素"

【返却JSONスキーマ】
{
  "workouts": [
    {
      "exercise_name": "種目名 (例: ダンベルベンチプレス)",
      "target_muscle": "対象部位",
      "weight": 20.0,
      "reps": 10,
      "sets": 3,
      "notes": "フォームや疲労度のメモ"
    }
  ]
}
`, req.Text)

	resText, err := callGemini(prompt)
	if err != nil {
		log.Printf("Gemini call error: %v", err)
		// フォールバック: 空の配列を返却
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(ParseWorkoutResponse{Workouts: []WorkoutItem{}})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.Write([]byte(resText))
}

func handleGenerateAdvice(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req GenerateAdviceRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}

	reqBytes, _ := json.MarshalIndent(req, "", "  ")

	prompt := fmt.Sprintf(`あなたは一流のプロパーソナルトレーナー・AIフィジカルコーチです。
ユーザーが「最初に設定した目標値」と「直近の計測データ推移」を分析し、
ユーザーのモチベーションを高めつつ、論理的で具体的かつ無理のない【3大処方箋】をJSON形式で返答してください。

【ユーザーの目標・現在値・履歴データ】
%s

【考慮すべき要素】
1. ペース診断: 目標期日までの残り日数と必要増減ペース、および直近の実測推移（週間増減率）を比較し、順調(on_track)、前倒し好調(ahead)、ペース低下(behind)、停滞(plateau)のいずれかを判定。
2. 次回やるべき筋トレ: 直近の履歴で鍛えた部位をチェックし、超回復（48〜72時間の休養）を考慮して、ユーザーの運動環境（ジム/自宅ダンベル/自重）に適合した最適な部位・具体的種目・推奨重量・セット数を提案。
3. 生活・食事のワンポイント: 具体的ですぐ実践できる一言（PFCバランス、水分量、睡眠、むくみ対策など）。

【返却JSONスキーマ】
{
  "pace_status": "on_track | ahead | behind | plateau",
  "pace_status_label": "順調なペース | 前倒し好調 | 要ペース調整 | 停滞期ブレイク推奨",
  "pace_message": "客観的で励みになるペース分析（1〜2文）",
  "timeline_gap": "期日と目標体重までの残差・達成見込みに関する具体的数値（1〜2文）",
  "next_action": "直近の部位疲労を踏まえた明日・次回のアクション方針（1〜2文）",
  "recommended_workout": {
    "target_muscle": "胸 | 背中 | 脚 | 肩 | 腕 | 腹",
    "exercise": "具体的種目名",
    "suggested_weight": "推奨重量 (例: 18kgダンベル / 自体重)",
    "suggested_sets": "推奨回数・セット数 (例: 10回 × 3セット)",
    "point": "意識すべきフォームのコツやテンションのかけ方"
  },
  "lifestyle_tip": "今すぐできる生活・食事ワンポイント助言"
}
`, string(reqBytes))

	resText, err := callGemini(prompt)
	if err != nil {
		log.Printf("Gemini call error: %v", err)
		// フォールバック返却
		fallback := AdviceResponse{
			PaceStatus:      "on_track",
			PaceStatusLabel: "順調なペース",
			PaceMessage:     "目標達成に向けて順調に推移しています。継続が最大の武器です。",
			TimelineGap:     fmt.Sprintf("目標体重 %.1fkg に向けて着実に前進しています。", req.Goal.TargetWeight),
			NextAction:      "直近のトレーニング部位を休ませ、バランスよく全身を鍛えましょう。",
			RecommendedWorkout: RecommendedWorkout{
				TargetMuscle:    "背中",
				Exercise:        "ラットプルダウン または ダンベルロウ",
				SuggestedWeight: "中重量（10回でややキツい重さ）",
				SuggestedSets:   "10回 × 3セット",
				Point:           "肩甲骨を引き寄せて背筋を収縮させましょう。",
			},
			LifestyleTip: "十分なタンパク質摂取（体重×1.5g目安）と水分補給を心がけてください。",
		}
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(fallback)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.Write([]byte(resText))
}

func main() {
	loadEnvFile(".env")
	loadEnvFile("ai-service/.env")

	if k := os.Getenv("GEMINI_API_KEY"); k != "" {
		apiKey = k
	}
	if m := os.Getenv("GEMINI_MODEL"); m != "" {
		model = m
	}

	port := os.Getenv("PORT")
	if port == "" {
		port = "8086"
	}

	http.HandleFunc("/health", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]interface{}{
			"status": "ok",
			"engine": "gemini-3.5-flash",
			"service": "body-metric-ai",
			"time":   time.Now().Format(time.RFC3339),
		})
	})

	http.HandleFunc("/api/parse-workout", handleParseWorkout)
	http.HandleFunc("/api/generate-advice", handleGenerateAdvice)

	log.Printf("🚀 BodyMetric AI Worker starting on port %s (model: %s)...", port, model)
	if err := http.ListenAndServe(":"+port, nil); err != nil {
		log.Fatalf("Server failed: %v", err)
	}
}
