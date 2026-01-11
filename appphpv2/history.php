<?php
include_once __DIR__ . '/account_config.php';
include_once __DIR__ . '/db_config.php';
// api_endpoint.php (v10)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Token');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Validasi token
$valid_token = "abc321Xyz";
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
$token_header = isset($_SERVER['HTTP_TOKEN']) ? $_SERVER['HTTP_TOKEN'] : '';

// Cek token dari GET, POST, atau header
if (empty($token) && !empty($token_header)) {
    $token = $token_header;
}

if ($token !== $valid_token) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

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
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

function logSync($pdo, $account, $sync_type, $count, $status, $error = null, $ip = null) {
    $stmt = $pdo->prepare("
        INSERT INTO sync_logs 
        (account_number, sync_type, positions_count, status, error_message, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$account, $sync_type, $count, $status, $error, $ip]);
}

try {
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $account = isset($_GET['account']) ? $_GET['account'] : $DEFAULT_ACCOUNT;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        
        // Token sudah divalidasi di atas
        
        if (empty($account)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Account parameter is required',
                'usage' => 'GET history.php?token=YOUR_TOKEN&account=YOUR_ACCOUNT_NUMBER&limit=100'
            ]);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                id, account_number, ticket, symbol, position_type, 
                volume, price, profit, position_time, comment, 
                sync_time, created_at
            FROM trading_positions 
            WHERE account_number = ?
            ORDER BY id DESC, created_at DESC, ticket DESC
            LIMIT ?
        ");
        $stmt->execute([$account, $limit]);
        $positions = $stmt->fetchAll();
        
        $stmt_total = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM trading_positions 
            WHERE account_number = ?
        ");
        $stmt_total->execute([$account]);
        $total = $stmt_total->fetch();
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'account_number' => $account,
            'total' => (int)$total['total'],
            'returned' => count($positions),
            'positions' => $positions
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
        logSync($pdo, 'unknown', 'sync', 0, 'failed', 'Invalid JSON', $ip_address);
        exit;
    }
    
    // Validasi token dari JSON body (jika ada)
    if (isset($input['token']) && $input['token'] !== $valid_token) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
        logSync($pdo, $input['account'] ?? 'unknown', 'sync', 0, 'failed', 'Invalid token', $ip_address);
        exit;
    }
    
    if (!isset($input['account']) || !isset($input['positions']) || !is_array($input['positions'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        $account = $input['account'] ?? 'unknown';
        logSync($pdo, $account, 'sync', 0, 'failed', 'Missing required fields', $ip_address);
        exit;
    }
    
    $account_number = $input['account'];
    $positions = $input['positions'];
    $success_count = 0;
    $error_count = 0;
    
    $stmt = $pdo->prepare("
        INSERT INTO trading_positions 
        (account_number, ticket, symbol, position_type, volume, price, profit, position_time, comment) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        symbol = VALUES(symbol),
        position_type = VALUES(position_type),
        volume = VALUES(volume),
        price = VALUES(price),
        profit = VALUES(profit),
        position_time = VALUES(position_time),
        comment = VALUES(comment),
        sync_time = CURRENT_TIMESTAMP
    ");
    
    foreach ($positions as $position) {
        try {
            $required_fields = ['ticket', 'symbol', 'type', 'volume', 'price', 'time'];
            foreach ($required_fields as $field) {
                if (!isset($position[$field])) {
                    throw new Exception("Missing field: $field");
                }
            }
            
            $position_time = date('Y-m-d H:i:s', strtotime($position['time']));
            if (!$position_time) {
                $position_time = date('Y-m-d H:i:s');
            }
            
            $stmt->execute([
                $account_number,
                $position['ticket'],
                $position['symbol'],
                $position['type'],
                $position['volume'],
                $position['price'],
                $position['profit'] ?? 0.00,
                $position_time,
                $position['comment'] ?? ''
            ]);
            
            $success_count++;
            
        } catch (Exception $e) {
            $error_count++;
            error_log("Position sync error v10: " . $e->getMessage());
        }
    }
    
    $total_count = count($positions);
    $sync_type = $total_count > 50 ? 'bulk' : 'normal';
    logSync($pdo, $account_number, $sync_type, $total_count, 'success', null, $ip_address);
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Positions synced successfully',
        'data' => [
            'total_received' => $total_count,
            'successful' => $success_count,
            'failed' => $error_count
        ]
    ]);
    
} catch (Exception $e) {
    $account = $input['account'] ?? 'unknown';
    logSync($pdo, $account, 'sync', 0, 'failed', $e->getMessage(), $ip_address ?? 'unknown');
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}
?>


