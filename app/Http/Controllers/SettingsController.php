<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * 目標・プロファイル設定画面
     */
    public function index(): Response
    {
        $goal = Goal::where('is_active', true)->latest()->first();

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

        return Inertia::render('Settings', [
            'goal' => $goal,
        ]);
    }

    /**
     * 目標・プロファイルの更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'height' => 'required|numeric|between:100,250',
            'age' => 'required|integer|between:10,120',
            'gender' => 'required|string|in:male,female,other',
            'workout_environment' => 'required|string|in:gym,dumbbell,bodyweight',
            'target_weight' => 'required|numeric|between:30,250',
            'target_body_fat' => 'nullable|numeric|between:3,60',
            'start_weight' => 'nullable|numeric|between:30,250',
            'start_body_fat' => 'nullable|numeric|between:3,60',
            'target_date' => 'nullable|date',
            'objective' => 'required|string|max:255',
            'activity_level' => 'required|string|in:sedentary,light,moderate,active,very_active',
        ]);

        $goal = Goal::where('is_active', true)->latest()->first();
        if ($goal) {
            $goal->update($validated);
        } else {
            Goal::create(array_merge($validated, ['is_active' => true]));
        }

        return redirect()->route('dashboard')->with('success', '目標およびプロファイル設定を更新しました！');
    }
}

