<template>
  <div class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-gray-200/50 shadow-lg p-3 sm:p-5 mb-4 sm:mb-6 max-w-7xl mx-auto px-3 sm:px-4 mt-4 sm:mt-6">
      <div class="flex flex-col gap-3">
        <div class="flex items-center gap-2 sm:gap-4">
          <NuxtLink to="/dashboard" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-3 sm:px-4 py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-xs"></i> Back
          </NuxtLink>
          <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center shadow-md">
              <i class="fas fa-coins text-white text-xs sm:text-sm"></i>
            </div>
            <h1 class="text-lg sm:text-2xl font-bold text-black truncate">
              Lot Management
            </h1>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
          <p class="text-xs sm:text-sm text-gray-600">
            Monitoring lot aktif per schedule (S1 - S9, SX) - Account: {{ defaultAccount }}
          </p>
          <div class="flex items-center gap-2 sm:gap-3">
            <button @click="loadData" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-md">
              <i class="fas fa-sync-alt text-xs" :class="{ 'fa-spin': loading }"></i> Refresh
            </button>
            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm">
              <i class="far fa-clock text-xs"></i> <span class="ml-1 font-semibold">{{ currentTime }}</span>
            </div>
            <ThemeToggle />
          </div>
        </div>
      </div>
    </header>

    <!-- Schedule Cards Grid -->
    <div class="max-w-7xl mx-auto px-3 sm:px-4 pb-6">
      <div v-if="loading && !activeLots" class="text-center py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 mb-4">
          <i class="fas fa-spinner fa-spin text-2xl text-white"></i>
        </div>
        <p class="text-gray-600 text-sm font-medium">Memuat data...</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 sm:gap-4">
        <div v-for="schedule in schedules" :key="schedule" class="space-y-2 sm:space-y-3">
          <!-- Schedule Card -->
          <div :class="getScheduleCardClass(schedule)" class="card-modern border-2 rounded-lg sm:rounded-xl p-3 sm:p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2 sm:mb-3">
              <div class="flex items-center gap-2">
                <div :class="getScheduleIconClass(schedule)" class="bg-white rounded-full p-1.5 sm:p-2">
                  <i class="fas fa-calendar-alt text-xs sm:text-sm"></i>
                </div>
                <div>
                  <h3 :class="getScheduleTextClass(schedule)" class="text-sm sm:text-base font-bold">{{ schedule }}</h3>
                  <p class="text-xs text-gray-600">Schedule {{ schedule }}</p>
                </div>
              </div>
              <div class="flex items-center">
                <span v-if="scheduleStatus[schedule]" class="text-xs font-semibold px-2 py-1 rounded" :class="scheduleStatus[schedule] === 'ON' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                  {{ scheduleStatus[schedule] }}
                </span>
                <span v-else class="text-xs text-gray-400">-</span>
              </div>
            </div>

            <div class="space-y-2 sm:space-y-3">
              <!-- Lot Aktif -->
              <div class="bg-white rounded-lg sm:rounded-xl p-2 sm:p-3 border border-gray-200">
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 text-xs sm:text-sm font-semibold">Lot Aktif</span>
                  <span :class="getScheduleTextClass(schedule)" class="text-base sm:text-lg font-bold">
                    {{ getActiveLot(schedule) }}
                  </span>
                </div>
                <div class="mt-1.5 sm:mt-2 space-y-1">
                  <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <i class="fas fa-layer-group text-xs"></i>
                    <span>Index: {{ getLotIndex(schedule) }}</span>
                  </div>
                  <div v-if="getLastLotUsed(schedule)" class="flex items-center gap-1.5 text-xs text-gray-500 border-t border-gray-100 pt-1">
                    <i class="fas fa-history text-xs"></i>
                    <span>Lot Terakhir: <span class="font-semibold text-gray-700">{{ getLastLotUsed(schedule) }}</span></span>
                  </div>
                  <div v-if="getStreakLoss(schedule) > 0" class="flex items-center gap-1.5 text-xs text-red-600 border-t border-gray-100 pt-1">
                    <i class="fas fa-fire text-xs"></i>
                    <span>Streak Loss: <span class="font-semibold">{{ getStreakLoss(schedule) }}</span></span>
                  </div>
                </div>
              </div>

              <!-- Stats Grid -->
              <div class="grid grid-cols-2 gap-2">
                <div class="bg-white rounded-lg p-2 border border-gray-200">
                  <div class="flex items-center gap-1.5 mb-1">
                    <i class="fas fa-chart-bar text-gray-400 text-xs"></i>
                    <span class="text-xs text-gray-600">Total</span>
                  </div>
                  <p class="text-xs sm:text-sm font-semibold text-gray-800">{{ getTotalTrades(schedule) }}</p>
                </div>
                  <div class="bg-white rounded-lg p-2 border border-gray-200">
                    <div class="flex items-center gap-1.5 mb-1">
                      <i :class="[getProfitIcon(schedule), getProfitClass(schedule), 'text-xs']"></i>
                      <span class="text-xs text-gray-600">Last Profit</span>
                    </div>
                    <p :class="getProfitClass(schedule)" class="text-xs sm:text-sm font-semibold">
                      {{ formatProfit(getLastProfit(schedule)) }}
                    </p>
                  </div>
              </div>

              <!-- Last Ticket -->
              <div v-if="getLastTicket(schedule)" class="bg-white rounded-lg p-2 border border-gray-200">
                <div class="flex items-center gap-1.5 mb-1">
                  <i class="fas fa-ticket-alt text-gray-400 text-xs"></i>
                  <span class="text-xs text-gray-600">Last Ticket</span>
                </div>
                <p class="text-xs font-mono text-gray-800">{{ getLastTicket(schedule) }}</p>
              </div>
            </div>
          </div>

          <!-- Last 15 Trades Table -->
          <div class="bg-white rounded-lg sm:rounded-xl border border-gray-200 overflow-hidden shadow-sm">
            <div class="bg-gradient-to-r from-gray-50 to-white px-2 sm:px-3 py-1.5 sm:py-2 border-b border-gray-200">
              <h4 :class="getScheduleTextClass(schedule)" class="text-xs sm:text-sm font-bold flex items-center gap-1.5 sm:gap-2">
                <i class="fas fa-list-ol text-xs"></i>
                15 Data Terbaru
              </h4>
            </div>
            <div class="overflow-x-auto max-h-56 sm:max-h-64 overflow-y-auto custom-scrollbar">
              <table class="w-full text-xs sm:text-sm">
                <thead class="sticky top-0 bg-gradient-to-r from-gray-50 to-gray-100">
                  <tr class="border-b border-gray-200">
                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase">No</th>
                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase">Ticket</th>
                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase">Lot</th>
                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase">Profit</th>
                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase">Comment</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="getLast15Trades(schedule).length === 0">
                    <tr>
                      <td colspan="5" class="px-3 py-6 sm:py-8 text-center text-gray-500">
                        <div class="inline-flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gray-100 mb-2">
                          <i class="fas fa-inbox text-gray-400 text-sm sm:text-base"></i>
                        </div>
                        <p class="text-xs sm:text-sm font-medium">Belum ada data transaksi</p>
                      </td>
                    </tr>
                  </template>
                  <template v-else>
                    <tr v-for="(trade, index) in getLast15Trades(schedule)" :key="index" class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white transition-colors">
                      <td class="px-2 sm:px-3 py-2 text-xs sm:text-sm text-gray-600">{{ index + 1 }}</td>
                      <td class="px-2 sm:px-3 py-2"><span class="font-mono text-gray-800 text-xs sm:text-sm">{{ trade.ticket || '-' }}</span></td>
                      <td class="px-2 sm:px-3 py-2"><span class="font-semibold text-blue-600 text-xs sm:text-sm">{{ formatLot(trade.lot_entry || trade.volume) }}</span></td>
                      <td class="px-2 sm:px-3 py-2">
                        <div class="flex items-center gap-1">
                          <i :class="[getTradeProfitIcon(trade), getTradeProfitClass(trade), 'text-xs']"></i>
                          <span :class="getTradeProfitClass(trade)" class="font-semibold text-xs sm:text-sm">
                            {{ formatProfit(trade.profit) }}
                          </span>
                        </div>
                      </td>
                      <td class="px-2 sm:px-3 py-2">
                        <span class="text-gray-700 truncate block text-xs sm:text-sm max-w-[120px] sm:max-w-none" :title="trade.comment || '-'">
                          {{ trade.comment || '-' }}
                        </span>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const defaultAccount = ref(config.defaultAccount || '270787386')
const loading = ref(true)
const activeLots = ref<Record<string, any>>({})
const scheduleStatus = ref<Record<string, string>>({})
const schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX']
const currentTime = ref('')

const scheduleColors: Record<string, any> = {
  'S1': { bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-800', icon: 'text-blue-800' },
  'S2': { bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-800', icon: 'text-green-800' },
  'S3': { bg: 'bg-purple-50', border: 'border-purple-200', text: 'text-purple-800', icon: 'text-purple-800' },
  'S4': { bg: 'bg-yellow-50', border: 'border-yellow-200', text: 'text-yellow-800', icon: 'text-yellow-800' },
  'S5': { bg: 'bg-pink-50', border: 'border-pink-200', text: 'text-pink-800', icon: 'text-pink-800' },
  'S6': { bg: 'bg-indigo-50', border: 'border-indigo-200', text: 'text-indigo-800', icon: 'text-indigo-800' },
  'S7': { bg: 'bg-teal-50', border: 'border-teal-200', text: 'text-teal-800', icon: 'text-teal-800' },
  'S8': { bg: 'bg-orange-50', border: 'border-orange-200', text: 'text-orange-800', icon: 'text-orange-800' },
  'S9': { bg: 'bg-sky-50', border: 'border-sky-200', text: 'text-sky-800', icon: 'text-sky-800' },
  'SX': { bg: 'bg-lime-50', border: 'border-lime-200', text: 'text-lime-800', icon: 'text-lime-800' }
}

const getScheduleCardClass = (schedule: string) => {
  const colors = scheduleColors[schedule] || scheduleColors['S1']
  return `${colors.bg} ${colors.border}`
}

const getScheduleTextClass = (schedule: string) => {
  // Return black text for all headers
  return 'text-black'
}

const getScheduleIconClass = (schedule: string) => {
  const colors = scheduleColors[schedule] || scheduleColors['S1']
  return colors.icon
}

const loadData = async () => {
  try {
    loading.value = true
    const token = config.apiToken || 'abc321Xyz'

    // Load EA status untuk schedule status
    const { data: controlData } = await useFetch('/api/control', {
      query: { token, account: defaultAccount.value, action: 'get' },
      key: `control-${defaultAccount.value}`,
      server: false
    })

    if (controlData.value?.status === 'success') {
      scheduleStatus.value = {
        'S1': controlData.value.schedule_s1 || 'ON',
        'S2': controlData.value.schedule_s2 || 'ON',
        'S3': controlData.value.schedule_s3 || 'ON',
        'S4': controlData.value.schedule_s4 || 'ON',
        'S5': controlData.value.schedule_s5 || 'ON',
        'S6': controlData.value.schedule_s6 || 'ON',
        'S7': controlData.value.schedule_s7 || 'ON',
        'S8': controlData.value.schedule_s8 || 'ON',
        'S9': controlData.value.schedule_s9 || 'ON',
        'SX': controlData.value.schedule_sx || 'ON'
      }
    }

    // Load lot data untuk semua schedules
    const { data: lotData } = await useFetch('/api/lot', {
      query: { token, account: defaultAccount.value, schedule: 'S1' },
      key: `lot-${defaultAccount.value}`,
      server: false
    })

    if (lotData.value?.status === 'success' && lotData.value.active_lots) {
      activeLots.value = lotData.value.active_lots
    }

    updateCurrentTime()
  } catch (error: any) {
    console.error('Error loading data:', error)
  } finally {
    loading.value = false
  }
}

const updateCurrentTime = () => {
  currentTime.value = new Date().toLocaleTimeString('id-ID')
}

const getActiveLot = (schedule: string) => {
  return activeLots.value[schedule]?.active_lot || '0.01'
}

const getLotIndex = (schedule: string) => {
  return activeLots.value[schedule]?.lot_index || 0
}

const getLastLotUsed = (schedule: string) => {
  const trades = activeLots.value[schedule]?.last_15_trades || []
  if (trades.length > 0) {
    const lot = trades[0].lot_entry || trades[0].volume
    return lot ? parseFloat(lot.toString()).toFixed(2) : null
  }
  return null
}

const getStreakLoss = (schedule: string) => {
  const trades = activeLots.value[schedule]?.last_15_trades || []
  let streak = 0
  for (const trade of trades) {
    const profit = parseFloat(trade.profit?.toString() || '0')
    if (profit < 0) {
      streak++
    } else if (profit > 0) {
      break
    }
  }
  return streak
}

const getTotalTrades = (schedule: string) => {
  return activeLots.value[schedule]?.total_trades || 0
}

const getLastProfit = (schedule: string) => {
  return activeLots.value[schedule]?.last_profit
}

const getLastTicket = (schedule: string) => {
  return activeLots.value[schedule]?.last_ticket || null
}

const getLast15Trades = (schedule: string) => {
  return activeLots.value[schedule]?.last_15_trades || []
}

const formatProfit = (profit: any) => {
  if (profit === null || profit === undefined) return '-'
  const num = parseFloat(profit.toString())
  if (isNaN(num)) return '-'
  return (num > 0 ? '+' : '') + num.toFixed(2)
}

const formatLot = (lot: any) => {
  if (!lot) return '-'
  return parseFloat(lot.toString()).toFixed(2)
}

const getProfitClass = (schedule: string) => {
  const profit = getLastProfit(schedule)
  if (profit === null || profit === undefined) return 'text-gray-400'
  const num = parseFloat(profit.toString())
  if (isNaN(num)) return 'text-gray-400'
  return num < 0 ? 'text-red-600' : num > 0 ? 'text-green-600' : 'text-gray-600'
}

const getProfitIcon = (schedule: string) => {
  const profit = getLastProfit(schedule)
  if (profit === null || profit === undefined) return 'fa-question'
  const num = parseFloat(profit.toString())
  if (isNaN(num)) return 'fa-question'
  return num < 0 ? 'fa-arrow-down' : num > 0 ? 'fa-arrow-up' : 'fa-minus'
}

const getTradeProfitClass = (trade: any) => {
  const profit = parseFloat(trade.profit?.toString() || '0')
  return profit < 0 ? 'text-red-600' : profit > 0 ? 'text-green-600' : 'text-gray-600'
}

const getTradeProfitIcon = (trade: any) => {
  const profit = parseFloat(trade.profit?.toString() || '0')
  return profit < 0 ? 'fa-arrow-down' : profit > 0 ? 'fa-arrow-up' : 'fa-minus'
}

// Auto refresh setiap 90 detik
onMounted(() => {
  loadData()
  setInterval(() => {
    loadData()
  }, 90000)
  
  // Update time setiap detik
  setInterval(updateCurrentTime, 1000)
})
</script>

<style scoped>
.card-modern {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid rgba(226, 232, 240, 0.8);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>

