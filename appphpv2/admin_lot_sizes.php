<?php
session_start();
include_once __DIR__ . '/db_config.php';

$admin_password = "admin123";
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $is_logged_in = true;
    } else {
        $error = "Password salah!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_lot_sizes.php');
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
    die('Database connection failed');
}

$action = $_GET['action'] ?? '';
$schedule_filter = $_GET['schedule'] ?? 'S1'; // Default filter untuk S1
$valid_schedules = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX'];
if (!in_array($schedule_filter, $valid_schedules)) {
    $schedule_filter = 'S1';
}

$message = '';
$message_type = '';

if ($is_logged_in && $action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule = $_POST['schedule'] ?? 'S1';
    if (!in_array($schedule, $valid_schedules)) {
        $schedule = 'S1';
    }
    $size = (float)$_POST['size'];
    $order_index = (int)$_POST['order_index'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$schedule, $size, $order_index, $is_active]);
        $message = "Lot size berhasil ditambahkan untuk schedule {$schedule}!";
        $message_type = "success";
        // Redirect ke schedule yang sama setelah create
        header("Location: admin_lot_sizes.php?schedule={$schedule}");
        exit;
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

if ($is_logged_in && $action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $schedule = $_POST['schedule'] ?? 'S1';
    if (!in_array($schedule, $valid_schedules)) {
        $schedule = 'S1';
    }
    $size = (float)$_POST['size'];
    $order_index = (int)$_POST['order_index'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE lot_sizes SET schedule = ?, size = ?, order_index = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$schedule, $size, $order_index, $is_active, $id]);
        $message = "Lot size berhasil diperbarui untuk schedule {$schedule}!";
        $message_type = "success";
        // Redirect ke schedule yang sama setelah update
        header("Location: admin_lot_sizes.php?schedule={$schedule}");
        exit;
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

if ($is_logged_in && $action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM lot_sizes WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Lot size berhasil dihapus!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

if ($is_logged_in && $action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT is_active FROM lot_sizes WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        $new_status = $current['is_active'] ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE lot_sizes SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        
        $message = "Status berhasil diubah!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

if ($is_logged_in && $action === 'reset_default') {
    try {
        $target_schedule = $_GET['schedule'] ?? 'S1';
        if (!in_array($target_schedule, $valid_schedules)) {
            $target_schedule = 'S1';
        }
        
        // Hapus hanya untuk schedule tertentu
        $pdo->prepare("DELETE FROM lot_sizes WHERE schedule = ?")->execute([$target_schedule]);
        
        $default_sizes = [
            ['0.03', 1], ['0.06', 2], ['0.10', 3], ['0.15', 4], ['0.24', 5],
            ['0.29', 6], ['0.39', 7], ['0.51', 8], ['0.67', 9], ['0.86', 10],
            ['1.10', 11], ['1.35', 12], ['1.76', 13], ['2.22', 14], ['2.80', 15], ['3.52', 16]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES (?, ?, ?, 1)");
        foreach ($default_sizes as $size_data) {
            $stmt->execute([$target_schedule, $size_data[0], $size_data[1]]);
        }
        
        $message = "Lot sizes berhasil direset ke default untuk schedule {$target_schedule}!";
        $message_type = "success";
        header("Location: admin_lot_sizes.php?schedule={$target_schedule}");
        exit;
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Ambil lot sizes berdasarkan schedule filter
$stmt = $pdo->prepare("SELECT * FROM lot_sizes WHERE schedule = ? ORDER BY order_index ASC");
$stmt->execute([$schedule_filter]);
$lot_sizes = $stmt->fetchAll();

$total_lot_sizes = count($lot_sizes);
$active_lot_sizes = array_filter($lot_sizes, function($item) {
    return $item['is_active'] == 1;
});
$inactive_lot_sizes = array_filter($lot_sizes, function($item) {
    return $item['is_active'] == 0;
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Lot Sizes Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6">
        <!-- Header -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-5 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <div>
                    <div class="flex items-center gap-4 mb-3">
                        <a href="dashboard.php" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-sm">
                            <i class="fas fa-arrow-left text-xs"></i> Back
                        </a>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md">
                                <i class="fas fa-sliders-h text-white text-sm"></i>
                            </div>
                            Lot Sizes Management
                        </h1>
                    </div>
                    <p class="text-sm text-gray-600">Manage lot sizes untuk trading system</p>
                </div>
                <?php if ($is_logged_in): ?>
                <div class="flex items-center gap-3">
                    <div class="text-sm text-gray-600 bg-gradient-to-br from-gray-50 to-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm flex items-center gap-2">
                        <i class="fas fa-user-circle text-purple-600"></i>
                        <span class="font-semibold">Admin</span>
                    </div>
                    <a href="?logout=1" class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-sm flex items-center gap-2">
                        <i class="fas fa-sign-out-alt text-xs"></i> Logout
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-4 gap-3">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                    <div class="text-xs text-blue-600 font-semibold uppercase tracking-wider mb-1">Total</div>
                    <div class="text-xl font-bold text-gray-900"><?php echo $total_lot_sizes; ?></div>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                    <div class="text-xs text-green-600 font-semibold uppercase tracking-wider mb-1">Active</div>
                    <div class="text-xl font-bold text-gray-900"><?php echo count($active_lot_sizes); ?></div>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-3 border border-gray-200">
                    <div class="text-xs text-gray-600 font-semibold uppercase tracking-wider mb-1">Inactive</div>
                    <div class="text-xl font-bold text-gray-900"><?php echo count($inactive_lot_sizes); ?></div>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-3 border border-purple-200">
                    <div class="text-xs text-purple-600 font-semibold uppercase tracking-wider mb-1">Next</div>
                    <div class="text-xl font-bold text-gray-900"><?php echo $total_lot_sizes + 1; ?></div>
                </div>
            </div>
        </div>

        <?php if (!$is_logged_in): ?>
        <!-- Login Form -->
        <div class="max-w-md mx-auto bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 text-center flex items-center justify-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-lock text-white text-sm"></i>
                </div>
                Admin Login
            </h2>
            <?php if (isset($error)): ?>
            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <input type="password" name="password" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all"
                           placeholder="Enter admin password" required>
                </div>
                <button type="submit" name="login" 
                        class="w-full bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-xl text-sm transition-all shadow-md">
                    <i class="fas fa-sign-in-alt text-sm mr-2"></i>Login
                </button>
            </form>
            <div class="mt-4 text-center text-sm text-gray-600">
                <a href="dashboard.php" class="text-purple-600 hover:text-purple-800 font-medium">
                    <i class="fas fa-arrow-left text-xs mr-1"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>

        <?php else: ?>
        <!-- Admin Panel Content -->
        
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-xl <?php echo $message_type === 'success' ? 'bg-gradient-to-br from-green-50 to-green-100 border border-green-200 text-green-700' : 'bg-gradient-to-br from-red-50 to-red-100 border border-red-200 text-red-700'; ?> text-sm flex items-center gap-2 shadow-sm">
            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Add/Edit -->
            <div class="lg:col-span-1">
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-5 sticky top-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br <?php echo isset($_GET['edit_id']) ? 'from-blue-500 to-blue-600' : 'from-green-500 to-green-600'; ?> flex items-center justify-center">
                            <i class="fas <?php echo isset($_GET['edit_id']) ? 'fa-edit' : 'fa-plus-circle'; ?> text-white text-xs"></i>
                        </div>
                        <?php echo isset($_GET['edit_id']) ? 'Edit Lot Size' : 'Tambah Lot Size'; ?>
                    </h2>
                    
                    <?php
                    $edit_data = null;
                    if (isset($_GET['edit_id'])) {
                        $edit_id = (int)$_GET['edit_id'];
                        $stmt = $pdo->prepare("SELECT * FROM lot_sizes WHERE id = ?");
                        $stmt->execute([$edit_id]);
                        $edit_data = $stmt->fetch();
                        if ($edit_data) {
                            $schedule_filter = $edit_data['schedule'] ?? 'S1';
                        }
                    }
                    ?>
                    
                    <form method="POST" action="?schedule=<?php echo $schedule_filter; ?>&action=<?php echo $edit_data ? 'update' : 'create'; ?>">
                        <?php if ($edit_data): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Schedule</label>
                            <select name="schedule" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all"
                                    required>
                                <?php foreach ($valid_schedules as $sched): ?>
                                <option value="<?php echo $sched; ?>" 
                                        <?php echo ($edit_data && $edit_data['schedule'] === $sched) || (!$edit_data && $schedule_filter === $sched) ? 'selected' : ''; ?>>
                                    <?php echo $sched; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Pilih schedule untuk lot size ini</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Lot Size</label>
                            <input type="number" name="size" step="0.01" min="0.01" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all"
                                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['size']) : ''; ?>"
                                   placeholder="0.01, 0.10, 1.00" required>
                            <p class="text-xs text-gray-500 mt-1">Gunakan 2 decimal</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Order Index</label>
                            <input type="number" name="order_index" min="1" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all"
                                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['order_index']) : ($total_lot_sizes + 1); ?>"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Urutan sequence (1, 2, 3...)</p>
                        </div>
                        
                        <div class="mb-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" 
                                       class="mr-2 h-4 w-4 text-purple-600 rounded focus:ring-purple-500"
                                       <?php echo ($edit_data && $edit_data['is_active'] == 1) || !$edit_data ? 'checked' : ''; ?>>
                                <span class="text-gray-700 text-sm font-medium">Aktif (tampil di API)</span>
                            </label>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="submit" 
                                    class="flex-1 bg-gradient-to-br <?php echo $edit_data ? 'from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700' : 'from-green-500 to-green-600 hover:from-green-600 hover:to-green-700'; ?> text-white font-semibold py-3 px-4 rounded-xl text-sm transition-all shadow-md">
                                <i class="fas <?php echo $edit_data ? 'fa-save' : 'fa-plus'; ?> text-xs mr-1"></i>
                                <?php echo $edit_data ? 'Update' : 'Tambah'; ?>
                            </button>
                            
                            <?php if ($edit_data): ?>
                            <a href="admin_lot_sizes.php?schedule=<?php echo $schedule_filter; ?>" 
                               class="bg-gradient-to-br from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-800 font-semibold py-3 px-4 rounded-xl text-sm transition-all shadow-sm flex items-center">
                                <i class="fas fa-times text-xs mr-1"></i>Batal
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <?php if (!$edit_data): ?>
                    <div class="mt-5 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Tips:</h3>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-info-circle text-purple-500 text-xs mt-0.5"></i>
                                <span>Urutan menentukan sequence lot increase</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-info-circle text-purple-500 text-xs mt-0.5"></i>
                                <span>Nonaktifkan lot size untuk sementara disable</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-info-circle text-purple-500 text-xs mt-0.5"></i>
                                <span>API akan membaca hanya yang aktif</span>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Data Table -->
            <div class="lg:col-span-2">
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                                        <i class="fas fa-list text-white text-xs"></i>
                                    </div>
                                    Daftar Lot Sizes
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">Total: <?php echo $total_lot_sizes; ?> entries untuk schedule <?php echo $schedule_filter; ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-semibold text-gray-700">Filter Schedule:</label>
                                <select id="scheduleFilter" onchange="window.location.href='?schedule=' + this.value" 
                                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold bg-white">
                                    <?php foreach ($valid_schedules as $sched): ?>
                                    <option value="<?php echo $sched; ?>" <?php echo $schedule_filter === $sched ? 'selected' : ''; ?>>
                                        <?php echo $sched; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Schedule</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Lot Size</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Created</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($lot_sizes)): ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-2">
                                            <i class="fas fa-inbox text-gray-400"></i>
                                        </div>
                                        <p class="text-sm font-medium">Belum ada data lot sizes untuk schedule <?php echo $schedule_filter; ?></p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($lot_sizes as $item): ?>
                                <tr class="hover:bg-gradient-to-r hover:from-gray-50 hover:to-white transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-600 font-medium">#<?php echo $item['id']; ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-bold rounded <?php 
                                            echo $item['schedule'] == 'S1' ? 'bg-blue-100 text-blue-700' : 
                                                ($item['schedule'] == 'S2' ? 'bg-green-100 text-green-700' : 
                                                ($item['schedule'] == 'SX' ? 'bg-lime-100 text-lime-700' : 'bg-purple-100 text-purple-700')); 
                                        ?>">
                                            <?php echo htmlspecialchars($item['schedule'] ?? 'S1'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-bold text-gray-900"><?php echo number_format($item['size'], 2); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo $item['order_index']; ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $item['is_active'] == 1 ? 'bg-gradient-to-br from-green-500 to-green-600 text-white' : 'bg-gradient-to-br from-gray-400 to-gray-500 text-white'; ?>">
                                            <?php echo $item['is_active'] == 1 ? 'ON' : 'OFF'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <a href="?schedule=<?php echo $schedule_filter; ?>&edit_id=<?php echo $item['id']; ?>" 
                                               class="px-3 py-1.5 bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-xs font-semibold rounded-lg transition-all shadow-sm">
                                                <i class="fas fa-edit text-xs mr-1"></i>Edit
                                            </a>
                                            <a href="?schedule=<?php echo $schedule_filter; ?>&action=toggle&id=<?php echo $item['id']; ?>" 
                                               class="px-3 py-1.5 bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-xs font-semibold rounded-lg transition-all shadow-sm"
                                               onclick="return confirm('Toggle status untuk lot size <?php echo $item['size']; ?>?')">
                                                <i class="fas fa-power-off text-xs mr-1"></i>Tgl
                                            </a>
                                            <a href="?schedule=<?php echo $schedule_filter; ?>&action=delete&id=<?php echo $item['id']; ?>" 
                                               class="px-3 py-1.5 bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white text-xs font-semibold rounded-lg transition-all shadow-sm"
                                               onclick="return confirm('Yakin hapus lot size <?php echo $item['size']; ?>?')">
                                                <i class="fas fa-trash text-xs mr-1"></i>Del
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Table Footer -->
                    <div class="px-5 py-4 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                            <div class="text-sm text-gray-600 flex items-center gap-2">
                                <i class="fas fa-info-circle text-purple-500"></i>
                                <?php echo count($active_lot_sizes); ?> aktif dari <?php echo $total_lot_sizes; ?> total
                            </div>
                            <div class="text-sm text-gray-600 font-semibold">
                                Order: <?php echo $lot_sizes[0]['order_index'] ?? 0; ?> - <?php echo end($lot_sizes)['order_index'] ?? 0; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                <i class="fas fa-bolt text-white text-xs"></i>
                            </div>
                            Quick Actions
                        </h3>
                        <div class="space-y-2">
                            <a href="admin_lot_sizes.php?schedule=<?php echo $schedule_filter; ?>&action=reset_default" 
                               class="block text-center px-4 py-2.5 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 text-blue-700 rounded-xl text-sm font-semibold transition-all border border-blue-200"
                               onclick="return confirm('Reset ke default lot sizes untuk schedule <?php echo $schedule_filter; ?>? Ini akan menghapus semua custom data untuk schedule ini!')">
                                <i class="fas fa-redo text-xs mr-1"></i>Reset to Default (<?php echo $schedule_filter; ?>)
                            </a>
                            <a href="lot.php?token=abc321Xyz&format=standard" target="_blank"
                               class="block text-center px-4 py-2.5 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 text-green-700 rounded-xl text-sm font-semibold transition-all border border-green-200">
                                <i class="fas fa-eye text-xs mr-1"></i>Test API Output
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-lg p-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-code text-white text-xs"></i>
                            </div>
                            API Info
                        </h3>
                        <div class="text-xs text-gray-600 space-y-2">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-link text-blue-500"></i>
                                <span>Endpoint: <code class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">lot.php</code></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-database text-green-500"></i>
                                <span>Table: <code class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">lot_sizes</code></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-filter text-purple-500"></i>
                                <span>Filter: <code class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">is_active = 1</code></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-sort text-orange-500"></i>
                                <span>Order: <code class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">order_index ASC</code></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sizeInput = document.querySelector('input[name="size"]');
            if (sizeInput) {
                sizeInput.focus();
            }
        });
        
        const deleteLinks = document.querySelectorAll('a[href*="action=delete"]');
        deleteLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Yakin ingin menghapus lot size ini?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
