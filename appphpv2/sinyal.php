<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Set timezone ke UTC
date_default_timezone_set('UTC');

// Konfigurasi
$account_number = isset($_GET['account']) ? $_GET['account'] : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

// ============================================
// KONFIGURASI MULTI SCHEDULE TYPE B (2 MENIT) - V10
// ============================================
// VARIABLE KHUSUS - BISA DIUBAH SESUAI KEBUTUHAN
$jarak_menit = 30;        // Jarak menit antara sinyal dalam satu schedule (30 menit)
$jumlah_entry = 40;       // Jumlah total entry per schedule

// DEFINE SCHEDULE (V10)
// S1  = 01:00:00
// S2  = 01:03:00 (selisih 3 menit)
// S3  = 01:06:00 (selisih 3 menit)
// S4  = 01:09:00 (selisih 3 menit)
// S5  = 01:12:00 (selisih 3 menit)
// S6  = 01:15:00 (selisih 3 menit)
// S7  = 01:18:00 (selisih 3 menit)
// S8  = 01:21:00 (selisih 3 menit)
// S9  = 01:24:00 (selisih 3 menit)
// SX = 01:27:00 (selisih 3 menit)
$schedules = [
    ['schedule_id' => 'S1',  'schedule_time' => '01:00:00'],
    ['schedule_id' => 'S2',  'schedule_time' => '01:03:00'],
    ['schedule_id' => 'S3',  'schedule_time' => '01:06:00'],
    ['schedule_id' => 'S4',  'schedule_time' => '01:09:00'],
    ['schedule_id' => 'S5',  'schedule_time' => '01:12:00'],
    ['schedule_id' => 'S6',  'schedule_time' => '01:15:00'],
    ['schedule_id' => 'S7',  'schedule_time' => '01:18:00'],
    ['schedule_id' => 'S8',  'schedule_time' => '01:21:00'],
    ['schedule_id' => 'S9',  'schedule_time' => '01:24:00'],
    ['schedule_id' => 'SX', 'schedule_time' => '01:27:00'],
];

// Validasi token
$valid_token = "abc321Xyz";
if($token !== $valid_token) {
    echo json_encode(["error" => "Invalid token"]);
    exit;
}

// Tanggal hari ini (UTC)
$tanggal_hari_ini = date('Y.m.d');

// ============================================
// GENERATE SINYAL TYPE B UNTUK SEMUA SCHEDULE
// ============================================
$all_entries = [];

// Loop untuk setiap schedule
foreach($schedules as $schedule) {
    $schedule_id = $schedule['schedule_id'];
    $schedule_time = $schedule['schedule_time'];
    
    // Parse jam mulai dari schedule
    $jam_mulai_parts = explode(':', $schedule_time);
    $start_hour = (int)$jam_mulai_parts[0];
    $start_minute = (int)$jam_mulai_parts[1];
    $start_second = isset($jam_mulai_parts[2]) ? (int)$jam_mulai_parts[2] : 0;
    
    // Initialize untuk schedule ini
    $current_hour = $start_hour;
    $current_minute = $start_minute;
    $counter = 1;
    
    // Generate 40 entries untuk schedule ini
    for($i = 0; $i < $jumlah_entry; $i++) {
        
        // Parameter FIX untuk Type B (BuyStop/SellStop)
        $triggerbuy = 10000;    // Jarak BUY STOP dari current price (DIATAS)
        $triggersell = 10000;   // Jarak SELL STOP dari current price (DIBAWAH)
        $slbuy = 20000;         // SL untuk BUY STOP position (dibawah entry)
        $tpbuy = 40000;         // TP untuk BUY STOP position (diatas entry)
        $slsell = 20000;        // SL untuk SELL STOP position (diatas entry)
        $tpsell = 40000;        // TP untuk SELL STOP position (dibawah entry)
        
        // Format waktu
        $time_formatted = sprintf("%02d:%02d", $current_hour, $current_minute);
        
        // Generate sinyal Type B dengan schedule identification
        $all_entries[] = [
            'schedule_id' => $schedule_id,
            'schedule_time' => $schedule_time,
            'nomor' => $counter,
            'tanggal' => $tanggal_hari_ini,  // Gunakan tanggal UTC
            'time' => $time_formatted,
            'entry' => 'B',  // TYPE B: BuyStop/SellStop
            'slbuy' => $slbuy,
            'tpbuy' => $tpbuy,
            'slsell' => $slsell,
            'tpsell' => $tpsell,
            'triggerbuy' => $triggerbuy,
            'triggersell' => $triggersell,
        ];
        
        // Tambah waktu berdasarkan jarak menit (30 menit antar entry dalam schedule)
        $total_minutes = ($current_hour * 60) + $current_minute + $jarak_menit;
        $current_hour = floor($total_minutes / 60) % 24;
        $current_minute = $total_minutes % 60;
        
        $counter++;
        
        // Jika sudah melewati jam 23, stop (opsional)
        if($current_hour > 23) {
            break;
        }
    }
}

// Generate JSON
$json = json_encode($all_entries, JSON_PRETTY_PRINT);

// Remove quotes from specific fields
$json = str_replace(
    ['"schedule_id": "', '"schedule_time": "', '"nomor": ', '"tanggal": "', '"time": "', '"slbuy": ', '"tpbuy": ', '"slsell": ', '"tpsell": ', '"entry": "', '"triggerbuy": ', '"triggersell": '],
    ['schedule_id: ', 'schedule_time: ', 'nomor: ', 'tanggal: ', 'time: ', 'slbuy: ', 'tpbuy: ', 'slsell: ', 'tpsell: ', 'entry: ', 'triggerbuy: ', 'triggersell: '],
    $json
);

// Remove remaining quotes except around object boundaries
$json = str_replace(['"'], [''], $json);

echo $json;

// Log akses (opsional)
/*
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'account' => $account_number,
    'schedules' => count($schedules),
    'entries_per_schedule' => $jumlah_entry,
    'jarak_menit' => $jarak_menit,
    'entry_type' => 'B',
    'total_signals' => count($all_entries),
    'timezone' => 'UTC',
    'ip' => $_SERVER['REMOTE_ADDR']
];
//file_put_contents('signal_v10_access.log', json_encode($log_data) . PHP_EOL, FILE_APPEND | LOCK_EX);
*/

?>


