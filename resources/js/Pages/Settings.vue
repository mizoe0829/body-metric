<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- ページヘッダー -->
      <div class="border-b border-slate-800 pb-4">
        <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
          <span>⚙️ 目標 ＆ プロファイル設定</span>
        </h1>
        <p class="text-xs text-slate-400 mt-1">
          ここで設定した目標値と前提環境に基づいて、Gemini 3.5 Flash が日々の助言・トレーニングメニューを処方します。
        </p>
      </div>

      <form @submit.prevent="updateSettings" class="space-y-6">
        <!-- 1. 基本身体プロファイル -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-lg space-y-4">
          <h2 class="text-sm font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>① 基本プロファイル（代謝・カロリー計算基準）</span>
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">身長 (cm)</label>
              <input
                v-model.number="form.height"
                type="number"
                step="0.1"
                min="100"
                max="250"
                required
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">年齢</label>
              <input
                v-model.number="form.age"
                type="number"
                min="10"
                max="120"
                required
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">性別</label>
              <select
                v-model="form.gender"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              >
                <option value="male">男性</option>
                <option value="female">女性</option>
                <option value="other">その他</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">運動環境</label>
              <select
                v-model="form.workout_environment"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-emerald-400 font-medium focus:outline-none focus:border-emerald-500"
              >
                <option value="gym">ジム（バーベル・ダンベル・マシン完備）</option>
                <option value="dumbbell">自宅ダンベル（可変式ダンベル・ベンチ）</option>
                <option value="bodyweight">自重・宅トレ（器具なし）</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">日常の活動レベル</label>
              <select
                v-model="form.activity_level"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              >
                <option value="sedentary">デスクワーク中心（ほぼ運動なし）</option>
                <option value="light">軽い運動（週1〜2回程度）</option>
                <option value="moderate">中程度の運動（週3〜5回のトレーニング）</option>
                <option value="active">高頻度運動（週6回以上または肉体労働）</option>
              </select>
            </div>
          </div>
        </div>

        <!-- 2. 目標値（Goal）設定 -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-lg space-y-4">
          <h2 class="text-sm font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
            <span>② 目標値設定（AI処方箋のゴール）</span>
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">
                目標体重 (kg) <span class="text-emerald-400">*</span>
              </label>
              <input
                v-model.number="form.target_weight"
                type="number"
                step="0.1"
                min="30"
                max="250"
                required
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-base font-bold text-emerald-400 focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">
                目標体脂肪率 (%)
              </label>
              <input
                v-model.number="form.target_body_fat"
                type="number"
                step="0.1"
                min="3"
                max="60"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-base font-bold text-cyan-400 focus:outline-none focus:border-cyan-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">開始時体重 (kg)</label>
              <input
                v-model.number="form.start_weight"
                type="number"
                step="0.1"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-300 focus:outline-none focus:border-slate-600"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">開始時体脂肪率 (%)</label>
              <input
                v-model.number="form.start_body_fat"
                type="number"
                step="0.1"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-300 focus:outline-none focus:border-slate-600"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">達成目標期日</label>
              <input
                v-model="form.target_date"
                type="date"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>

          <div class="pt-2 space-y-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">
                ボディメイクの主な目的 <span class="text-emerald-400">*</span>
              </label>
              <select
                v-model="form.objective"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm font-bold text-emerald-400 focus:outline-none focus:border-emerald-500"
              >
                <option value="減量・除脂肪（体重＆体脂肪率を落とす）">🔥 減量・除脂肪（体重・体脂肪を落とし筋量を死守する）</option>
                <option value="増量・バルクアップ（筋肥大＆体重増）">💪 増量・バルクアップ（筋肉量を増やし挙上重量を伸ばす）</option>
                <option value="引き締め・細マッチョ（体脂肪率を下げ筋肉を強調）">⚡ 引き締め・細マッチョ（体重維持〜微減で引き締める）</option>
                <option value="健康維持・体力向上（コンディション維持）">🌱 健康維持・体力向上（無理のない習慣と体型維持）</option>
              </select>
            </div>
            <p class="text-[11px] text-slate-400">
              ※ 選択した目的に応じて、AIが提案する推奨セット数・重量負荷・食事アドバイス（高タンパクか炭水化物補給か等）が自動的に最適化されます。
            </p>
          </div>
        </div>

        <!-- 保存ボタン -->
        <div class="flex items-center justify-end gap-3 pt-4">
          <Link
            href="/"
            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 transition-colors"
          >
            キャンセル
          </Link>

          <button
            type="submit"
            :disabled="form.processing"
            class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-slate-950" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>設定を保存する</span>
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';

const props = defineProps({
  goal: Object,
});

const formatDateInput = (dateString) => {
  if (!dateString) return '';
  return dateString.split('T')[0];
};

const form = useForm({
  height: props.goal.height ?? 172.0,
  age: props.goal.age ?? 30,
  gender: props.goal.gender ?? 'male',
  workout_environment: props.goal.workout_environment ?? 'gym',
  activity_level: props.goal.activity_level ?? 'moderate',
  target_weight: props.goal.target_weight ?? 65.0,
  target_body_fat: props.goal.target_body_fat ?? 15.0,
  start_weight: props.goal.start_weight ?? 70.0,
  start_body_fat: props.goal.start_body_fat ?? 20.0,
  target_date: formatDateInput(props.goal.target_date),
  objective: props.goal.objective ?? '適正筋肉量を維持したまま除脂肪・引き締め',
});

const updateSettings = () => {
  form.put('/settings', {
    preserveScroll: true,
  });
};
</script>

