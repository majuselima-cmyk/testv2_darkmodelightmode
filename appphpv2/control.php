<?php
include_once __DIR__ . '/account_config.php';
include_once __DIR__ . '/db_config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');
// Prevent caching untuk memastikan data selalu terbaru
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Konfigurasi
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
$account = isset($_GET['account']) ? $_GET['account'] : (isset($_POST['account']) ? $_POST['account'] : $DEFAULT_ACCOUNT);
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'get');
$schedule = isset($_GET['schedule']) ? strtoupper($_GET['schedule']) : (isset($_POST['schedule']) ? strtoupper($_POST['schedule']) : '');
$format = isset($_GET['format']) ? $_GET['format'] : 'standard';

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

// Get IP address untuk tracking
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Fungsi untuk mendapatkan status EA (dengan per-schedule)
function getEAStatus($pdo, $account) {
    $stmt = $pdo->prepare("
        SELECT account_number, status, 
               schedule_s1, schedule_s2, schedule_s3, schedule_s4, schedule_s5, 
               schedule_s6, schedule_s7, schedule_s8, schedule_s9, schedule_sx,
               updated_at, updated_by
        FROM ea_control 
        WHERE account_number = ?
    ");
    $stmt->execute([$account]);
    $result = $stmt->fetch();
    
    if(!$result) {
        // Jika belum ada, buat default dengan status ON untuk semua (S1-S9, SX)
        $stmt = $pdo->prepare("
            INSERT INTO ea_control (
                account_number, status, 
                schedule_s1, schedule_s2, schedule_s3, schedule_s4, schedule_s5,
                schedule_s6, schedule_s7, schedule_s8, schedule_s9, schedule_sx,
                updated_by
            ) 
            VALUES (?, 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', ?)
        ");
        $stmt->execute([$account, 'system']);
        
        return [
            'account_number' => $account,
            'status' => 'ON',
            'schedule_s1' => 'ON',
            'schedule_s2' => 'ON',
            'schedule_s3' => 'ON',
            'schedule_s4' => 'ON',
            'schedule_s5' => 'ON',
            'schedule_s6' => 'ON',
            'schedule_s7' => 'ON',
            'schedule_s8' => 'ON',
            'schedule_s9' => 'ON',
            'schedule_sx' => 'ON',
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => 'system'
        ];
    }
    
    // Backward compatibility: jika kolom belum ada, set default ON
    $schedules = ['schedule_s1', 'schedule_s2', 'schedule_s3', 'schedule_s4', 'schedule_s5',
                  'schedule_s6', 'schedule_s7', 'schedule_s8', 'schedule_s9', 'schedule_sx'];
    foreach($schedules as $key) {
        if(!isset($result[$key])) {
            $result[$key] = 'ON';
        }
    }
    
    return $result;
}

// Fungsi untuk set status EA global
function setEAStatus($pdo, $account, $status, $updated_by) {
    if($status !== 'ON' && $status !== 'OFF') {
        return false;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO ea_control (account_number, status, updated_by) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
        status = VALUES(status),
        updated_by = VALUES(updated_by),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    return $stmt->execute([$account, $status, $updated_by]);
}

// Fungsi untuk set status per schedule
function setScheduleStatus($pdo, $account, $schedule, $status, $updated_by) {
    // Validasi schedule: S1-S9 atau SX
    if(!preg_match('/^S([1-9]|X)$/i', $schedule)) {
        return false;
    }
    if($status !== 'ON' && $status !== 'OFF') {
        return false;
    }
    
    // Convert schedule ke nama kolom database
    $column = 'schedule_' . strtolower($schedule);
    
    $stmt = $pdo->prepare("
        INSERT INTO ea_control (account_number, {$column}, updated_by) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
        {$column} = VALUES({$column}),
        updated_by = VALUES(updated_by),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    return $stmt->execute([$account, $status, $updated_by]);
}

// Fungsi untuk set semua schedule sekaligus
function setAllSchedulesStatus($pdo, $account, $status, $updated_by) {
    if($status !== 'ON' && $status !== 'OFF') {
        return false;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO ea_control (
            account_number, 
            schedule_s1, schedule_s2, schedule_s3, schedule_s4, schedule_s5,
            schedule_s6, schedule_s7, schedule_s8, schedule_s9, schedule_sx,
            updated_by
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        schedule_s1 = VALUES(schedule_s1),
        schedule_s2 = VALUES(schedule_s2),
        schedule_s3 = VALUES(schedule_s3),
        schedule_s4 = VALUES(schedule_s4),
        schedule_s5 = VALUES(schedule_s5),
        schedule_s6 = VALUES(schedule_s6),
        schedule_s7 = VALUES(schedule_s7),
        schedule_s8 = VALUES(schedule_s8),
        schedule_s9 = VALUES(schedule_s9),
        schedule_sx = VALUES(schedule_sx),
        updated_by = VALUES(updated_by),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    return $stmt->execute([
        $account, 
        $status, $status, $status, $status, $status,
        $status, $status, $status, $status, $status,
        $updated_by
    ]);
}

// Main processing
try {
    if($action === 'get') {
        $status = getEAStatus($pdo, $account);
        
        $response_data = [
            'status' => 'success',
            'account_number' => $status['account_number'],
            'ea_status' => $status['status'],
            'schedule_s1' => $status['schedule_s1'] ?? 'ON',
            'schedule_s2' => $status['schedule_s2'] ?? 'ON',
            'schedule_s3' => $status['schedule_s3'] ?? 'ON',
            'schedule_s4' => $status['schedule_s4'] ?? 'ON',
            'schedule_s5' => $status['schedule_s5'] ?? 'ON',
            'schedule_s6' => $status['schedule_s6'] ?? 'ON',
            'schedule_s7' => $status['schedule_s7'] ?? 'ON',
            'schedule_s8' => $status['schedule_s8'] ?? 'ON',
            'schedule_s9' => $status['schedule_s9'] ?? 'ON',
            'schedule_sx' => $status['schedule_sx'] ?? 'ON',
            'updated_at' => $status['updated_at'],
            'updated_by' => $status['updated_by'] ?? 'system'
        ];
        
    } else if($action === 'set' || $action === 'on' || $action === 'off') {
        $new_status = 'ON';
        
        if($action === 'off') {
            $new_status = 'OFF';
        } else if($action === 'set') {
            $new_status = isset($_GET['status']) ? strtoupper($_GET['status']) : (isset($_POST['status']) ? strtoupper($_POST['status']) : 'ON');
            if($new_status !== 'ON' && $new_status !== 'OFF') {
                throw new Exception('Invalid status. Must be ON or OFF');
            }
        }
        
        if(setEAStatus($pdo, $account, $new_status, $ip_address)) {
            $status = getEAStatus($pdo, $account);
            
            $response_data = [
                'status' => 'success',
                'message' => 'EA global status updated successfully',
                'account_number' => $status['account_number'],
                'ea_status' => $status['status'],
                'schedule_s1' => $status['schedule_s1'] ?? 'ON',
                'schedule_s2' => $status['schedule_s2'] ?? 'ON',
                'schedule_s3' => $status['schedule_s3'] ?? 'ON',
                'schedule_s4' => $status['schedule_s4'] ?? 'ON',
                'schedule_s5' => $status['schedule_s5'] ?? 'ON',
                'schedule_s6' => $status['schedule_s6'] ?? 'ON',
                'schedule_s7' => $status['schedule_s7'] ?? 'ON',
                'schedule_s8' => $status['schedule_s8'] ?? 'ON',
                'schedule_s9' => $status['schedule_s9'] ?? 'ON',
                'schedule_sx' => $status['schedule_sx'] ?? 'ON',
                'updated_at' => $status['updated_at'],
                'updated_by' => $status['updated_by'] ?? 'system'
            ];
        } else {
            throw new Exception('Failed to update EA status');
        }
        
    } else if(
        // Handle S1-S9 dan SX (on/off)
        preg_match('/^s([1-9]|x)_(on|off)$/i', $action)
    ) {
        $schedule_action = strtoupper($action);
        $schedule_id = '';
        $new_status = '';
        
        if(preg_match('/^S([1-9]|X)_(ON|OFF)$/i', $schedule_action, $m)) {
            $schedule_id = 'S' . strtoupper($m[1]);
            $new_status = strtoupper($m[2]);
        }
        
        if($schedule_id === '' || $new_status === '') {
            throw new Exception('Invalid schedule action');
        }
        
        if(setScheduleStatus($pdo, $account, $schedule_id, $new_status, $ip_address)) {
            $status = getEAStatus($pdo, $account);
            
            $response_data = [
                'status' => 'success',
                'message' => "Schedule {$schedule_id} status updated successfully to {$new_status}",
                'account_number' => $status['account_number'],
                'ea_status' => $status['status'],
                'schedule_s1' => $status['schedule_s1'] ?? 'ON',
                'schedule_s2' => $status['schedule_s2'] ?? 'ON',
                'schedule_s3' => $status['schedule_s3'] ?? 'ON',
                'schedule_s4' => $status['schedule_s4'] ?? 'ON',
                'schedule_s5' => $status['schedule_s5'] ?? 'ON',
                'schedule_s6' => $status['schedule_s6'] ?? 'ON',
                'schedule_s7' => $status['schedule_s7'] ?? 'ON',
                'schedule_s8' => $status['schedule_s8'] ?? 'ON',
                'schedule_s9' => $status['schedule_s9'] ?? 'ON',
                'schedule_sx' => $status['schedule_sx'] ?? 'ON',
                'updated_at' => $status['updated_at'],
                'updated_by' => $status['updated_by'] ?? 'system'
            ];
        } else {
            throw new Exception('Failed to update schedule status');
        }
        
    } else if($action === 'all_on' || $action === 'all_off') {
        $new_status = ($action === 'all_on') ? 'ON' : 'OFF';
        
        if(setAllSchedulesStatus($pdo, $account, $new_status, $ip_address)) {
            $status = getEAStatus($pdo, $account);
            
            $response_data = [
                'status' => 'success',
                'message' => "All schedules status updated successfully to {$new_status}",
                'account_number' => $status['account_number'],
                'ea_status' => $status['status'],
                'schedule_s1' => $status['schedule_s1'] ?? 'ON',
                'schedule_s2' => $status['schedule_s2'] ?? 'ON',
                'schedule_s3' => $status['schedule_s3'] ?? 'ON',
                'schedule_s4' => $status['schedule_s4'] ?? 'ON',
                'schedule_s5' => $status['schedule_s5'] ?? 'ON',
                'schedule_s6' => $status['schedule_s6'] ?? 'ON',
                'schedule_s7' => $status['schedule_s7'] ?? 'ON',
                'schedule_s8' => $status['schedule_s8'] ?? 'ON',
                'schedule_s9' => $status['schedule_s9'] ?? 'ON',
                'schedule_sx' => $status['schedule_sx'] ?? 'ON',
                'updated_at' => $status['updated_at'],
                'updated_by' => $status['updated_by'] ?? 'system'
            ];
        } else {
            throw new Exception('Failed to update all schedules status');
        }
        
    } else {
        throw new Exception('Invalid action. Use: get, set, on, off, s1_on..s9_on, sx_on, s1_off..s9_off, sx_off, all_on, all_off');
    }
    
    if($format === 'standard') {
        header('Content-Type: application/json');
        echo json_encode($response_data, JSON_PRETTY_PRINT);
    } else {
        $json = json_encode($response_data, JSON_PRETTY_PRINT);
        $json = str_replace(
            ['"status": "', '"ea_status": "', '"account_number": "', '"message": "'],
            ['status: ', 'ea_status: ', 'account_number: ', 'message: '],
            $json
        );
        $json = preg_replace('/"([^"]*)"/', '$1', $json);
        echo $json;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

?>