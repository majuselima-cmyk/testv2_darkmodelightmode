<template>
  <div class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6">
      <!-- Header -->
      <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-5 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 gap-3">
          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-4 mb-3">
              <NuxtLink to="/dashboard" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-sm">
                <i class="fas fa-arrow-left text-xs"></i> Back
              </NuxtLink>
              <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-md">
                  <i class="fas fa-toggle-on text-white text-sm"></i>
                </div>
                EA Control Dashboard
              </h1>
            </div>
            <p class="text-sm text-gray-600">Kontrol ON/OFF EA Trading - Multi Schedule v10 (S1 - S9, SX)</p>
          </div>
          <div class="flex items-center gap-3">
            <button @click="refreshStatus" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md">
              <i class="fas fa-sync-alt text-xs" :class="{ 'fa-spin': loading }"></i> Refresh
            </button>
            <div class="text-sm text-gray-600 bg-gradient-to-br from-gray-50 to-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm">
              <i class="far fa-clock text-xs"></i> <span class="ml-1 font-semibold">{{ lastUpdate }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading && !eaStatus" class="text-center py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 mb-4">
          <i class="fas fa-spinner fa-spin text-2xl text-white"></i>
        </div>
        <p class="text-gray-600 text-sm font-medium">Memuat status...</p>
      </div>

      <!-- Error State -->
      <div v-if="error" class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-4 mb-6 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center">
            <i class="fas fa-exclamation-circle text-white text-sm"></i>
          </div>
          <div>
            <h3 class="font-bold text-red-900 text-sm">Error</h3>
            <p class="text-red-700 text-sm mt-0.5">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Main Card -->
      <div v-if="!loading || eaStatus">
        <!-- Global Status Card -->
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xl mb-6">
          <div :class="isOn ? 'bg-gradient-to-br from-green-600 to-green-700' : 'bg-gradient-to-br from-red-600 to-red-700'" class="p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/20 rounded-full -mr-16 -mt-16"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/20 rounded-full -ml-12 -mb-12"></div>
            <div class="relative flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div :class="isOn ? 'status-badge' : ''" class="w-16 h-16 rounded-xl bg-white/30 backdrop-blur-sm flex items-center justify-center shadow-lg border-2 border-white/50">
                  <i :class="isOn ? 'fa-toggle-on' : 'fa-toggle-off'" class="fas text-white text-2xl drop-shadow-lg"></i>
                </div>
                <div>
                  <h2 class="text-2xl font-bold text-white drop-shadow-md flex items-center gap-2">
                    EA Global Status: <span class="text-3xl text-white font-black">{{ isOn ? 'ON' : 'OFF' }}</span>
                  </h2>
                  <p class="text-white/95 mt-1 text-sm font-medium drop-shadow-sm">Account: {{ accountNumber }}</p>
                </div>
              </div>
              <div class="text-right">
                <div class="text-xs text-white/90 mb-1 uppercase tracking-wider font-semibold drop-shadow-sm">Global</div>
                <div class="text-4xl font-black text-white drop-shadow-lg">{{ isOn ? 'ON' : 'OFF' }}</div>
              </div>
            </div>
          </div>
          <div class="p-4 bg-gradient-to-br from-gray-50 to-white">
            <div class="flex flex-col sm:flex-row gap-3">
              <button @click="setStatus('ON')" :disabled="isOn" 
                      :class="[
                        isOn ? 'bg-gradient-to-br from-gray-400 to-gray-500 cursor-not-allowed' : 'bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 hover:scale-105'
                      ]" 
                      class="flex-1 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center justify-center gap-2">
                <i class="fas fa-power-off text-sm"></i> Turn ON All
              </button>
              <button @click="setStatus('OFF')" :disabled="!isOn"
                      :class="[
                        !isOn ? 'bg-gradient-to-br from-gray-400 to-gray-500 cursor-not-allowed' : 'bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 hover:scale-105'
                      ]"
                      class="flex-1 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center justify-center gap-2">
                <i class="fas fa-stop text-sm"></i> Turn OFF All
              </button>
            </div>
          </div>
        </div>

        <!-- Schedule Cards Grid -->
        <div class="schedule-grid grid gap-4 mb-6">
          <div v-for="schedule in schedules" :key="schedule.id" :class="getScheduleCardClass(schedule)" class="schedule-card bg-white rounded-xl border-2 overflow-hidden shadow-md transition-all">
            <div :class="schedule.isEnabled ? schedule.color.bg : 'bg-gradient-to-br from-red-500 to-red-600'" class="p-4">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-sm border border-white/30">
                    <i :class="schedule.isEnabled ? 'fa-toggle-on' : 'fa-toggle-off'" class="fas text-white text-lg drop-shadow"></i>
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-white drop-shadow-sm">{{ schedule.id }}</h3>
                    <p class="text-xs text-white/90 mt-0.5 font-medium">{{ schedule.time }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-xs text-white/90 mb-0.5 font-semibold uppercase tracking-wide">Status</div>
                  <div class="inline-flex items-center px-3 py-1 rounded-lg bg-white/25 backdrop-blur-sm border border-white/30 shadow-sm">
                    <span class="text-base font-black text-white drop-shadow-sm">{{ schedule.isEnabled ? 'ON' : 'OFF' }}</span>
                  </div>
                </div>
              </div>
              <div class="text-xs text-white/90 font-medium mt-2">40 entries per schedule</div>
            </div>
            <div class="p-4 space-y-3 bg-gradient-to-br from-white to-gray-50">
              <div class="flex gap-2">
                <button @click="setScheduleStatus(schedule.id, 'ON')" 
                        :disabled="schedule.isEnabled"
                        :class="[
                          schedule.isEnabled ? 'bg-gradient-to-br from-gray-400 to-gray-500 cursor-not-allowed' : schedule.color.btn + ' hover:scale-105',
                          'flex-1 text-white px-3 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5'
                        ]">
                  <i class="fas fa-power-off text-xs"></i> ON
                </button>
                <button @click="setScheduleStatus(schedule.id, 'OFF')" 
                        :disabled="!schedule.isEnabled"
                        :class="[
                          !schedule.isEnabled ? 'bg-gradient-to-br from-gray-400 to-gray-500 cursor-not-allowed' : 'bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 hover:scale-105',
                          'flex-1 text-white px-3 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5'
                        ]">
                  <i class="fas fa-stop text-xs"></i> OFF
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-5 shadow-lg mb-6">
          <h4 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
              <i class="fas fa-bolt text-white text-sm"></i>
            </div>
            Quick Actions
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <button @click="setAllSchedulesStatus('ON')" class="bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center justify-center gap-2 hover:scale-105">
              <i class="fas fa-power-off text-sm"></i> All ON
            </button>
            <button @click="setAllSchedulesStatus('OFF')" class="bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center justify-center gap-2 hover:scale-105">
              <i class="fas fa-stop text-sm"></i> All OFF
            </button>
          </div>
        </div>

        <!-- Info Section -->
        <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-5 shadow-lg">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-4 border border-gray-200">
              <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                  <i class="fas fa-calendar-alt text-blue-600 text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-gray-700">Last Updated</span>
              </div>
              <p class="text-sm font-bold text-gray-900 break-words">{{ updatedAt }}</p>
            </div>
          </div>

          <!-- Warning Message -->
          <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border-l-4 border-yellow-500 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-lg bg-yellow-500 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-white text-sm"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-yellow-900 mb-1">Perhatian!</p>
                <ul class="text-xs text-yellow-800 space-y-1">
                  <li>• Global OFF: Semua schedule tidak akan memproses sinyal baru</li>
                  <li>• Schedule OFF: Hanya schedule tersebut yang tidak aktif</li>
                  <li>• Posisi terbuka tetap berjalan | EA cek status setiap 60 detik</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const accountNumber = ref(config.defaultAccount || '270787386')
const loading = ref(true)
const error = ref('')
const eaStatus = ref<string>('')
const scheduleStatus = ref<Record<string, string>>({})
const lastUpdate = ref('-')
const updatedAt = ref('-')

const scheduleTimes: Record<string, string> = {
  'S1': '01:00:00',
  'S2': '01:03:00',
  'S3': '01:06:00',
  'S4': '01:09:00',
  'S5': '01:12:00',
  'S6': '01:15:00',
  'S7': '01:18:00',
  'S8': '01:21:00',
  'S9': '01:24:00',
  'SX': '01:27:00'
}

const scheduleColorsConfig: Record<string, any> = {
  'S1': { bg: 'bg-gradient-to-br from-blue-500 to-blue-600', btn: 'bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700', border: 'border-blue-300', text: 'text-blue-800' },
  'S2': { bg: 'bg-gradient-to-br from-green-500 to-green-600', btn: 'bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700', border: 'border-green-300', text: 'text-green-800' },
  'S3': { bg: 'bg-gradient-to-br from-purple-500 to-purple-600', btn: 'bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700', border: 'border-purple-300', text: 'text-purple-800' },
  'S4': { bg: 'bg-gradient-to-br from-orange-500 to-orange-600', btn: 'bg-gradient-to-br from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700', border: 'border-orange-300', text: 'text-orange-800' },
  'S5': { bg: 'bg-gradient-to-br from-pink-500 to-pink-600', btn: 'bg-gradient-to-br from-pink-500 to-pink-600 hover:from-pink-600 hover:to-pink-700', border: 'border-pink-300', text: 'text-pink-800' },
  'S6': { bg: 'bg-gradient-to-br from-indigo-500 to-indigo-600', btn: 'bg-gradient-to-br from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700', border: 'border-indigo-300', text: 'text-indigo-800' },
  'S7': { bg: 'bg-gradient-to-br from-red-500 to-red-600', btn: 'bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700', border: 'border-red-300', text: 'text-red-800' },
  'S8': { bg: 'bg-gradient-to-br from-teal-500 to-teal-600', btn: 'bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700', border: 'border-teal-300', text: 'text-teal-800' },
  'S9': { bg: 'bg-gradient-to-br from-amber-500 to-amber-600', btn: 'bg-gradient-to-br from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700', border: 'border-amber-300', text: 'text-amber-800' },
  'SX': { bg: 'bg-gradient-to-br from-cyan-500 to-cyan-600', btn: 'bg-gradient-to-br from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700', border: 'border-cyan-300', text: 'text-cyan-800' }
}

const schedules = computed(() => {
  return ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX'].map(id => ({
    id,
    time: scheduleTimes[id],
    isEnabled: (scheduleStatus.value[id] || 'ON') === 'ON',
    color: scheduleColorsConfig[id] || scheduleColorsConfig['S1']
  }))
})

const isOn = computed(() => eaStatus.value === 'ON')

const getScheduleCardClass = (schedule: any) => {
  if (schedule.isEnabled) {
    return `${schedule.color.border} hover:border-opacity-80`
  }
  return 'border-red-300 hover:border-red-400'
}

const refreshStatus = async () => {
  await fetchStatus()
}

const fetchStatus = async () => {
  try {
    loading.value = true
    error.value = ''
    const token = config.apiToken || 'abc321Xyz'

    const { data, error: fetchError } = await useFetch('/api/control', {
      query: { token, account: accountNumber.value, action: 'get' },
      key: `control-status-${accountNumber.value}`,
      server: false
    })

    if (fetchError.value) {
      throw fetchError.value
    }

    if (data.value?.status === 'success') {
      eaStatus.value = data.value.ea_status || 'OFF'
      scheduleStatus.value = {
        'S1': data.value.schedule_s1 || 'ON',
        'S2': data.value.schedule_s2 || 'ON',
        'S3': data.value.schedule_s3 || 'ON',
        'S4': data.value.schedule_s4 || 'ON',
        'S5': data.value.schedule_s5 || 'ON',
        'S6': data.value.schedule_s6 || 'ON',
        'S7': data.value.schedule_s7 || 'ON',
        'S8': data.value.schedule_s8 || 'ON',
        'S9': data.value.schedule_s9 || 'ON',
        'SX': data.value.schedule_sx || 'ON'
      }
      updatedAt.value = data.value.updated_at ? new Date(data.value.updated_at).toLocaleString('id-ID') : '-'
      lastUpdate.value = new Date().toLocaleTimeString('id-ID')
    }
  } catch (err: any) {
    console.error('Error:', err)
    error.value = err.message || 'Failed to get status'
  } finally {
    loading.value = false
  }
}

const setStatus = async (status: string) => {
  try {
    error.value = ''
    const token = config.apiToken || 'abc321Xyz'
    const action = status.toLowerCase()

    const { data, error: fetchError } = await useFetch('/api/control', {
      method: 'POST',
      body: { token, account: accountNumber.value, action },
      key: `control-set-${accountNumber.value}-${Date.now()}`,
      server: false
    })

    if (fetchError.value) {
      throw fetchError.value
    }

    if (data.value?.status === 'success') {
      await fetchStatus()
      showNotification(`Status global berhasil diubah menjadi ${status}`, 'success')
    }
  } catch (err: any) {
    console.error('Error:', err)
    error.value = err.message || 'Failed to update status'
    showNotification(`Gagal mengubah status: ${err.message}`, 'error')
  }
}

const setScheduleStatus = async (schedule: string, status: string) => {
  try {
    error.value = ''
    const token = config.apiToken || 'abc321Xyz'
    const action = `${schedule.toLowerCase()}_${status.toLowerCase()}`

    const { data, error: fetchError } = await useFetch('/api/control', {
      method: 'POST',
      body: { token, account: accountNumber.value, action },
      key: `control-schedule-${schedule}-${Date.now()}`,
      server: false
    })

    if (fetchError.value) {
      throw fetchError.value
    }

    if (data.value?.status === 'success') {
      await fetchStatus()
      showNotification(`Schedule ${schedule} berhasil diubah menjadi ${status}`, 'success')
    }
  } catch (err: any) {
    console.error('Error:', err)
    error.value = err.message || 'Failed to update schedule status'
    showNotification(`Gagal mengubah status schedule: ${err.message}`, 'error')
  }
}

const setAllSchedulesStatus = async (status: string) => {
  try {
    error.value = ''
    const token = config.apiToken || 'abc321Xyz'
    const action = `all_${status.toLowerCase()}`

    const { data, error: fetchError } = await useFetch('/api/control', {
      method: 'POST',
      body: { token, account: accountNumber.value, action },
      key: `control-all-${status}-${Date.now()}`,
      server: false
    })

    if (fetchError.value) {
      throw fetchError.value
    }

    if (data.value?.status === 'success') {
      await fetchStatus()
      showNotification(`Semua schedule berhasil diubah menjadi ${status}`, 'success')
    }
  } catch (err: any) {
    console.error('Error:', err)
    error.value = err.message || 'Failed to update all schedules status'
    showNotification(`Gagal mengubah status semua schedule: ${err.message}`, 'error')
  }
}

const showNotification = (message: string, type: 'success' | 'error') => {
  // Simple notification - bisa diganti dengan toast library jika perlu
  if (type === 'success') {
    alert(message)
  } else {
    alert(message)
  }
}

onMounted(() => {
  fetchStatus()
  setInterval(() => {
    fetchStatus()
  }, 30000) // Auto refresh setiap 30 detik
})
</script>

<style scoped>
.schedule-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.schedule-card:hover {
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
}

.status-badge {
  animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
  }
  50% {
    box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
  }
}

/* Ensure disabled buttons have gray color and stay visible */
button:disabled {
  cursor: not-allowed !important;
  background: linear-gradient(to bottom right, rgb(156, 163, 175), rgb(107, 114, 128)) !important;
  background-image: linear-gradient(to bottom right, rgb(156, 163, 175), rgb(107, 114, 128)) !important;
  color: white !important;
  -webkit-text-fill-color: white !important;
}

button:disabled:hover {
  transform: none !important;
  background: linear-gradient(to bottom right, rgb(156, 163, 175), rgb(107, 114, 128)) !important;
  background-image: linear-gradient(to bottom right, rgb(156, 163, 175), rgb(107, 114, 128)) !important;
  color: white !important;
}

/* Override browser default disabled button styling */
button[disabled] {
  background: linear-gradient(to bottom right, rgb(156, 163, 175), rgb(107, 114, 128)) !important;
  background-image: linear-gradient(to bottom right, rgb(156, 163, 175), rgb(107, 114, 128)) !important;
  color: white !important;
  -webkit-text-fill-color: white !important;
  opacity: 1 !important;
}

@media (max-width: 640px) {
  .schedule-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 641px) and (max-width: 1024px) {
  .schedule-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1025px) {
  .schedule-grid {
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }
}
</style>

