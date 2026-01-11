<?php
include_once __DIR__ . '/account_config.php';
include_once __DIR__ . '/db_config.php';

date_default_timezone_set('UTC');

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed');
}

$account = isset($_GET['account']) ? $_GET['account'] : $DEFAULT_ACCOUNT;
$days = isset($_GET['days']) ? max(7, min(90, (int)$_GET['days'])) : 30; // Default 30 hari, min 7, max 90

// Query untuk mendapatkan pendapatan harian
$sql = "
    SELECT 
        DATE(position_time) as date,
        COUNT(*) as total_trades,
        SUM(profit) as total_profit,
        SUM(CASE WHEN profit > 0 THEN 1 ELSE 0 END) as win_count,
        SUM(CASE WHEN profit < 0 THEN 1 ELSE 0 END) as loss_count,
        SUM(CASE WHEN profit > 0 THEN profit ELSE 0 END) as win_profit,
        SUM(CASE WHEN profit < 0 THEN profit ELSE 0 END) as loss_profit
    FROM trading_positions
    WHERE account_number = ?
      AND position_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(position_time)
    ORDER BY date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$account, $days]);
$dailyData = $stmt->fetchAll();

// Query untuk statistik keseluruhan
$sqlStats = "
    SELECT 
        COUNT(*) as total_trades,
        SUM(profit) as total_profit,
        SUM(CASE WHEN profit > 0 THEN 1 ELSE 0 END) as win_count,
        SUM(CASE WHEN profit < 0 THEN 1 ELSE 0 END) as loss_count,
        AVG(profit) as avg_profit,
        MAX(profit) as max_profit,
        MIN(profit) as min_profit
    FROM trading_positions
    WHERE account_number = ?
      AND position_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
";

$stmtStats = $pdo->prepare($sqlStats);
$stmtStats->execute([$account, $days]);
$stats = $stmtStats->fetch();

// Prepare data untuk chart
$chartLabels = [];
$chartProfit = [];
$chartWinCount = [];
$chartLossCount = [];

foreach ($dailyData as $row) {
    $chartLabels[] = date('d M', strtotime($row['date']));
    $chartProfit[] = (float)$row['total_profit'];
    $chartWinCount[] = (int)$row['win_count'];
    $chartLossCount[] = (int)$row['loss_count'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendapatan Harian - Trading System v10</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .card-modern {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6">
        <!-- Header -->
        <div class="bg-white/80 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-gray-200/50 shadow-lg p-3 sm:p-5 mb-4 sm:mb-6">
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-2 sm:gap-4">
                    <a href="dashboard.php" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-3 sm:px-4 py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-sm whitespace-nowrap">
                        <i class="fas fa-arrow-left text-xs"></i> 
                        <span class="hidden sm:inline">Back</span>
                    </a>
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-md flex-shrink-0">
                            <i class="fas fa-chart-line text-white text-xs sm:text-sm"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h1 class="text-lg sm:text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent truncate">
                                Pendapatan Harian
                            </h1>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
                    <p class="text-xs sm:text-sm text-gray-600">Grafik pendapatan harian dari trading</p>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <select id="daysFilter" onchange="changeDays()" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-xs sm:text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 Hari</option>
                            <option value="14" <?= $days == 14 ? 'selected' : '' ?>>14 Hari</option>
                            <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 Hari</option>
                            <option value="60" <?= $days == 60 ? 'selected' : '' ?>>60 Hari</option>
                            <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 Hari</option>
                        </select>
                        <button onclick="refreshData()" class="bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-md">
                            <i class="fas fa-sync-alt text-xs"></i> Refresh
                        </button>
                        <div class="text-xs sm:text-sm text-gray-600 bg-gradient-to-br from-gray-50 to-white px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl border border-gray-200 shadow-sm whitespace-nowrap">
                            <i class="far fa-clock text-xs"></i> <span id="lastUpdate" class="ml-1 font-semibold">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="card-modern rounded-xl p-4 sm:p-5 border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-xs sm:text-sm font-semibold">Total Profit</span>
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br <?= ($stats['total_profit'] ?? 0) >= 0 ? 'from-green-500 to-green-600' : 'from-red-500 to-red-600' ?> flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-white text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-bold <?= ($stats['total_profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                    <?= number_format($stats['total_profit'] ?? 0, 2) ?>
                </p>
            </div>

            <div class="card-modern rounded-xl p-4 sm:p-5 border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-xs sm:text-sm font-semibold">Total Trades</span>
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i class="fas fa-chart-bar text-white text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-gray-800">
                    <?= number_format($stats['total_trades'] ?? 0) ?>
                </p>
            </div>

            <div class="card-modern rounded-xl p-4 sm:p-5 border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-xs sm:text-sm font-semibold">Win Rate</span>
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i class="fas fa-trophy text-white text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-green-600">
                    <?php 
                    $winRate = ($stats['total_trades'] ?? 0) > 0 
                        ? (($stats['win_count'] ?? 0) / ($stats['total_trades'] ?? 1)) * 100 
                        : 0;
                    echo number_format($winRate, 1) . '%';
                    ?>
                </p>
            </div>

            <div class="card-modern rounded-xl p-4 sm:p-5 border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-xs sm:text-sm font-semibold">Avg Profit</span>
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i class="fas fa-calculator text-white text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-gray-800">
                    <?= number_format($stats['avg_profit'] ?? 0, 2) ?>
                </p>
            </div>
        </div>

        <!-- Chart -->
        <div class="card-modern rounded-xl sm:rounded-2xl border border-gray-200 shadow-lg p-4 sm:p-6 mb-4 sm:mb-6">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-area text-green-600"></i>
                Grafik Pendapatan Harian
            </h2>
            <div class="relative" style="height: 400px;">
                <canvas id="profitChart"></canvas>
            </div>
        </div>

        <!-- Win/Loss Chart -->
        <div class="card-modern rounded-xl sm:rounded-2xl border border-gray-200 shadow-lg p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-pie text-blue-600"></i>
                Win vs Loss per Hari
            </h2>
            <div class="relative" style="height: 300px;">
                <canvas id="winLossChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Profit Chart
        const ctxProfit = document.getElementById('profitChart').getContext('2d');
        const profitChart = new Chart(ctxProfit, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Total Profit',
                    data: <?= json_encode($chartProfit) ?>,
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
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
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
        });

        // Win/Loss Chart
        const ctxWinLoss = document.getElementById('winLossChart').getContext('2d');
        const winLossChart = new Chart(ctxWinLoss, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Win',
                        data: <?= json_encode($chartWinCount) ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    },
                    {
                        label: 'Loss',
                        data: <?= json_encode($chartLossCount) ?>,
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
        });

        function changeDays() {
            const days = document.getElementById('daysFilter').value;
            window.location.href = 'pendapatan.php?account=<?= htmlspecialchars($account) ?>&days=' + days;
        }

        function refreshData() {
            window.location.reload();
        }

        document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('id-ID');
    </script>
</body>
</html>

