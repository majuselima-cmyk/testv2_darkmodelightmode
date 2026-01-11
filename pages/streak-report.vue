<template>
  <div class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6">
      <!-- Header -->
      <header class="mb-4 sm:mb-6">
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-lg p-3 sm:p-5 mb-4 sm:mb-6">
          <div class="flex flex-col gap-3">
            <div class="flex items-center gap-2 sm:gap-4">
              <NuxtLink to="/dashboard" class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-500 text-gray-700 dark:text-gray-200 px-3 sm:px-4 py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-sm">
                <i class="fas fa-arrow-left text-xs"></i> Back
              </NuxtLink>
              <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-md">
                  <i class="fas fa-chart-bar text-white text-xs sm:text-sm"></i>
                </div>
                <h1 class="text-lg sm:text-2xl font-bold text-black dark:text-white truncate">
                  Streak Report
                </h1>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
              <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                Analisis Streak Loss v10 (S1 - SX) - Account: <span class="font-mono font-semibold">{{ account }}</span> | Tanggal (UTC): <span class="font-semibold">{{ filterDate }}</span>
              </p>
              <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-600 dark:text-gray-400 shadow-sm">
                <div class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Info Waktu</div>
                <div>Hari UTC: <span class="font-mono">{{ filterDate }}</span></div>
                <div>Rentang: 00:00:00 - 23:59:59 UTC</div>
              </div>
              <ThemeToggle />
            </div>
            <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 max-w-2xl">
              Streak dihitung per schedule berdasarkan <span class="font-semibold">trade berurutan yang rugi (profit &lt; 0)</span>.
              Satu blok streak dengan panjang L akan dihitung sebagai: 
              <span class="font-mono">L</span> kalau 1 ≤ L ≤ 15,
              dan dimasukkan ke <span class="font-mono">15</span> kalau L &gt; 15.
            </p>
          </div>
        </div>
      </header>

      <!-- Filter -->
      <section class="mb-4 sm:mb-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-3 sm:p-4">
        <form @submit.prevent="loadData" class="flex flex-wrap items-end gap-3">
          <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Account</label>
            <input 
              v-model="account" 
              type="text" 
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
            >
          </div>
          <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal (UTC)</label>
            <input 
              v-model="filterDate" 
              type="date" 
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>
          <div class="flex items-center gap-2">
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs sm:text-sm font-semibold transition-all shadow-md"
            >
              Refresh
            </button>
            <button
              type="button"
              @click="setToday"
              class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs sm:text-sm font-semibold transition-all"
            >
              Hari Ini (Default)
            </button>
          </div>
        </form>
      </section>

      <!-- Summary Cards -->
      <section class="mb-4 sm:mb-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 sm:p-4 shadow-sm">
          <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Total Posisi (hari ini)</div>
          <div class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100">
            {{ statistics.total_positions || 0 }}
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 sm:p-4 shadow-sm">
          <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Schedule Aktif (ada posisi)</div>
          <div class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100">
            {{ statistics.active_schedules || 0 }}
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 sm:p-4 shadow-sm">
          <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Total Blok Streak ≥ 1</div>
          <div class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100">
            {{ statistics.total_blocks || 0 }}
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 sm:p-4 shadow-sm">
          <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Rentang Streak</div>
          <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ minStreak }} &ndash; {{ maxStreak }} loss berurutan
          </div>
        </div>
      </section>

      <!-- Main Table: per schedule -->
      <section class="mb-4 sm:mb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-x-auto">
          <div class="px-3 sm:px-4 py-2 sm:py-3 border-b border-gray-200">
            <h2 class="text-xs sm:text-sm md:text-base font-semibold text-gray-900">
              Tabel Streak Loss per Schedule (Panjang 1 &ndash; 15, exact length)
            </h2>
            <p class="text-[11px] text-gray-500 mt-1">
              Contoh: nilai di kolom 1 artinya &ldquo;berapa kali muncul blok 1 loss berurutan&rdquo;,
              kolom 2 artinya blok 2 loss berurutan, dan seterusnya sampai kolom 15.
              Streak lebih dari 15 loss akan dijumlahkan di kolom 15.
            </p>
          </div>
        <div v-if="loading" class="px-4 py-8 text-center text-gray-500">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-2">
            <i class="fas fa-spinner fa-spin text-gray-400"></i>
          </div>
          <p class="text-sm font-medium">Loading...</p>
        </div>
        <table v-else class="min-w-full text-xs sm:text-sm">
          <thead class="bg-gray-100 border-b border-gray-200">
            <tr>
              <th class="px-3 py-2 text-left font-semibold text-gray-700">Schedule</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-700">Total Posisi</th>
              <th 
                v-for="n in streakRange" 
                :key="n" 
                class="px-2 py-2 text-center font-semibold text-gray-700"
              >
                {{ n === maxStreak ? n + '+' : n }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr 
              v-for="schedule in schedules" 
              :key="schedule"
              class="hover:bg-gray-50"
            >
              <td class="px-3 py-2 font-semibold text-gray-900">
                {{ schedule }}
              </td>
              <td class="px-3 py-2 text-gray-800">
                {{ streakStats[schedule]?.total_trades || 0 }}
              </td>
              <td 
                v-for="n in streakRange" 
                :key="n"
                :class="getStreakCellClass(streakStats[schedule]?.streak_counts[n] || 0)"
                class="px-2 py-2 text-center"
              >
                {{ streakStats[schedule]?.streak_counts[n] || 0 }}
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- Total Summary -->
      <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-3 sm:p-4 mb-4">
        <h2 class="text-xs sm:text-sm md:text-base font-semibold text-gray-900 mb-2">
          Ringkasan Total Semua Schedule
        </h2>
        <div :class="getTotalSummaryGridClass()" class="grid gap-2">
          <div 
            v-for="n in streakRange" 
            :key="n"
            :class="getTotalSummaryCardClass(totalStreakPerLength[n] || 0)"
            class="border rounded-lg px-3 py-2"
          >
            <div class="text-[11px] text-gray-600 mb-0.5">
              Streak {{ n === maxStreak ? n + '+' : n }} loss
            </div>
            <div :class="getTotalSummaryValueClass(totalStreakPerLength[n] || 0)" class="text-base font-bold">
              {{ totalStreakPerLength[n] || 0 }}
            </div>
          </div>
        </div>
      </section>

      <!-- Note -->
      <section class="text-[11px] text-gray-500 mt-2">
        <p>
          Analisis ini hanya melihat <span class="font-semibold">loss berurutan</span> berdasarkan urutan
          <span class="font-mono">position_time</span> pada hari dan account yang dipilih. 
          Jika ingin pola lain (misalnya gabung beberapa hari atau per minggu), bisa dibuat halaman laporan tambahan.
        </p>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const account = ref(config.defaultAccount || '206943771')
const filterDate = ref(new Date().toISOString().split('T')[0])
const minStreak = 1
const maxStreak = 15
const schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX']

const loading = ref(false)
const statistics = ref({
  total_positions: 0,
  active_schedules: 0,
  total_blocks: 0
})
const streakStats = ref<Record<string, any>>({})
const totalStreakPerLength = ref<Record<number, number>>({})

const streakRange = computed(() => {
  const range: number[] = []
  for (let i = minStreak; i <= maxStreak; i++) {
    range.push(i)
  }
  return range
})

// Load data
const loadData = async () => {
  try {
    loading.value = true

    const queryParams = new URLSearchParams({
      account: account.value,
      date: filterDate.value
    })

    const response = await $fetch(`/api/streak-report?${queryParams.toString()}`)

    if (response.status === 'error') throw new Error(response.message || 'Error loading data')

    statistics.value = response.statistics || statistics.value
    streakStats.value = response.streak_stats || {}
    totalStreakPerLength.value = response.total_streak_per_length || {}

    // Initialize streak stats for all schedules if not present
    schedules.forEach(s => {
      if (!streakStats.value[s]) {
        streakStats.value[s] = {
          total_trades: 0,
          streak_counts: {}
        }
        for (let n = minStreak; n <= maxStreak; n++) {
          if (!streakStats.value[s].streak_counts[n]) {
            streakStats.value[s].streak_counts[n] = 0
          }
        }
      }
    })
  } catch (error: any) {
    console.error('Error loading streak report:', error)
    alert('Error loading data: ' + (error.message || 'Unknown error'))
  } finally {
    loading.value = false
  }
}

const setToday = () => {
  filterDate.value = new Date().toISOString().split('T')[0]
  loadData()
}

const getStreakCellClass = (val: number): string => {
  return val > 0 ? 'bg-red-50 text-red-800 font-semibold' : 'text-gray-400'
}

const getTotalSummaryGridClass = (): string => {
  const count = maxStreak - minStreak + 1
  if (count <= 4) return 'grid-cols-2 sm:grid-cols-4'
  if (count <= 6) return 'grid-cols-2 sm:grid-cols-3 md:grid-cols-6'
  return 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6'
}

const getTotalSummaryCardClass = (val: number): string => {
  return val > 0 ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'
}

const getTotalSummaryValueClass = (val: number): string => {
  return val > 0 ? 'text-red-700' : 'text-gray-500'
}

onMounted(() => {
  loadData()
})
</script>

