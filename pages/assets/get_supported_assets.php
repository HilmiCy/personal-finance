<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$cache_dir = '../../cache';
if (!file_exists($cache_dir)) {
    mkdir($cache_dir, 0777, true);
}
$cache_file = $cache_dir . '/supported_assets.json';
$cache_time = 86400; // 24 hours

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
    echo file_get_contents($cache_file);
    exit;
}

// Fetch from fawazahmed0 API (Fiat + some Crypto)
$url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies.json";
$assets = [];

try {
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        foreach ($data as $code => $name) {
            $code_upper = strtoupper($code);
            // Simple heuristic to distinguish crypto (usually 3-5 chars, but many fiat are 3 too)
            // We'll provide them as a general list and let user refine
            $assets[] = [
                'symbol' => $code_upper,
                'name' => $name,
                'type' => 'other' // Will refine in frontend or let user confirm
            ];
        }
    }
} catch (Exception $e) {
    // Fail silently
}

// Add some known assets that might be missing or need specific types
$manual_additions = [
    ['symbol' => 'BTC', 'name' => 'Bitcoin', 'type' => 'crypto'],
    ['symbol' => 'ETH', 'name' => 'Ethereum', 'type' => 'crypto'],
    ['symbol' => 'TAO', 'name' => 'Bittensor', 'type' => 'crypto'],
    ['symbol' => 'BNB', 'name' => 'Binance Coin', 'type' => 'crypto'],
    ['symbol' => 'SOL', 'name' => 'Solana', 'type' => 'crypto'],
    ['symbol' => 'USDT', 'name' => 'Tether', 'type' => 'crypto'],
    ['symbol' => 'XRP', 'name' => 'Ripple', 'type' => 'crypto'],
    ['symbol' => 'ADA', 'name' => 'Cardano', 'type' => 'crypto'],
    ['symbol' => 'DOGE', 'name' => 'Dogecoin', 'type' => 'crypto'],
    ['symbol' => 'DOT', 'name' => 'Polkadot', 'type' => 'crypto'],
    ['symbol' => 'MATIC', 'name' => 'Polygon', 'type' => 'crypto'],
    ['symbol' => 'AVAX', 'name' => 'Avalanche', 'type' => 'crypto'],
    ['symbol' => 'LINK', 'name' => 'Chainlink', 'type' => 'crypto'],
    ['symbol' => 'LTC', 'name' => 'Litecoin', 'type' => 'crypto'],
    ['symbol' => 'USD', 'name' => 'US Dollar', 'type' => 'currency'],
    ['symbol' => 'EUR', 'name' => 'Euro', 'type' => 'currency'],
    ['symbol' => 'SGD', 'name' => 'Singapore Dollar', 'type' => 'currency'],
    ['symbol' => 'JPY', 'name' => 'Japanese Yen', 'type' => 'currency'],
];

// Merge and remove duplicates by symbol
$final_assets = [];
$seen_symbols = [];

foreach ($manual_additions as $asset) {
    $final_assets[] = $asset;
    $seen_symbols[$asset['symbol']] = true;
}

$crypto_symbols = ['BTC', 'ETH', 'SOL', 'XRP', 'DOGE', 'ADA', 'BNB', 'USDT', 'DOT', 'MATIC', 'AVAX', 'LINK', 'LTC', 'TAO', 'SHIB', 'TRX', 'BCH', 'XLM', 'ATOM', 'UNI', 'XMR', 'ETC', 'ALGO', 'VET', 'FIL'];

foreach ($assets as $asset) {
    if (!isset($seen_symbols[$asset['symbol']])) {
        // More robust type detection
        $is_crypto = in_array($asset['symbol'], $crypto_symbols) || strlen($asset['symbol']) >= 4;
        $asset['type'] = $is_crypto ? 'crypto' : 'currency';
        $final_assets[] = $asset;
        $seen_symbols[$asset['symbol']] = true;
    }
}

file_put_contents($cache_file, json_encode($final_assets));
echo json_encode($final_assets);
