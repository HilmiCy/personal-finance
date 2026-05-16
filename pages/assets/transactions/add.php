<?php
require_once '../../../config/config.php';
require_once '../../../config/session.php';
require_once '../../../includes/functions.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/AssetTransaction.php';

if (!isLoggedIn()) {
    header('Location: ../../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assetTransaction = new AssetTransaction();
    $db = Database::getInstance()->getConnection();
    
    // Clean number inputs
    $quantity = cleanNumber($_POST['quantity']);
    $price_per_unit = cleanNumber($_POST['price_per_unit']);
    $total_price = $quantity * $price_per_unit;
    $type = $_POST['type'];
    
    // VALIDASI UNTUK TRANSAKSI JUAL
    // Hitung profit/loss dengan metode FIFO
    $stmt = $db->prepare("
        SELECT * FROM asset_transactions 
        WHERE asset_id = ? AND user_id = ? AND type = 'buy'
        ORDER BY transaction_date ASC, id ASC
    ");
    $stmt->execute([$_POST['asset_id'], $_SESSION['user_id']]);
    $buys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // DEBUG: Simpan detail pembelian untuk dilihat
    $debug_buys = [];
    $remaining_to_sell = $quantity;
    $total_cost = 0;

    foreach ($buys as $buy) {
        if ($remaining_to_sell <= 0) break;
        
        $sell_qty = min($buy['quantity'], $remaining_to_sell);
        $cost_for_this = $sell_qty * $buy['price_per_unit'];
        $total_cost += $cost_for_this;
        
        // Simpan detail untuk debugging
        $debug_buys[] = [
            'buy_quantity' => $buy['quantity'],
            'buy_price' => $buy['price_per_unit'],
            'sell_qty' => $sell_qty,
            'cost' => $cost_for_this
        ];
        
        $remaining_to_sell -= $sell_qty;
    }

    $revenue = $quantity * $price_per_unit;
    $profit_loss = $revenue - $total_cost;
    $avg_cost = ($quantity > 0) ? $total_cost / $quantity : 0;
    $profit_percent = ($total_cost > 0) ? ($profit_loss / $total_cost) * 100 : 0;

    // Simpan info detail untuk ditampilkan
    $_SESSION['last_sale'] = [
        'quantity' => $quantity,
        'avg_cost' => $avg_cost,
        'sell_price' => $price_per_unit,
        'revenue' => $revenue,
        'total_cost' => $total_cost,
        'profit_loss' => $profit_loss,
        'profit_percent' => $profit_percent,
        'debug_buys' => $debug_buys  // Tambahkan ini untuk debugging
    ];
    
    $data = [
        'asset_id' => $_POST['asset_id'],
        'user_id' => $_SESSION['user_id'],
        'type' => $type,
        'quantity' => $quantity,
        'price_per_unit' => $price_per_unit,
        'total_price' => $total_price,
        'transaction_date' => $_POST['transaction_date']
    ];
    
    if ($assetTransaction->create($data)) {
    if ($type == 'sell' && isset($_SESSION['last_sale'])) {
        $sale = $_SESSION['last_sale'];
        $profitLossFormatted = ($sale['profit_loss'] >= 0 ? '🟢 PROFIT' : '🔴 LOSS');
        
        $_SESSION['success'] = "✅ Transaksi penjualan berhasil!\n\n";
        $_SESSION['success'] .= "📊 DETAIL TRANSAKSI:\n";
        $_SESSION['success'] .= "• Jumlah Jual: " . number_format($sale['quantity'], 4) . " unit\n";
        $_SESSION['success'] .= "• Harga Jual: " . formatRupiah($sale['sell_price']) . " / unit\n";
        $_SESSION['success'] .= "• Total Hasil Jual: " . formatRupiah($sale['revenue']) . "\n\n";
        
        $_SESSION['success'] .= "📈 DETAIL PEMBELIAN (FIFO):\n";
        foreach ($sale['debug_buys'] as $index => $buy) {
            $_SESSION['success'] .= "• Batch " . ($index + 1) . ": " . number_format($buy['sell_qty'], 4) . " unit @ " . formatRupiah($buy['buy_price']) . " = " . formatRupiah($buy['cost']) . "\n";
        }
        
        $_SESSION['success'] .= "\n💰 TOTAL MODAL: " . formatRupiah($sale['total_cost']) . "\n";
        $_SESSION['success'] .= "📊 " . $profitLossFormatted . ": " . formatRupiah(abs($sale['profit_loss'])) . " (" . number_format(abs($sale['profit_percent']), 2) . "%)\n";
        
        unset($_SESSION['last_sale']);
    } else {
        $_SESSION['success'] = '✅ Transaksi pembelian berhasil ditambahkan!';
    }
}
}

header('Location: ../index.php');
exit;
?>