<template>
  <div class="w-full h-64 sm:h-80">
    <Line v-if="chartData.labels.length > 0" :data="chartData" :options="chartOptions" />
    <div v-else class="h-full flex items-center justify-center text-slate-500 text-sm">
      データがありません。計測値を記録するとグラフが表示されます。
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
  metrics: {
    type: Array,
    required: true,
  },
  targetWeight: {
    type: Number,
    default: null,
  },
});

const chartData = computed(() => {
  const labels = props.metrics.map(m => m.date);
  const weights = props.metrics.map(m => m.weight);
  const bodyFats = props.metrics.map(m => m.body_fat);

  const datasets = [
    {
      label: '体重 (kg)',
      data: weights,
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      borderWidth: 2.5,
      pointBackgroundColor: '#10b981',
      pointBorderColor: '#0f172a',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6,
      fill: true,
      tension: 0.25,
      yAxisID: 'yWeight',
    },
    {
      label: '体脂肪率 (%)',
      data: bodyFats,
      borderColor: '#06b6d4',
      backgroundColor: 'transparent',
      borderWidth: 2,
      borderDash: [4, 4],
      pointBackgroundColor: '#06b6d4',
      pointBorderColor: '#0f172a',
      pointBorderWidth: 2,
      pointRadius: 3,
      pointHoverRadius: 5,
      fill: false,
      tension: 0.25,
      yAxisID: 'yBodyFat',
    },
  ];

  // 目標体重ライン（水平破線）
  if (props.targetWeight && labels.length > 0) {
    datasets.push({
      label: `目標体重 (${props.targetWeight}kg)`,
      data: labels.map(() => props.targetWeight),
      borderColor: 'rgba(244, 63, 94, 0.6)',
      borderWidth: 1.5,
      borderDash: [6, 4],
      pointRadius: 0,
      fill: false,
      yAxisID: 'yWeight',
    });
  }

  return {
    labels,
    datasets,
  };
});

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      position: 'top',
      align: 'end',
      labels: {
        color: '#94a3b8',
        boxWidth: 14,
        boxHeight: 10,
        font: {
          family: 'Inter, sans-serif',
          size: 11,
        },
      },
    },
    tooltip: {
      backgroundColor: '#0f172a',
      titleColor: '#e2e8f0',
      bodyColor: '#cbd5e1',
      borderColor: '#334155',
      borderWidth: 1,
      padding: 10,
      cornerRadius: 8,
    },
  },
  scales: {
    x: {
      grid: {
        color: 'rgba(51, 65, 85, 0.4)',
      },
      ticks: {
        color: '#64748b',
        font: { size: 10 },
      },
    },
    yWeight: {
      type: 'linear',
      position: 'left',
      grid: {
        color: 'rgba(51, 65, 85, 0.4)',
      },
      ticks: {
        color: '#10b981',
        font: { size: 10 },
        callback: (value) => `${value}kg`,
      },
    },
    yBodyFat: {
      type: 'linear',
      position: 'right',
      grid: {
        drawOnChartArea: false,
      },
      ticks: {
        color: '#06b6d4',
        font: { size: 10 },
        callback: (value) => `${value}%`,
      },
    },
  },
}));
</script>
