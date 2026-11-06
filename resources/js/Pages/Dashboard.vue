<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- 1. 目標進捗ステータスサマリーカード -->
      <section class="bg-gradient-to-r from-slate-900 via-slate-850 to-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl relative overflow-hidden">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Current Target</span>
              <span class="text-xs text-slate-400">| {{ goal.objective }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1">
              目標: <span class="text-emerald-400">{{ goal.target_weight }} kg</span>
              <span v-if="goal.target_body_fat" class="text-slate-400 text-lg font-normal ml-2">({{ goal.target_body_fat }}%)</span>
            </h1>
          </div>

          <!-- 数値メトリクスグリッド -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3">
              <span class="text-[11px] text-slate-400 block font-medium">現在体重</span>
              <span class="text-lg font-bold text-slate-100">
                {{ progress.current_weight ? `${progress.current_weight} kg` : '--' }}
              </span>
            </div>

            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3">
              <span class="text-[11px] text-slate-400 block font-medium">目標まで残り</span>
              <span :class="['text-lg font-bold', progress.remaining_weight > 0 ? 'text-amber-400' : 'text-emerald-400']">
                {{ progress.remaining_weight !== null ? `${progress.remaining_weight > 0 ? '-' : '+'}${Math.abs(progress.remaining_weight)} kg` : '--' }}
              </span>
            </div>

            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3">
              <span class="text-[11px] text-slate-400 block font-medium">現在体脂肪率</span>
              <span class="text-lg font-bold text-cyan-400">
                {{ progress.current_body_fat ? `${progress.current_body_fat} %` : '--' }}
              </span>
            </div>

            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3">
              <span class="text-[11px] text-slate-400 block font-medium">期日までの残り</span>
              <span class="text-lg font-bold text-slate-200">
                {{ progress.days_remaining !== null ? `${progress.days_remaining} 日` : '期日未設定' }}
              </span>
            </div>
          </div>
        </div>

        <!-- 進捗プログレスバー -->
        <div class="mt-5 pt-4 border-t border-slate-800/60">
          <div class="flex items-center justify-between text-xs text-slate-400 mb-1.5 font-medium">
            <span>開始時: {{ goal.start_weight ?? 70 }}kg</span>
            <span>達成率: {{ progressPercentage }}%</span>
            <span>ゴール: {{ goal.target_weight }}kg</span>
          </div>
          <div class="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden p-0.5">
            <div
              class="h-full bg-gradient-to-r from-teal-500 to-emerald-400 rounded-full transition-all duration-700 ease-out"
              :style="{ width: `${progressPercentage}%` }"
            ></div>
          </div>
        </div>
      </section>

      <!-- 2. 中段：日々の入力フォーム（左） ＆ AI 3大処方箋カード（右） -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- 左：日々の分割入力フォーム (5 col) -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-lg flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <span>本日の計測値入力</span>
              </h2>
              <span class="text-xs text-slate-400">人が行うのはこれだけ</span>
            </div>

            <form @submit.prevent="submitMetric" class="space-y-4">
              <!-- 計測日 -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">計測日</label>
                <input
                  v-model="form.record_date"
                  type="date"
                  required
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                />
              </div>

              <!-- 体重 ＆ 体脂肪率（横並び） -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">
                    体重 (kg) <span class="text-emerald-400">*</span>
                  </label>
                  <input
                    v-model.number="form.weight"
                    type="number"
                    step="0.1"
                    min="30"
                    max="250"
                    placeholder="例: 68.4"
                    required
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                  />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">
                    体脂肪率 (%) <span class="text-slate-500 text-[10px]">任意</span>
                  </label>
                  <input
                    v-model.number="form.body_fat"
                    type="number"
                    step="0.1"
                    min="3"
                    max="60"
                    placeholder="例: 17.5"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-cyan-300 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                  />
                </div>
              </div>

              <!-- 今日のメモ（自由記述：筋トレ・食事・体調） -->
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-semibold text-slate-300">
                    メモ（筋トレ・食事・体調）
                  </label>
                  <span class="text-[11px] text-emerald-400">AIが筋トレ種目を自動抽出</span>
                </div>
                <textarea
                  v-model="form.memo"
                  rows="4"
                  placeholder="例: ベンチプレス75kg 10回3セット、インクラインダンベル20kg 10回3セット。胸にしっかり効いた。睡眠7時間。"
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                ></textarea>
                <p class="text-[11px] text-slate-400 mt-1">
                  ※ 筋トレ内容を書くと、Geminiが自動解析して種目・重量・部位を記録します。
                </p>
              </div>

              <!-- 送信ボタン -->
              <button
                type="submit"
                :disabled="form.processing"
                class="w-full mt-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-bold py-3 rounded-xl transition-all shadow-lg shadow-emerald-500/20 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <svg v-if="form.processing" class="animate-spin h-5 w-5 text-slate-950" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-else>
                  記録を保存してAI助言を取得
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- 右：AI 3大処方箋カード (7 col) -->
        <div class="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-lg flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
                <h2 class="text-base font-bold text-white">AIパーソナルトレーナー処方箋</h2>
              </div>
              <span class="text-xs px-2 py-0.5 rounded-md bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 font-medium">
                Gemini 3.5 Flash
              </span>
            </div>

            <!-- 処方箋コンテンツ（存在する場合） -->
            <div v-if="aiFeedback" class="space-y-4">
              <!-- ① 進捗ペース診断カード -->
              <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <span class="text-emerald-400 text-sm">📈</span>
                    <span class="text-xs font-bold text-slate-200">① 進捗ペース診断</span>
                  </div>
                  <span
                    :class="[
                      'text-[11px] font-bold px-2 py-0.5 rounded-full border',
                      paceBadgeClass(aiFeedback.pace_status)
                    ]"
                  >
                    {{ aiFeedback.pace_status_label ?? '順調' }}
                  </span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">{{ aiFeedback.pace_message }}</p>
                <p class="text-[11px] text-slate-400 mt-2 font-medium bg-slate-900/60 p-2 rounded-lg border border-slate-800/60">
                  🎯 <span class="text-slate-300">{{ aiFeedback.timeline_gap }}</span>
                </p>
              </div>

              <!-- ② 次回やるべき筋トレ提案カード -->
              <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <span class="text-cyan-400 text-sm">🏋️</span>
                    <span class="text-xs font-bold text-slate-200">② 次回やるべき筋トレ（部位・種目）</span>
                  </div>
                  <span class="text-[11px] font-bold text-cyan-300 bg-cyan-500/10 px-2 py-0.5 rounded-md border border-cyan-500/20">
                    部位: {{ aiFeedback.recommended_workout?.target_muscle ?? '全身' }}
                  </span>
                </div>
                <div class="bg-slate-900/80 p-3 rounded-lg border border-slate-800/80 space-y-1.5">
                  <div class="flex items-baseline justify-between">
                    <span class="font-bold text-sm text-slate-100">
                      {{ aiFeedback.recommended_workout?.exercise }}
                    </span>
                    <span class="text-xs text-emerald-400 font-semibold">
                      {{ aiFeedback.recommended_workout?.suggested_weight }}
                    </span>
                  </div>
                  <div class="text-xs text-slate-400">
                    推奨負荷: <span class="text-slate-200">{{ aiFeedback.recommended_workout?.suggested_sets }}</span>
                  </div>
                  <p class="text-[11px] text-slate-400 border-t border-slate-800/60 pt-1.5">
                    💡 コツ: {{ aiFeedback.recommended_workout?.point }}
                  </p>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                  {{ aiFeedback.next_action }}
                </p>
              </div>

              <!-- ③ 生活・食事のワンポイント改善 -->
              <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1.5">
                  <span class="text-amber-400 text-sm">🥗</span>
                  <span class="text-xs font-bold text-slate-200">③ 今日の生活・食事アドバイス</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                  {{ aiFeedback.lifestyle_tip }}
                </p>
              </div>
            </div>

            <!-- データ未登録時のプレースホルダー -->
            <div v-else class="h-64 flex flex-col items-center justify-center text-center p-6 bg-slate-950/40 rounded-xl border border-dashed border-slate-800">
              <span class="text-3xl mb-2">🤖</span>
              <p class="text-sm font-semibold text-slate-300">AI処方箋の準備ができています</p>
              <p class="text-xs text-slate-500 mt-1 max-w-sm">
                左のフォームから今日の体重とメモを入力すると、Gemini 3.5 Flash があなたの目標達成へのアプローチと次の一手を処方します。
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. 下段：体組成推移グラフ (Chart.js) -->
      <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-lg">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-teal-400"></span>
              <span>体組成・体重推移グラフ</span>
            </h2>
            <p class="text-xs text-slate-400">過去の計測トレンドと目標ライン</p>
          </div>
        </div>

        <MetricChart :metrics="chartMetrics" :targetWeight="goal.target_weight" />
      </section>

      <!-- 4. 最下段：直近の計測ログ一覧テーブル -->
      <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-lg">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
              <span>計測履歴（直近14日）</span>
            </h2>
            <p class="text-xs text-slate-400">自動抽出された筋トレ実績とメモ</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
              <tr>
                <th class="py-3 px-4 font-semibold">日付</th>
                <th class="py-3 px-4 font-semibold">体重</th>
                <th class="py-3 px-4 font-semibold">体脂肪率</th>
                <th class="py-3 px-4 font-semibold">抽出された筋トレ実績</th>
                <th class="py-3 px-4 font-semibold">メモ</th>
                <th class="py-3 px-4 text-right">操作</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="item in history" :key="item.id" class="hover:bg-slate-850/50 transition-colors">
                <td class="py-3 px-4 font-medium text-slate-200 whitespace-nowrap">
                  {{ formatDate(item.record_date) }}
                </td>
                <td class="py-3 px-4 font-bold text-emerald-400 whitespace-nowrap">
                  {{ item.weight }} kg
                </td>
                <td class="py-3 px-4 text-cyan-300 whitespace-nowrap">
                  {{ item.body_fat ? `${item.body_fat} %` : '-' }}
                </td>
                <td class="py-3 px-4">
                  <div v-if="item.workouts && item.workouts.length > 0" class="flex flex-wrap gap-1.5">
                    <span
                      v-for="w in item.workouts"
                      :key="w.id"
                      class="inline-flex items-center gap-1 text-[11px] bg-slate-950 border border-slate-800 px-2 py-0.5 rounded-md text-slate-200"
                    >
                      <span class="font-semibold text-emerald-400">[{{ w.target_muscle ?? '部位' }}]</span>
                      <span>{{ w.exercise_name }}</span>
                      <span v-if="w.weight" class="text-slate-400">{{ w.weight }}kg</span>
                      <span v-if="w.reps && w.sets" class="text-slate-400">({{ w.reps }}回×{{ w.sets }})</span>
                    </span>
                  </div>
                  <span v-else class="text-slate-500">-</span>
                </td>
                <td class="py-3 px-4 text-slate-400 max-w-xs truncate">
                  {{ item.memo || '-' }}
                </td>
                <td class="py-3 px-4 text-right">
                  <button
                    @click="deleteMetric(item.id)"
                    class="text-rose-400 hover:text-rose-300 text-xs px-2 py-1 rounded hover:bg-rose-500/10 transition-colors"
                  >
                    削除
                  </button>
                </td>
              </tr>
              <tr v-if="history.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-500">
                  記録データがまだありません。
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import MetricChart from '../Components/MetricChart.vue';

const props = defineProps({
  goal: Object,
  latestMetric: Object,
  chartMetrics: Array,
  history: Array,
  progress: Object,
  aiFeedback: Object,
});

// 本日の日付を YYYY-MM-DD で取得
const todayString = new Date().toISOString().split('T')[0];

const form = useForm({
  record_date: todayString,
  weight: props.latestMetric ? props.latestMetric.weight : '',
  body_fat: props.latestMetric?.body_fat ? props.latestMetric.body_fat : '',
  memo: '',
});

const submitMetric = () => {
  form.post('/metrics', {
    preserveScroll: true,
    onSuccess: () => {
      form.memo = '';
    },
  });
};

const deleteMetric = (id) => {
  if (confirm('この記録を削除してもよろしいですか？')) {
    router.delete(`/metrics/${id}`, {
      preserveScroll: true,
    });
  }
};

const progressPercentage = computed(() => {
  const start = props.goal.start_weight || 70;
  const target = props.goal.target_weight || 65;
  const current = props.progress.current_weight || start;

  if (start === target) return 100;
  const totalChange = start - target;
  const currentChange = start - current;
  const pct = Math.round((currentChange / totalChange) * 100);
  return Math.min(Math.max(pct, 0), 100);
});

const paceBadgeClass = (status) => {
  switch (status) {
    case 'ahead':
      return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
    case 'on_track':
      return 'bg-teal-500/20 text-teal-300 border-teal-500/40';
    case 'behind':
      return 'bg-amber-500/20 text-amber-300 border-amber-500/40';
    case 'plateau':
      return 'bg-purple-500/20 text-purple-300 border-purple-500/40';
    default:
      return 'bg-slate-800 text-slate-300 border-slate-700';
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const d = new Date(dateString);
  return `${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`;
};
</script>

