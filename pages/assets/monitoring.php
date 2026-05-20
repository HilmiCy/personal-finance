<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/CurrencyService.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Monitoring Kurs Mata Uang';
$current_page = 'assets';

$rates = CurrencyService::getExchangeRates();
$last_updated = file_exists('../../cache/exchange_rates.json') ? date('H:i:s', filemtime('../../cache/exchange_rates.json')) : date('H:i:s');

// Define popular currencies to monitor
$popular_currencies = [
    'USD' => ['name' => 'US Dollar', 'flag' => '🇺🇸'],
    'EUR' => ['name' => 'Euro', 'flag' => '🇪🇺'],
    'JPY' => ['name' => 'Japanese Yen', 'flag' => '🇯🇵'],
    'SGD' => ['name' => 'Singapore Dollar', 'flag' => '🇸🇬'],
    'GBP' => ['name' => 'British Pound', 'flag' => '🇬🇧'],
    'AUD' => ['name' => 'Australian Dollar', 'flag' => '🇦🇺']
];

$crypto_assets = [
    'BTC' => ['name' => 'Bitcoin', 'icon' => 'fa-brands fa-bitcoin', 'color' => '#f7931a'],
    'ETH' => ['name' => 'Ethereum', 'icon' => 'fa-brands fa-ethereum', 'color' => '#627eea'],
    'TAO' => ['name' => 'Bittensor', 'icon' => 'fa-solid fa-brain', 'color' => '#000000'],
    'BNB' => ['name' => 'Binance Coin', 'icon' => 'fa-solid fa-coins', 'color' => '#f3ba2f'],
    'SOL' => ['name' => 'Solana', 'icon' => 'fa-solid fa-bolt', 'color' => '#14f195'],
    'USDT' => ['name' => 'Tether', 'icon' => 'fa-solid fa-dollar-sign', 'color' => '#26a17b']
];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f3f4f6;
    }
    .currency-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        border: 1px solid #f3f4f6;
        transition: all 0.3s ease;
        height: 100%;
    }
    .currency-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.05);
        border-color: #667eea;
    }
    .currency-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .currency-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .currency-flag {
        font-size: 2rem;
    }
    .currency-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    .currency-code {
        font-weight: 800;
        font-size: 1.2rem;
        color: #1f2937;
        margin: 0;
    }
    .currency-name {
        font-size: 0.85rem;
        color: #6b7280;
    }
    .rate-container {
        background: #f9fafb;
        border-radius: 16px;
        padding: 15px;
        text-align: center;
    }
    .rate-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .rate-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: #10b981;
    }
    .inverse-rate {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 5px;
    }
    .welcome-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    .btn-back {
        background: #f3f4f6;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        color: #4b5563;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-back:hover {
        background: #e5e7eb;
        color: #1f2937;
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Monitoring Kurs</h1>
                    <p class="welcome-subtitle">Pantau nilai tukar Rupiah (IDR) terhadap Fiat & Crypto</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <a href="../assets/index.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Aset
                    </a>
                </div>
            </div>
        </div>

        <!-- Market Index & Commodities Section -->
        <h2 class="section-title animated" style="animation-delay: 0.1s">
            <i class="fas fa-chart-pie text-info"></i> Indeks Pasar & Komoditas
        </h2>
        <div class="row g-4 mb-5">
            <!-- IHSG -->
            <?php if (isset($rates['IHSG'])): 
                $ihsg_val = $rates['IHSG'];
                $ihsg_prev = $rates['IHSG_PREV'] ?? $ihsg_val;
                $ihsg_diff = $ihsg_val - $ihsg_prev;
                $ihsg_pct = ($ihsg_diff / $ihsg_prev) * 100;
                $ihsg_color = $ihsg_diff >= 0 ? '#10b981' : '#ef4444';
                $ihsg_icon = $ihsg_diff >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
            ?>
            <div class="col-md-6 col-xl-4 animated" style="animation-delay: 0.2s">
                <div class="currency-card">
                    <div class="currency-header">
                        <div class="currency-info">
                            <div class="currency-icon" style="background: #0ea5e9">
                                <i class="fas fa-landmark"></i>
                            </div>
                            <div>
                                <h3 class="currency-code">IHSG</h3>
                                <span class="currency-name">Indeks Harga Saham Gabungan</span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">Nilai Indeks</div>
                        <div class="rate-value" style="color: <?= $ihsg_color ?>">
                            <?= number_format($ihsg_val, 2, '.', ',') ?>
                        </div>
                        <div class="mt-1" style="color: <?= $ihsg_color ?>; font-weight: 600; font-size: 0.9rem;">
                            <i class="fas <?= $ihsg_icon ?>"></i> 
                            <?= ($ihsg_diff >= 0 ? '+' : '') . number_format($ihsg_diff, 2, '.', ',') ?> 
                            (<?= ($ihsg_diff >= 0 ? '+' : '') . number_format($ihsg_pct, 2, '.', ',') ?>%)
                        </div>
                        <button class="btn btn-sm btn-outline-primary mt-3 w-100 rounded-pill" onclick="showChart('IDX:COMPOSITE', 'IHSG')">
                            <i class="fas fa-chart-area me-1"></i> Lihat Chart
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- DXY -->
            <?php if (isset($rates['DXY'])): 
                $dxy_val = $rates['DXY'];
                $dxy_prev = $rates['DXY_PREV'] ?? $dxy_val;
                $dxy_diff = $dxy_val - $dxy_prev;
                $dxy_pct = ($dxy_prev > 0) ? ($dxy_diff / $dxy_prev) * 100 : 0;
                $dxy_color = $dxy_diff >= 0 ? '#10b981' : '#ef4444';
                $dxy_icon = $dxy_diff >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
            ?>
            <div class="col-md-6 col-xl-4 animated" style="animation-delay: 0.25s">
                <div class="currency-card">
                    <div class="currency-header">
                        <div class="currency-info">
                            <div class="currency-icon" style="background: #6366f1">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <h3 class="currency-code">DXY</h3>
                                <span class="currency-name">US Dollar Index</span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">Nilai Indeks</div>
                        <div class="rate-value" style="color: <?= $dxy_color ?>">
                            <?= number_format($dxy_val, 3, '.', ',') ?>
                        </div>
                        <div class="mt-1" style="color: <?= $dxy_color ?>; font-weight: 600; font-size: 0.9rem;">
                            <i class="fas <?= $dxy_icon ?>"></i> 
                            <?= ($dxy_diff >= 0 ? '+' : '') . number_format($dxy_diff, 3, '.', ',') ?> 
                            (<?= ($dxy_diff >= 0 ? '+' : '') . number_format($dxy_pct, 2, '.', ',') ?>%)
                        </div>
                        <button class="btn btn-sm btn-outline-indigo mt-3 w-100 rounded-pill" style="border-color: #6366f1; color: #6366f1;" onclick="showChart('TVC:DXY', 'US Dollar Index (DXY)')">
                            <i class="fas fa-chart-area me-1"></i> Lihat Chart
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gold (XAU) -->
            <?php if (isset($rates['XAU'])): 
                $xau_to_idr = 1 / $rates['XAU'];
                $gram_to_idr = $xau_to_idr / 31.1035;
            ?>
            <div class="col-md-6 col-xl-4 animated" style="animation-delay: 0.3s">
                <div class="currency-card">
                    <div class="currency-header">
                        <div class="currency-info">
                            <div class="currency-icon" style="background: #f59e0b">
                                <i class="fas fa-gold-bar"></i>
                            </div>
                            <div>
                                <h3 class="currency-code">EMAS</h3>
                                <span class="currency-name">Gold (XAU) per Gram</span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">Estimasi Harga (1gr)</div>
                        <div class="rate-value">Rp <?= number_format($gram_to_idr, 0, ',', '.') ?></div>
                        <div class="inverse-rate">1 Oz = Rp <?= number_format($xau_to_idr, 0, ',', '.') ?></div>
                        <?php 
                            if (isset($rates['USD']) && $rates['USD'] > 0) {
                                $usd_price = $rates['USD'] / $rates['XAU'];
                                echo '<div class="fw-bold text-muted mt-1" style="font-size: 0.9rem;">$ ' . number_format($usd_price, 2, '.', ',') . ' / oz</div>';
                            }
                        ?>
                        <button class="btn btn-sm btn-outline-warning mt-3 w-100 rounded-pill" onclick="showChart('TVC:GOLD', 'Harga Emas (XAU/USD)')">
                            <i class="fas fa-chart-area me-1"></i> Lihat Chart
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Fiat Currencies Section -->
        <h2 class="section-title animated" style="animation-delay: 0.4s">
            <i class="fas fa-money-bill-wave text-primary"></i> Mata Uang Fiat
        </h2>
        <div class="row g-4 mb-5">
            <?php foreach ($popular_currencies as $code => $info): 
                if (!isset($rates[$code])) continue;
                $rate_to_idr = 1 / $rates[$code];
                $idr_to_rate = $rates[$code];
            ?>
            <div class="col-md-6 col-xl-4 animated" style="animation-delay: 0.2s">
                <div class="currency-card">
                    <div class="currency-header">
                        <div class="currency-info">
                            <span class="currency-flag"><?= $info['flag'] ?></span>
                            <div>
                                <h3 class="currency-code"><?= $code ?></h3>
                                <span class="currency-name"><?= $info['name'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">1 <?= $code ?> =</div>
                        <div class="rate-value">Rp <?= number_format($rate_to_idr, 2, ',', '.') ?></div>
                        <div class="inverse-rate">1 IDR = <?= number_format($idr_to_rate, 8, '.', ',') ?> <?= $code ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Crypto Assets Section -->
        <h2 class="section-title animated" style="animation-delay: 0.3s">
            <i class="fas fa-chart-line text-warning"></i> Aset Kripto
        </h2>
        <div class="row g-4">
            <?php foreach ($crypto_assets as $code => $info): 
                if (!isset($rates[$code])) continue;
                $rate_to_idr = 1 / $rates[$code];
                $idr_to_rate = $rates[$code];
            ?>
            <div class="col-md-6 col-xl-4 animated" style="animation-delay: 0.4s">
                <div class="currency-card">
                    <div class="currency-header">
                        <div class="currency-info">
                            <div class="currency-icon" style="background: <?= $info['color'] ?>">
                                <i class="<?= $info['icon'] ?>"></i>
                            </div>
                            <div>
                                <h3 class="currency-code"><?= $code ?></h3>
                                <span class="currency-name"><?= $info['name'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">1 <?= $code ?> =</div>
                        <div class="rate-value">Rp <?= number_format($rate_to_idr, 2, ',', '.') ?></div>
                        <?php 
                            // Calculate price in USD for crypto
                            if (isset($rates['USD']) && $rates['USD'] > 0) {
                                $usd_price = $rates['USD'] / $rates[$code];
                                echo '<div class="fw-bold text-muted mt-1" style="font-size: 0.9rem;">$ ' . number_format($usd_price, 4, '.', ',') . '</div>';
                            }

                            // Determine Binance Symbol
                            $binance_symbol = "BINANCE:" . $code . "USDT";
                            // Special case for USDT itself
                            if ($code === 'USDT') $binance_symbol = "BINANCE:USDTIDR";
                        ?>
                        <button class="btn btn-sm btn-outline-dark mt-3 w-100 rounded-pill" onclick="showChart('<?= $binance_symbol ?>', '<?= $info['name'] ?>')">
                            <i class="fas fa-chart-area me-1"></i> Lihat Chart
                        </button>
                        <div class="inverse-rate">1 IDR = <?= sprintf("%.12f", $idr_to_rate) ?> <?= $code ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 text-center animated" style="animation-delay: 0.5s">
            <p class="text-muted small">
                <i class="fas fa-info-circle"></i> Terakhir diperbarui: <strong><?= $last_updated ?></strong>. 
                Data diperbarui setiap 15 menit menggunakan multi-source API.
            </p>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Chart Modal -->
<div class="modal fade" id="chartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="chartModalTitle">Chart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 600px;">
                <div id="tradingview_widget" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
<script>
function showChart(symbol, title) {
    document.getElementById('chartModalTitle').innerText = 'Chart ' + title;
    var modal = new bootstrap.Modal(document.getElementById('chartModal'));
    modal.show();

    // Small delay to ensure modal is rendered
    setTimeout(function() {
        new TradingView.widget({
            "autosize": true,
            "symbol": symbol,
            "interval": "D",
            "timezone": "Asia/Jakarta",
            "theme": "light",
            "style": "1",
            "locale": "id",
            "toolbar_bg": "#f1f3f6",
            "enable_publishing": false,
            "hide_side_toolbar": false,
            "allow_symbol_change": true,
            "container_id": "tradingview_widget"
        });
    }, 200);
}
</script>
