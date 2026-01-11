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

// Fungsi untuk mendapatkan lot sizes berdasarkan schedule
function getLotSizesBySchedule($pdo, $schedule) {
    try {
        $stmt_lot_sizes = $pdo->prepare("
            SELECT size 
            FROM lot_sizes 
            WHERE schedule = ? AND is_active = 1 
            ORDER BY order_index ASC
        ");
        $stmt_lot_sizes->execute([$schedule]);
        $lot_sizes_db = $stmt_lot_sizes->fetchAll(PDO::FETCH_COLUMN);
        
        $lot_sizes = array_map(function($size) {
            return number_format((float)$size, 2, '.', '');
        }, $lot_sizes_db);
        
        if (empty($lot_sizes)) {
            $lot_sizes = ["0.03", "0.06", "0.10", "0.15", "0.24", "0.29", "0.39", "0.51", "0.67", "0.86", "1.10", "1.35", "1.76", "2.22", "2.80", "3.52"];
        }
        return $lot_sizes;
    } catch (PDOException $e) {
        return ["0.03", "0.06", "0.10", "0.15", "0.24", "0.29", "0.39", "0.51", "0.67", "0.86", "1.10", "1.35", "1.76", "2.22", "2.80", "3.52"];
    }
}

// Include fungsi-fungsi dari lot.php
function getSchedulePatterns($schedule) {
    return [
        '%_' . $schedule . '_%',
        '%' . $schedule . '_%',
        '%_' . $schedule . '%',
    ];
}

function filterTradesBySchedule($trades, $schedule) {
    $filtered = [];
    foreach($trades as $trade) {
        $comment = $trade['comment'] ?? '';
        if(preg_match('/[^0-9]' . preg_quote($schedule, '/') . '(?![0-9])|^' . preg_quote($schedule, '/') . '(?![0-9])/', $comment)) {
            $filtered[] = $trade;
        }
    }
    return $filtered;
}

function calculateLotIndexFromLast15Trades($pdo, $account, $schedule, $lot_sizes) {
    $patterns = getSchedulePatterns($schedule);
    
    $trades = [];
    foreach($patterns as $pattern) {
        $stmt = $pdo->prepare("
            SELECT id, ticket, profit, comment, created_at, volume
            FROM trading_positions 
            WHERE account_number = ? AND comment LIKE ?
            ORDER BY id DESC, created_at DESC, ticket DESC
            LIMIT 25
        ");
        $stmt->execute([$account, $pattern]);
        $found_trades = $stmt->fetchAll();
        if(!empty($found_trades)) {
            // Filter untuk memastikan schedule match tepat
            $filtered_trades = filterTradesBySchedule($found_trades, $schedule);
            if(!empty($filtered_trades)) {
                $trades = array_slice($filtered_trades, 0, 15);
                break;
            }
        }
    }
    
    if(empty($trades)) {
        return 0;
    }
    
    $lot_index = 0;
    $trades_reversed = array_reverse($trades);
    
    // Hitung lot index dari trade terlama ke terbaru
    foreach($trades_reversed as $trade) {
        $profit = (float)$trade['profit'];
        $volume = isset($trade['volume']) ? (float)$trade['volume'] : null;
        
        if($profit < 0) {
            // Loss: increment lot index
            $lot_index = min($lot_index + 1, count($lot_sizes) - 1);
        } else if($profit > 0) {
            // Profit: reset ke index 0 (PENTING: reset harus terjadi setiap kali ada profit)
            $lot_index = 0;
        }
        // profit = 0 → index tetap
    }
    
    // Validasi final: pastikan konsistensi dengan trade terakhir
    if(!empty($trades)) {
        $last_trade = $trades[0]; // Trade terakhir (paling baru)
        $last_profit = (float)$last_trade['profit'];
        $last_volume = isset($last_trade['volume']) ? (float)$last_trade['volume'] : null;
        
        // PENTING: Jika trade terakhir profit, lot_index HARUS 0 (force reset)
        if($last_profit > 0) {
            $lot_index = 0;
            return $lot_index; // Langsung return, tidak perlu validasi lebih lanjut
        }
        
        // Jika trade terakhir loss, validasi lot_index berdasarkan volume yang digunakan
        if($last_profit < 0 && $last_volume !== null) {
            // Cari index dari volume yang digunakan di trade terakhir
            $used_lot_index = -1;
            for($i = 0; $i < count($lot_sizes); $i++) {
                if(abs((float)$lot_sizes[$i] - $last_volume) < 0.001) {
                    $used_lot_index = $i;
                    break;
                }
            }
            
            // Jika ada trade sebelum ini, cek lot yang digunakan
            if(count($trades) > 1) {
                $prev_trade = $trades[1];
                $prev_profit = (float)$prev_trade['profit'];
                $prev_volume = isset($prev_trade['volume']) ? (float)$prev_trade['volume'] : null;
                
                // Jika trade sebelumnya profit, lot_index harus minimal 1 (karena loss setelah profit)
                if($prev_profit > 0) {
                    $lot_index = max(1, $lot_index);
                }
                // Jika trade sebelumnya loss, WAJIB lot_index lebih besar (tidak boleh sama)
                else if($prev_profit < 0 && $prev_volume !== null) {
                    $prev_lot_index = -1;
                    for($i = 0; $i < count($lot_sizes); $i++) {
                        if(abs((float)$lot_sizes[$i] - $prev_volume) < 0.001) {
                            $prev_lot_index = $i;
                            break;
                        }
                    }
                    
                    // PENTING: Jika trade sebelumnya loss, lot_index HARUS lebih besar dari prev_lot_index
                    // Tidak boleh sama! Setiap loss harus naik satu tingkat
                    if($prev_lot_index >= 0) {
                        // Lot_index harus minimal prev_lot_index + 1 (naik satu tingkat)
                        $min_required_index = min($prev_lot_index + 1, count($lot_sizes) - 1);
                        // Pastikan lot_index tidak sama dengan prev_lot_index
                        if($lot_index <= $prev_lot_index) {
                            $lot_index = $min_required_index;
                        } else {
                            // Jika sudah lebih besar, pastikan minimal sesuai requirement
                            $lot_index = max($lot_index, $min_required_index);
                        }
                    }
                }
            }
        }
    }
    
    return $lot_index;
}

function getActiveLotsPerSchedule($pdo, $account) {
    $active_lots = [];
    $schedules = ['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'];
    
    foreach($schedules as $sched) {
        // Ambil lot sizes untuk schedule ini
        $sched_lot_sizes = getLotSizesBySchedule($pdo, $sched);
        $calculated_lot_index = calculateLotIndexFromLast15Trades($pdo, $account, $sched, $sched_lot_sizes);
        
        $patterns = getSchedulePatterns($sched);
        
        $last_trade = null;
        $last_15_trades = [];
        $used_pattern = '';
        
        foreach($patterns as $pattern) {
            $stmt = $pdo->prepare("
                SELECT id, ticket, profit, comment, created_at, volume
                FROM trading_positions 
                WHERE account_number = ? AND comment LIKE ?
                ORDER BY id DESC, created_at DESC, ticket DESC
                LIMIT 25
            ");
            $stmt->execute([$account, $pattern]);
            $found_trades = $stmt->fetchAll();
            
            if(!empty($found_trades)) {
                $filtered_trades = filterTradesBySchedule($found_trades, $sched);
                if(!empty($filtered_trades)) {
                    $last_trade = $filtered_trades[0];
                    $used_pattern = $pattern;
                    $last_15_trades = array_slice($filtered_trades, 0, 15);
                    break;
                }
            }
        }
        
        if(!empty($last_15_trades)) {
            $lot_index = 0;
            $trades_reversed = array_reverse($last_15_trades);
            
            // Hitung lot index dari trade terlama ke terbaru
            foreach($trades_reversed as $trade) {
                $profit = (float)$trade['profit'];
                if($profit < 0) {
                    // Loss: increment lot index
                    $lot_index = min($lot_index + 1, count($sched_lot_sizes) - 1);
                } else if($profit > 0) {
                    // Profit: reset ke index 0 (PENTING: reset harus terjadi setiap kali ada profit)
                    $lot_index = 0;
                }
                // profit = 0 → index tetap
            }
            
            // Validasi final: pastikan konsistensi dengan trade terakhir
            $last_trade_check = $last_15_trades[0]; // Trade terakhir (paling baru)
            $last_profit_check = (float)$last_trade_check['profit'];
            $last_volume_check = isset($last_trade_check['volume']) ? (float)$last_trade_check['volume'] : null;
            
            // PENTING: Jika trade terakhir profit, lot_index HARUS 0 (force reset)
            if($last_profit_check > 0) {
                $lot_index = 0;
            }
            // Jika trade terakhir loss, validasi lot_index berdasarkan volume yang digunakan
            else if($last_profit_check < 0 && $last_volume_check !== null) {
                // Cari index dari volume yang digunakan di trade terakhir
                $used_lot_index = -1;
                for($i = 0; $i < count($sched_lot_sizes); $i++) {
                    if(abs((float)$sched_lot_sizes[$i] - $last_volume_check) < 0.001) {
                        $used_lot_index = $i;
                        break;
                    }
                }
                
                // Jika ada trade sebelum ini, cek lot yang digunakan
                if(count($last_15_trades) > 1) {
                    $prev_trade_check = $last_15_trades[1];
                    $prev_profit_check = (float)$prev_trade_check['profit'];
                    $prev_volume_check = isset($prev_trade_check['volume']) ? (float)$prev_trade_check['volume'] : null;
                    
                    // Jika trade sebelumnya profit, lot_index harus minimal 1 (karena loss setelah profit)
                    if($prev_profit_check > 0) {
                        $lot_index = max(1, $lot_index);
                    }
                    // Jika trade sebelumnya loss, WAJIB lot_index lebih besar (tidak boleh sama)
                    else if($prev_profit_check < 0 && $prev_volume_check !== null) {
                        $prev_lot_index = -1;
                        for($i = 0; $i < count($sched_lot_sizes); $i++) {
                            if(abs((float)$sched_lot_sizes[$i] - $prev_volume_check) < 0.001) {
                                $prev_lot_index = $i;
                                break;
                            }
                        }
                        
                        // PENTING: Jika trade sebelumnya loss, lot_index HARUS lebih besar dari prev_lot_index
                        // Tidak boleh sama! Setiap loss harus naik satu tingkat
                        if($prev_lot_index >= 0) {
                            // Lot_index harus minimal prev_lot_index + 1 (naik satu tingkat)
                            $min_required_index = min($prev_lot_index + 1, count($sched_lot_sizes) - 1);
                            // Pastikan lot_index tidak sama dengan prev_lot_index
                            if($lot_index <= $prev_lot_index) {
                                $lot_index = $min_required_index;
                            } else {
                                // Jika sudah lebih besar, pastikan minimal sesuai requirement
                                $lot_index = max($lot_index, $min_required_index);
                            }
                        }
                    }
                }
            }
            
            $calculated_lot_index = $lot_index;
        } else {
            $calculated_lot_index = 0;
        }
        
        $last_15_trades_with_lot = [];
        foreach($last_15_trades as $trade) {
            $trade['lot_entry'] = isset($trade['volume']) ? (float)$trade['volume'] : null;
            $last_15_trades_with_lot[] = $trade;
        }
        
        $active_lots[$sched] = [
            'schedule' => $sched,
            'lot_index' => $calculated_lot_index,
            'active_lot' => $sched_lot_sizes[$calculated_lot_index],
            'last_profit' => $last_trade ? $last_trade['profit'] : null,
            'last_ticket' => $last_trade ? $last_trade['ticket'] : null,
            'last_comment' => $last_trade ? $last_trade['comment'] : null,
            'total_trades' => count($last_15_trades),
            'last_15_trades' => $last_15_trades_with_lot,
            'pattern_used' => $used_pattern,
            'account_used' => $account
        ];
    }
    
    return $active_lots;
}

// Get active lots data
$active_lots = getActiveLotsPerSchedule($pdo, $account);

// Get schedule statuses from ea_control table
$schedule_statuses = [];
try {
    $stmt_status = $pdo->prepare("
        SELECT schedule_s1, schedule_s2, schedule_s3, schedule_s4, schedule_s5,
               schedule_s6, schedule_s7, schedule_s8, schedule_s9, schedule_sx
        FROM ea_control 
        WHERE account_number = ?
        LIMIT 1
    ");
    $stmt_status->execute([$account]);
    $status_row = $stmt_status->fetch();
    
    if($status_row) {
        $schedule_statuses = [
            'S1' => strtoupper($status_row['schedule_s1'] ?? 'ON'),
            'S2' => strtoupper($status_row['schedule_s2'] ?? 'ON'),
            'S3' => strtoupper($status_row['schedule_s3'] ?? 'ON'),
            'S4' => strtoupper($status_row['schedule_s4'] ?? 'ON'),
            'S5' => strtoupper($status_row['schedule_s5'] ?? 'ON'),
            'S6' => strtoupper($status_row['schedule_s6'] ?? 'ON'),
            'S7' => strtoupper($status_row['schedule_s7'] ?? 'ON'),
            'S8' => strtoupper($status_row['schedule_s8'] ?? 'ON'),
            'S9' => strtoupper($status_row['schedule_s9'] ?? 'ON'),
            'SX' => strtoupper($status_row['schedule_sx'] ?? 'ON')
        ];
    } else {
        // Default semua ON jika belum ada data
        foreach(['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'] as $sched) {
            $schedule_statuses[$sched] = 'ON';
        }
    }
} catch (PDOException $e) {
    // Default semua ON jika error
    foreach(['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'] as $sched) {
        $schedule_statuses[$sched] = 'ON';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lot Management - Responsive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Custom scrollbar */
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
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6">
        <!-- Header -->
        <div class="bg-white/80 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-gray-200/50 shadow-lg p-3 sm:p-5 mb-4 sm:mb-6">
            <div class="flex flex-col gap-3">
                <!-- Top row: Back button and Title -->
                <div class="flex items-center gap-2 sm:gap-4">
                    <a href="dashboard.php" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-3 sm:px-4 py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-sm whitespace-nowrap">
                        <i class="fas fa-arrow-left text-xs"></i> 
                        <span class="hidden sm:inline">Back</span>
                    </a>
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center shadow-md flex-shrink-0">
                            <i class="fas fa-coins text-white text-xs sm:text-sm"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h1 class="text-lg sm:text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent truncate">
                                Lot Management
                            </h1>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom row: Description and Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
                    <p class="text-xs sm:text-sm text-gray-600">
                        Monitoring lot aktif per schedule (S1 - S9, SX) 
                        <span class="font-semibold text-gray-800">- Account: <?= htmlspecialchars($account) ?></span>
                    </p>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <a href="view-lot.php?account=<?= htmlspecialchars($account) ?>" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-md flex-1 sm:flex-initial justify-center">
                            <i class="fas fa-sync-alt text-xs"></i> Refresh
                        </a>
                        <div class="text-xs sm:text-sm text-gray-600 bg-gradient-to-br from-gray-50 to-white px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl border border-gray-200 shadow-sm whitespace-nowrap">
                            <i class="far fa-clock text-xs"></i> <span class="ml-1 font-semibold"><?= date('H:i:s') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 sm:gap-4">
            <?php
            $scheduleColors = [
                'S1' =>  ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-800', 'icon' => 'fa-calendar-day'],
                'S2' =>  ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'icon' => 'fa-calendar-check'],
                'S3' =>  ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-800', 'icon' => 'fa-calendar-alt'],
                'S4' =>  ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-800', 'icon' => 'fa-calendar-week'],
                'S5' =>  ['bg' => 'bg-pink-50', 'border' => 'border-pink-200', 'text' => 'text-pink-800', 'icon' => 'fa-calendar-plus'],
                'S6' =>  ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'text' => 'text-indigo-800', 'icon' => 'fa-calendar'],
                'S7' =>  ['bg' => 'bg-teal-50', 'border' => 'border-teal-200', 'text' => 'text-teal-800', 'icon' => 'fa-calendar'],
                'S8' =>  ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'text' => 'text-orange-800', 'icon' => 'fa-calendar'],
                'S9' =>  ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'text' => 'text-sky-800', 'icon' => 'fa-calendar'],
                'SX' => ['bg' => 'bg-lime-50', 'border' => 'border-lime-200', 'text' => 'text-lime-800', 'icon' => 'fa-calendar']
            ];
            
            foreach(['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'] as $schedule) {
                $scheduleData = $active_lots[$schedule] ?? [];
                $colors = $scheduleColors[$schedule] ?? $scheduleColors['S1'];
                $lotIndex = $scheduleData['lot_index'] ?? 0;
                $activeLot = $scheduleData['active_lot'] ?? '0.01';
                $totalTrades = $scheduleData['total_trades'] ?? 0;
                $lastProfitRaw = $scheduleData['last_profit'] ?? null;
                $lastProfit = ($lastProfitRaw !== null && $lastProfitRaw !== '') ? (float)$lastProfitRaw : null;
                $lastTicket = $scheduleData['last_ticket'] ?? null;
                $last15Trades = $scheduleData['last_15_trades'] ?? [];
                
                // Get schedule status
                $scheduleStatus = $schedule_statuses[$schedule] ?? 'ON';
                $isOn = ($scheduleStatus === 'ON');
                
                // Ambil lot terakhir
                $lastLotUsed = null;
                if (!empty($last15Trades) && isset($last15Trades[0])) {
                    $lastTrade = $last15Trades[0];
                    $lastLotUsed = isset($lastTrade['lot_entry']) ? $lastTrade['lot_entry'] : (isset($lastTrade['volume']) ? $lastTrade['volume'] : null);
                    if ($lastLotUsed !== null) {
                        $lastLotUsed = number_format((float)$lastLotUsed, 2, '.', '');
                    }
                }
                
                // Hitung streak loss (dari trade terakhir mundur sampai ketemu profit)
                $streakLoss = 0;
                if (!empty($last15Trades)) {
                    foreach($last15Trades as $trade) {
                        $profit = isset($trade['profit']) ? (float)$trade['profit'] : 0;
                        if($profit < 0) {
                            // Loss: increment streak
                            $streakLoss++;
                        } else if($profit > 0) {
                            // Profit: stop counting, streak reset
                            break;
                        }
                        // profit = 0: continue (tidak mempengaruhi streak)
                    }
                }
                
                $profitClass = $lastProfit !== null && !is_nan($lastProfit)
                    ? ($lastProfit < 0 ? 'text-red-600' : ($lastProfit > 0 ? 'text-green-600' : 'text-gray-600'))
                    : 'text-gray-400';
                $profitIcon = $lastProfit !== null && !is_nan($lastProfit)
                    ? ($lastProfit < 0 ? 'fa-arrow-down' : ($lastProfit > 0 ? 'fa-arrow-up' : 'fa-minus'))
                    : 'fa-question';
            ?>
            <div class="space-y-2 sm:space-y-3">
                <div class="card-modern <?= $colors['bg'] ?> <?= $colors['border'] ?> border-2 rounded-lg sm:rounded-xl p-3 sm:p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2 sm:mb-3">
                        <div class="flex items-center gap-2">
                            <div class="<?= $colors['text'] ?> bg-white rounded-full p-1.5 sm:p-2">
                                <i class="fas <?= $colors['icon'] ?> text-xs sm:text-sm"></i>
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-bold <?= $colors['text'] ?>"><?= htmlspecialchars($schedule) ?></h3>
                                <p class="text-xs text-gray-600">Schedule <?= htmlspecialchars($schedule) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <span class="text-xs font-semibold px-2 py-1 rounded <?= $isOn ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $isOn ? 'ON' : 'OFF' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="space-y-2 sm:space-y-3">
                        <div class="bg-white rounded-lg sm:rounded-xl p-2 sm:p-3 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 text-xs sm:text-sm font-semibold">Lot Aktif</span>
                                <span class="text-base sm:text-lg font-bold <?= $colors['text'] ?>"><?= htmlspecialchars($activeLot) ?></span>
                            </div>
                            <div class="mt-1.5 sm:mt-2 space-y-1">
                                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    <span>Index: <?= $lotIndex ?></span>
                                </div>
                                <?php if($lastLotUsed): ?>
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 border-t border-gray-100 pt-1">
                                    <i class="fas fa-history text-xs"></i>
                                    <span>Lot Terakhir: <span class="font-semibold text-gray-700"><?= htmlspecialchars($lastLotUsed) ?></span></span>
                                </div>
                                <?php endif; ?>
                                <?php if($streakLoss > 0): ?>
                                <div class="flex items-center gap-1.5 text-xs text-red-600 border-t border-gray-100 pt-1">
                                    <i class="fas fa-fire text-xs"></i>
                                    <span>Streak Loss: <span class="font-semibold"><?= $streakLoss ?></span></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-white rounded-lg p-2 border border-gray-200">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <i class="fas fa-chart-bar text-gray-400 text-xs"></i>
                                    <span class="text-xs text-gray-600">Total</span>
                                </div>
                                <p class="text-xs sm:text-sm font-semibold text-gray-800"><?= $totalTrades ?></p>
                            </div>
                            <div class="bg-white rounded-lg p-2 border border-gray-200">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <i class="fas <?= $profitIcon ?> <?= $profitClass ?> text-xs"></i>
                                    <span class="text-xs text-gray-600">Last Profit</span>
                                </div>
                                <p class="text-xs sm:text-sm font-semibold <?= $profitClass ?>">
                                    <?= $lastProfit !== null && !is_nan($lastProfit) ? ($lastProfit > 0 ? '+' : '') . number_format($lastProfit, 2) : '-' ?>
                                </p>
                            </div>
                        </div>

                        <?php if($lastTicket): ?>
                            <div class="bg-white rounded-lg p-2 border border-gray-200">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <i class="fas fa-ticket-alt text-gray-400 text-xs"></i>
                                    <span class="text-xs text-gray-600">Last Ticket</span>
                                </div>
                                <p class="text-xs font-mono text-gray-800"><?= htmlspecialchars($lastTicket) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-lg sm:rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                    <div class="bg-gradient-to-r from-gray-50 to-white px-2 sm:px-3 py-1.5 sm:py-2 border-b border-gray-200">
                        <h4 class="text-xs sm:text-sm font-bold <?= $colors['text'] ?> flex items-center gap-1.5 sm:gap-2">
                            <i class="fas fa-list-ol text-xs"></i>
                            15 Data Terbaru
                        </h4>
                    </div>
                    <div class="overflow-x-auto max-h-56 sm:max-h-64 overflow-y-auto custom-scrollbar">
                        <table class="w-full text-xs sm:text-sm">
                            <thead class="sticky top-0 bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr class="border-b border-gray-200">
                                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase tracking-wider">No</th>
                                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase tracking-wider">Ticket</th>
                                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase tracking-wider">Lot</th>
                                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase tracking-wider">Profit</th>
                                    <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-bold text-gray-700 text-xs uppercase tracking-wider">Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($last15Trades)): ?>
                                    <?php foreach($last15Trades as $index => $trade): ?>
                                        <?php
                                        $tradeProfit = isset($trade['profit']) ? (float)$trade['profit'] : 0;
                                        $tradeProfitClass = $tradeProfit < 0 ? 'text-red-600' : ($tradeProfit > 0 ? 'text-green-600' : 'text-gray-600');
                                        $tradeProfitIcon = $tradeProfit < 0 ? 'fa-arrow-down' : ($tradeProfit > 0 ? 'fa-arrow-up' : 'fa-minus');
                                        $lotEntry = isset($trade['lot_entry']) ? $trade['lot_entry'] : (isset($trade['volume']) ? $trade['volume'] : null);
                                        $lotEntryDisplay = $lotEntry !== null ? number_format((float)$lotEntry, 2) : '-';
                                        ?>
                                        <tr class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white transition-colors">
                                            <td class="px-2 sm:px-3 py-2 text-xs sm:text-sm text-gray-600"><?= $index + 1 ?></td>
                                            <td class="px-2 sm:px-3 py-2"><span class="font-mono text-gray-800 text-xs sm:text-sm"><?= htmlspecialchars($trade['ticket'] ?? '-') ?></span></td>
                                            <td class="px-2 sm:px-3 py-2"><span class="font-semibold text-blue-600 text-xs sm:text-sm"><?= htmlspecialchars($lotEntryDisplay) ?></span></td>
                                            <td class="px-2 sm:px-3 py-2">
                                                <div class="flex items-center gap-1">
                                                    <i class="fas <?= $tradeProfitIcon ?> <?= $tradeProfitClass ?> text-xs"></i>
                                                    <span class="font-semibold <?= $tradeProfitClass ?> text-xs sm:text-sm">
                                                        <?= $tradeProfit > 0 ? '+' : '' ?><?= number_format($tradeProfit, 2) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-2 sm:px-3 py-2">
                                                <span class="text-gray-700 truncate block text-xs sm:text-sm max-w-[120px] sm:max-w-none" title="<?= htmlspecialchars($trade['comment'] ?? '-') ?>"><?= htmlspecialchars($trade['comment'] ?? '-') ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 sm:py-8 text-center text-gray-500">
                                            <div class="inline-flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gray-100 mb-2">
                                                <i class="fas fa-inbox text-gray-400 text-sm sm:text-base"></i>
                                            </div>
                                            <p class="text-xs sm:text-sm font-medium">Belum ada data transaksi</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <script>
        // Auto refresh setiap 90 detik
        setTimeout(function() {
            window.location.reload();
        }, 90000);
    </script>
</body>
</html>
