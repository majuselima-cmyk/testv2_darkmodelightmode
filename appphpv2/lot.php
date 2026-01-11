<?php
include_once __DIR__ . '/account_config.php';
include_once __DIR__ . '/db_config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
// Prevent caching untuk memastikan data selalu terbaru
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Konfigurasi
$token = isset($_GET['token']) ? $_GET['token'] : '';
$update = isset($_GET['update']) ? $_GET['update'] : false;
$account = isset($_GET['account']) ? $_GET['account'] : $DEFAULT_ACCOUNT; // Default account
$schedule = isset($_GET['schedule']) ? $_GET['schedule'] : ''; // Schedule ID (S1 - S9, SX)
$format = isset($_GET['format']) ? $_GET['format'] : 'standard'; // 'standard' untuk JSON valid, 'legacy' untuk format lama

// Validasi token
$valid_token = "abc321Xyz";
if($token !== $valid_token) {
    echo json_encode(["error" => "Invalid token"]);
    exit;
}

// Koneksi database
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
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

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
        
        // Convert ke format string dengan 2 decimal
        $lot_sizes = array_map(function($size) {
            return number_format((float)$size, 2, '.', '');
        }, $lot_sizes_db);
        
        // Fallback ke default jika tabel kosong atau error
        if (empty($lot_sizes)) {
            $lot_sizes = ["0.03", "0.06", "0.10", "0.15", "0.24", "0.29", "0.39", "0.51", "0.67", "0.86", "1.10", "1.35", "1.76", "2.22", "2.80", "3.52"];
        }
        return $lot_sizes;
    } catch (PDOException $e) {
        // Fallback ke default jika error
        return ["0.03", "0.06", "0.10", "0.15", "0.24", "0.29", "0.39", "0.51", "0.67", "0.86", "1.10", "1.35", "1.76", "2.22", "2.80", "3.52"];
    }
}

// Validasi schedule
if(empty($schedule) || !in_array($schedule, ['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'])) {
    $schedule = 'S1';
}

// Ambil lot sizes untuk schedule yang dipilih
$lot_sizes = getLotSizesBySchedule($pdo, $schedule);

// Semua data langsung dari database, tidak menggunakan file untuk menyimpan state

// Fungsi untuk memfilter trades berdasarkan schedule yang tepat
function filterTradesBySchedule($trades, $schedule) {
    $filtered = [];
    foreach($trades as $trade) {
        $comment = $trade['comment'] ?? '';
        // Pastikan schedule muncul sebagai pattern yang tepat
        // Pattern: schedule diikuti bukan digit (bisa underscore, spasi, atau end)
        if(preg_match('/[^0-9]' . preg_quote($schedule, '/') . '(?![0-9])|^' . preg_quote($schedule, '/') . '(?![0-9])/', $comment)) {
            $filtered[] = $trade;
        }
    }
    return $filtered;
}

// Fungsi untuk mendapatkan pattern yang tepat per schedule
function getSchedulePatterns($schedule) {
    return [
        '%_' . $schedule . '_%',  // _S1_, _S2_, _SX_, dll
        '%' . $schedule . '_%',   // S1_, S2_, SX_, dll (tanpa underscore di depan)
        '%_' . $schedule . '%',   // _S1, _S2, _SX, dll (tanpa underscore di belakang)
    ];
}

// Fungsi untuk menghitung lot index berdasarkan 15 data terakhir per schedule
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

// Inisialisasi current index per schedule (v10: S1 - S9, SX)
$schedule_indices = [
    'S1' => 0, 'S2' => 0, 'S3' => 0, 'S4' => 0, 'S5' => 0,
    'S6' => 0, 'S7' => 0, 'S8' => 0, 'S9' => 0, 'SX' => 0
];

// Fungsi untuk mendapatkan transaksi terakhir yang sudah closed berdasarkan schedule
function getLastClosedTrade($pdo, $account, $schedule = '') {
    if(!empty($schedule)) {
        $patterns = getSchedulePatterns($schedule);
        
        foreach($patterns as $pattern) {
            $stmt = $pdo->prepare("
                SELECT id, ticket, profit, position_type, volume, price, created_at, comment
                FROM trading_positions 
                WHERE account_number = ? AND comment LIKE ?
                ORDER BY id DESC, created_at DESC, ticket DESC
                LIMIT 10
            ");
            $stmt->execute([$account, $pattern]);
            $found_trades = $stmt->fetchAll();
            if(!empty($found_trades)) {
                // Filter untuk memastikan schedule match tepat
                $filtered_trades = filterTradesBySchedule($found_trades, $schedule);
                if(!empty($filtered_trades)) {
                    return $filtered_trades[0];
                }
            }
        }
        return null;
    } else {
        $stmt = $pdo->prepare("
            SELECT id, ticket, profit, position_type, volume, price, created_at, comment
            FROM trading_positions 
            WHERE account_number = ?
            ORDER BY id DESC, created_at DESC, ticket DESC
            LIMIT 1
        ");
        $stmt->execute([$account]);
        return $stmt->fetch();
    }
}

// Fungsi untuk menghitung loss streak global (opsional, untuk debug)
function getLossStreak($pdo, $account) {
    $stmt = $pdo->prepare("
        SELECT profit
        FROM trading_positions 
        WHERE account_number = ?
        ORDER BY id DESC, created_at DESC, ticket DESC
        LIMIT 10
    ");
    $stmt->execute([$account]);
    $trades = $stmt->fetchAll();
    
    $loss_streak = 0;
    foreach($trades as $trade) {
        if((float)$trade['profit'] < 0) {
            $loss_streak++;
        } else {
            break;
        }
    }
    
    return $loss_streak;
}

// Fungsi untuk mendapatkan lot aktif per schedule
function getActiveLotsPerSchedule($pdo, $account, $schedule_indices) {
    $active_lots = [];
    $schedules = ['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'];
    
    foreach($schedules as $sched) {
        // Ambil lot sizes untuk schedule ini
        $sched_lot_sizes = getLotSizesBySchedule($pdo, $sched);
        $calculated_lot_index = calculateLotIndexFromLast15Trades($pdo, $account, $sched, $sched_lot_sizes);
        $schedule_indices[$sched] = $calculated_lot_index;
        
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
                // Filter untuk memastikan schedule match tepat
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
            
            foreach($trades_reversed as $trade) {
                $profit = (float)$trade['profit'];
                if($profit < 0) {
                    $lot_index = min($lot_index + 1, count($sched_lot_sizes) - 1);
                } else if($profit > 0) {
                    $lot_index = 0;
                }
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

// PENTING: Selalu recalculate semua schedule langsung dari database
// Setiap kali lot.php dipanggil, selalu hitung ulang dari database tanpa menyimpan state di file
$all_schedules = ['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'];
$schedule_indices = [];

// Hitung lot index untuk semua schedule langsung dari database
foreach($all_schedules as $sched) {
    // Ambil lot sizes untuk setiap schedule
    $sched_lot_sizes = getLotSizesBySchedule($pdo, $sched);
    // SELALU hitung ulang lot index dari database (untuk memastikan data terbaru)
    $new_lot_index_check = calculateLotIndexFromLast15Trades($pdo, $account, $sched, $sched_lot_sizes);
    $schedule_indices[$sched] = $new_lot_index_check;
}

$current_index = $schedule_indices[$schedule];

// Ambil data transaksi terakhir untuk schedule ini langsung dari database
$last_trade = getLastClosedTrade($pdo, $account, $schedule);
$last_profit = $last_trade ? (float)$last_trade['profit'] : 0;
$last_ticket = $last_trade ? (int)$last_trade['ticket'] : 0;

// Tidak perlu tracking last processed ticket karena semua data langsung dari database
$has_new_trade = true; // Selalu true karena selalu ambil data terbaru dari database

// Dapatkan lot aktif per schedule (untuk semua response)
$active_lots_per_schedule = getActiveLotsPerSchedule($pdo, $account, $schedule_indices);

// Logika update lot berdasarkan 15 data terakhir (martingale)
// SELALU gunakan data terbaru yang sudah dihitung langsung dari database
$new_lot_index = $schedule_indices[$schedule]; // Sudah dihitung langsung dari database
$old_lot_index = 0; // Tidak perlu menyimpan old index karena semua dari database
$current_index = $new_lot_index;

if($update || $has_new_trade) {
    if($last_trade) {
        $action = 'maintain';
        $message = 'No change';
        if($new_lot_index > $old_lot_index) {
            $action = 'increase';
            $message = 'Lot increased due to loss';
        } else if($new_lot_index < $old_lot_index) {
            $action = 'reset';
            $message = 'Lot reset to initial due to profit';
        } else if($last_profit < 0) {
            $action = 'increase';
            $message = 'Lot increased due to loss (already at max or calculated)';
        } else if($last_profit > 0) {
            $action = 'reset';
            $message = 'Lot reset to initial due to profit';
        }
        
        $active_lots_per_schedule = getActiveLotsPerSchedule($pdo, $account, $schedule_indices);
        
        $response_data = [
            'status' => 'success',
            'schedule' => $schedule,
            'current_index' => $current_index,
            'current_lot' => $lot_sizes[$current_index],
            'message' => $message,
            'last_profit' => $last_profit,
            'action' => $action,
            'old_index' => $old_lot_index,
            'new_index' => $new_lot_index,
            'active_lots' => $active_lots_per_schedule
        ];
    } else {
        // Jika tidak ada last_trade, tetap gunakan data terbaru yang sudah dihitung
        $active_lots_per_schedule = getActiveLotsPerSchedule($pdo, $account, $schedule_indices);
        
        $response_data = [
            'status' => 'success',
            'schedule' => $schedule,
            'current_index' => $current_index,
            'current_lot' => $lot_sizes[$current_index],
            'message' => 'Manual update requested',
            'last_profit' => $last_profit,
            'action' => 'manual',
            'active_lots' => $active_lots_per_schedule
        ];
    }
} else {
    // SELALU gunakan data terbaru yang sudah dihitung, bahkan tanpa parameter update
    $active_lots_per_schedule = getActiveLotsPerSchedule($pdo, $account, $schedule_indices);
    $loss_streak = getLossStreak($pdo, $account);
    
    $stmt_total = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM trading_positions 
        WHERE account_number = ?
    ");
    $stmt_total->execute([$account]);
    $total_data = $stmt_total->fetch();
    
    $stmt_sample = $pdo->prepare("
        SELECT comment 
        FROM trading_positions 
        WHERE account_number = ? AND comment IS NOT NULL AND comment != ''
        ORDER BY id DESC
        LIMIT 5
    ");
    $stmt_sample->execute([$account]);
    $sample_comments = $stmt_sample->fetchAll();
    
    $response_data = [
        'status' => 'success', 
        'schedule' => $schedule,
        'lot_sizes' => $lot_sizes,
        'current_index' => $current_index,
        'current_lot' => $lot_sizes[$current_index],
        'total_lots' => count($lot_sizes),
        'last_profit' => $last_profit,
        'loss_streak' => $loss_streak,
        'last_ticket' => $last_ticket,
        'all_schedules' => $schedule_indices,
        'active_lots' => $active_lots_per_schedule,
        'debug' => [
            'account_number' => $account,
            'total_data_in_db' => $total_data ? (int)$total_data['total'] : 0,
            'sample_comments' => array_column($sample_comments, 'comment')
        ]
    ];
}

// Generate JSON
if($format === 'standard') {
    header('Content-Type: application/json');
    echo json_encode([$response_data], JSON_PRETTY_PRINT);
} else {
    $json = json_encode([$response_data], JSON_PRETTY_PRINT);

    $json = str_replace(
        ['"status": "', '"current_index": ', '"current_lot": "', '"message": "', '"lot_sizes": [', '"total_lots": '],
        ['status: ', 'current_index: ', 'current_lot: ', 'message: ', 'lot_sizes: [', 'total_lots: '],
        $json
    );

    $json = preg_replace('/"([^"]*)"/', '$1', $json);

    echo $json;
}

?>


