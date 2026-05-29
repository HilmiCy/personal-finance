<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/CurrencyService.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Monitoring Pasar';
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
    /* ========== MONITORING SPECIFIC STYLES ========== */
    .section-title { 
        font-size: 11px; 
        font-weight: 800; 
        color: var(--muted); 
        text-transform: uppercase; 
        letter-spacing: 2px; 
        margin-bottom: 30px; 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        padding-bottom: 15px; 
        border-bottom: 1px solid rgba(0,0,0,0.05); 
    }
    
    .currency-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 35px; 
        transition: var(--transition); 
        height: 100%; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        display: flex;
        flex-direction: column;
    }
    .currency-card:hover { transform: translateY(-8px); box-shadow: 0 25px 60px rgba(0, 0, 0, 0.08); border-color: rgba(66, 133, 244, 0.3); }
    
    .currency-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
    .currency-info { display: flex; align-items: center; gap: 15px; }
    
    .currency-icon { 
        width: 48px; height: 48px; 
        border-radius: 14px; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 20px; color: white;
        box-shadow: 0 8px 15px rgba(0,0,0,0.05);
    }
    
    .currency-flag { font-size: 32px; line-height: 1; }
    
    .currency-code { font-weight: 800; font-size: 18px; color: var(--fg); margin: 0; letter-spacing: -0.01em; }
    .currency-name { font-size: 12px; color: var(--muted); font-weight: 600; }
    
    .rate-container { 
        background: var(--surface); 
        border-radius: 24px; 
        padding: 25px; 
        text-align: center; 
        border: 1px solid rgba(0,0,0,0.03);
        margin-top: auto;
    }
    
    .rate-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 800; margin-bottom: 8px; }
    .rate-value { font-size: 22px; font-weight: 800; color: var(--fg); letter-spacing: -0.02em; }
    
    .inverse-rate { font-size: 11px; color: var(--muted); margin-top: 8px; font-weight: 600; }
    
    .btn-chart {
        margin-top: 20px;
        width: 100%;
        padding: 12px;
        border-radius: 14px;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1px solid rgba(0,0,0,0.05);
        background: var(--fg);
        color: white;
    }
    .btn-chart:hover { background: #000; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
    
    .btn-back-custom {
        background: var(--surface);
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 13px;
        text-decoration: none !important;
        color: var(--fg);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        border: 1px solid var(--border);
    }
    .btn-back-custom:hover { background: var(--border); transform: translateX(-5px); }

    .modal-tv { border-radius: 32px !important; overflow: hidden; border: 1px solid rgba(0,0,0,0.1) !important; box-shadow: 0 30px 80px rgba(0,0,0,0.2) !important; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="welcome-title">Monitoring Pasar</h1>
                    <p class="welcome-subtitle">Pantau pergerakan ekonomi global & lokal secara real-time</p>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <a href="index.php" class="btn-back-custom">
                        <i class="fas fa-arrow-left"></i> Kembali ke Portofolio
                    </a>
                </div>
            </div>
        </div>

        <!-- Market Index & Commodities Section -->
        <h2 class="section-title animated" style="animation-delay: 0.1s">
            <i class="fas fa-chart-pie"></i> Indeks Pasar & Komoditas
        </h2>
        <div class="row g-4 mb-5">
            <!-- IHSG -->
            <?php if (isset($rates['IHSG'])): 
                $ihsg_val = $rates['IHSG'];
                $ihsg_prev = $rates['IHSG_PREV'] ?? $ihsg_val;
                $ihsg_diff = $ihsg_val - $ihsg_prev;
                $ihsg_pct = ($ihsg_prev > 0) ? ($ihsg_diff / $ihsg_prev) * 100 : 0;
                $ihsg_color = $ihsg_diff >= 0 ? '#10b981' : '#ea4335';
                $ihsg_icon = $ihsg_diff >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
            ?>
            <div class="col-md-6 col-xl-4 animated" style="animation-delay: 0.2s">
                <div class="currency-card">
                    <div class="currency-header">
                        <div class="currency-info">
                            <div class="currency-icon" style="background: #4285f4">
                                <i class="fas fa-landmark"></i>
                            </div>
                            <div>
                                <h3 class="currency-code">IHSG</h3>
                                <span class="currency-name">Indeks Saham Gabungan</span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">Nilai Indeks</div>
                        <div class="rate-value" style="color: <?= $ihsg_color ?>">
                            <?= number_format($ihsg_val, 2, '.', ',') ?>
                        </div>
                        <div class="mt-2" style="color: <?= $ihsg_color ?>; font-weight: 800; font-size: 13px;">
                            <i class="fas <?= $ihsg_icon ?> me-1"></i> 
                            <?= ($ihsg_diff >= 0 ? '+' : '') . number_format($ihsg_diff, 2, '.', ',') ?> 
                            (<?= ($ihsg_diff >= 0 ? '+' : '') . number_format($ihsg_pct, 2, '.', ',') ?>%)
                        </div>
                        <button class="btn-chart" onclick="showChart('IDX:COMPOSITE', 'IHSG')">
                            <i class="fas fa-chart-area"></i> Buka Grafik
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
                $dxy_color = $dxy_diff >= 0 ? '#10b981' : '#ea4335';
                $dxy_icon = $dxy_diff >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
            ?>
            <div class="col-md-6 col-xl-4 animated" style="animation-delay: 0.25s">
                <div class="currency-card">
                    <div class="currency-header">
                        <div class="currency-info">
                            <div class="currency-icon" style="background: #202124">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <h3 class="currency-code">DXY</h3>
                                <span class="currency-name">US Dollar Index</span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">Kekuatan Dollar</div>
                        <div class="rate-value" style="color: <?= $dxy_color ?>">
                            <?= number_format($dxy_val, 3, '.', ',') ?>
                        </div>
                        <div class="mt-2" style="color: <?= $dxy_color ?>; font-weight: 800; font-size: 13px;">
                            <i class="fas <?= $dxy_icon ?> me-1"></i> 
                            <?= ($dxy_diff >= 0 ? '+' : '') . number_format($dxy_diff, 3, '.', ',') ?> 
                            (<?= ($dxy_diff >= 0 ? '+' : '') . number_format($dxy_pct, 2, '.', ',') ?>%)
                        </div>
                        <button class="btn-chart" onclick="showChart('TVC:DXY', 'US Dollar Index (DXY)')">
                            <i class="fas fa-chart-area"></i> Buka Grafik
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
                                <i class="fas fa-coins"></i>
                            </div>
                            <div>
                                <h3 class="currency-code">EMAS</h3>
                                <span class="currency-name">Gold (XAU) per Gram</span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">Harga Estimasi (1gr)</div>
                        <div class="rate-value" style="color: #f59e0b">
                            Rp <?= number_format($gram_to_idr, 0, ',', '.') ?>
                        </div>
                        <div class="inverse-rate">
                            1 Oz = Rp <?= number_format($xau_to_idr, 0, ',', '.') ?>
                        </div>
                        <button class="btn-chart" onclick="showChart('TVC:GOLD', 'Harga Emas (XAU/USD)')">
                            <i class="fas fa-chart-area"></i> Buka Grafik
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Fiat Currencies Section -->
        <h2 class="section-title animated" style="animation-delay: 0.4s">
            <i class="fas fa-money-bill-wave"></i> Mata Uang Fiat (Kurs IDR)
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
                        <div class="rate-label">1 <?= $code ?> setara dengan</div>
                        <div class="rate-value">Rp <?= number_format($rate_to_idr, 2, ',', '.') ?></div>
                        <div class="inverse-rate">1 IDR = <?= number_format($idr_to_rate, 8, '.', ',') ?> <?= $code ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Crypto Assets Section -->
        <h2 class="section-title animated" style="animation-delay: 0.3s">
            <i class="fas fa-rocket"></i> Aset Kripto (Market Price)
        </h2>
        <div class="row g-4 mb-5">
            <?php foreach ($crypto_assets as $code => $info): 
                if (!isset($rates[$code])) continue;
                $rate_to_idr = 1 / $rates[$code];
                $idr_to_rate = $rates[$code];
            ?>
            <div class="col-md-6 col-xl-4 animated" style="animation-delay: 0.4s">
                <div class="currency-card">
                    <div class="currency-header">
                        <div class="currency-info">
                            <div class="currency-icon" style="background: <?= $info['color'] ?>; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                                <i class="<?= $info['icon'] ?>"></i>
                            </div>
                            <div>
                                <h3 class="currency-code"><?= $code ?></h3>
                                <span class="currency-name"><?= $info['name'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="rate-container">
                        <div class="rate-label">Harga dalam Rupiah</div>
                        <div class="rate-value" style="font-size: 20px;">Rp <?= number_format($rate_to_idr, 0, ',', '.') ?></div>
                        <?php 
                            if (isset($rates['USD']) && $rates['USD'] > 0) {
                                $usd_price = $rates['USD'] / $rates[$code];
                                echo '<div style="font-size: 13px; font-weight: 800; color: var(--muted); margin-top: 5px;">$ ' . number_format($usd_price, $usd_price < 1 ? 4 : 2, '.', ',') . '</div>';
                            }
                            $binance_symbol = ($code === 'USDT') ? "BINANCE:USDTIDR" : "BINANCE:" . $code . "USDT";
                        ?>
                        <button class="btn-chart" onclick="showChart('<?= $binance_symbol ?>', '<?= $info['name'] ?>')">
                            <i class="fas fa-chart-area"></i> Lihat Grafik
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="alert animated" style="animation-delay: 0.5s; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05); border-radius: 24px; padding: 25px; text-align: center;">
            <p class="text-muted small mb-0 fw-bold">
                <i class="fas fa-clock me-2"></i> Terakhir diperbarui: <?= $last_updated ?>. 
                Data diperbarui otomatis setiap 15 menit melalui integrasi API Global.
            </p>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Chart Modal -->
<div class="modal fade" id="chartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-tv">
            <div class="modal-header border-0 pb-3 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="chartModalTitle">Market Chart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 650px;">
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