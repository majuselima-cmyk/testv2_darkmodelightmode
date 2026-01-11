<template>
  <div class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6">
      <!-- Header -->
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-lg p-3 sm:p-5 mb-4 sm:mb-6">
        <div class="flex flex-col gap-3">
          <div class="flex items-center gap-2 sm:gap-4">
            <NuxtLink to="/dashboard" class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-500 text-gray-700 dark:text-gray-200 px-3 sm:px-4 py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-sm">
              <i class="fas fa-arrow-left text-xs"></i> Back
            </NuxtLink>
            <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
              <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-md">
                <i class="fas fa-chart-line text-white text-xs sm:text-sm"></i>
              </div>
              <h1 class="text-lg sm:text-2xl font-bold text-black dark:text-white truncate">
                Pendapatan Harian
              </h1>
            </div>
          </div>
          
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Grafik pendapatan harian dari trading</p>
            <div class="flex items-center gap-2 sm:gap-3">
              <select v-model="days" @change="loadData" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500">
                <option :value="7">7 Hari</option>
                <option :value="14">14 Hari</option>
                <option :value="30">30 Hari</option>
                <option :value="60">60 Hari</option>
                <option :value="90">90 Hari</option>
              </select>
              <button @click="loadData" class="bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-md">
                <i class="fas fa-sync-alt text-xs" :class="{ 'fa-spin': loading }"></i> Refresh
              </button>
              <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm">
                <i class="far fa-clock text-xs"></i> <span class="ml-1 font-semibold">{{ lastUpdate }}</span>
              </div>
              <ThemeToggle />
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
        <div class="card-modern rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm font-semibold">Total Profit</span>
            <div :class="(stats.total_profit || 0) >= 0 ? 'from-green-500 to-green-600' : 'from-red-500 to-red-600'" class="w-10 h-10 rounded-lg bg-gradient-to-br flex items-center justify-center">
              <i class="fas fa-dollar-sign text-white text-sm"></i>
            </div>
          </div>
          <p :class="(stats.total_profit || 0) >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xl sm:text-2xl font-bold">
            {{ formatNumber(stats.total_profit || 0) }}
          </p>
        </div>

        <div class="card-modern rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm font-semibold">Total Trades</span>
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
              <i class="fas fa-chart-bar text-white text-sm"></i>
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-200">
            {{ formatNumber(stats.total_trades || 0) }}
          </p>
        </div>

        <div class="card-modern rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm font-semibold">Win Rate</span>
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
              <i class="fas fa-trophy text-white text-sm"></i>
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-green-600">
            {{ winRate.toFixed(1) }}%
          </p>
        </div>

        <div class="card-modern rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm font-semibold">Avg Profit</span>
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
              <i class="fas fa-calculator text-white text-sm"></i>
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-200">
            {{ formatNumber(stats.avg_profit || 0) }}
          </p>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Profit Chart -->
        <div class="card-modern rounded-xl sm:rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-4 sm:p-6">
          <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-area text-green-600"></i>
            Grafik Pendapatan Harian
          </h2>
          <div class="relative" style="height: 400px;">
            <canvas ref="profitChartRef"></canvas>
          </div>
        </div>

        <!-- Win/Loss Chart -->
        <div class="card-modern rounded-xl sm:rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-4 sm:p-6">
          <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-blue-600"></i>
            Win vs Loss per Hari
          </h2>
          <div class="relative" style="height: 400px;">
            <canvas ref="winLossChartRef"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
let Chart: any = null
let ChartJS: any = null

const route = useRoute()
const config = useRuntimeConfig()
const defaultAccount = ref((route.query.account as string) || config.defaultAccount || '270787386')
const days = ref(parseInt(route.query.days as string) || 30)
const loading = ref(false)
const lastUpdate = ref('-')

const stats = ref({
  total_profit: 0,
  total_trades: 0,
  win_count: 0,
  loss_count: 0,
  avg_profit: 0
})

const dailyData = ref<any[]>([])
const profitChartRef = ref<HTMLCanvasElement | null>(null)
const winLossChartRef = ref<HTMLCanvasElement | null>(null)
let profitChart: any = null
let winLossChart: any = null

const winRate = computed(() => {
  if (!stats.value.total_trades || stats.value.total_trades === 0) return 0
  return ((stats.value.win_count || 0) / stats.value.total_trades) * 100
})

const loadData = async () => {
  try {
    loading.value = true
    const { supabase } = useSupabase()

    // Get daily profit data
    const endDate = new Date()
    const startDate = new Date()
    startDate.setDate(startDate.getDate() - days.value)

    const { data: positions, error } = await supabase
      .from('trading_positions')
      .select('profit, position_time')
      .eq('account_number', defaultAccount.value)
      .gte('position_time', startDate.toISOString())
      .lte('position_time', endDate.toISOString())
      .order('position_time', { ascending: true })

    if (error) throw error

    // Group by date
    const groupedByDate: Record<string, any> = {}
    positions?.forEach((pos: any) => {
      const date = new Date(pos.position_time).toISOString().split('T')[0]
      if (!groupedByDate[date]) {
        groupedByDate[date] = {
          date,
          total_profit: 0,
          win_count: 0,
          loss_count: 0,
          total_trades: 0
        }
      }
      const profit = parseFloat(pos.profit?.toString() || '0')
      groupedByDate[date].total_profit += profit
      groupedByDate[date].total_trades++
      if (profit > 0) groupedByDate[date].win_count++
      if (profit < 0) groupedByDate[date].loss_count++
    })

    dailyData.value = Object.values(groupedByDate).sort((a: any, b: any) => 
      new Date(a.date).getTime() - new Date(b.date).getTime()
    )

    // Calculate stats
    const totalProfit = positions?.reduce((sum: number, pos: any) => 
      sum + parseFloat(pos.profit?.toString() || '0'), 0) || 0
    const totalTrades = positions?.length || 0
    const winCount = positions?.filter((pos: any) => parseFloat(pos.profit?.toString() || '0') > 0).length || 0
    const lossCount = positions?.filter((pos: any) => parseFloat(pos.profit?.toString() || '0') < 0).length || 0
    const avgProfit = totalTrades > 0 ? totalProfit / totalTrades : 0

    stats.value = {
      total_profit: totalProfit,
      total_trades: totalTrades,
      win_count: winCount,
      loss_count: lossCount,
      avg_profit: avgProfit
    }

    updateCharts()
    lastUpdate.value = new Date().toLocaleTimeString('id-ID')
  } catch (error: any) {
    console.error('Error loading data:', error)
    alert('Error loading data: ' + error.message)
  } finally {
    loading.value = false
  }
}

const updateCharts = async () => {
  if (!profitChartRef.value || !winLossChartRef.value) return

  // Load Chart.js if not loaded
  if (!Chart && process.client) {
    try {
      ChartJS = await import('chart.js/auto')
      Chart = ChartJS.Chart
    } catch (error) {
      console.error('Error loading Chart.js:', error)
      return
    }
  }

  if (!Chart) return

  const labels = dailyData.value.map(d => {
    const date = new Date(d.date)
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
  })

  const profitData = dailyData.value.map(d => d.total_profit)
  const winData = dailyData.value.map(d => d.win_count)
  const lossData = dailyData.value.map(d => d.loss_count)

  // Profit Chart
  if (profitChart) {
    profitChart.destroy()
  }

  profitChart = new Chart(profitChartRef.value, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Total Profit',
        data: profitData,
        borderColor: 'rgb(34, 197, 94)',
        backgroundColor: 'rgba(34, 197, 94, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        }
      },
      scales: {
        y: {
          beginAtZero: false,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  })

  // Win/Loss Chart
  if (winLossChart) {
    winLossChart.destroy()
  }

  winLossChart = new Chart(winLossChartRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Win',
          data: winData,
          backgroundColor: 'rgba(34, 197, 94, 0.7)',
          borderColor: 'rgb(34, 197, 94)',
          borderWidth: 1
        },
        {
          label: 'Loss',
          data: lossData,
          backgroundColor: 'rgba(239, 68, 68, 0.7)',
          borderColor: 'rgb(239, 68, 68)',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  })
}

const formatNumber = (num: number) => {
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

onMounted(async () => {
  // Load Chart.js
  if (process.client) {
    try {
      ChartJS = await import('chart.js/auto')
      Chart = ChartJS.Chart
    } catch (error) {
      console.error('Error loading Chart.js:', error)
    }
  }
  await loadData()
})

onBeforeUnmount(() => {
  if (profitChart) {
    profitChart.destroy()
    profitChart = null
  }
  if (winLossChart) {
    winLossChart.destroy()
    winLossChart = null
  }
})
</script>

<style scoped>
.card-modern {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid rgba(226, 232, 240, 0.8);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark .card-modern {
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  border: 1px solid rgba(55, 65, 81, 0.8);
}

.card-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}
</style>

