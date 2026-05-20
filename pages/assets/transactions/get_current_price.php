<?php
require_once '../../../config/config.php';
require_once '../../../config/session.php';
require_once '../../../includes/functions.php';
require_once '../../../classes/CurrencyService.php';
require_once '../../../classes/Database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$asset_id = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;

if ($asset_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Asset ID']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT symbol, type FROM assets WHERE id = ? AND user_id = ?");
    $stmt->execute([$asset_id, $_SESSION['user_id']]);
    $asset = $stmt->fetch();

    if (!$asset) {
        echo json_encode(['success' => false, 'message' => 'Asset not found']);
        exit;
    }

    $symbol = strtoupper(trim($asset['symbol']));
    $name = strtolower(trim($asset['name']));
    
    // Mapping for common symbols and names if symbol is empty
    $symbol_map = [
        '$' => 'USD',
        '€' => 'EUR',
        '£' => 'GBP',
        '¥' => 'JPY',
        '₿' => 'BTC',
        'rp' => 'IDR'
    ];
    
    $name_map = [
        'bitcoin' => 'BTC',
        'ethereum' => 'ETH',
        'bittensor' => 'TAO',
        'tao' => 'TAO',
        'binance' => 'BNB',
        'bnb' => 'BNB',
        'solana' => 'SOL',
        'sol' => 'SOL',
        'tether' => 'USDT',
        'usdt' => 'USDT',
        'ripple' => 'XRP',
        'xrp' => 'XRP',
        'us dollar' => 'USD',
        'dollar' => 'USD',
        'usd' => 'USD'
    ];
    
    if (empty($symbol) && isset($name_map[$name])) {
        $symbol = $name_map[$name];
    }
    
    if (isset($symbol_map[$symbol])) {
        $symbol = $symbol_map[$symbol];
    }

    $rates = CurrencyService::getExchangeRates();

    if (isset($rates[$symbol]) && $rates[$symbol] > 0) {
        $price_idr = 1 / $rates[$symbol];
        echo json_encode([
            'success' => true,
            'price' => $price_idr,
            'symbol' => $symbol
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Price data not available for ' . $symbol]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
