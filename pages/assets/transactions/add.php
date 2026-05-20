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
    // Debug: Log all POST data to a local file
    $log_msg = date('Y-m-d H:i:s') . " - POST DATA: " . json_encode($_POST) . "\n";
    file_put_contents('debug_log.txt', $log_msg, FILE_APPEND);
    
    $assetTransaction = new AssetTransaction();
    $db = Database::getInstance()->getConnection();
    
    // Clean number inputs
    $quantity = cleanNumber($_POST['quantity']);
    $price_per_unit = cleanNumber($_POST['price_per_unit']);
    $total_price = $quantity * $price_per_unit;
    $type = $_POST['type'];
    $asset_id = $_POST['asset_id'];
    $buy_currency = strtolower($_POST['buy_currency'] ?? 'idr');

    // Preparation for USDT deduction (if needed later)
    $usdt_deduction_data = null;
    if ($type == 'buy' && $buy_currency == 'usdt') {
        // Check current USDT balance
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE WHEN type = 'buy' THEN quantity ELSE -quantity END) as balance
            FROM asset_transactions
            WHERE user_id = ? AND asset_id IN (
                SELECT id FROM assets WHERE user_id = ? AND (LOWER(name) LIKE '%tether%' OR UPPER(symbol) = 'USDT')
            )
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $usdt_balance = $stmt->fetchColumn() ?: 0;

        if ($total_price > $usdt_balance) {
            $_SESSION['error'] = "Saldo USDT tidak mencukupi! Anda membutuhkan " . number_format($total_price, 4) . " USDT, tapi hanya memiliki " . number_format($usdt_balance, 4) . " USDT.";
            header('Location: ../index.php');
            exit;
        }

        // Prepare USDT deduction data
        $stmt = $db->prepare("SELECT id FROM assets WHERE user_id = ? AND (LOWER(name) LIKE '%tether%' OR UPPER(symbol) = 'USDT') LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $usdt_asset_id = $stmt->fetchColumn();

        if ($usdt_asset_id) {
            $exchange_rate = cleanNumber($_POST['exchange_rate'] ?? 16000);
            $usdt_deduction_data = [
                'asset_id' => $usdt_asset_id,
                'user_id' => $_SESSION['user_id'],
                'type' => 'sell',
                'quantity' => $total_price,
                'price_per_unit' => $exchange_rate,
                'total_price' => $total_price * $exchange_rate,
                'currency' => 'IDR',
                'exchange_rate' => 1,
                'transaction_date' => $_POST['transaction_date']
            ];
        }
    }
    
    // Logic for SELL (FIFO cost calculation)
    $total_cost = 0;
    $debug_buys = [];
    if ($type == 'sell') {
        // ... (keep existing FIFO logic) ...
        $stmt = $db->prepare("
            SELECT * FROM asset_transactions 
            WHERE asset_id = ? AND user_id = ? AND type = 'buy'
            ORDER BY transaction_date ASC, id ASC
        ");
        $stmt->execute([$asset_id, $_SESSION['user_id']]);
        $buys = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining_to_sell = $quantity;
        foreach ($buys as $buy) {
            if ($remaining_to_sell <= 0) break;
            $sell_qty = min($buy['quantity'], $remaining_to_sell);
            $cost_for_this = $sell_qty * $buy['price_per_unit'];
            $total_cost += $cost_for_this;
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

        $_SESSION['last_sale'] = [
            'quantity' => $quantity,
            'avg_cost' => $avg_cost,
            'sell_price' => $price_per_unit,
            'revenue' => $revenue,
            'total_cost' => $total_cost,
            'profit_loss' => $profit_loss,
            'profit_percent' => $profit_percent,
            'debug_buys' => $debug_buys
        ];
    }
    
    $data = [
        'asset_id' => $asset_id,
        'user_id' => $_SESSION['user_id'],
        'type' => $type,
        'quantity' => $quantity,
        'price_per_unit' => $price_per_unit,
        'total_price' => $total_price,
        'currency' => strtoupper($_POST['buy_currency'] ?? 'IDR'),
        'exchange_rate' => cleanNumber($_POST['exchange_rate'] ?? 1),
        'transaction_date' => $_POST['transaction_date']
    ];
    
    if ($assetTransaction->create($data)) {
        // SUCCESS: Now perform USDT deduction if needed
        if ($usdt_deduction_data) {
            $assetTransaction->create($usdt_deduction_data);
        }

        // Fetch asset details for success message
        $stmt = $db->prepare("SELECT currency FROM assets WHERE id = ?");
        $stmt->execute([$asset_id]);
        $asset_data = $stmt->fetch();
        $curr = $asset_data ? $asset_data['currency'] : 'IDR';

        if ($type == 'sell' && isset($_SESSION['last_sale'])) {
            // ... (keep existing success message logic) ...
            $sale = $_SESSION['last_sale'];
            $profitLossFormatted = ($sale['profit_loss'] >= 0 ? 'PROFIT' : 'LOSS');
            
            $_SESSION['success'] = "Transaksi penjualan berhasil!\n\n";
            $_SESSION['success'] .= "DETAIL TRANSAKSI:\n";
            $_SESSION['success'] .= "• Jumlah Jual: " . number_format($sale['quantity'], 4) . " unit\n";
            $_SESSION['success'] .= "• Harga Jual: " . formatCurrency($sale['sell_price'], $curr) . " / unit\n";
            $_SESSION['success'] .= "• Total Hasil Jual: " . formatCurrency($sale['revenue'], $curr) . "\n\n";
            
            $_SESSION['success'] .= "DETAIL PEMBELIAN (FIFO):\n";
            foreach ($sale['debug_buys'] as $index => $buy) {
                $_SESSION['success'] .= "• Batch " . ($index + 1) . ": " . number_format($buy['sell_qty'], 4) . " unit @ " . formatCurrency($buy['buy_price'], $curr) . " = " . formatCurrency($buy['cost'], $curr) . "\n";
            }
            
            $_SESSION['success'] .= "\nTOTAL MODAL: " . formatCurrency($sale['total_cost'], $curr) . "\n";
            $_SESSION['success'] .= "STATUS " . $profitLossFormatted . ": " . formatCurrency(abs($sale['profit_loss']), $curr) . " (" . number_format(abs($sale['profit_percent']), 2) . "%)\n";
            
            unset($_SESSION['last_sale']);
        } else {
            $_SESSION['success'] = 'Transaksi berhasil ditambahkan!' . ($usdt_deduction_data ? " (Saldo USDT otomatis terpotong)" : "");
        }
    } else {
        $_SESSION['error'] = 'Gagal menyimpan transaksi ke database!';
        error_log("Failed to create transaction: " . json_encode($data));
    }
}

header('Location: ../index.php');
exit;
?>