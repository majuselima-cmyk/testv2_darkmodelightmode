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

$account    = isset($_GET['account']) ? $_GET['account'] : $DEFAULT_ACCOUNT;
$filterDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$minStreak  = 1;
$maxStreak  = 15;

$dateStartUTC = $filterDate . ' 00:00:00';
$dateEndUTC   = $filterDate . ' 23:59:59';

$sql = "
    SELECT 
        id,
        account_number,
        profit,
        comment,
        position_time
    FROM trading_positions
    WHERE account_number = ?
      AND position_time >= ?
      AND position_time <= ?
    ORDER BY position_time ASC, id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$account, $dateStartUTC, $dateEndUTC]);
$positions = $stmt->fetchAll();

function escapeHtml($text) {
    if ($text === null || $text === '') return '';
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
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

// Kelompokkan posisi per schedule (S1–S9, SX)
$bySchedule = [];
foreach (['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'] as $s) {
    $bySchedule[$s] = [];
}

foreach ($positions as $pos) {
    $schedule = getScheduleV10($pos['comment'] ?? '');
    if (!isset($bySchedule[$schedule])) {
        continue;
    }
    $bySchedule[$schedule][] = $pos;
}

// Hitung streak loss per schedule, hanya dari posisi dengan profit < 0
// Satu blok berurutan loss dengan panjang L dihitung sebagai:
// - kalau 1 <= L <= 15: masuk ke bin L (exact length)
// - kalau L > 15: dimasukkan ke bin 15 (artinya 15 atau lebih)
$streakStats = [];
foreach (['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'] as $s) {
    $streakStats[$s] = [
        'total_trades' => count($bySchedule[$s]),
        'streak_counts' => []
    ];
    for ($n = $minStreak; $n <= $maxStreak; $n++) {
        $streakStats[$s]['streak_counts'][$n] = 0;
    }

    $currentLossStreak = 0;
    foreach ($bySchedule[$s] as $pos) {
        $profit = (float)($pos['profit'] ?? 0);
        if ($profit < 0) {
            $currentLossStreak++;
        } else {
            if ($currentLossStreak >= $minStreak) {
                $len = $currentLossStreak;
                if ($len > $maxStreak) {
                    $len = $maxStreak;
                }
                if (!isset($streakStats[$s]['streak_counts'][$len])) {
                    $streakStats[$s]['streak_counts'][$len] = 0;
                }
                $streakStats[$s]['streak_counts'][$len]++;
            }
            $currentLossStreak = 0;
        }
    }

    // Tutup streak di akhir data
    if ($currentLossStreak >= $minStreak) {
        $len = $currentLossStreak;
        if ($len > $maxStreak) {
            $len = $maxStreak;
        }
        if (!isset($streakStats[$s]['streak_counts'][$len])) {
            $streakStats[$s]['streak_counts'][$len] = 0;
        }
        $streakStats[$s]['streak_counts'][$len]++;
    }
}

// Hitung total streak per panjang (semua schedule)
$totalStreakPerLength = [];
for ($n = $minStreak; $n <= $maxStreak; $n++) {
    $totalStreakPerLength[$n] = 0;
}
foreach (['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'] as $s) {
    for ($n = $minStreak; $n <= $maxStreak; $n++) {
        $totalStreakPerLength[$n] += $streakStats[$s]['streak_counts'][$n] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Streak Loss v10 - Account <?php echo escapeHtml($account); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Header -->
        <header class="mb-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-gray-200/50 shadow-lg p-3 sm:p-5 mb-4 sm:mb-6">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-2 sm:gap-4">
                        <a href="dashboard.php" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-3 sm:px-4 py-2 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 transition-all shadow-sm">
                            <i class="fas fa-arrow-left text-xs"></i> Back
                        </a>
                        <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-md">
                                <i class="fas fa-chart-bar text-white text-xs sm:text-sm"></i>
                            </div>
                            <h1 class="text-lg sm:text-2xl font-bold text-black truncate">
                                Streak Report
                            </h1>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
                        <p class="text-xs sm:text-sm text-gray-600">
                            Analisis Streak Loss v10 (S1 - SX) - Account: <span class="font-mono font-semibold"><?php echo escapeHtml($account); ?></span> | Tanggal (UTC): <span class="font-semibold"><?php echo escapeHtml($filterDate); ?></span>
                        </p>
                        <div class="bg-white border border-gray-200 rounded-lg px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-600 shadow-sm">
                            <div class="font-semibold text-gray-800 mb-1">Info Waktu</div>
                            <div>Hari UTC: <span class="font-mono"><?php echo escapeHtml($filterDate); ?></span></div>
                            <div>Rentang: 00:00:00 - 23:59:59 UTC</div>
                        </div>
                    </div>
                    <p class="text-[11px] sm:text-xs text-gray-500 max-w-2xl">
                        Streak dihitung per schedule berdasarkan <span class="font-semibold">trade berurutan yang rugi (profit &lt; 0)</span>.
                        Satu blok streak dengan panjang L akan dihitung sebagai: 
                        <span class="font-mono">L</span> kalau 1 &le; L &le; 15,
                        dan dimasukkan ke <span class="font-mono">15</span> kalau L &gt; 15.
                    </p>
                </div>
            </div>
        </header>

        <!-- Filter -->
        <section class="mb-5 bg-white border border-gray-200 rounded-lg shadow-sm p-4">
            <form method="GET" action="" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Account</label>
                    <input 
                        type="text" 
                        name="account" 
                        value="<?php echo escapeHtml($account); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
                    >
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal (UTC)</label>
                    <input 
                        type="date" 
                        name="date" 
                        value="<?php echo escapeHtml($filterDate); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold"
                    >
                        Refresh
                    </button>
                    <a href="?account=<?php echo urlencode($DEFAULT_ACCOUNT); ?>&date=<?php echo date('Y-m-d'); ?>"
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-semibold">
                        Hari Ini (Default)
                    </a>
                </div>
            </form>
        </section>

        <!-- Ringkasan atas -->
        <section class="mb-5 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                <div class="text-xs text-gray-600 mb-1">Total Posisi (hari ini)</div>
                <div class="text-xl font-bold text-gray-900">
                    <?php echo count($positions); ?>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                <div class="text-xs text-gray-600 mb-1">Schedule Aktif (ada posisi)</div>
                <div class="text-xl font-bold text-gray-900">
                    <?php
                    $activeSchedules = 0;
                    foreach (['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'] as $s) {
                        if ($streakStats[$s]['total_trades'] > 0) $activeSchedules++;
                    }
                    echo $activeSchedules;
                    ?>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                <div class="text-xs text-gray-600 mb-1">Total Blok Streak ≥ 1</div>
                <div class="text-xl font-bold text-gray-900">
                    <?php
                    $totalBlocks = 0;
                    foreach ($totalStreakPerLength as $n => $cnt) {
                        $totalBlocks += $cnt;
                    }
                    echo $totalBlocks;
                    ?>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                <div class="text-xs text-gray-600 mb-1">Rentang Streak</div>
                <div class="text-sm font-semibold text-gray-900">
                    <?php echo $minStreak; ?> &ndash; <?php echo $maxStreak; ?> loss berurutan
                </div>
            </div>
        </section>

        <!-- Tabel utama: per schedule -->
        <section class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-sm md:text-base font-semibold text-gray-900">
                    Tabel Streak Loss per Schedule (Panjang 1 &ndash; 15, exact length)
                </h2>
                <p class="text-[11px] text-gray-500 mt-1">
                    Contoh: nilai di kolom 1 artinya &ldquo;berapa kali muncul blok 1 loss berurutan&rdquo;,
                    kolom 2 artinya blok 2 loss berurutan, dan seterusnya sampai kolom 15.
                    Streak lebih dari 15 loss akan dijumlahkan di kolom 15.
                </p>
            </div>
            <table class="min-w-full text-xs md:text-sm">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Schedule</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Total Posisi</th>
                        <?php for ($n = $minStreak; $n <= $maxStreak; $n++): ?>
                            <th class="px-2 py-2 text-center font-semibold text-gray-700">
                                <?php echo $n === $maxStreak ? $n . '+' : $n; ?>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach (['S1','S2','S3','S4','S5','S6','S7','S8','S9','SX'] as $s): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-semibold text-gray-900">
                                <?php echo $s; ?>
                            </td>
                            <td class="px-3 py-2 text-gray-800">
                                <?php echo $streakStats[$s]['total_trades']; ?>
                            </td>
                            <?php for ($n = $minStreak; $n <= $maxStreak; $n++): ?>
                                <?php
                                $val = $streakStats[$s]['streak_counts'][$n] ?? 0;
                                $cellClass = $val > 0 ? 'bg-red-50 text-red-800 font-semibold' : 'text-gray-400';
                                ?>
                                <td class="px-2 py-2 text-center <?php echo $cellClass; ?>">
                                    <?php echo $val; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Ringkasan total semua schedule -->
        <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 mb-4">
            <h2 class="text-sm md:text-base font-semibold text-gray-900 mb-2">
                Ringkasan Total Semua Schedule
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-<?php echo ($maxStreak - $minStreak + 1) > 6 ? 6 : ($maxStreak - $minStreak + 1); ?> gap-2">
                <?php for ($n = $minStreak; $n <= $maxStreak; $n++): ?>
                    <?php
                    $val = $totalStreakPerLength[$n] ?? 0;
                    $hasVal = $val > 0;
                    ?>
                    <div class="border rounded-lg px-3 py-2 <?php echo $hasVal ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'; ?>">
                        <div class="text-[11px] text-gray-600 mb-0.5">
                            Streak <?php echo $n === $maxStreak ? $n . '+' : $n; ?> loss
                        </div>
                        <div class="text-base font-bold <?php echo $hasVal ? 'text-red-700' : 'text-gray-500'; ?>">
                            <?php echo $val; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

        <!-- Catatan -->
        <section class="text-[11px] text-gray-500 mt-2">
            <p>
                Analisis ini hanya melihat <span class="font-semibold">loss berurutan</span> berdasarkan urutan
                <span class="font-mono">position_time</span> pada hari dan account yang dipilih. 
                Jika ingin pola lain (misalnya gabung beberapa hari atau per minggu), bisa dibuat halaman laporan tambahan.
            </p>
        </section>
    </div>
</body>
</html>


