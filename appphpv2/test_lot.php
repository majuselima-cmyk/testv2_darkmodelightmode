<?php
/**
 * Unit Test untuk Logika Lot Reset
 * 
 * Test Cases:
 * 1. Setelah profit → reset ke lot awal (index 0)
 * 2. Setelah loss → lot naik satu tingkat
 * 3. Loss berturut-turut → lot tidak boleh sama
 * 4. Kasus 1 dan Kasus 2 dari user
 */

// Mock lot sizes (default)
$lot_sizes = ["0.01", "0.02", "0.03", "0.04", "0.06", "0.10", "0.16", "0.24", "0.40", "0.60", "1.20", "1.40", "2.00", "2.60", "5.00"];

// Fungsi untuk menghitung lot index (simplified version untuk testing)
function calculateLotIndexFromTrades($trades, $lot_sizes) {
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
            // Profit: reset ke index 0
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
            return $lot_index;
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

// Helper function untuk test
function assertEqual($expected, $actual, $testName) {
    if($expected === $actual) {
        echo "✓ PASS: $testName (Expected: $expected, Got: $actual)\n";
        return true;
    } else {
        echo "✗ FAIL: $testName (Expected: $expected, Got: $actual)\n";
        return false;
    }
}

function assertLotSize($expected_lot, $actual_index, $lot_sizes, $testName) {
    $actual_lot = $lot_sizes[$actual_index];
    if(abs((float)$expected_lot - (float)$actual_lot) < 0.001) {
        echo "✓ PASS: $testName (Expected lot: $expected_lot, Got: $actual_lot at index $actual_index)\n";
        return true;
    } else {
        echo "✗ FAIL: $testName (Expected lot: $expected_lot, Got: $actual_lot at index $actual_index)\n";
        return false;
    }
}

echo "========================================\n";
echo "UNIT TEST: Lot Reset Logic\n";
echo "========================================\n\n";

$passed = 0;
$failed = 0;

// ============================================
// TEST 1: Reset setelah profit
// ============================================
echo "TEST 1: Reset setelah profit\n";
echo "----------------------------------------\n";

$trades1 = [
    ['profit' => -20.75, 'volume' => 0.06],
    ['profit' => 24.00, 'volume' => 0.06],  // Profit → harus reset
];
$result1 = calculateLotIndexFromTrades($trades1, $lot_sizes);
if(assertEqual(0, $result1, "Setelah profit, lot_index harus 0")) $passed++; else $failed++;

// ============================================
// TEST 2: Lot naik setelah loss
// ============================================
echo "\nTEST 2: Lot naik setelah loss\n";
echo "----------------------------------------\n";

$trades2 = [
    ['profit' => 24.00, 'volume' => 0.06],   // Profit → reset ke 0
    ['profit' => -6.10, 'volume' => 0.03],   // Loss → harus naik ke index 1
];
$result2 = calculateLotIndexFromTrades($trades2, $lot_sizes);
if(assertEqual(1, $result2, "Setelah loss dari profit, lot_index harus 1")) $passed++; else $failed++;

// ============================================
// TEST 3: Loss berturut-turut - lot tidak boleh sama
// ============================================
echo "\nTEST 3: Loss berturut-turut - lot tidak boleh sama\n";
echo "----------------------------------------\n";

$trades3 = [
    ['profit' => -50.22, 'volume' => 0.24],  // Loss dengan lot 0.24 (index 7)
    ['profit' => -30.00, 'volume' => 0.15],  // Loss berikutnya harus lebih besar dari 0.24
];
$result3 = calculateLotIndexFromTrades($trades3, $lot_sizes);
$expected_lot_index3 = 8; // Harus lebih besar dari index 7 (0.24)
if($result3 > 7) {
    echo "✓ PASS: Loss berturut-turut, lot_index harus lebih besar (Expected: > 7, Got: $result3)\n";
    $passed++;
} else {
    echo "✗ FAIL: Loss berturut-turut, lot_index harus lebih besar (Expected: > 7, Got: $result3)\n";
    $failed++;
}

// ============================================
// TEST 4: Kasus 1 dari user
// ============================================
echo "\nTEST 4: Kasus 1 dari user\n";
echo "----------------------------------------\n";
echo "Data: -20.75, +24.00, -6.10, +11.99\n";

$trades4 = [
    ['profit' => -20.75, 'volume' => 0.06],
    ['profit' => 24.00, 'volume' => 0.06],  // Profit → reset
    ['profit' => -6.10, 'volume' => 0.03],   // Loss → naik
    ['profit' => 11.99, 'volume' => 0.03],   // Profit → reset ke 0
];
$result4 = calculateLotIndexFromTrades($trades4, $lot_sizes);
if(assertEqual(0, $result4, "Kasus 1: Trade terakhir profit, harus reset ke 0")) $passed++; else $failed++;

// ============================================
// TEST 5: Kasus 2 dari user (partial)
// ============================================
echo "\nTEST 5: Kasus 2 dari user (Trade 6-7)\n";
echo "----------------------------------------\n";
echo "Trade 6: -50.22 (lot 0.24), Trade 7: -30.00 (seharusnya lot > 0.24)\n";

$trades5 = [
    ['profit' => 153.92, 'volume' => 0.39],  // Profit → reset
    ['profit' => -50.22, 'volume' => 0.24],  // Loss → index 1 (lot 0.02)
    ['profit' => -30.00, 'volume' => 0.15],  // Loss berikutnya → harus lebih besar
];
$result5 = calculateLotIndexFromTrades($trades5, $lot_sizes);

// Cari index dari lot 0.24
$lot_24_index = -1;
for($i = 0; $i < count($lot_sizes); $i++) {
    if(abs((float)$lot_sizes[$i] - 0.24) < 0.001) {
        $lot_24_index = $i;
        break;
    }
}

// Trade 6 menggunakan lot 0.24 (index 7)
// Trade 7 harus menggunakan lot dengan index minimal 8 (lebih besar dari 7)
if($result5 > $lot_24_index) {
    echo "✓ PASS: Trade 7 lot_index ($result5) lebih besar dari Trade 6 lot_index ($lot_24_index)\n";
    echo "  Trade 6 lot: {$lot_sizes[$lot_24_index]}, Trade 7 lot: {$lot_sizes[$result5]}\n";
    $passed++;
} else {
    echo "✗ FAIL: Trade 7 lot_index ($result5) harus lebih besar dari Trade 6 lot_index ($lot_24_index)\n";
    $failed++;
}

// ============================================
// TEST 6: Multiple losses berturut-turut
// ============================================
echo "\nTEST 6: Multiple losses berturut-turut\n";
echo "----------------------------------------\n";

$trades6 = [
    ['profit' => 11.99, 'volume' => 0.03],    // Profit → reset ke 0
    ['profit' => -50.22, 'volume' => 0.24],  // Loss 1 → index 1
    ['profit' => -30.00, 'volume' => 0.15],  // Loss 2 → index 2
    ['profit' => -31.26, 'volume' => 0.15],  // Loss 3 → index 3
    ['profit' => -20.35, 'volume' => 0.10],  // Loss 4 → index 4
];
$result6 = calculateLotIndexFromTrades($trades6, $lot_sizes);
if(assertEqual(4, $result6, "5 losses berturut-turut, lot_index harus 4")) $passed++; else $failed++;

// ============================================
// TEST 7: Profit setelah multiple losses
// ============================================
echo "\nTEST 7: Profit setelah multiple losses\n";
echo "----------------------------------------\n";

$trades7 = [
    ['profit' => -50.22, 'volume' => 0.24],
    ['profit' => -30.00, 'volume' => 0.15],
    ['profit' => -31.26, 'volume' => 0.15],
    ['profit' => 11.92, 'volume' => 0.03],   // Profit → harus reset ke 0
];
$result7 = calculateLotIndexFromTrades($trades7, $lot_sizes);
if(assertEqual(0, $result7, "Profit setelah losses, harus reset ke 0")) $passed++; else $failed++;

// ============================================
// TEST 8: Loss setelah profit (edge case)
// ============================================
echo "\nTEST 8: Loss setelah profit\n";
echo "----------------------------------------\n";

$trades8 = [
    ['profit' => 24.00, 'volume' => 0.06],   // Profit → reset ke 0
    ['profit' => -21.08, 'volume' => 0.06],  // Loss → harus minimal index 1
];
$result8 = calculateLotIndexFromTrades($trades8, $lot_sizes);
if($result8 >= 1) {
    echo "✓ PASS: Loss setelah profit, lot_index minimal 1 (Got: $result8)\n";
    $passed++;
} else {
    echo "✗ FAIL: Loss setelah profit, lot_index minimal 1 (Got: $result8)\n";
    $failed++;
}

// ============================================
// TEST 9: Full Kasus 2 dari user
// ============================================
echo "\nTEST 9: Full Kasus 2 dari user\n";
echo "----------------------------------------\n";
echo "Trade sequence: -21.08, +24.00, -6.10, +11.99, +153.92, -50.22, -30.00, -31.26, -20.35, -12.64\n";

$trades9 = [
    ['profit' => -21.08, 'volume' => 0.06],
    ['profit' => 24.00, 'volume' => 0.06],   // Profit → reset
    ['profit' => -6.10, 'volume' => 0.03],    // Loss → index 1
    ['profit' => 11.99, 'volume' => 0.03],   // Profit → reset
    ['profit' => 153.92, 'volume' => 0.39],   // Profit → reset
    ['profit' => -50.22, 'volume' => 0.24],  // Loss → index 1
    ['profit' => -30.00, 'volume' => 0.15],  // Loss → harus lebih besar dari 0.24
    ['profit' => -31.26, 'volume' => 0.15],  // Loss → harus lebih besar lagi
    ['profit' => -20.35, 'volume' => 0.10],  // Loss → harus lebih besar lagi
    ['profit' => -12.64, 'volume' => 0.06],  // Loss → harus lebih besar lagi
];

$result9 = calculateLotIndexFromTrades($trades9, $lot_sizes);

// Cari index dari lot 0.24 (trade 6)
$lot_24_index = -1;
for($i = 0; $i < count($lot_sizes); $i++) {
    if(abs((float)$lot_sizes[$i] - 0.24) < 0.001) {
        $lot_24_index = $i;
        break;
    }
}

// Trade 6 menggunakan lot 0.24 (index 7)
// Trade 7 harus menggunakan lot dengan index minimal 8
if($result9 > $lot_24_index) {
    echo "✓ PASS: Trade terakhir lot_index ($result9) lebih besar dari Trade 6 lot_index ($lot_24_index)\n";
    echo "  Trade 6 lot: {$lot_sizes[$lot_24_index]}, Trade terakhir lot: {$lot_sizes[$result9]}\n";
    $passed++;
} else {
    echo "✗ FAIL: Trade terakhir lot_index ($result9) harus lebih besar dari Trade 6 lot_index ($lot_24_index)\n";
    $failed++;
}

// ============================================
// SUMMARY
// ============================================
echo "\n========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "========================================\n";

if($failed === 0) {
    echo "🎉 ALL TESTS PASSED!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED!\n";
    exit(1);
}

