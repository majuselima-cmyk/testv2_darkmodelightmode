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
$filterDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filterSchedule = isset($_GET['schedule']) ? $_GET['schedule'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;

$dateStartUTC = $filterDate . ' 00:00:00';
$dateEndUTC = $filterDate . ' 23:59:59';

$whereConditions = [
    'account_number = ?',
    'position_time >= ?',
    'position_time <= ?'
];
$params = [$account, $dateStartUTC, $dateEndUTC];

if (!empty($filterSchedule) && in_array($filterSchedule, ['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'])) {
    $whereConditions[] = 'comment LIKE ?';
    $params[] = '%_' . $filterSchedule . '_%';
}

$whereClause = implode(' AND ', $whereConditions);

$sql_count = "SELECT COUNT(*) as total FROM trading_positions WHERE " . $whereClause;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total = $stmt_count->fetch();
$totalCount = (int)$total['total'];

$totalPages = max(1, ceil($totalCount / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT 
        id, account_number, ticket, symbol, position_type, 
        volume, price, profit, position_time, comment, 
        sync_time, created_at
    FROM trading_positions 
    WHERE " . $whereClause . "
    ORDER BY id DESC, created_at DESC, ticket DESC
    LIMIT ? OFFSET ?
";

$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$positions = $stmt->fetchAll();

$stmt_total_all = $pdo->prepare("SELECT COUNT(*) as total FROM trading_positions WHERE account_number = ?");
$stmt_total_all->execute([$account]);
$totalAll = $stmt_total_all->fetch();

$totalProfit = 0;
$buyCount = 0;
$sellCount = 0;

foreach ($positions as $pos) {
    $totalProfit += (float)($pos['profit'] ?? 0);
    $positionType = strtoupper($pos['position_type'] ?? '');
    if ($positionType === 'BUY') {
        $buyCount++;
    } elseif ($positionType === 'SELL') {
        $sellCount++;
    }
}

function escapeHtml($text) {
    if ($text === null || $text === '') return '';
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function formatCurrency($value) {
    $num = (float)$value;
    return $num >= 0 ? '+' . number_format($num, 2) : number_format($num, 2);
}

function formatDateLocalWIB($dateString) {
    if (empty($dateString)) return '-';
    try {
        $date = new DateTime($dateString, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $date->format('d/m/Y H:i:s') . ' WIB';
    } catch (Exception $e) {
        return $dateString;
    }
}

function getScheduleV10($comment) {
    if (empty($comment)) return '-';
    if (strpos($comment, '_SX_') !== false) return 'SX';
    if (strpos($comment, '_S1_')  !== false) return 'S1';
    if (strpos($comment, '_S2_')  !== false) return 'S2';
    if (strpos($comment, '_S3_')  !== false) return 'S3';
    if (strpos($comment, '_S4_')  !== false) return 'S4';
    if (strpos($comment, '_S5_')  !== false) return 'S5';
    if (strpos($comment, '_S6_')  !== false) return 'S6';
    if (strpos($comment, '_S7_')  !== false) return 'S7';
    if (strpos($comment, '_S8_')  !== false) return 'S8';
    if (strpos($comment, '_S9_')  !== false) return 'S9';
    return '-';
}

$returned = count($positions);
$totalCountAll = (int)$totalAll['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading History Report v10 - Account <?php echo escapeHtml($account); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">
        <!-- Header -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-5 mb-6 no-print">
            <div class="flex items-center justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <a href="dashboard.php" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-sm">
                            <i class="fas fa-arrow-left text-xs"></i> Back
                        </a>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md">
                                <i class="fas fa-history text-white text-sm"></i>
                            </div>
                            History Report
                        </h1>
                    </div>
                    <p class="text-sm text-gray-600">Trading History Report v10</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl px-4 py-3 border border-blue-200 shadow-sm">
                        <div class="text-xs text-gray-600 mb-1 font-semibold">Clock</div>
                        <div class="text-sm font-bold text-gray-900">
                            <span id="timeUTC" class="text-blue-600">--:--:--</span> <span class="text-gray-500 text-xs">UTC</span>
                        </div>
                        <div class="text-sm font-bold text-gray-900">
                            <span id="timeWIB" class="text-green-600">--:--:--</span> <span class="text-gray-500 text-xs">WIB</span>
                        </div>
                    </div>
                    <button onclick="window.print()" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md">
                        <i class="fas fa-print text-xs"></i> Print
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Date Filter -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-5 mb-6 no-print">
            <form method="GET" action="" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="account" value="<?php echo escapeHtml($account); ?>">
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Tanggal (UTC)</label>
                    <input type="date" name="date" value="<?php echo escapeHtml($filterDate); ?>" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Schedule</label>
                    <select name="schedule" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        <option value="">Semua</option>
                        <option value="S1" <?php echo $filterSchedule === 'S1' ? 'selected' : ''; ?>>S1</option>
                        <option value="S2" <?php echo $filterSchedule === 'S2' ? 'selected' : ''; ?>>S2</option>
                        <option value="S3" <?php echo $filterSchedule === 'S3' ? 'selected' : ''; ?>>S3</option>
                        <option value="S4" <?php echo $filterSchedule === 'S4' ? 'selected' : ''; ?>>S4</option>
                        <option value="S5" <?php echo $filterSchedule === 'S5' ? 'selected' : ''; ?>>S5</option>
                        <option value="S6" <?php echo $filterSchedule === 'S6' ? 'selected' : ''; ?>>S6</option>
                        <option value="S7" <?php echo $filterSchedule === 'S7' ? 'selected' : ''; ?>>S7</option>
                        <option value="S8" <?php echo $filterSchedule === 'S8' ? 'selected' : ''; ?>>S8</option>
                        <option value="S9" <?php echo $filterSchedule === 'S9' ? 'selected' : ''; ?>>S9</option>
                        <option value="SX" <?php echo $filterSchedule === 'SX' ? 'selected' : ''; ?>>SX</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-4 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md">
                        <i class="fas fa-filter text-xs"></i> Filter
                    </button>
                    <a href="?account=<?php echo escapeHtml($account); ?>&date=<?php echo date('Y-m-d'); ?>" 
                       class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 px-4 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-sm">
                        <i class="fas fa-calendar-day text-xs"></i> Hari Ini
                    </a>
                </div>
            </form>
            <div class="mt-3 text-xs text-gray-600 flex items-center gap-2">
                <i class="fas fa-info-circle text-purple-500"></i>
                Filter UTC | 15 data/halaman
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-4">
                <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">Account</div>
                <div class="text-lg font-bold text-gray-900"><?php echo escapeHtml($account); ?></div>
            </div>
            <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-4">
                <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">Tanggal</div>
                <div class="text-lg font-bold text-blue-600"><?php echo date('d/m/Y', strtotime($filterDate)); ?></div>
                <div class="text-xs text-gray-500 mt-1">UTC</div>
            </div>
            <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-4">
                <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">Positions</div>
                <div class="text-lg font-bold text-blue-600"><?php echo $totalCount; ?></div>
                <div class="text-xs text-gray-500 mt-1">Total: <?php echo $totalCountAll; ?></div>
            </div>
            <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-4">
                <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">Profit/Loss</div>
                <div class="text-lg font-bold <?php echo $totalProfit >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo formatCurrency($totalProfit); ?>
                </div>
            </div>
            <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/50 shadow-sm p-4">
                <div class="text-xs text-gray-600 mb-1 font-semibold uppercase tracking-wider">BUY / SELL</div>
                <div class="text-lg font-bold text-gray-900"><?php echo $buyCount; ?> / <?php echo $sellCount; ?></div>
            </div>
        </div>
        
        <!-- Table -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-base font-bold text-gray-900">
                    Positions: <?php echo $returned; ?> of <?php echo $totalCount; ?> 
                    (<?php echo date('d/m/Y', strtotime($filterDate)); ?> UTC)
                    <?php if (!empty($filterSchedule)): ?>
                        | Schedule: <?php echo escapeHtml($filterSchedule); ?>
                    <?php endif; ?>
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Symbol</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Volume</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Price</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Profit</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Schedule</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Comment</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($positions) > 0): ?>
                            <?php foreach ($positions as $pos): ?>
                                <?php
                                $profit = (float)($pos['profit'] ?? 0);
                                $profitClass = $profit >= 0 ? 'text-green-600' : 'text-red-600';
                                $schedule = getScheduleV10($pos['comment'] ?? '');
                                if ($schedule === 'S1') {
                                    $scheduleClass = 'bg-blue-100 text-blue-800';
                                } elseif ($schedule === 'S2') {
                                    $scheduleClass = 'bg-green-100 text-green-800';
                                } elseif ($schedule === 'S3') {
                                    $scheduleClass = 'bg-purple-100 text-purple-800';
                                } elseif ($schedule === 'S4') {
                                    $scheduleClass = 'bg-yellow-100 text-yellow-800';
                                } elseif ($schedule === 'S5') {
                                    $scheduleClass = 'bg-pink-100 text-pink-800';
                                } elseif ($schedule === 'S6') {
                                    $scheduleClass = 'bg-indigo-100 text-indigo-800';
                                } elseif ($schedule === 'S7') {
                                    $scheduleClass = 'bg-teal-100 text-teal-800';
                                } elseif ($schedule === 'S8') {
                                    $scheduleClass = 'bg-orange-100 text-orange-800';
                                } elseif ($schedule === 'S9') {
                                    $scheduleClass = 'bg-sky-100 text-sky-800';
                                } elseif ($schedule === 'SX') {
                                    $scheduleClass = 'bg-lime-100 text-lime-800';
                                } else {
                                    $scheduleClass = 'bg-gray-100 text-gray-800';
                                }
                                $positionType = strtoupper($pos['position_type'] ?? '');
                                $typeClass = $positionType === 'BUY' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                $price = (float)($pos['price'] ?? 0);
                                ?>
                                <tr class="border-b hover:bg-gradient-to-r hover:from-gray-50 hover:to-white transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium"><?php echo escapeHtml($pos['ticket'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold"><?php echo escapeHtml($pos['symbol'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded-lg text-xs font-semibold <?php echo $typeClass; ?>">
                                            <?php echo escapeHtml($positionType ?: '-'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php echo escapeHtml($pos['volume'] ?? '0.00'); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo number_format($price, 5); ?></td>
                                    <td class="px-4 py-3 text-sm font-bold <?php echo $profitClass; ?>">
                                        <?php echo formatCurrency($pos['profit'] ?? 0); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded-lg text-xs font-semibold <?php echo $scheduleClass; ?>">
                                            <?php echo escapeHtml($schedule); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 truncate max-w-[200px]"><?php echo escapeHtml($pos['comment'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo formatDateLocalWIB($pos['position_time'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-2">
                                        <i class="fas fa-inbox text-gray-400"></i>
                                    </div>
                                    <p class="text-sm font-medium">No trading history found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-5 py-4 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-white no-print">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="text-sm text-gray-600 font-medium">
                        Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?> 
                        (Total: <?php echo $totalCount; ?>)
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <?php
                        $queryParams = http_build_query([
                            'account' => $account,
                            'date' => $filterDate,
                            'schedule' => $filterSchedule
                        ]);
                        
                        if ($page > 1):
                            $prevPage = $page - 1;
                        ?>
                            <a href="?<?php echo $queryParams; ?>&page=<?php echo $prevPage; ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-xl text-sm hover:bg-gray-50 transition-all shadow-sm">
                                <i class="fas fa-chevron-left text-xs mr-1"></i>Prev
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-xl text-sm text-gray-400 cursor-not-allowed">
                                <i class="fas fa-chevron-left text-xs mr-1"></i>Prev
                            </span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1):
                        ?>
                            <a href="?<?php echo $queryParams; ?>&page=1" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-xl text-sm hover:bg-gray-50 transition-all shadow-sm">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="px-2 py-2 text-gray-400 text-sm">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-3 py-2 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-md"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo $queryParams; ?>&page=<?php echo $i; ?>" 
                                   class="px-3 py-2 bg-white border border-gray-300 rounded-xl text-sm hover:bg-gray-50 transition-all shadow-sm"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="px-2 py-2 text-gray-400 text-sm">...</span>
                            <?php endif; ?>
                            <a href="?<?php echo $queryParams; ?>&page=<?php echo $totalPages; ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-xl text-sm hover:bg-gray-50 transition-all shadow-sm"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo $queryParams; ?>&page=<?php echo $page + 1; ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-xl text-sm hover:bg-gray-50 transition-all shadow-sm">
                                Next<i class="fas fa-chevron-right text-xs ml-1"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-xl text-sm text-gray-400 cursor-not-allowed">
                                Next<i class="fas fa-chevron-right text-xs ml-1"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="mt-6 text-center text-sm text-gray-500 no-print">
            <p class="text-xs">Report generated on <?php echo date('d/m/Y H:i:s'); ?> UTC</p>
            <p class="mt-1 text-xs">
                Account: <?php echo escapeHtml($account); ?> | 
                Tanggal: <?php echo date('d/m/Y', strtotime($filterDate)); ?> UTC | 
                Showing <?php echo $returned; ?> of <?php echo $totalCount; ?> positions
                <?php if (!empty($filterSchedule)): ?>
                    | Schedule: <?php echo escapeHtml($filterSchedule); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <script>
        function updateClock() {
            const now = new Date();
            const utcTime = now.toISOString().substr(11, 8);
            document.getElementById('timeUTC').textContent = utcTime;
            const wibTime = new Date(now.getTime() + (7 * 60 * 60 * 1000));
            const wibTimeStr = wibTime.toISOString().substr(11, 8);
            document.getElementById('timeWIB').textContent = wibTimeStr;
        }
        
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
