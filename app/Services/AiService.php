<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\DailyMetric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8086'));
    }

    /**
     * メモ欄の文章から筋トレ種目・重量・回数を自動抽出
     */
    public function parseWorkout(string $memo): array
    {
        if (trim($memo) === '') {
            return [];
        }

        try {
            $response = Http::timeout(20)->post("{$this->baseUrl}/api/parse-workout", [
                'text' => $memo,
            ]);

            if ($response->successful()) {
                return $response->json('workouts') ?? [];
            }

            Log::warning('AI parse-workout request failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('AI parse-workout exception: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * 目標・最新値・履歴からAI 3大処方箋を生成
     */
    public function generateAdvice(Goal $goal, DailyMetric $latestMetric, Collection $recentHistory): array
    {
        $historyData = $recentHistory->map(function (DailyMetric $m) {
            return [
                'record_date' => $m->record_date->toDateString(),
                'weight' => $m->weight,
                'body_fat' => $m->body_fat,
                'memo' => $m->memo ?? '',
                'workouts' => $m->workouts->map(function ($w) {
                    return [
                        'exercise_name' => $w->exercise_name,
                        'target_muscle' => $w->target_muscle ?? '',
                        'weight' => $w->weight,
                        'reps' => $w->reps,
                        'sets' => $w->sets,
                        'notes' => $w->notes ?? '',
                    ];
                })->toArray(),
            ];
        })->toArray();

        $payload = [
            'goal' => [
                'height' => (float)$goal->height,
                'age' => (int)$goal->age,
                'gender' => $goal->gender,
                'workout_environment' => $goal->workout_environment,
                'activity_level' => $goal->activity_level ?? 'moderate',
                'target_weight' => (float)$goal->target_weight,
                'target_body_fat' => (float)($goal->target_body_fat ?? 0),
                'start_weight' => (float)($goal->start_weight ?? $latestMetric->weight),
                'target_date' => $goal->target_date ? $goal->target_date->toDateString() : '',
                'objective' => $goal->objective,
            ],
            'latest_metric' => [
                'record_date' => $latestMetric->record_date->toDateString(),
                'weight' => (float)$latestMetric->weight,
                'body_fat' => (float)($latestMetric->body_fat ?? 0),
                'memo' => $latestMetric->memo ?? '',
                'workouts' => $latestMetric->workouts->map(fn($w) => [
                    'exercise_name' => $w->exercise_name,
                    'target_muscle' => $w->target_muscle ?? '',
                    'weight' => $w->weight,
                    'reps' => $w->reps,
                    'sets' => $w->sets,
                ])->toArray(),
            ],
            'recent_history' => $historyData,
        ];

        try {
            $response = Http::timeout(25)->post("{$this->baseUrl}/api/generate-advice", $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    return $this->ensureAdviceSchema($data, $goal);
                }
            }

            Log::warning('AI generate-advice request failed', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('AI generate-advice exception: ' . $e->getMessage());
        }

        return $this->getDefaultAdvice($goal);
    }

    /**
     * AI回答の構造スキーマを検証し、欠落キーをフォールバック補完
     */
    protected function ensureAdviceSchema(array $data, Goal $goal): array
    {
        $defaults = $this->getDefaultAdvice($goal);

        $merged = array_merge($defaults, $data);

        // recommended_workout オブジェクトの深層マージ・バリデーション
        if (!isset($data['recommended_workout']) || !is_array($data['recommended_workout'])) {
            $merged['recommended_workout'] = $defaults['recommended_workout'];
        } else {
            $merged['recommended_workout'] = array_merge(
                $defaults['recommended_workout'],
                $data['recommended_workout']
            );
        }

        // pace_status の許容値バリデーション
        $allowedStatuses = ['on_track', 'ahead', 'behind', 'plateau'];
        if (!in_array($merged['pace_status'] ?? '', $allowedStatuses, true)) {
            $merged['pace_status'] = 'on_track';
        }

        return $merged;
    }

    /**
     * 目標・運動量に応じたデフォルト処方箋
     */
    protected function getDefaultAdvice(Goal $goal): array
    {
        $obj = $goal->objective ?? '';
        $isBulk = str_contains($obj, '増量') || str_contains($obj, 'バルクアップ');
        $isCut = str_contains($obj, '減量') || str_contains($obj, '除脂肪');

        $sets = '10回 × 3セット';
        if ($isBulk) {
            $sets = '8〜10回 × 4セット';
        } elseif ($goal->activity_level === 'sedentary' || $goal->activity_level === 'light') {
            $sets = '12〜15回 × 2〜3セット';
        }

        $tip = '十分なタンパク質摂取（体重×1.5g目安）と1日2L以上の水分補給を心がけてください。';
        if ($isBulk) {
            $tip = '筋肉合成を最大化するため、毎食の炭水化物（白米・イモ類）をしっかり補給し、睡眠8時間を確保しましょう。';
        } elseif ($isCut) {
            $tip = '筋分解を防ぐため高タンパク（体重×1.8g以上）を徹底し、1日2.5L以上の水分補給で代謝を維持しましょう。';
        }

        return [
            'pace_status' => 'on_track',
            'pace_status_label' => '順調な推移',
            'pace_message' => '日々の計測データの蓄積が目標達成の第一歩です。この調子で記録を継続しましょう。',
            'timeline_gap' => '目標体重 ' . ($goal->target_weight ?? 65.0) . 'kg に向けて着実に前進しています。',
            'next_action' => $isBulk ? '高重量×多セットで総負荷量を稼ぎ、筋肥大シグナルを送りましょう。' : '筋力を維持しつつ、疲労していない大筋群を無理なく刺激しましょう。',
            'recommended_workout' => [
                'target_muscle' => '背中',
                'exercise' => 'ダンベルロウ または チンニング',
                'suggested_weight' => '中重量（適正負荷）',
                'suggested_sets' => $sets,
                'point' => '肩甲骨の寄せを意識して背筋全体を収縮させましょう。',
            ],
            'lifestyle_tip' => $tip,
        ];
    }
}

