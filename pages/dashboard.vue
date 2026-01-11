<template>
  <div class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-b border-gray-200/50 dark:border-gray-700/50 shadow-sm sticky top-0 z-10">
      <div class="max-w-6xl mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-chart-line text-white text-sm"></i>
              </div>
              Dashboard
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Trading System v10</p>
          </div>
          <div class="flex items-center gap-4">
            <div class="text-right">
              <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Account</div>
              <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ defaultAccount }}</div>
              <div class="text-xs text-gray-400 dark:text-gray-500 mt-1" id="lastRefreshTime">-</div>
            </div>
            <ThemeToggle />
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-6">
      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="stat-card rounded-xl p-4 shadow-sm fade-in">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider mb-1">EA Status</div>
              <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                <span v-if="eaStatus === 'ON'" class="text-green-600 bg-green-100 px-3 py-1 rounded-lg">ON</span>
                <span v-else-if="eaStatus === 'OFF'" class="text-red-600 bg-red-100 px-3 py-1 rounded-lg">OFF</span>
                <span v-else-if="eaStatus === 'Error'" class="text-orange-600 bg-orange-100 px-3 py-1 rounded-lg">Error</span>
                <span v-else class="text-gray-500 dark:text-gray-400">Loading...</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-md">
              <i class="fas fa-power-off text-white text-lg"></i>
            </div>
          </div>
        </div>
        <div class="stat-card rounded-xl p-4 shadow-sm fade-in">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider mb-1">Total Schedules</div>
              <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">10</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md">
              <i class="fas fa-calendar-alt text-white text-lg"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Message Alert -->
      <div v-if="errorMessage" class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 dark:border-orange-400 p-4 mb-6 rounded-r-lg">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-orange-500 dark:text-orange-400"></i>
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-semibold text-orange-800 dark:text-orange-300 mb-1">Configuration Error</h3>
            <p class="text-sm text-orange-700 dark:text-orange-300">{{ errorMessage }}</p>
            <div class="mt-2 text-xs text-orange-600 dark:text-orange-400">
              <p><strong>Quick Fix:</strong></p>
              <ol class="list-decimal list-inside ml-2 space-y-1">
                <li>Go to Vercel Dashboard → Your Project → Settings → Environment Variables</li>
                <li>Add <code class="bg-orange-100 px-1 rounded">SUPABASE_URL</code> and <code class="bg-orange-100 px-1 rounded">SUPABASE_ANON_KEY</code></li>
                <li>Redeploy your project</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Schedule Status -->
      <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 dark:border-gray-700/50 p-5 shadow-lg mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
              <i class="fas fa-calendar-alt text-white text-xs"></i>
            </div>
            Schedule Status (S1 - S9, SX)
          </h2>
          <button @click="loadEAStatus" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all shadow-sm">
            <i class="fas fa-sync-alt text-xs" :class="{ 'fa-spin': loading }"></i> Refresh
          </button>
        </div>
        <div class="flex flex-wrap gap-2">
          <div v-for="sched in schedules" :key="sched" class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 px-3 py-2 flex items-center gap-2 shadow-sm">
            <div class="text-xs text-gray-600 dark:text-gray-300 font-semibold">{{ sched }}:</div>
            <div class="text-xs font-bold text-gray-900 dark:text-gray-100">
              <span v-if="scheduleStatus[sched]" :class="scheduleStatus[sched] === 'ON' ? 'text-green-600' : 'text-red-600'">
                {{ scheduleStatus[sched] }}
              </span>
              <span v-else class="text-gray-400 dark:text-gray-500">Loading...</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Menu -->
      <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 dark:border-gray-700/50 p-6 shadow-lg mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
            <i class="fas fa-th-large text-white text-xs"></i>
          </div>
          Main Menu
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-4">
          <NuxtLink to="/view-lot" class="card-hover bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 border border-gray-200 dark:border-gray-600 shadow-sm group">
            <div class="flex items-start justify-between mb-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <i class="fas fa-coins text-white text-lg"></i>
              </div>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">Lot Management</h3>
              <p class="text-gray-600 dark:text-gray-300 text-sm">Kelola lot size & tracking</p>
            </div>
          </NuxtLink>

          <NuxtLink to="/admin-lot-sizes" class="card-hover bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 border border-gray-200 dark:border-gray-600 shadow-sm group">
            <div class="flex items-start justify-between mb-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <i class="fas fa-sliders-h text-white text-lg"></i>
              </div>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">Admin Lot Sizes</h3>
              <p class="text-gray-600 dark:text-gray-300 text-sm">Kelola lot sizes (CRUD)</p>
            </div>
          </NuxtLink>

          <NuxtLink :to="`/pendapatan?account=${defaultAccount}`" class="card-hover bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 border border-gray-200 dark:border-gray-600 shadow-sm group">
            <div class="flex items-start justify-between mb-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <i class="fas fa-chart-line text-white text-lg"></i>
              </div>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">Pendapatan</h3>
              <p class="text-gray-600 dark:text-gray-300 text-sm">Grafik pendapatan harian</p>
            </div>
          </NuxtLink>

          <NuxtLink to="/control-dashboard" class="card-hover bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 border border-gray-200 dark:border-gray-600 shadow-sm group">
            <div class="flex items-start justify-between mb-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <i class="fas fa-toggle-on text-white text-lg"></i>
              </div>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">EA Control</h3>
              <p class="text-gray-600 dark:text-gray-300 text-sm">Kontrol ON/OFF EA</p>
            </div>
          </NuxtLink>

          <NuxtLink :to="`/history-report?account=${defaultAccount}&date=${new Date().toISOString().split('T')[0]}`" class="card-hover bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 border border-gray-200 dark:border-gray-600 shadow-sm group">
            <div class="flex items-start justify-between mb-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <i class="fas fa-history text-white text-lg"></i>
              </div>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">History Report</h3>
              <p class="text-gray-600 dark:text-gray-300 text-sm">Laporan history trading</p>
            </div>
          </NuxtLink>

          <NuxtLink :to="`/streak-report?account=${defaultAccount}&date=${new Date().toISOString().split('T')[0]}`" class="card-hover bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 border border-gray-200 dark:border-gray-600 shadow-sm group">
            <div class="flex items-start justify-between mb-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <i class="fas fa-chart-bar text-white text-lg"></i>
              </div>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">Streak Report</h3>
              <p class="text-gray-600 dark:text-gray-300 text-sm">Analisis streak loss</p>
            </div>
          </NuxtLink>

          <a :href="`/api/sinyal?token=${apiToken}&account=${defaultAccount}`" target="_blank" class="card-hover bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 border border-gray-200 dark:border-gray-600 shadow-sm group">
            <div class="flex items-start justify-between mb-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <i class="fas fa-signal text-white text-lg"></i>
              </div>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">Signal API</h3>
              <p class="text-gray-600 dark:text-gray-300 text-sm">Generate sinyal trading</p>
            </div>
          </a>
        </div>
      </div>

      <!-- API Endpoints & System Info -->
      <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 dark:border-gray-700/50 p-6 shadow-lg">
        <details class="cursor-pointer">
          <summary class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2 mb-4 cursor-pointer list-none hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center">
              <i class="fas fa-code text-white text-xs"></i>
            </div>
            <span class="flex-1">API Endpoints & System Info v10</span>
            <i class="fas fa-chevron-down text-gray-400 dark:text-gray-500 text-xs transition-transform details-chevron"></i>
          </summary>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div>
              <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">API Endpoints</div>
              <div class="space-y-3">
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1">Signal</div>
                  <a :href="`/api/sinyal?token=${apiToken}&account=${defaultAccount}`" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                    /api/sinyal
                  </a>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1">Lot</div>
                  <a :href="`/api/lot?token=${apiToken}&account=${defaultAccount}&schedule=S1`" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                    /api/lot
                  </a>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1">Control</div>
                  <a :href="`/api/control?token=${apiToken}&account=${defaultAccount}&action=get`" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                    /api/control
                  </a>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1">History API</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 font-mono">/api/history (POST)</div>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1">History Report</div>
                  <a :href="`/history-report?account=${defaultAccount}&date=${new Date().toISOString().split('T')[0]}`" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                    /history-report
                  </a>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1">Streak Report</div>
                  <a :href="`/streak-report?account=${defaultAccount}&date=${new Date().toISOString().split('T')[0]}`" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                    /streak-report
                  </a>
                </div>
              </div>
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">System Info</div>
              <div class="space-y-2">
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Database</div>
                  <div class="text-sm font-bold text-gray-900 dark:text-gray-100">Supabase PostgreSQL</div>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Schedules</div>
                  <div class="text-sm font-bold text-gray-900 dark:text-gray-100">S1-S9, SX</div>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Entries</div>
                  <div class="text-sm font-bold text-gray-900 dark:text-gray-100">40 per schedule, 30 min interval</div>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Type</div>
                  <div class="text-sm font-bold text-gray-900 dark:text-gray-100">Type B (BuyStop/SellStop)</div>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Framework</div>
                  <div class="text-sm font-bold text-gray-900 dark:text-gray-100">Nuxt 3 + Supabase</div>
                </div>
              </div>
            </div>
          </div>
        </details>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border-t border-gray-200/50 dark:border-gray-700/50 mt-8">
      <div class="max-w-6xl mx-auto px-4 py-4">
        <div class="text-center text-sm text-gray-600 dark:text-gray-400">
          <p>© 2025 Trading Dashboard v10 - Multi Schedule (S1-S9, SX)</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const defaultAccount = ref(config.defaultAccount || '206943771')
const apiToken = computed(() => config.apiToken || 'abc321Xyz')
const eaStatus = ref<string>('')
const scheduleStatus = ref<Record<string, string>>({})
const loading = ref(false)
const errorMessage = ref<string>('')
const schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX']

const loadEAStatus = async () => {
  try {
    loading.value = true
    errorMessage.value = ''
    
    // Use useFetch untuk Nuxt 3 (auto-refresh support)
    const { data: response, error } = await useFetch(`/api/control`, {
      query: {
        token: apiToken.value,
        account: defaultAccount.value,
        action: 'get'
      },
      key: `ea-status-${defaultAccount.value}`,
      server: false
    })
    
    if (error.value) {
      // Extract error message dari response
      const errorData = error.value.data
      if (errorData?.statusMessage) {
        errorMessage.value = errorData.statusMessage
      } else if (error.value.message) {
        errorMessage.value = error.value.message
      } else {
        errorMessage.value = 'Failed to load EA status. Please check your Supabase configuration.'
      }
      eaStatus.value = 'Error'
      return
    }
    
    if (response.value && response.value.status === 'success') {
      eaStatus.value = response.value.ea_status || 'OFF'
      scheduleStatus.value = {
        'S1': response.value.schedule_s1 || 'ON',
        'S2': response.value.schedule_s2 || 'ON',
        'S3': response.value.schedule_s3 || 'ON',
        'S4': response.value.schedule_s4 || 'ON',
        'S5': response.value.schedule_s5 || 'ON',
        'S6': response.value.schedule_s6 || 'ON',
        'S7': response.value.schedule_s7 || 'ON',
        'S8': response.value.schedule_s8 || 'ON',
        'S9': response.value.schedule_s9 || 'ON',
        'SX': response.value.schedule_sx || 'ON'
      }
      errorMessage.value = '' // Clear error on success
      updateLastRefreshTime()
    }
  } catch (error: any) {
    console.error('Error loading EA status:', error)
    errorMessage.value = error.message || 'Failed to load EA status. Please check your configuration.'
    eaStatus.value = 'Error'
  } finally {
    loading.value = false
  }
}

const updateLastRefreshTime = () => {
  const timeEl = document.getElementById('lastRefreshTime')
  if (timeEl) {
    timeEl.textContent = new Date().toLocaleTimeString('id-ID')
  }
}

// Auto refresh setiap 10 detik (jangan terlalu sering untuk menghindari spam request)
let refreshInterval: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  loadEAStatus()
  
  // Start auto-refresh interval
  refreshInterval = setInterval(() => {
    // Only refresh if no error (stop spamming on error)
    if (!errorMessage.value) {
      loadEAStatus()
    }
  }, 10000) // 10 detik
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
})
</script>

<style scoped>
.stat-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.dark .stat-card {
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  border: 1px solid rgba(55, 65, 81, 0.8);
}

.fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card-hover {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

details[open] .details-chevron {
  transform: rotate(180deg);
}

details summary::-webkit-details-marker {
  display: none;
}

details summary::marker {
  display: none;
}
</style>

