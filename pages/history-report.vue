<template>
  <div class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6">
      <!-- Header -->
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-lg p-3 sm:p-5 mb-4 sm:mb-6 no-print">
        <div class="flex flex-col gap-3">
          <div class="flex items-center gap-2 sm:gap-4">
            <NuxtLink to="/dashboard" class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-500 text-gray-700 dark:text-gray-200 px-3 sm:px-4 py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-sm">
              <i class="fas fa-arrow-left text-xs"></i> Back
            </NuxtLink>
            <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
              <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md">
                <i class="fas fa-history text-white text-xs sm:text-sm"></i>
              </div>
              <h1 class="text-lg sm:text-2xl font-bold text-black dark:text-white truncate">
                History Report
              </h1>
            </div>
          </div>
          
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Trading History Report v10</p>
            <div class="flex items-center gap-2 sm:gap-3">
              <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl px-3 sm:px-4 py-2 sm:py-3 border border-blue-200 shadow-sm">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-semibold">Clock</div>
                <div class="text-xs sm:text-sm font-bold text-gray-900 dark:text-gray-100">
                  <span class="text-blue-600">{{ timeUTC }}</span> <span class="text-gray-500 dark:text-gray-400 text-xs">UTC</span>
                </div>
                <div class="text-xs sm:text-sm font-bold text-gray-900 dark:text-gray-100">
                  <span class="text-green-600">{{ timeWIB }}</span> <span class="text-gray-500 dark:text-gray-400 text-xs">WIB</span>
                </div>
              </div>
              <button @click="window.print()" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 sm:px-4 py-2 sm:py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-md no-print">
                <i class="fas fa-print text-xs"></i> Print
              </button>
              <ThemeToggle />
            </div>
          </div>
        </div>
      </div>

      <!-- Date Filter -->
      <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-lg p-3 sm:p-5 mb-4 sm:mb-6 no-print">
        <form @submit.prevent="loadData" class="flex flex-wrap items-end gap-3">
          <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Account</label>
            <input 
              v-model="account" 
              type="text" 
              class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all font-mono"
              placeholder="Account number"
            >
          </div>
          <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-semibold text-gray-700 mb-2">Tanggal (UTC)</label>
            <input 
              v-model="filterDate" 
              type="date" 
              class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
            >
          </div>
          <div class="min-w-[150px]">
            <label class="block text-xs font-semibold text-gray-700 mb-2">Schedule</label>
            <select 
              v-model="filterSchedule" 
              class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
            >
              <option value="">Semua</option>
              <option value="S1">S1</option>
              <option value="S2">S2</option>
              <option value="S3">S3</option>
              <option value="S4">S4</option>
              <option value="S5">S5</option>
              <option value="S6">S6</option>
              <option value="S7">S7</option>
              <option value="S8">S8</option>
              <option value="S9">S9</option>
              <option value="SX">SX</option>
            </select>
          </div>
          <div class="flex gap-2">
            <button 
              type="submit" 
              class="bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-3 sm:px-4 py-2 sm:py-3 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all shadow-md"
            >
              <i class="fas fa-filter text-xs"></i> Filter
            </button>
            <button 
              type="button"
              @click="setToday"
              class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-500 text-gray-700 dark:text-gray-200 px-3 sm:px-4 py-2 sm:py-3 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all shadow-sm"
            >
              <i class="fas fa-calendar-day text-xs"></i> Hari Ini
            </button>
          </div>
        </form>
        <div class="mt-3 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-2">
          <i class="fas fa-info-circle text-purple-500"></i>
          Filter UTC | 15 data/halaman
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4 mb-4 sm:mb-6">
        <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-3 sm:p-4">
          <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">Account</div>
          <div class="text-base sm:text-lg font-bold text-gray-900 font-mono">{{ account }}</div>
        </div>
        <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-3 sm:p-4">
          <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">Tanggal</div>
          <div class="text-base sm:text-lg font-bold text-blue-600">{{ formatDate(filterDate) }}</div>
          <div class="text-xs text-gray-500 mt-1">UTC</div>
        </div>
        <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-3 sm:p-4">
          <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">Positions</div>
          <div class="text-base sm:text-lg font-bold text-blue-600">{{ pagination.total_count || 0 }}</div>
          <div class="text-xs text-gray-500 mt-1">Total: {{ pagination.total_all || 0 }}</div>
        </div>
        <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-3 sm:p-4">
          <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">Profit/Loss</div>
          <div :class="(statistics.total_profit || 0) >= 0 ? 'text-green-600' : 'text-red-600'" class="text-base sm:text-lg font-bold">
            {{ formatCurrency(statistics.total_profit || 0) }}
          </div>
        </div>
        <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-3 sm:p-4">
          <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">BUY / SELL</div>
          <div class="text-base sm:text-lg font-bold text-gray-900">{{ statistics.buy_count || 0 }} / {{ statistics.sell_count || 0 }}</div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg overflow-hidden mb-4 sm:mb-6">
        <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
          <h2 class="text-sm sm:text-base font-bold text-gray-900">
            Positions: {{ statistics.returned || 0 }} of {{ pagination.total_count || 0 }} 
            ({{ formatDate(filterDate) }} UTC)
            <span v-if="filterSchedule"> | Schedule: {{ filterSchedule }}</span>
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
              <tr>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ticket</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Symbol</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Volume</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Price</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Profit</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Schedule</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Comment</th>
                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Time</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-if="loading" class="border-b">
                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                  <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-2">
                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                  </div>
                  <p class="text-sm font-medium">Loading...</p>
                </td>
              </tr>
              <tr v-else-if="positions.length === 0" class="border-b">
                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                  <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-2">
                    <i class="fas fa-inbox text-gray-400"></i>
                  </div>
                  <p class="text-sm font-medium">No trading history found</p>
                </td>
              </tr>
              <tr 
                v-else
                v-for="pos in positions" 
                :key="pos.id || pos.ticket"
                class="border-b hover:bg-gradient-to-r hover:from-gray-50 hover:to-white transition-colors"
              >
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-medium">{{ pos.ticket || '-' }}</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-semibold">{{ pos.symbol || '-' }}</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm">
                  <span :class="getTypeClass(pos.position_type)" class="px-2 py-1 rounded-lg text-xs font-semibold">
                    {{ pos.position_type?.toUpperCase() || '-' }}
                  </span>
                </td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm">{{ pos.volume || '0.00' }}</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm">{{ formatNumber(parseFloat(pos.price || 0), 5) }}</td>
                <td :class="getProfitClass(parseFloat(pos.profit || 0))" class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-bold">
                  {{ formatCurrency(pos.profit || 0) }}
                </td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm">
                  <span :class="getScheduleClass(getSchedule(pos.comment))" class="px-2 py-1 rounded-lg text-xs font-semibold">
                    {{ getSchedule(pos.comment) }}
                  </span>
                </td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-600 truncate max-w-[200px]">{{ pos.comment || '-' }}</td>
                <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-500">{{ formatDateLocalWIB(pos.position_time) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total_pages > 1" class="px-4 sm:px-5 py-3 sm:py-4 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-white no-print">
          <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="text-xs sm:text-sm text-gray-600 font-medium">
              Halaman {{ pagination.page }} dari {{ pagination.total_pages }} 
              (Total: {{ pagination.total_count }})
            </div>
            <div class="flex gap-2 flex-wrap">
              <button
                v-if="pagination.page > 1"
                @click="goToPage(pagination.page - 1)"
                class="px-3 py-2 bg-white border border-gray-300 rounded-xl text-xs sm:text-sm hover:bg-gray-50 transition-all shadow-sm"
              >
                <i class="fas fa-chevron-left text-xs mr-1"></i>Prev
              </button>
              <span
                v-else
                class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-xl text-xs sm:text-sm text-gray-400 cursor-not-allowed"
              >
                <i class="fas fa-chevron-left text-xs mr-1"></i>Prev
              </span>

              <template v-for="pageNum in getPageNumbers()" :key="pageNum">
                <button
                  v-if="pageNum !== '...'"
                  @click="goToPage(pageNum as number)"
                  :class="pageNum === pagination.page ? 'bg-gradient-to-br from-purple-500 to-purple-600 text-white font-semibold shadow-md' : 'bg-white border border-gray-300 hover:bg-gray-50'"
                  class="px-3 py-2 rounded-xl text-xs sm:text-sm transition-all shadow-sm"
                >
                  {{ pageNum }}
                </button>
                <span v-else class="px-2 py-2 text-gray-400 text-xs sm:text-sm">...</span>
              </template>

              <button
                v-if="pagination.page < pagination.total_pages"
                @click="goToPage(pagination.page + 1)"
                class="px-3 py-2 bg-white border border-gray-300 rounded-xl text-xs sm:text-sm hover:bg-gray-50 transition-all shadow-sm"
              >
                Next<i class="fas fa-chevron-right text-xs ml-1"></i>
              </button>
              <span
                v-else
                class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-xl text-xs sm:text-sm text-gray-400 cursor-not-allowed"
              >
                Next<i class="fas fa-chevron-right text-xs ml-1"></i>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-4 sm:mt-6 text-center text-xs sm:text-sm text-gray-500 no-print">
        <p class="text-xs">Report generated on {{ new Date().toLocaleString('en-US', { timeZone: 'UTC' }) }} UTC</p>
        <p class="mt-1 text-xs">
          Account: {{ account }} | 
          Tanggal: {{ formatDate(filterDate) }} UTC | 
          Showing {{ statistics.returned || 0 }} of {{ pagination.total_count || 0 }} positions
          <span v-if="filterSchedule"> | Schedule: {{ filterSchedule }}</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const account = ref(config.defaultAccount || '270787386')
const filterDate = ref(new Date().toISOString().split('T')[0])
const filterSchedule = ref('')
const currentPage = ref(1)

const loading = ref(false)
const positions = ref<any[]>([])
const pagination = ref({
  page: 1,
  per_page: 15,
  total_pages: 1,
  total_count: 0,
  total_all: 0
})
const statistics = ref({
  total_profit: 0,
  buy_count: 0,
  sell_count: 0,
  returned: 0
})

const timeUTC = ref('--:--:--')
const timeWIB = ref('--:--:--')

// Load data
const loadData = async (page = 1) => {
  try {
    loading.value = true
    currentPage.value = page

    const queryParams = new URLSearchParams({
      account: account.value,
      date: filterDate.value,
      page: page.toString()
    })
    if (filterSchedule.value) {
      queryParams.append('schedule', filterSchedule.value)
    }

    const response = await $fetch(`/api/history-report?${queryParams.toString()}`)

    if (response.status === 'error') throw new Error(response.message || 'Error loading data')

    positions.value = response.positions || []
    pagination.value = response.pagination || pagination.value
    statistics.value = response.statistics || statistics.value
  } catch (error: any) {
    console.error('Error loading history report:', error)
    alert('Error loading data: ' + (error.message || 'Unknown error'))
  } finally {
    loading.value = false
  }
}

// Helper functions
const getSchedule = (comment: string | null): string => {
  if (!comment) return '-'
  if (comment.includes('_SX_')) return 'SX'
  if (comment.includes('_S1_')) return 'S1'
  if (comment.includes('_S2_')) return 'S2'
  if (comment.includes('_S3_')) return 'S3'
  if (comment.includes('_S4_')) return 'S4'
  if (comment.includes('_S5_')) return 'S5'
  if (comment.includes('_S6_')) return 'S6'
  if (comment.includes('_S7_')) return 'S7'
  if (comment.includes('_S8_')) return 'S8'
  if (comment.includes('_S9_')) return 'S9'
  return '-'
}

const getScheduleClass = (schedule: string): string => {
  const classes: Record<string, string> = {
    'S1': 'bg-blue-100 text-blue-800',
    'S2': 'bg-green-100 text-green-800',
    'S3': 'bg-purple-100 text-purple-800',
    'S4': 'bg-yellow-100 text-yellow-800',
    'S5': 'bg-pink-100 text-pink-800',
    'S6': 'bg-indigo-100 text-indigo-800',
    'S7': 'bg-teal-100 text-teal-800',
    'S8': 'bg-orange-100 text-orange-800',
    'S9': 'bg-sky-100 text-sky-800',
    'SX': 'bg-lime-100 text-lime-800'
  }
  return classes[schedule] || 'bg-gray-100 text-gray-800'
}

const getTypeClass = (type: string | null): string => {
  if (!type) return 'bg-gray-100 text-gray-800'
  return type.toUpperCase() === 'BUY' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
}

const getProfitClass = (profit: number): string => {
  return profit >= 0 ? 'text-green-600' : 'text-red-600'
}

const formatCurrency = (value: number | string): string => {
  const num = parseFloat(value?.toString() || '0')
  return num >= 0 ? `+${num.toFixed(2)}` : num.toFixed(2)
}

const formatNumber = (value: number, decimals = 2): string => {
  return parseFloat(value?.toString() || '0').toFixed(decimals)
}

const formatDate = (dateString: string): string => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatDateLocalWIB = (dateString: string | null): string => {
  if (!dateString) return '-'
  try {
    const date = new Date(dateString)
    const wibDate = new Date(date.getTime() + (7 * 60 * 60 * 1000))
    return wibDate.toLocaleString('id-ID', { 
      day: '2-digit', 
      month: '2-digit', 
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    }) + ' WIB'
  } catch {
    return dateString
  }
}

const setToday = () => {
  filterDate.value = new Date().toISOString().split('T')[0]
  loadData(1)
}

const goToPage = (page: number) => {
  loadData(page)
}

const getPageNumbers = (): (number | string)[] => {
  const total = pagination.value.total_pages
  const current = pagination.value.page
  const pages: (number | string)[] = []

  let startPage = Math.max(1, current - 2)
  let endPage = Math.min(total, current + 2)

  if (startPage > 1) {
    pages.push(1)
    if (startPage > 2) {
      pages.push('...')
    }
  }

  for (let i = startPage; i <= endPage; i++) {
    pages.push(i)
  }

  if (endPage < total) {
    if (endPage < total - 1) {
      pages.push('...')
    }
    pages.push(total)
  }

  return pages
}

// Update clock
const updateClock = () => {
  const now = new Date()
  const utcTime = now.toISOString().substr(11, 8)
  timeUTC.value = utcTime
  
  const wibTime = new Date(now.getTime() + (7 * 60 * 60 * 1000))
  const wibTimeStr = wibTime.toISOString().substr(11, 8)
  timeWIB.value = wibTimeStr
}

onMounted(() => {
  loadData(1)
  updateClock()
  setInterval(updateClock, 1000)
})
</script>

<style scoped>
@media print {
  .no-print {
    display: none;
  }
  body {
    padding: 0;
  }
}
</style>

