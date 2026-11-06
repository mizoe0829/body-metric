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
                return $response->json() ?? [];
            }

            Log::warning('AI generate-advice request failed', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('AI generate-advice exception: ' . $e->getMessage());
        }

        // フォールバック
        return [
            'pace_status' => 'on_track',
            'pace_status_label' => '順調な推移',
            'pace_message' => '日々の計測データの蓄積が目標達成の第一歩です。この調子で記録を継続しましょう。',
            'timeline_gap' => '目標体重 ' . $goal->target_weight . 'kg に向けて着実に前進しています。',
            'next_action' => '前回のトレーニング部位を休ませ、無理のない負荷で別部位を鍛えましょう。',
            'recommended_workout' => [
                'target_muscle' => '背中',
                'exercise' => 'ダンベルロウ または チンニング',
                'suggested_weight' => '中重量（10回で適度な負荷）',
                'suggested_sets' => '10回 × 3セット',
                'point' => '肩甲骨の寄せを意識して背筋を収縮させましょう。',
            ],
            'lifestyle_tip' => '高タンパクな食事と良質な睡眠（7時間以上）を維持しましょう。',
        ];
    }
}
