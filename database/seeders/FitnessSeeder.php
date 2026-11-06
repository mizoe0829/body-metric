<?php

namespace Database\Seeders;

use App\Models\Goal;
use App\Models\DailyMetric;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FitnessSeeder extends Seeder
{
    public function run(): void
    {
        // 1. デフォルト目標 (68kg / 15% / 3ヶ月後期日)
        $goal = Goal::create([
            'target_weight' => 65.0,
            'target_body_fat' => 15.0,
            'start_weight' => 71.5,
            'start_body_fat' => 20.5,
            'target_date' => Carbon::today()->addDays(60),
            'objective' => '適正筋肉量を維持したまま体脂肪率15%・体重65kgへ引き締め',
            'activity_level' => 'moderate',
            'is_active' => true,
        ]);

        // 2. 過去数日のサンプル計測値
        $history = [
            [
                'days_ago' => 4,
                'weight' => 70.8,
                'body_fat' => 20.1,
                'raw_input' => '70.8kg 20.1% スクワット80kg 8回3セット 脚の疲労強め',
                'memo' => '脚トレ実施。水分補給しっかり',
                'workout' => [
                    'exercise_name' => 'バーベルバックスクワット',
                    'target_muscle' => '脚',
                    'weight' => 80.0,
                    'reps' => 8,
                    'sets' => 3,
                    'rpe' => 8.5,
                ],
                'feedback' => [
                    'pace_status' => 'on_track',
                    'pace_message' => '順調な減量ペースです（開始から-0.7kg）。下半身トレによる基礎代謝向上が期待できます。',
                    'timeline_gap' => '目標達成まであと5.8kg（残り64日）。週あたり0.63kg減ペースで目標日ジャスト達成可能です。',
                    'next_action' => '明日は下半身を休め、上半身（胸・上腕三頭筋）にフォーカスしましょう。ダンベルプレスまたはプッシュアップがおすすめ。',
                    'recommended_workout' => [
                        'exercise' => 'ダンベルベンチプレス',
                        'target' => '胸・三頭',
                        'suggested_weight' => 18.0,
                        'suggested_sets' => '10回 × 3セット',
                    ],
                ],
            ],
            [
                'days_ago' => 2,
                'weight' => 70.3,
                'body_fat' => 19.8,
                'raw_input' => '体重70.3kg 体脂肪19.8% ダンベルプレス20kg 10回3セット 胸に効いた',
                'memo' => '睡眠7時間。筋肉痛なし',
                'workout' => [
                    'exercise_name' => 'ダンベルベンチプレス',
                    'target_muscle' => '胸',
                    'weight' => 20.0,
                    'reps' => 10,
                    'sets' => 3,
                    'rpe' => 8.0,
                ],
                'feedback' => [
                    'pace_status' => 'ahead',
                    'pace_message' => '体重・体脂肪率ともに綺麗な右肩下がり傾向。胸の負荷設定（20kg×10回）も適正です。',
                    'timeline_gap' => '目標期日に対して約4日前倒しの好ペース。体脂肪率も20%を下回りました。',
                    'next_action' => '背中（広背筋・僧帽筋）を鍛えて姿勢を改善し、代謝をさらに高めましょう。チンニングまたはダンベルロウを推奨。',
                    'recommended_workout' => [
                        'exercise' => 'ワンハンドダンベルロウ',
                        'target' => '背中',
                        'suggested_weight' => 16.0,
                        'suggested_sets' => '10回 × 3セット',
                    ],
                ],
            ],
            [
                'days_ago' => 0,
                'weight' => 69.9,
                'body_fat' => 19.4,
                'raw_input' => '69.9kg 19.4% 70kg切った！ ラットプルダウン50kg 10回3セット',
                'memo' => 'ついに60kg台突入。体調良好',
                'workout' => [
                    'exercise_name' => 'ラットプルダウン',
                    'target_muscle' => '背中',
                    'weight' => 50.0,
                    'reps' => 10,
                    'sets' => 3,
                    'rpe' => 7.5,
                ],
                'feedback' => [
                    'pace_status' => 'excellent',
                    'pace_message' => '祝！60kg台到達！体脂肪率も19.4%まで順調に燃焼中。背中の引き締まりが効いています。',
                    'timeline_gap' => '目標体重65.0kgまであと4.9kg。期日（残り60日）に対して余裕のある完璧な進行です。',
                    'next_action' => '明日は体幹・腹筋、または肩（三角筋）を刺激してプロポーションを整えましょう。',
                    'recommended_workout' => [
                        'exercise' => 'ダンベルショルダープレス & ハンギングレッグレイズ',
                        'target' => '肩・腹直筋',
                        'suggested_weight' => 12.0,
                        'suggested_sets' => '12回 × 3セット',
                    ],
                ],
            ],
        ];

        foreach ($history as $item) {
            $metric = DailyMetric::create([
                'record_date' => Carbon::today()->subDays($item['days_ago'])->toDateString(),
                'weight' => $item['weight'],
                'body_fat' => $item['body_fat'],
                'raw_input' => $item['raw_input'],
                'memo' => $item['memo'],
                'ai_feedback' => $item['feedback'],
            ]);

            if (!empty($item['workout'])) {
                $metric->workouts()->create($item['workout']);
            }
        }
    }
}
