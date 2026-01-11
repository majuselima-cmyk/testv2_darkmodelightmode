<?php include_once __DIR__ . '/account_config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>EA Control Dashboard - v10</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        @media (max-width: 640px) {
            .schedule-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 641px) and (max-width: 1024px) {
            .schedule-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1025px) {
            .schedule-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6">
        <!-- Header -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 gap-3">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-4 mb-3">
                        <a href="dashboard.php" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-sm">
                            <i class="fas fa-arrow-left text-xs"></i> Back
                        </a>
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
                    <button onclick="refreshStatus()" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md">
                        <i class="fas fa-sync-alt text-xs"></i> Refresh
                    </button>
                    <div class="text-sm text-gray-600 bg-gradient-to-br from-gray-50 to-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm">
                        <i class="far fa-clock text-xs"></i> <span id="lastUpdate" class="ml-1 font-semibold">-</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading" class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 mb-4">
                <i class="fas fa-spinner fa-spin text-2xl text-white"></i>
            </div>
            <p class="text-gray-600 text-sm font-medium">Memuat status...</p>
        </div>

        <!-- Error State -->
        <div id="error" class="hidden bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-4 mb-6 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-red-900 text-sm">Error</h3>
                    <p class="text-red-700 text-sm mt-0.5" id="errorMessage"></p>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div id="mainCard" class="hidden"></div>
    </div>

    <script>
        const API_URL = 'control.php?token=abc321Xyz&format=standard';
        const ACCOUNT = '<?= addslashes($DEFAULT_ACCOUNT) ?>';

        async function fetchStatus() {
            try {
                document.getElementById('loading').classList.remove('hidden');
                document.getElementById('error').classList.add('hidden');
                document.getElementById('mainCard').classList.add('hidden');

                const response = await fetch(API_URL + '&account=' + ACCOUNT + '&action=get');
                const data = await response.json();
                
                if (data.status === 'success') {
                    displayStatusCard(data);
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('id-ID');
                } else {
                    throw new Error(data.message || 'Failed to get status');
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error').classList.remove('hidden');
                document.getElementById('errorMessage').textContent = error.message;
            } finally {
                document.getElementById('loading').classList.add('hidden');
            }
        }

        async function setStatus(status) {
            try {
                document.getElementById('error').classList.add('hidden');
                const response = await fetch(API_URL + '&account=' + ACCOUNT + '&action=' + status.toLowerCase(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'token=abc321Xyz&account=' + ACCOUNT + '&action=' + status.toLowerCase()
                });
                const data = await response.json();
                if (data.status === 'success') {
                    await fetchStatus();
                    // Trigger refresh di dashboard.php via localStorage
                    localStorage.setItem('ea_status_updated', Date.now().toString());
                    showNotification('Status global berhasil diubah menjadi ' + status, 'success');
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error').classList.remove('hidden');
                document.getElementById('errorMessage').textContent = error.message;
                showNotification('Gagal mengubah status: ' + error.message, 'error');
            }
        }

        async function setScheduleStatus(schedule, status) {
            try {
                document.getElementById('error').classList.add('hidden');
                const action = schedule.toLowerCase() + '_' + status.toLowerCase();
                const response = await fetch(API_URL + '&account=' + ACCOUNT + '&action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'token=abc321Xyz&account=' + ACCOUNT + '&action=' + action
                });
                const data = await response.json();
                if (data.status === 'success') {
                    await fetchStatus();
                    // Trigger refresh di dashboard.php via localStorage
                    localStorage.setItem('ea_status_updated', Date.now().toString());
                    showNotification(`Schedule ${schedule} berhasil diubah menjadi ${status}`, 'success');
                } else {
                    throw new Error(data.message || 'Failed to update schedule status');
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error').classList.remove('hidden');
                document.getElementById('errorMessage').textContent = error.message;
                showNotification('Gagal mengubah status schedule: ' + error.message, 'error');
            }
        }

        async function setAllSchedulesStatus(status) {
            try {
                document.getElementById('error').classList.add('hidden');
                const action = 'all_' + status.toLowerCase();
                const response = await fetch(API_URL + '&account=' + ACCOUNT + '&action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'token=abc321Xyz&account=' + ACCOUNT + '&action=' + action
                });
                const data = await response.json();
                if (data.status === 'success') {
                    await fetchStatus();
                    // Trigger refresh di dashboard.php via localStorage
                    localStorage.setItem('ea_status_updated', Date.now().toString());
                    showNotification(`Semua schedule berhasil diubah menjadi ${status}`, 'success');
                } else {
                    throw new Error(data.message || 'Failed to update all schedules status');
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error').classList.remove('hidden');
                document.getElementById('errorMessage').textContent = error.message;
                showNotification('Gagal mengubah status semua schedule: ' + error.message, 'error');
            }
        }

        function displayStatusCard(data) {
            const isOn = data.ea_status === 'ON';
            const sOn = {
                'S1': (data.schedule_s1 || 'ON') === 'ON',
                'S2': (data.schedule_s2 || 'ON') === 'ON',
                'S3': (data.schedule_s3 || 'ON') === 'ON',
                'S4': (data.schedule_s4 || 'ON') === 'ON',
                'S5': (data.schedule_s5 || 'ON') === 'ON',
                'S6': (data.schedule_s6 || 'ON') === 'ON',
                'S7': (data.schedule_s7 || 'ON') === 'ON',
                'S8': (data.schedule_s8 || 'ON') === 'ON',
                'S9': (data.schedule_s9 || 'ON') === 'ON',
                'SX': (data.schedule_sx || 'ON') === 'ON'
            };
            
            const statusGradient = isOn ? 'from-green-600 to-green-700' : 'from-red-600 to-red-700';
            const statusIcon = isOn ? 'fa-toggle-on' : 'fa-toggle-off';
            const statusText = isOn ? 'ON' : 'OFF';
            const updatedAt = data.updated_at ? new Date(data.updated_at).toLocaleString('id-ID') : '-';

            const scheduleTimes = {
                S1: '01:00:00', S2: '01:03:00', S3: '01:06:00', S4: '01:09:00', S5: '01:12:00',
                S6: '01:15:00', S7: '01:18:00', S8: '01:22:00', S9: '01:25:00', SX: '01:28:00'
            };

            function createScheduleCard(scheduleId, isEnabled, startTime) {
                const colors = {
                    'S1':  { bg: 'from-blue-500 to-blue-600',    bgLight: 'bg-blue-50',    border: 'border-blue-300',    text: 'text-blue-800',    btn: 'from-blue-500 to-blue-600' },
                    'S2':  { bg: 'from-green-500 to-green-600',   bgLight: 'bg-green-50',   border: 'border-green-300',   text: 'text-green-800',   btn: 'from-green-500 to-green-600' },
                    'S3':  { bg: 'from-purple-500 to-purple-600', bgLight: 'bg-purple-50',  border: 'border-purple-300',  text: 'text-purple-800',  btn: 'from-purple-500 to-purple-600' },
                    'S4':  { bg: 'from-orange-500 to-orange-600', bgLight: 'bg-orange-50',  border: 'border-orange-300',  text: 'text-orange-800',  btn: 'from-orange-500 to-orange-600' },
                    'S5':  { bg: 'from-pink-500 to-pink-600',    bgLight: 'bg-pink-50',    border: 'border-pink-300',    text: 'text-pink-800',    btn: 'from-pink-500 to-pink-600' },
                    'S6':  { bg: 'from-indigo-500 to-indigo-600', bgLight: 'bg-indigo-50',  border: 'border-indigo-300',  text: 'text-indigo-800',  btn: 'from-indigo-500 to-indigo-600' },
                    'S7':  { bg: 'from-red-500 to-red-600',     bgLight: 'bg-red-50',     border: 'border-red-300',     text: 'text-red-800',     btn: 'from-red-500 to-red-600' },
                    'S8':  { bg: 'from-teal-500 to-teal-600',    bgLight: 'bg-teal-50',    border: 'border-teal-300',    text: 'text-teal-800',    btn: 'from-teal-500 to-teal-600' },
                    'S9':  { bg: 'from-orange-500 to-orange-600', bgLight: 'bg-orange-50',  border: 'border-orange-300',  text: 'text-orange-800',  btn: 'from-orange-500 to-orange-600' },
                    'SX': { bg: 'from-cyan-500 to-cyan-600',    bgLight: 'bg-cyan-50',    border: 'border-cyan-300',    text: 'text-cyan-800',    btn: 'from-cyan-500 to-cyan-600' },
                };
                const c = colors[scheduleId] || colors['S1'];
                const statusGradient = isEnabled ? c.bg : 'from-red-500 to-red-600';
                
                return `
                    <div class="schedule-card bg-white rounded-xl border-2 ${isEnabled ? c.border : 'border-red-300'} overflow-hidden shadow-md">
                        <div class="bg-gradient-to-br ${statusGradient} p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                        <i class="fas ${isEnabled ? 'fa-toggle-on' : 'fa-toggle-off'} text-white text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-white">${scheduleId}</h3>
                                        <p class="text-xs text-white/80 mt-0.5">${startTime}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-white/80 mb-0.5">Status</div>
                                    <div class="inline-flex items-center px-3 py-1 rounded-lg bg-white/20 backdrop-blur-sm">
                                        <span class="text-lg font-bold text-white">${isEnabled ? 'ON' : 'OFF'}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs text-white/80">40 entries per schedule</div>
                        </div>
                        <div class="p-4 space-y-3 bg-gradient-to-br from-white to-gray-50">
                            <div class="flex gap-2">
                                <button onclick="setScheduleStatus('${scheduleId}', 'ON')" 
                                        class="flex-1 bg-gradient-to-br ${c.btn} hover:opacity-90 text-white px-3 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5 ${isEnabled ? 'opacity-50 cursor-not-allowed' : ''}"
                                        ${isEnabled ? 'disabled' : ''}>
                                    <i class="fas fa-power-off text-xs"></i> ON
                                </button>
                                <button onclick="setScheduleStatus('${scheduleId}', 'OFF')" 
                                        class="flex-1 bg-gradient-to-br from-red-500 to-red-600 hover:opacity-90 text-white px-3 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5 ${!isEnabled ? 'opacity-50 cursor-not-allowed' : ''}"
                                        ${!isEnabled ? 'disabled' : ''}>
                                    <i class="fas fa-stop text-xs"></i> OFF
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            const schedulesHtml = ['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX']
                .map(id => createScheduleCard(id, sOn[id] || false, scheduleTimes[id]))
                .join('');

            const card = `
                <div class="space-y-6">
                    <!-- Global Status Card -->
                    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xl">
                        <div class="bg-gradient-to-br ${statusGradient} p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/20 rounded-full -mr-16 -mt-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/20 rounded-full -ml-12 -mb-12"></div>
                            <div class="relative flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 rounded-xl bg-white/30 backdrop-blur-sm flex items-center justify-center shadow-lg border-2 border-white/50 ${isOn ? 'status-badge' : ''}">
                                        <i class="fas ${statusIcon} text-white text-2xl drop-shadow-lg"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold text-white drop-shadow-md flex items-center gap-2">
                                            EA Global Status: <span class="text-3xl text-white font-black">${statusText}</span>
                                        </h2>
                                        <p class="text-white/95 mt-1 text-sm font-medium drop-shadow-sm">Account: ${data.account_number}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-white/90 mb-1 uppercase tracking-wider font-semibold drop-shadow-sm">Global</div>
                                    <div class="text-4xl font-black text-white drop-shadow-lg">${statusText}</div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-gradient-to-br from-gray-50 to-white">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button onclick="setStatus('ON')" 
                                        class="flex-1 bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center justify-center gap-2 ${isOn ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105'}"
                                        ${isOn ? 'disabled' : ''}>
                                    <i class="fas fa-power-off text-sm"></i> Turn ON All
                                </button>
                                <button onclick="setStatus('OFF')" 
                                        class="flex-1 bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center justify-center gap-2 ${!isOn ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105'}"
                                        ${!isOn ? 'disabled' : ''}>
                                    <i class="fas fa-stop text-sm"></i> Turn OFF All
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Cards Grid -->
                    <div class="schedule-grid grid gap-4">
                        ${schedulesHtml}
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-5 shadow-lg">
                        <h4 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                                <i class="fas fa-bolt text-white text-sm"></i>
                            </div>
                            Quick Actions
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button onclick="setAllSchedulesStatus('ON')" 
                                    class="bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center justify-center gap-2 hover:scale-105">
                                <i class="fas fa-power-off text-sm"></i> All ON
                            </button>
                            <button onclick="setAllSchedulesStatus('OFF')" 
                                    class="bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center justify-center gap-2 hover:scale-105">
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
                                <p class="text-sm font-bold text-gray-900 break-words">${updatedAt}</p>
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
            `;

            document.getElementById('mainCard').innerHTML = card;
            document.getElementById('mainCard').classList.remove('hidden');
        }

        function refreshStatus() {
            fetchStatus();
        }

        function showNotification(message, type) {
            const bgGradient = type === 'success' 
                ? 'bg-gradient-to-br from-green-500 to-green-600' 
                : 'bg-gradient-to-br from-red-500 to-red-600';
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${bgGradient} text-white px-5 py-3 rounded-xl shadow-2xl z-50 flex items-center gap-3 text-sm font-semibold max-w-md`;
            notification.style.animation = 'slideInRight 0.3s ease-out';
            notification.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-sm"></i>
                </div>
                <span class="break-words">${message}</span>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        fetchStatus();
        setInterval(fetchStatus, 30000);
    </script>
</body>
</html>
