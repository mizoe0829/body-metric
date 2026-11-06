<?php

namespace App\Http\Controllers;

use App\Models\DailyMetric;
use App\Models\Goal;
use App\Services\AiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * メインダッシュボード画面
     */
    public function index(): Response
    {
        $goal = Goal::where('is_active', true)->latest()->first();

        // 目標が未設定の場合は初期レコードを作成
        if (!$goal) {
            $goal = Goal::create([
                'height' => 172.0,
                'age' => 30,
                'gender' => 'male',
                'workout_environment' => 'gym',
                'target_weight' => 65.0,
                'target_body_fat' => 15.0,
                'start_weight' => 70.0,
                'start_body_fat' => 20.0,
                'target_date' => Carbon::today()->addDays(60),
                'objective' => '適正筋肉量を維持したまま除脂肪・引き締め',
                'activity_level' => 'moderate',
                'is_active' => true,
            ]);
        }

        // 最新の計測値
        $latestMetric = DailyMetric::with('workouts')->orderByDesc('record_date')->first();

        // 過去30日間の推移データ（グラフ用：日付昇順）
        $chartMetrics = DailyMetric::orderBy('record_date')
            ->where('record_date', '>=', Carbon::today()->subDays(60))
            ->get(['record_date', 'weight', 'body_fat'])
            ->map(fn($m) => [
                'date' => Carbon::parse($m->record_date)->format('m/d'),
                'weight' => $m->weight,
                'body_fat' => $m->body_fat,
            ]);

        // 履歴テーブル用（直近14件：日付降順）
        $history = DailyMetric::with('workouts')
            ->orderByDesc('record_date')
            ->limit(14)
            ->get();

        // 目標進捗計算
        $currentWeight = $latestMetric ? $latestMetric->weight : $goal->start_weight;
        $currentBodyFat = $latestMetric ? $latestMetric->body_fat : $goal->start_body_fat;
        $remainingWeight = $currentWeight ? round($currentWeight - $goal->target_weight, 1) : null;
        
        $daysRemaining = null;
        if ($goal->target_date) {
            $daysRemaining = max(0, Carbon::today()->diffInDays(Carbon::parse($goal->target_date), false));
        }

        // 最新AIアドバイス（最新メトリクスに紐付いたもの、無ければデフォルト）
        $aiFeedback = $latestMetric?->ai_feedback;

        return Inertia::render('Dashboard', [
            'goal' => $goal,
            'latestMetric' => $latestMetric,
            'chartMetrics' => $chartMetrics,
            'history' => $history,
            'progress' => [
                'current_weight' => $currentWeight,
                'current_body_fat' => $currentBodyFat,
                'remaining_weight' => $remainingWeight,
                'days_remaining' => $daysRemaining,
            ],
            'aiFeedback' => $aiFeedback,
        ]);
    }

    /**
     * 日々の計測値（分割フォーム）を保存し、AIで自動パース＆助言生成
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'record_date' => 'required|date',
            'weight' => 'required|numeric|between:30,250',
            'body_fat' => 'nullable|numeric|between:3,60',
            'memo' => 'nullable|string|max:1000',
        ]);

        $goal = Goal::where('is_active', true)->latest()->first();

        DB::transaction(function () use ($validated, $goal) {
            // 同日の既存記録があれば上書き更新、なければ新規作成
            $metric = DailyMetric::updateOrCreate(
                ['record_date' => $validated['record_date']],
                [
                    'weight' => $validated['weight'],
                    'body_fat' => $validated['body_fat'] ?? null,
                    'memo' => $validated['memo'] ?? null,
                    'raw_input' => $validated['memo'] ?? null,
                ]
            );

            // メモが存在する場合、AIで筋トレ種目を自動抽出
            if (!empty($validated['memo'])) {
                $extractedWorkouts = $this->aiService->parseWorkout($validated['memo']);

                // 既存の同日ワークアウトを一旦クリアして再登録
                $metric->workouts()->delete();

                foreach ($extractedWorkouts as $w) {
                    $metric->workouts()->create([
                        'exercise_name' => $w['exercise_name'] ?? 'トレーニング',
                        'target_muscle' => $w['target_muscle'] ?? null,
                        'weight' => !empty($w['weight']) ? (float)$w['weight'] : null,
                        'reps' => !empty($w['reps']) ? (int)$w['reps'] : null,
                        'sets' => !empty($w['sets']) ? (int)$w['sets'] : null,
                        'notes' => $w['notes'] ?? null,
                    ]);
                }
            }

            // 直近の履歴を取得してAI 3大処方箋（アドバイス）を生成
            $recentHistory = DailyMetric::with('workouts')
                ->where('record_date', '<=', $validated['record_date'])
                ->orderByDesc('record_date')
                ->limit(7)
                ->get();

            if ($goal) {
                $advice = $this->aiService->generateAdvice($goal, $metric->fresh('workouts'), $recentHistory);
                $metric->update(['ai_feedback' => $advice]);
            }
        });

        return redirect()->route('dashboard')->with('success', '計測データを記録し、AIトレーナーの処方箋を更新しました！');
    }

    /**
     * 過去ログの削除
     */
    public function destroy(DailyMetric $metric)
    {
        $metric->delete();
        return redirect()->route('dashboard')->with('success', '計測ログを削除しました。');
    }
}

