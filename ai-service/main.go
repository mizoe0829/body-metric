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

	candidateModels := []string{model, "gemini-2.5-flash", "gemini-2.0-flash"}
	var lastErr error

	for _, m := range candidateModels {
		url := fmt.Sprintf("https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s", m, apiKey)

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
		httpReq, err := http.NewRequestWithContext(ctx, "POST", url, bytes.NewBuffer(jsonBytes))
		if err != nil {
			cancel()
			return "", err
		}
		httpReq.Header.Set("Content-Type", "application/json")

		resp, err := http.DefaultClient.Do(httpReq)
		if err != nil {
			cancel()
			lastErr = err
			continue
		}

		bodyBytes, err := io.ReadAll(resp.Body)
		resp.Body.Close()
		cancel()

		if err != nil {
			lastErr = err
			continue
		}

		if resp.StatusCode == http.StatusOK {
			var geminiResp struct {
				Candidates []struct {
					Content struct {
						Parts []struct {
							Text string `json:"text"`
						} `json:"parts"`
					} `json:"content"`
				} `json:"candidates"`
			}

			if err := json.Unmarshal(bodyBytes, &geminiResp); err == nil && len(geminiResp.Candidates) > 0 && len(geminiResp.Candidates[0].Content.Parts) > 0 {
				return geminiResp.Candidates[0].Content.Parts[0].Text, nil
			}
		}

		log.Printf("Model %s returned status %d: %s. Trying next fallback model...", m, resp.StatusCode, string(bodyBytes))
		lastErr = fmt.Errorf("API error (status %d): %s", resp.StatusCode, string(bodyBytes))
	}

	return "", lastErr
}

func cleanJSON(raw string) string {
	s := strings.TrimSpace(raw)
	// ```json ... ``` または ``` ... ``` を除去
	if strings.HasPrefix(s, "```") {
		lines := strings.Split(s, "\n")
		if len(lines) >= 2 {
			// 先頭行 (```json) を除外
			lines = lines[1:]
			// 末尾行 (```) を除外
			if len(lines) > 0 && strings.HasPrefix(strings.TrimSpace(lines[len(lines)-1]), "```") {
				lines = lines[:len(lines)-1]
			}
			s = strings.TrimSpace(strings.Join(lines, "\n"))
		}
	}
	return s
}

var validMuscles = map[string]bool{
	"胸": true, "背中": true, "脚": true, "肩": true, "腕": true, "腹": true, "有酸素": true, "全身": true,
}

func sanitizeWorkouts(items []WorkoutItem) []WorkoutItem {
	var valid []WorkoutItem
	for _, it := range items {
		name := strings.TrimSpace(it.ExerciseName)
		if name == "" {
			continue
		}
		if len([]rune(name)) > 50 {
			name = string([]rune(name)[:50])
		}

		muscle := strings.TrimSpace(it.TargetMuscle)
		if !validMuscles[muscle] {
			muscle = "全身"
		}

		w := it.Weight
		if w < 0 {
			w = 0
		} else if w > 500 {
			w = 500
		}

		reps := it.Reps
		if reps < 0 {
			reps = 0
		} else if reps > 100 {
			reps = 100
		}

		sets := it.Sets
		if sets < 0 {
			sets = 0
		} else if sets > 30 {
			sets = 30
		}

		valid = append(valid, WorkoutItem{
			ExerciseName: name,
			TargetMuscle: muscle,
			Weight:       w,
			Reps:         reps,
			Sets:         sets,
			Notes:        strings.TrimSpace(it.Notes),
		})
	}
	return valid
}

func sanitizeAdvice(adv *AdviceResponse, req GenerateAdviceRequest) {
	// 1. pace_status の検証
	switch adv.PaceStatus {
	case "on_track", "ahead", "behind", "plateau":
		// OK
	default:
		adv.PaceStatus = "on_track"
	}

	// 2. ラベルの補正
	if adv.PaceStatusLabel == "" {
		switch adv.PaceStatus {
		case "ahead":
			adv.PaceStatusLabel = "前倒し好調"
		case "behind":
			adv.PaceStatusLabel = "要ペース調整"
		case "plateau":
			adv.PaceStatusLabel = "停滞期ブレイク推奨"
		default:
			adv.PaceStatusLabel = "順調なペース"
		}
	}

	// 3. テキストの欠落補正
	if strings.TrimSpace(adv.PaceMessage) == "" {
		adv.PaceMessage = "目標値に向けて着実に前進しています。日々の記録継続が最大の推進力です。"
	}

	if strings.TrimSpace(adv.TimelineGap) == "" {
		adv.TimelineGap = fmt.Sprintf("目標体重 %.1fkg に向けたアプローチを維持しましょう。", req.Goal.TargetWeight)
	}

	if strings.TrimSpace(adv.NextAction) == "" {
		adv.NextAction = "前回のトレーニング部位を休ませ、バランスよく全身をローテーションしましょう。"
	}

	if strings.TrimSpace(adv.LifestyleTip) == "" {
		adv.LifestyleTip = "十分なタンパク質補給（体重×1.5g目安）と1日2L以上の水分補給を心がけてください。"
	}

	// 4. recommended_workout の検証
	if strings.TrimSpace(adv.RecommendedWorkout.Exercise) == "" {
		switch req.Goal.WorkoutEnvironment {
		case "dumbbell":
			adv.RecommendedWorkout = RecommendedWorkout{
				TargetMuscle:    "背中",
				Exercise:        "ダンベルワンハンドロウ",
				SuggestedWeight: "14〜16kgダンベル",
				SuggestedSets:   "10回 × 3セット",
				Point:           "背筋を伸ばし、肘を腰骨へ向かって引く意識で広背筋を収縮させましょう。",
			}
		case "bodyweight":
			adv.RecommendedWorkout = RecommendedWorkout{
				TargetMuscle:    "胸・体幹",
				Exercise:        "プッシュアップ (腕立て伏せ)",
				SuggestedWeight: "自体重",
				SuggestedSets:   "12〜15回 × 3セット",
				Point:           "体を一直線に保ち、胸を床ギリギリまで深く下ろしましょう。",
			}
		default:
			adv.RecommendedWorkout = RecommendedWorkout{
				TargetMuscle:    "背中",
				Exercise:        "ラットプルダウン または チンニング",
				SuggestedWeight: "中重量（10回で適正負荷）",
				SuggestedSets:   "10回 × 3セット",
				Point:           "肩甲骨を引き寄せて背筋全体でバーを引きましょう。",
			}
		}
	}

	if strings.TrimSpace(adv.RecommendedWorkout.TargetMuscle) == "" {
		adv.RecommendedWorkout.TargetMuscle = "全身"
	}
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
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(ParseWorkoutResponse{Workouts: []WorkoutItem{}})
		return
	}

	// 1. JSON クレンジング
	cleanStr := cleanJSON(resText)

	// 2. 構造体へのデコード（構文チェック）
	var resp ParseWorkoutResponse
	if err := json.Unmarshal([]byte(cleanStr), &resp); err != nil {
		log.Printf("Failed to unmarshal parse-workout JSON: %v. Raw text: %s", err, cleanStr)
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(ParseWorkoutResponse{Workouts: []WorkoutItem{}})
		return
	}

	// 3. バリデーション & サニタイズ
	resp.Workouts = sanitizeWorkouts(resp.Workouts)

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(resp)
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

	var advice AdviceResponse

	if err != nil {
		log.Printf("Gemini call error: %v", err)
		// デフォルトフォールバック
		advice = AdviceResponse{
			PaceStatus:      "on_track",
			PaceStatusLabel: "順調なペース",
			PaceMessage:     "目標達成に向けて順調に推移しています。継続が最大の武器です。",
			TimelineGap:     fmt.Sprintf("目標体重 %.1fkg に向けて着実に前進しています。", req.Goal.TargetWeight),
			NextAction:      "直近のトレーニング部位を休ませ、バランスよく全身を鍛えましょう。",
		}
	} else {
		// 1. JSON クレンジング
		cleanStr := cleanJSON(resText)

		// 2. 構造体へのデコード（構文チェック）
		if err := json.Unmarshal([]byte(cleanStr), &advice); err != nil {
			log.Printf("Failed to unmarshal advice JSON: %v. Raw text: %s", err, cleanStr)
			advice = AdviceResponse{
				PaceStatus:      "on_track",
				PaceStatusLabel: "順調なペース",
				PaceMessage:     "目標達成に向けて順調に推移しています。",
				TimelineGap:     fmt.Sprintf("目標体重 %.1fkg に向けて前進中です。", req.Goal.TargetWeight),
				NextAction:      "超回復を意識し、疲労していない部位を無理なく鍛えましょう。",
			}
		}
	}

	// 3. バリデーション & 欠落補正
	sanitizeAdvice(&advice, req)

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(advice)
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

