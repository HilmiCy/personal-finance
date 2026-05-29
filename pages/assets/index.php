<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Asset.php';
require_once '../../classes/AssetTransaction.php';
require_once '../../classes/CurrencyService.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Portofolio Aset';
$current_page = 'assets';

$db = Database::getInstance()->getConnection();
$asset = new Asset();
$assetTransaction = new AssetTransaction();

// Get exchange rates for auto-price
$rates = CurrencyService::getExchangeRates();

// Get all assets
$assets = $asset->getAll($_SESSION['user_id']);
$summary = $asset->getPortfolioSummary($_SESSION['user_id']);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* ========== ASSETS SPECIFIC STYLES ========== */
    .summary-stats { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 30px 25px; 
        text-align: center; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        transition: var(--transition); 
        height: 100%;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    .summary-stats:hover { transform: translateY(-5px); box-shadow: 0 25px 60px rgba(0, 0, 0, 0.07); }
    
    .stat-icon-circle { 
        width: 52px; height: 52px; 
        border-radius: 16px; 
        display: flex; align-items: center; justify-content: center; 
        margin: 0 auto 20px; 
        font-size: 22px; 
        background: var(--surface);
        border: 1px solid var(--border);
    }
    
    .stat-title { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
    .stat-number { font-size: 22px; font-weight: 800; color: var(--fg); letter-spacing: -0.02em; }
    
    .asset-card { 
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
    .asset-card:hover { transform: translateY(-8px); box-shadow: 0 25px 60px rgba(0, 0, 0, 0.08); border-color: rgba(66, 133, 244, 0.3); }
    
    .asset-name { font-size: 18px; font-weight: 800; color: var(--fg); margin-bottom: 5px; }
    .asset-type-pill { 
        display: inline-flex; align-items: center; padding: 6px 14px; 
        border-radius: 9999px; font-size: 10px; font-weight: 800; 
        text-transform: uppercase; letter-spacing: 1px;
        background: var(--surface);
        color: var(--muted);
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .asset-stats-grid { 
        display: grid; grid-template-columns: 1fr 1fr; 
        gap: 20px; margin: 30px 0; padding: 25px 0; 
        border-top: 1px solid rgba(0,0,0,0.04); 
        border-bottom: 1px solid rgba(0,0,0,0.04); 
    }
    .asset-stat-label { font-size: 10px; color: var(--muted); text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin-bottom: 6px; }
    .asset-stat-value { font-size: 15px; font-weight: 750; color: var(--fg); }
    
    .btn-buy-asset, .btn-sell-asset { 
        flex: 1; padding: 12px; border-radius: 14px; font-weight: 800; 
        font-size: 13px; transition: var(--transition); border: none;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-buy-asset { background: var(--fg); color: white; }
    .btn-buy-asset:hover { background: #000; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
    
    .btn-sell-asset { background: var(--surface); color: var(--fg); border: 1px solid rgba(0,0,0,0.05); }
    .btn-sell-asset:hover { background: var(--border); transform: translateY(-2px); }
    
    .asset-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
    
    .dropdown-minimal .btn-link { color: var(--muted); padding: 0; font-size: 18px; text-decoration: none; }
    .dropdown-minimal .btn-link:hover { color: var(--fg); }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Portofolio Aset</h1>
                    <p class="welcome-subtitle">Kelola seluruh investasi Anda secara profesional</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0 header-actions">
                    <a href="monitoring.php" class="btn-action-minimal me-2">
                        <i class="fas fa-chart-line"></i> Monitoring Kurs
                    </a>
                    <button class="btn-action-minimal me-2" data-bs-toggle="modal" data-bs-target="#converterModal">
                        <i class="fas fa-calculator"></i> Konverter
                    </button>
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="fas fa-plus"></i> Tambah Aset
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="summary-stats animated" style="animation-delay: 0.1s">
                    <div class="stat-icon-circle" style="background: rgba(66, 133, 244, 0.1); color: #4285f4;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-title">Total Aset</div>
                    <div class="stat-number"><?= $summary['total_assets'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="summary-stats animated" style="animation-delay: 0.15s">
                    <div class="stat-icon-circle" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-title">Modal</div>
                    <div class="stat-number"><?= formatRupiah($summary['total_investment']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="summary-stats animated" style="animation-delay: 0.2s">
                    <div class="stat-icon-circle" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-title">Nilai Aset</div>
                    <div class="stat-number"><?= formatRupiah($summary['total_value']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="summary-stats animated" style="animation-delay: 0.25s">
                    <div class="stat-icon-circle" style="background: <?= $summary['profit_loss'] >= 0 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' ?>; color: <?= $summary['profit_loss'] >= 0 ? '#10b981' : '#ef4444' ?>;">
                        <i class="fas fa-<?= $summary['profit_loss'] >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                    </div>
                    <div class="stat-title">P/L (%)</div>
                    <div class="stat-number <?= $summary['profit_loss'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($summary['profit_loss_percent'], 2) ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Assets Grid -->
        <div class="row g-4">
            <?php if (count($assets) > 0): ?>
                <?php foreach ($assets as $index => $a): 
                    $current_value = $a['total_quantity'] * $a['current_price'];
                    $profit_loss = ($current_value + $a['total_sell']) - $a['total_buy'];
                    $profit_percent = $a['total_buy'] > 0 ? ($profit_loss / $a['total_buy']) * 100 : 0;        
                ?>
                <div class="col-md-6 col-xl-4" style="animation: fadeInUp 0.5s ease <?= $index * 0.05 ?>s both;">
                    <div class="asset-card">
                        <div class="asset-header">
                            <div>
                                <div class="asset-name"><?= htmlspecialchars($a['name']) ?></div>
                                <div class="asset-type-pill type-<?= strtolower($a['type']) ?>"><?= ucfirst($a['type']) ?></div>
                            </div>
                            <div class="dropdown dropdown-minimal">
                                <button class="btn btn-link" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                    <li>
                                        <button class="dropdown-item" onclick="editAsset(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', '<?= $a['type'] ?>', '<?= htmlspecialchars($a['symbol'] ?: '') ?>', '<?= $a['currency'] ?>')">
                                            <i class="fas fa-pencil-alt me-2 text-primary"></i> Edit Aset
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" onclick="viewTransactions(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', '<?= $a['currency'] ?>')">
                                            <i class="fas fa-history me-2 text-info"></i> Riwayat
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deleteAsset(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>')">
                                            <i class="fas fa-trash-alt me-2"></i> Hapus Aset
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="asset-stats-grid">
                            <div>
                                <div class="asset-stat-label">Jumlah</div>
                                <div class="asset-stat-value"><?= number_format($a['total_quantity'], 4) ?> <?= htmlspecialchars($a['symbol'] ?: '') ?></div>
                            </div>
                            <div>
                                <div class="asset-stat-label">Nilai</div>
                                <div class="asset-stat-value"><?= formatCurrency($current_value, $a['currency']) ?></div>
                            </div>
                            <div>
                                <div class="asset-stat-label">Modal</div>
                                <div class="asset-stat-value"><?= formatCurrency($a['total_buy'], $a['currency']) ?></div>
                            </div>
                            <div>
                                <div class="asset-stat-label">Profit/Loss</div>
                                <div class="asset-stat-value <?= $profit_loss >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($profit_percent, 2) ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn-buy-asset" onclick="openTransactionModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', '<?= htmlspecialchars($a['symbol'] ?: '') ?>', 'buy', '<?= $a['currency'] ?>')">
                                <i class="fas fa-plus-circle me-1"></i> BELI
                            </button>
                            <button class="btn-sell-asset" onclick="openTransactionModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', '<?= htmlspecialchars($a['symbol'] ?: '') ?>', 'sell', '<?= $a['currency'] ?>')" <?= $a['total_quantity'] <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-minus-circle me-1"></i> JUAL
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="card p-5">
                        <i class="fas fa-chart-line fa-4x mb-3 opacity-20" style="color: #4285f4;"></i>
                        <p class="text-muted">Portofolio Anda masih kosong.</p>
                        <div class="d-flex justify-content-center">
                            <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                                Tambah Aset Pertama
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Tambah Aset -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Tambah Aset Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold">Cari Aset (Crypto / Fiat)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="asset_search_input" class="form-control border-start-0" placeholder="Ketik nama atau simbol (cth: Bitcoin, BTC, USD)...">
                        </div>
                        <div id="search_results" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 2000; max-height: 250px; overflow-y: auto; display: none; background: white;"></div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Aset</label>
                        <input type="text" name="name" id="add_asset_name" class="form-control" placeholder="Pilih dari pencarian di atas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Aset</label>
                        <select name="type" id="add_asset_type" class="form-select" required>
                            <option value="crypto">Crypto</option>
                            <option value="stock">Saham</option>
                            <option value="gold">Emas</option>
                            <option value="reksadana">Reksadana</option>
                            <option value="currency">Mata Uang</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Symbol (Opsional)</label>
                        <input type="text" name="symbol" id="add_asset_symbol" class="form-control" placeholder="Contoh: BTC, BBCA, USD">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mata Uang Basis</label>
                        <select name="currency" class="form-select">
                            <option value="IDR">IDR (Rupiah)</option>
                            <option value="USD">USD (Dollar)</option>
                            <option value="EUR">EUR (Euro)</option>
                        </select>
                        <small class="text-muted">Mata uang yang digunakan untuk harga aset ini.</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-custom">Simpan Aset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Aset -->
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Edit Aset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAssetForm" action="edit.php" method="POST">
                <input type="hidden" name="id" id="edit_asset_id">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Aset</label>
                        <input type="text" name="name" id="edit_asset_name_input" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Aset</label>
                        <select name="type" id="edit_asset_type_input" class="form-select" required>
                            <option value="crypto">Crypto</option>
                            <option value="stock">Saham</option>
                            <option value="gold">Emas</option>
                            <option value="reksadana">Reksadana</option>
                            <option value="currency">Mata Uang</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Symbol</label>
                        <input type="text" name="symbol" id="edit_asset_symbol_input" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mata Uang Basis</label>
                        <select name="currency" id="edit_asset_currency_input" class="form-select">
                            <option value="IDR">IDR (Rupiah)</option>
                            <option value="USD">USD (Dollar)</option>
                            <option value="EUR">EUR (Euro)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-custom">Update Aset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Transaksi -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Transaksi Aset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addTransactionForm" action="transactions/add.php" method="POST">
                <input type="hidden" name="asset_id" id="transaction_asset_id">
                <input type="hidden" name="asset_currency" id="transaction_asset_currency">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Aset</label>
                        <input type="text" id="transaction_asset_name" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe</label>
                        <select name="type" id="transaction_type" class="form-select" required>
                            <option value="buy">BELI</option>
                            <option value="sell">JUAL</option>
                        </select>
                    </div>
                    <div class="mb-3" id="auto_price_container" style="display: none;">
                        <div class="form-check form-switch p-3 bg-light rounded-3 border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="auto_price_toggle">
                            <label class="form-check-label fw-bold" for="auto_price_toggle">Gunakan Harga Pasar Otomatis</label>
                            <div class="small text-muted mt-1">Mengambil harga terbaru dari API global.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mata Uang Pembelian</label>
                        <select id="buy_currency" name="buy_currency" class="form-select">
                            <option value="idr">IDR (Rupiah)</option>
                            <option value="usdt">USDT (Tether)</option>
                            <option value="usd">USD (Dollar)</option>
                        </select>
                    </div>
                    <div id="buy_amount_container" class="mb-3">
                        <label class="form-label fw-bold">Total Pembelian (<span id="buy_amount_label">IDR</span>)</label>
                        <input type="text" id="buy_total_amount" class="form-control currency-input" placeholder="Masukkan total uang yang dikeluarkan">
                        <small class="text-muted">Opsional: Masukkan total uang untuk menghitung jumlah unit otomatis.</small>
                    </div>
                    <div id="usd_fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kurs (1 USD ke IDR)</label>
                            <input type="text" name="exchange_rate" id="trans_exchange_rate" class="form-control currency-input" value="16.000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Harga per Unit (USD)</label>
                            <input type="text" id="price_usd" class="form-control currency-input" placeholder="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Unit</label>
                        <input type="number" name="quantity" id="trans_quantity" step="any" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Harga per Unit (IDR)</label>
                        <input type="text" id="price_per_unit_display" class="form-control currency-input" placeholder="0" required>
                        <input type="hidden" name="price_per_unit" id="price_per_unit_hidden">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal</label>
                        <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-custom">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Daftar Transaksi -->
<div class="modal fade" id="transactionsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Riwayat Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modal-body-custom" id="transactionsList">
                <div class="text-center py-4">Memuat data...</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konverter -->
<div class="modal fade" id="converterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Konverter Aset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modal-body-custom">
                <div class="mb-4">
                    <label class="form-label fw-bold">Kurs (1 USD ke IDR)</label>
                    <input type="text" id="exchange_rate" class="form-control currency-input" value="16.000">
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">USD</label>
                        <input type="text" id="val_usd" class="form-control currency-input" placeholder="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">IDR</label>
                        <input type="text" id="val_idr" class="form-control currency-input" placeholder="0">
                    </div>
                </div>
                <hr class="my-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Kalkulator</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text">Unit</span>
                        <input type="number" id="calc_unit" class="form-control" placeholder="0.00" step="any">
                    </div>
                    <div class="input-group mb-2">
                        <span class="input-group-text">Harga</span>
                        <input type="text" id="calc_price" class="form-control currency-input" placeholder="0">
                    </div>
                    <div class="bg-light p-3 rounded-3 mt-3 text-center">
                        <h4 class="fw-bold mb-0" id="calc_total">Rp 0</h4>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-primary-custom w-100" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const exchangeRates = <?= json_encode($rates) ?>;

function parseLoc(str) { 
    if (typeof str === 'number') return str;
    if (typeof str !== 'string') return 0;
    // Format IDR: '.' is thousands, ',' is decimal. 
    let clean = str.replace(/\./g, '').replace(/,/g, '.');
    clean = clean.replace(/[^0-9.]/g, '');
    return parseFloat(clean) || 0; 
}

function formatNumberId(num) {
    return num.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

// Centralized Sync and Calculation Function
function syncAssetForm(triggerSource) {
    const buyCurrencySelect = document.getElementById('buy_currency');
    if (!buyCurrencySelect) return;
    
    const buyCurrency = buyCurrencySelect.value;
    const assetCurrency = document.getElementById('transaction_asset_currency').value || 'IDR';
    const exchangeRate = parseLoc(document.getElementById('trans_exchange_rate').value) || 16000;
    const priceUsdInput = document.getElementById('price_usd');
    const priceIdrDisplay = document.getElementById('price_per_unit_display');
    const priceIdrHidden = document.getElementById('price_per_unit_hidden');
    const buyTotalAmountInput = document.getElementById('buy_total_amount');
    const buyTotalAmount = parseLoc(buyTotalAmountInput.value);
    const quantityInput = document.getElementById('trans_quantity');
    const quantity = parseFloat(quantityInput.value) || 0;

    if (triggerSource === 'usd' || triggerSource === 'rate') {
        const usd = parseLoc(priceUsdInput.value);
        const idr = usd * exchangeRate;
        priceIdrDisplay.value = formatNumberId(idr);
        
        // price_per_unit_hidden MUST be in buyCurrency
        if (buyCurrency === 'usd' || buyCurrency === 'usdt') {
            priceIdrHidden.value = usd;
        } else {
            priceIdrHidden.value = idr;
        }
    } else if (triggerSource === 'idr') {
        const idr = parseLoc(priceIdrDisplay.value);
        if (buyCurrency === 'usd' || buyCurrency === 'usdt') {
            const usd = idr / exchangeRate;
            priceUsdInput.value = formatNumberId(usd);
            priceIdrHidden.value = usd;
        } else {
            // Bought with IDR
            priceIdrHidden.value = idr;
        }
    }

    // Auto-calculate logic
    if (triggerSource === 'total' || triggerSource === 'idr' || triggerSource === 'usd' || triggerSource === 'rate') {
        // Calculate Quantity from Total Amount
        if (buyTotalAmount > 0) {
            let currentPrice = 0;
            if (buyCurrency === 'usd' || buyCurrency === 'usdt') {
                currentPrice = parseLoc(priceUsdInput.value);
            } else {
                currentPrice = parseLoc(priceIdrDisplay.value);
            }
            
            if (currentPrice > 0) {
                quantityInput.value = (buyTotalAmount / currentPrice).toFixed(8);
            }
        }
    } else if (triggerSource === 'quantity') {
        // Calculate Total Amount from Quantity
        let currentPrice = 0;
        if (buyCurrency === 'usd' || buyCurrency === 'usdt') {
            currentPrice = parseLoc(priceUsdInput.value);
        } else {
            currentPrice = parseLoc(priceIdrDisplay.value);
        }

        if (currentPrice > 0 && quantity > 0) {
            const total = quantity * currentPrice;
            buyTotalAmountInput.value = formatNumberId(total);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var swalBaseConfig = {
        customClass: { popup: 'swal2-popup', title: 'swal2-title', confirmButton: 'swal2-confirm', cancelButton: 'swal2-cancel' },
        buttonsStyling: false
    };

    function getSwalConfig(overrides) {
        var config = JSON.parse(JSON.stringify(swalBaseConfig));
        if (overrides) {
            for (var key in overrides) {
                if (key === 'customClass') {
                    for (var subKey in overrides[key]) config.customClass[subKey] = overrides[key][subKey];
                } else { config[key] = overrides[key]; }
            }
        }
        return config;
    }

    // Success Alerts
    <?php if (isset($_SESSION['success'])): ?>
    Swal.fire(getSwalConfig({ title: 'Berhasil!', text: <?= json_encode($_SESSION['success']) ?>, icon: 'success' })).then(function() {
        window.location.href = 'index.php';
    });
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    // Error Alerts
    <?php if (isset($_SESSION['error'])): ?>
    Swal.fire(getSwalConfig({ title: 'Gagal!', text: <?= json_encode($_SESSION['error']) ?>, icon: 'error' }));
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    // Add listener for quantity field
    document.getElementById('trans_quantity').addEventListener('input', function() {
        syncAssetForm('quantity');
    });

    // Currency Input Handling
    document.querySelectorAll('.currency-input').forEach(function(input) {
        input.addEventListener('input', function() {
            var val = this.value;
            
            // Prevent replacing while user types a comma
            if (val.endsWith(',')) return;
            
            var num = parseLoc(val);
            
            // Intelligent formatting for display
            if (val !== '') {
                if (!val.includes(',')) {
                    this.value = num.toLocaleString('id-ID'); // integers
                } else {
                    let parts = val.split(',');
                    let intPart = parseLoc(parts[0]);
                    let decPart = parts[1].replace(/[^0-9]/g, '');
                    this.value = intPart.toLocaleString('id-ID') + ',' + decPart;
                }
            }
            
            // Sync logic
            if (this.id === 'price_per_unit_display') {
                syncAssetForm('idr');
            } else if (this.id === 'price_usd') {
                syncAssetForm('usd');
            } else if (this.id === 'trans_exchange_rate') {
                syncAssetForm('rate');
            } else if (this.id === 'buy_total_amount') {
                syncAssetForm('total');
            }
            
            // Converter Logic (Independent)
            if (this.id === 'val_usd' || this.id === 'val_idr' || this.id === 'exchange_rate') {
                var rate = parseLoc(document.getElementById('exchange_rate').value);
                if (this.id === 'val_usd') {
                    document.getElementById('val_idr').value = formatNumberId(parseLoc(this.value) * rate);
                } else if (this.id === 'val_idr') {
                    document.getElementById('val_usd').value = rate > 0 ? formatNumberId(parseLoc(this.value) / rate) : '0';
                }
            }
            
            if (this.id === 'calc_price') updateCalc();
        });
    });

    if (document.getElementById('calc_unit')) document.getElementById('calc_unit').addEventListener('input', updateCalc);
    function updateCalc() {
        var unit = parseFloat(document.getElementById('calc_unit').value) || 0;
        var price = parseLoc(document.getElementById('calc_price').value);
        document.getElementById('calc_total').textContent = 'Rp ' + (unit * price).toLocaleString('id-ID');
    }

    // Purchase Currency Toggle
    const buyCurrency = document.getElementById('buy_currency');
    if (buyCurrency) {
        buyCurrency.addEventListener('change', function() {
            const val = this.value;
            const isUsd = val === 'usd';
            const isUsdt = val === 'usdt';
            
            document.getElementById('usd_fields').style.display = (isUsd || isUsdt) ? 'block' : 'none';
            document.getElementById('buy_amount_label').textContent = val.toUpperCase();
            
            // Update pricing labels
            const priceLabel = document.querySelector('label[for="price_usd"]') || document.getElementById('price_usd').previousElementSibling;
            if (priceLabel) priceLabel.textContent = `Harga per Unit (${val.toUpperCase()})`;
            
            // Trigger auto price update if toggle is on
            const toggle = document.getElementById('auto_price_toggle');
            if (toggle && toggle.checked) {
                toggle.onchange();
            }
        });
    }

    // Asset Search Logic
    let allSupportedAssets = [];
    const searchInput = document.getElementById('asset_search_input');
    const resultsContainer = document.getElementById('search_results');

    // Auto set currency to USD for crypto
    const assetTypeSelect = document.getElementById('add_asset_type');
    if (assetTypeSelect) {
        assetTypeSelect.addEventListener('change', function() {
            const currencySelect = document.querySelector('select[name="currency"]');
            if (this.value === 'crypto') {
                currencySelect.value = 'USD';
            } else if (this.value === 'stock' || this.value === 'reksadana') {
                currencySelect.value = 'IDR';
            }
        });
    }

    // Fetch assets when modal is shown or once
    fetch('get_supported_assets.php')
        .then(r => r.json())
        .then(data => { allSupportedAssets = data; });

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (query.length < 2) {
            resultsContainer.style.display = 'none';
            return;
        }

        const filtered = allSupportedAssets.filter(a => 
            a.name.toLowerCase().includes(query) || a.symbol.toLowerCase().includes(query)
        ).slice(0, 10);

        if (filtered.length > 0) {
            resultsContainer.innerHTML = filtered.map(a => `
                <button type="button" class="list-group-item list-group-item-action py-3 border-0" onclick="selectAsset('${a.name}', '${a.symbol}', '${a.type}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold">${a.name}</span>
                            <span class="badge bg-light text-dark ms-2">${a.symbol}</span>
                        </div>
                        <span class="badge ${a.type === 'crypto' ? 'bg-warning text-dark' : 'bg-primary'}">${a.type.toUpperCase()}</span>
                    </div>
                </button>
            `).join('');
            resultsContainer.style.display = 'block';
        } else {
            resultsContainer.style.display = 'none';
        }
    });

    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
});

function selectAsset(name, symbol, type) {
    document.getElementById('add_asset_name').value = name;
    document.getElementById('add_asset_symbol').value = symbol;
    document.getElementById('add_asset_type').value = type;
    document.getElementById('search_results').style.display = 'none';
    document.getElementById('asset_search_input').value = name;
    
    // Auto focus quantity if exists or just give visual feedback
    document.getElementById('add_asset_name').classList.add('bg-light');
    setTimeout(() => document.getElementById('add_asset_name').classList.remove('bg-light'), 500);
}

function editAsset(id, name, type, symbol, currency) {
    document.getElementById('edit_asset_id').value = id;
    document.getElementById('edit_asset_name_input').value = name;
    document.getElementById('edit_asset_type_input').value = type;
    document.getElementById('edit_asset_symbol_input').value = symbol;
    document.getElementById('edit_asset_currency_input').value = currency || 'IDR';
    new bootstrap.Modal(document.getElementById('editAssetModal')).show();
}

function deleteAsset(id, name) {
    Swal.fire({
        title: 'Hapus Aset?',
        html: 'Hapus <strong>' + name + '</strong>?<br><small class="text-danger">Seluruh riwayat transaksi akan hilang!</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm bg-danger', cancelButton: 'swal2-cancel' },
        buttonsStyling: false
    }).then(function(result) {
        if (result.isConfirmed) window.location.href = 'delete.php?id=' + id;
    });
}

function openTransactionModal(id, name, symbol, type, assetCurrency) {
    const modal = document.getElementById('addTransactionModal');
    document.getElementById('transaction_asset_id').value = id;
    document.getElementById('transaction_asset_name').value = name;
    document.getElementById('transaction_type').value = type;
    document.getElementById('transaction_asset_currency').value = assetCurrency || 'IDR';
    
    // Reset fields
    document.getElementById('buy_total_amount').value = '';
    document.getElementById('trans_quantity').value = '';
    document.getElementById('price_per_unit_display').value = '';
    document.getElementById('price_per_unit_hidden').value = '';
    document.getElementById('price_per_unit_display').readOnly = false;
    document.getElementById('auto_price_toggle').checked = false;
    document.getElementById('auto_price_container').style.display = 'none';

    const buyCurrencySelect = document.getElementById('buy_currency');
    const n = name.toLowerCase();
    const s = (symbol || '').toUpperCase();
    
    // WORKFLOW LOCK:
    // 1. If buying USDT or USD or Fiat, use IDR only
    if (n.includes('tether') || s === 'USDT' || n.includes('dollar') || s === 'USD') {
        buyCurrencySelect.value = 'idr';
        Array.from(buyCurrencySelect.options).forEach(opt => {
            opt.disabled = opt.value !== 'idr';
        });
        document.getElementById('buy_amount_container').style.display = 'block';
    } 
    // 2. If buying other CRYPTO, enforce USDT
    else if (assetCurrency === 'USD' || assetCurrency === 'EUR' || s !== '') {
        buyCurrencySelect.value = 'usdt';
        Array.from(buyCurrencySelect.options).forEach(opt => {
            opt.disabled = opt.value !== 'usdt';
        });
        // Check if user has USDT
        const usdtAsset = <?= json_encode(array_values(array_filter($assets, function($a) { 
            $n = strtolower($a['name']);
            $s = strtoupper($a['symbol']);
            return strpos($n, 'tether') !== false || $s === 'USDT';
        }))) ?>;
        
        const usdtBalance = usdtAsset.length > 0 ? parseFloat(usdtAsset[0].total_quantity) : 0;
        
        if (type === 'buy') {
            document.getElementById('buy_amount_container').innerHTML = `
                <label class="form-label fw-bold text-primary">Saldo USDT Anda: ${formatNumberId(usdtBalance)} USDT</label>
                <input type="text" id="buy_total_amount" class="form-control currency-input" placeholder="Total USDT yang digunakan">
                <small class="text-info">Pembelian crypto wajib menggunakan USDT.</small>
            `;
            
            // Re-bind currency input handling for the new element
            const newTotalInput = document.getElementById('buy_total_amount');
            newTotalInput.addEventListener('input', function() {
                const val = parseLoc(this.value);
                if (val > usdtBalance) {
                    this.classList.add('is-invalid');
                    Swal.fire({
                        title: 'Saldo USDT Kurang!',
                        text: `Saldo USDT Anda hanya ${formatNumberId(usdtBalance)}. Silakan beli USDT terlebih dahulu dengan Rupiah.`,
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                } else {
                    this.classList.remove('is-invalid');
                }
                syncAssetForm('total');
            });
        }
    } else {
        buyCurrencySelect.value = 'idr';
        Array.from(buyCurrencySelect.options).forEach(opt => {
            opt.disabled = false;
        });
    }

    // Trigger change event to show/hide USD fields and update labels
    buyCurrencySelect.dispatchEvent(new Event('change'));

    // Try to guess symbol from name if empty
    if (!symbol) {
        const n = name.toLowerCase();
        if (n.includes('bitcoin')) symbol = 'BTC';
        else if (n.includes('ethereum')) symbol = 'ETH';
        else if (n.includes('tether') || n.includes('usdt')) symbol = 'USDT';
        else if (n.includes('bittensor') || n.includes('tao')) symbol = 'TAO';
        else if (n.includes('dollar') || n.includes('usd')) symbol = 'USD';
    }
    
    // Normalize symbol
    symbol = symbol.replace(/[^A-Za-z]/g, '').toUpperCase();
    
    // Auto populate exchange rate from API if available
    if (exchangeRates['USD']) {
        const rateToIdr = 1 / exchangeRates['USD'];
        document.getElementById('trans_exchange_rate').value = formatNumberId(rateToIdr);
    }
    
    // Check if price exists in embedded exchangeRates (from CurrencyService)
    if (symbol && exchangeRates[symbol]) {
        document.getElementById('auto_price_container').style.display = 'block';
        const toggle = document.getElementById('auto_price_toggle');
        const currentPrice = 1 / exchangeRates[symbol];
        
        toggle.onchange = function() {
            const priceDisplay = document.getElementById('price_per_unit_display');
            const priceHidden = document.getElementById('price_per_unit_hidden');
            const buyCurrency = document.getElementById('buy_currency').value;
            
            if (this.checked) {
                const priceIdr = currentPrice;
                priceDisplay.value = formatNumberId(priceIdr);
                priceHidden.value = priceIdr;
                priceDisplay.readOnly = true;
                
                if (buyCurrency === 'usd' || buyCurrency === 'usdt') {
                    const rateStr = document.getElementById('trans_exchange_rate').value;
                    const rate = parseLoc(rateStr) || 16000;
                    document.getElementById('price_usd').value = formatNumberId(currentPrice / rate);
                }
                syncAssetForm('idr');
            } else {
                priceDisplay.readOnly = false;
                priceDisplay.value = '';
                priceHidden.value = '';
            }
        };
    }

    new bootstrap.Modal(modal).show();
}

function viewTransactions(assetId, assetName, assetCurrency) {
    var modal = new bootstrap.Modal(document.getElementById('transactionsModal'));
    var contentDiv = document.getElementById('transactionsList');
    contentDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Memuat...</p></div>';
    modal.show();
    
    fetch('transactions/get.php?asset_id=' + assetId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.transactions && data.transactions.length > 0) {
                var html = '<div class="list-group">';
                data.transactions.forEach(function(t) {
                    var isBuy = t.type === 'buy';
                    var symbol = assetCurrency === 'USD' ? '$' : (assetCurrency === 'EUR' ? '€' : 'Rp');
                    html += '<div class="list-group-item d-flex justify-content-between align-items-center border-0 mb-2 rounded-3" style="background: ' + (isBuy ? '#f0fdf4' : '#fef2f2') + '">';
                    html += '<div><div class="fw-bold ' + (isBuy ? 'text-success' : 'text-danger') + '">' + (isBuy ? 'BELI' : 'JUAL') + '</div><small class="text-muted">' + t.transaction_date + '</small></div>';
                    html += '<div class="text-end"><div class="fw-bold">' + parseFloat(t.quantity).toLocaleString('id-ID', {maximumFractionDigits: 8}) + ' unit</div><small>@ ' + symbol + ' ' + parseFloat(t.price_per_unit).toLocaleString('id-ID', {maximumFractionDigits: 2}) + '</small></div></div>';
                });
                html += '</div>';
                contentDiv.innerHTML = html;
            } else {
                contentDiv.innerHTML = '<div class="text-center py-4 text-muted">Belum ada transaksi.</div>';
            }
        });
}

function formatRupiah(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(n); }
</script>

<?php include '../../includes/footer.php'; ?>