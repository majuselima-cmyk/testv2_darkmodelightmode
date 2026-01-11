<?php include_once __DIR__ . '/account_config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Trading System v10</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.4s ease-out; }
        .card-hover {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 shadow-sm sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-chart-line text-white text-sm"></i>
                        </div>
                        Dashboard
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Trading System v10</p>
                </div>
                    <div class="text-right">
                    <div class="text-xs text-gray-500 font-medium">Account</div>
                    <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($DEFAULT_ACCOUNT) ?></div>
                    <div class="text-xs text-gray-400 mt-1" id="lastRefreshTime">-</div>
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
                        <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">EA Status</div>
                        <div class="text-2xl font-bold text-gray-900" id="eaStatus">Loading...</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-md">
                        <i class="fas fa-power-off text-white text-lg"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card rounded-xl p-4 shadow-sm fade-in">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Total Schedules</div>
                        <div class="text-2xl font-bold text-gray-900">10</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md">
                        <i class="fas fa-calendar-alt text-white text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Status -->
        <div class="bg-white/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-5 shadow-lg mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-white text-xs"></i>
                    </div>
                    Schedule Status (S1 - S9, SX)
                </h2>
                <button onclick="loadEAStatus()" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all shadow-sm">
                    <i class="fas fa-sync-alt text-xs"></i> Refresh
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php 
                $schedules_display = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX'];
                foreach($schedules_display as $sched): 
                ?>
                <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg border border-gray-200 px-3 py-2 flex items-center gap-2 shadow-sm">
                    <div class="text-xs text-gray-600 font-semibold"><?php echo $sched; ?>:</div>
                    <div class="text-xs font-bold text-gray-900" id="schedule<?php echo $sched; ?>Status">Loading...</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Menu -->
        <div class="bg-white/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6 shadow-lg mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                    <i class="fas fa-th-large text-white text-xs"></i>
                </div>
                Main Menu
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7 gap-4">
                <a href="view-lot.php" class="card-hover bg-gradient-to-br from-white to-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-coins text-white text-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Lot Management</h3>
                        <p class="text-gray-600 text-sm">Kelola lot size & tracking</p>
                    </div>
                </a>

                <a href="admin_lot_sizes.php" class="card-hover bg-gradient-to-br from-white to-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-sliders-h text-white text-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Admin Lot Sizes</h3>
                        <p class="text-gray-600 text-sm">Kelola lot sizes (CRUD)</p>
                    </div>
                </a>

                <a href="pendapatan.php?account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>" class="card-hover bg-gradient-to-br from-white to-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-line text-white text-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Pendapatan</h3>
                        <p class="text-gray-600 text-sm">Grafik pendapatan harian</p>
                    </div>
                </a>

                <a href="control_dashboard.php" class="card-hover bg-gradient-to-br from-white to-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-toggle-on text-white text-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">EA Control</h3>
                        <p class="text-gray-600 text-sm">Kontrol ON/OFF EA</p>
                    </div>
                </a>

                <a href="sinyal.php?token=abc321Xyz&account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>" target="_blank" class="card-hover bg-gradient-to-br from-white to-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-signal text-white text-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Signal API</h3>
                        <p class="text-gray-600 text-sm">Generate sinyal trading</p>
                    </div>
                </a>

                <a href="historyreport.php?account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>" target="_blank" class="card-hover bg-gradient-to-br from-white to-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-history text-white text-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">History Report</h3>
                        <p class="text-gray-600 text-sm">Laporan trading history</p>
                    </div>
                </a>

                <a href="streak_report.php?account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>&date=<?= date('Y-m-d') ?>" target="_blank" class="card-hover bg-gradient-to-br from-white to-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-bar text-white text-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Streak Report</h3>
                        <p class="text-gray-600 text-sm">Analisis streak loss</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- API Endpoints & System Info -->
        <div class="bg-white/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6 shadow-lg">
            <details class="cursor-pointer">
                <summary class="text-lg font-bold text-gray-900 flex items-center gap-2 mb-4 cursor-pointer">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center">
                        <i class="fas fa-code text-white text-xs"></i>
                    </div>
                    API Endpoints & System Info v10
                </summary>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 pt-4 border-t border-gray-200">
                    <div>
                        <div class="text-sm font-semibold text-gray-700 mb-3">API Endpoints</div>
                        <div class="space-y-3">
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs font-semibold text-gray-900 mb-1">Signal</div>
                                <a href="sinyal.php?token=abc321Xyz&account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                                    sinyal.php
                                </a>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs font-semibold text-gray-900 mb-1">Lot</div>
                                <a href="lot.php?token=abc321Xyz&account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>&schedule=S1" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                                    lot.php
                                </a>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs font-semibold text-gray-900 mb-1">Control</div>
                                <a href="control.php?token=abc321Xyz&account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>&action=get" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                                    control.php
                                </a>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs font-semibold text-gray-900 mb-1">History API</div>
                                <div class="text-xs text-gray-600 font-mono">history.php (POST)</div>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs font-semibold text-gray-900 mb-1">History Report</div>
                                <a href="historyreport.php?account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>&date=<?= date('Y-m-d') ?>" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                                    historyreport.php
                                </a>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs font-semibold text-gray-900 mb-1">Streak Report</div>
                                <a href="streak_report.php?account=<?= htmlspecialchars($DEFAULT_ACCOUNT) ?>&date=<?= date('Y-m-d') ?>" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-mono break-all underline">
                                    streak_report.php
                                </a>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-700 mb-3">System Info</div>
                        <div class="space-y-2">
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Database</div>
                                <div class="text-sm font-bold text-gray-900">ranj7473_brealv10</div>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Schedules</div>
                                <div class="text-sm font-bold text-gray-900">S1-S9, SX</div>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Entries</div>
                                <div class="text-sm font-bold text-gray-900">40 per schedule, 30 min interval</div>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Type</div>
                                <div class="text-sm font-bold text-gray-900">Type B (BuyStop/SellStop)</div>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Framework</div>
                                <div class="text-sm font-bold text-gray-900">PHP + MySQL</div>
                            </div>
                        </div>
                    </div>
                </div>
            </details>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white/60 backdrop-blur-sm border-t border-gray-200/50 mt-8">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="text-center text-sm text-gray-600">
                <p>© 2025 Trading Dashboard v10 - Multi Schedule (S1-S9, SX)</p>
            </div>
        </div>
    </footer>

    <script>
        const TOKEN = 'abc321Xyz';
        const ACCOUNT = '<?= addslashes($DEFAULT_ACCOUNT) ?>';
        const CONTROL_API = 'control.php';

        window.addEventListener('DOMContentLoaded', () => {
            loadEAStatus();
            updateLastRefreshTime();
        });

        async function loadEAStatus() {
            try {
                // Tambahkan timestamp untuk cache busting
                const timestamp = new Date().getTime();
                let url = `${CONTROL_API}?token=${TOKEN}&account=${ACCOUNT}&action=get&format=standard&_t=${timestamp}`;
                let response = await fetch(url, {
                    cache: 'no-store',
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache',
                        'Expires': '0'
                    }
                });
                let data = await response.json();
                
                if(data.status !== 'success') {
                    throw new Error(data.message || 'Unknown error');
                }

                // Update EA Status dengan animasi
                if(data.ea_status) {
                    const status = data.ea_status === 'ON' ? 'ON' : 'OFF';
                    const color = data.ea_status === 'ON' ? 'text-green-600' : 'text-red-600';
                    const bgColor = data.ea_status === 'ON' ? 'bg-green-100' : 'bg-red-100';
                    const statusEl = document.getElementById('eaStatus');
                    if (statusEl) {
                        // Fade animation
                        statusEl.style.opacity = '0.5';
                        setTimeout(() => {
                            statusEl.innerHTML = `<span class="${color} ${bgColor} px-3 py-1 rounded-lg font-bold transition-all">${status}</span>`;
                            statusEl.style.opacity = '1';
                        }, 100);
                    }
                } else {
                    document.getElementById('eaStatus').innerHTML = '<span class="text-gray-500">-</span>';
                }
                
                // Update Schedule Status dengan animasi
                const schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX'];
                schedules.forEach(sched => {
                    const key = sched === 'SX' ? 'schedule_sx' : 'schedule_s' + sched.substring(1);
                    const elId = 'schedule' + sched + 'Status';
                    const el = document.getElementById(elId);
                    if (!el) return;
                    if (data[key]) {
                        const sStatus = data[key] === 'ON' ? 'ON' : 'OFF';
                        const sColor = data[key] === 'ON' ? 'text-green-600' : 'text-red-600';
                        // Fade animation
                        el.style.opacity = '0.5';
                        setTimeout(() => {
                            el.innerHTML = `<span class="${sColor} font-bold transition-all">${sStatus}</span>`;
                            el.style.opacity = '1';
                        }, 100);
                    } else {
                        el.innerHTML = '<span class="text-gray-500">-</span>';
                    }
                });

                // Update last refresh time
                updateLastRefreshTime();

            } catch(error) {
                console.error('Error loading EA status v10:', error);
                document.getElementById('eaStatus').innerHTML = '<span class="text-red-500 text-sm">Error</span>';
                const schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX'];
                schedules.forEach(sched => {
                    const el = document.getElementById('schedule' + sched + 'Status');
                    if (el) el.innerHTML = '<span class="text-red-500 text-sm">Error</span>';
                });
            }
        }

        // Real-time polling setiap 1 detik untuk update lebih cepat
        setInterval(() => {
            loadEAStatus();
        }, 1000);

        // Auto-refresh ketika window focus (user kembali ke tab)
        let isPageVisible = true;
        document.addEventListener('visibilitychange', () => {
            isPageVisible = !document.hidden;
            if (isPageVisible) {
                // Langsung refresh ketika user kembali ke tab
                loadEAStatus();
            }
        });

        // Auto-refresh ketika window focus
        window.addEventListener('focus', () => {
            loadEAStatus();
        });

        // Listen untuk update dari control_dashboard.php via localStorage
        let lastUpdateCheck = null;
        setInterval(() => {
            const updateTime = localStorage.getItem('ea_status_updated');
            if (updateTime && updateTime !== lastUpdateCheck) {
                lastUpdateCheck = updateTime;
                // Langsung refresh ketika ada update dari control dashboard
                loadEAStatus();
                console.log('EA status updated detected, refreshing...');
            }
        }, 500); // Check setiap 500ms untuk real-time

        // Refresh indicator
        let lastUpdateTime = null;
        function updateLastRefreshTime() {
            lastUpdateTime = new Date();
            const timeEl = document.getElementById('lastRefreshTime');
            if (timeEl) {
                timeEl.textContent = lastUpdateTime.toLocaleTimeString('id-ID');
            }
        }
    </script>
</body>
</html>
