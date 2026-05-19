<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Asset.php';
require_once '../../classes/AssetTransaction.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Portofolio Aset';
$current_page = 'assets';

$db = Database::getInstance()->getConnection();
$asset = new Asset();
$assetTransaction = new AssetTransaction();

// Get all assets
$assets = $asset->getAll($_SESSION['user_id']);
$summary = $asset->getPortfolioSummary($_SESSION['user_id']);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* ========== CARD STYLES ========== */
    .card { border-radius: 20px !important; border: none !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; transition: transform 0.2s, box-shadow 0.2s !important; margin-bottom: 20px !important; overflow: hidden !important; background: white !important; }
    
    /* ========== WELCOME CARD ========== */
    .welcome-card { background: #ffffff; border-radius: 20px; padding: 24px; margin-bottom: 24px; color: #1f2937; position: relative; overflow: hidden; width: 100%; border: 1px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
    .welcome-title { font-size: 1.6rem; font-weight: 700; margin: 0; color: #1f2937; }
    .welcome-subtitle { margin: 8px 0 0 0; color: #6b7280; font-size: 0.95rem; }
    
    /* Button Styles */
    .btn-primary-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white !important; border: none; padding: 10px 24px; border-radius: 12px; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
    
    .btn-action-minimal { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 20px; border-radius: 12px; font-weight: 600; color: #4b5563; transition: all 0.2s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-action-minimal:hover { background: #f3f4f6; color: #1f2937; transform: translateY(-1px); }

    /* ========== SUMMARY CARDS ========== */
    .summary-stats { background: white; border-radius: 20px; padding: 24px; text-align: center; border: 1px solid #e5e7eb; transition: all 0.3s ease; }
    .summary-stats:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
    .stat-icon-circle { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 20px; }
    .stat-title { font-size: 11px; color: #6b7280; margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; }
    .stat-number { font-size: 1.4rem; font-weight: 800; color: #1f2937; }
    
    /* ========== ASSET CARDS ========== */
    .asset-card { background: white; border-radius: 20px; padding: 24px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; position: relative; border: 1px solid #f3f4f6; }
    .asset-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08); border-color: #667eea; }
    .asset-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .asset-name { font-size: 1.1rem; font-weight: 700; color: #1f2937; margin-bottom: 2px; }
    .asset-type-pill { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px; }
    .type-crypto { background: #fef3c7; color: #d97706; }
    .type-stock { background: #d1fae5; color: #059669; }
    .type-gold { background: #fed7aa; color: #c2410c; }
    .type-reksadana { background: #e0e7ff; color: #4f46e5; }
    .type-currency { background: #e2e8f0; color: #475569; }
    
    .asset-stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; padding: 15px 0; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6; }
    .asset-stat-label { font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
    .asset-stat-value { font-size: 14px; font-weight: 700; color: #374151; }
    
    /* Action Buttons */
    .btn-buy-asset { flex: 1; background: #10b981; color: white; border: none; padding: 10px; border-radius: 12px; font-weight: 700; font-size: 12px; transition: all 0.2s ease; }
    .btn-sell-asset { flex: 1; background: #ef4444; color: white; border: none; padding: 10px; border-radius: 12px; font-weight: 700; font-size: 12px; transition: all 0.2s ease; }
    .btn-buy-asset:hover, .btn-sell-asset:hover:not(:disabled) { transform: translateY(-2px); opacity: 0.9; }

    .dropdown-minimal .btn-link { color: #9ca3af; padding: 0; font-size: 18px; text-decoration: none; }
    
    /* SweetAlert2 Professional Style */
    .swal2-popup { background: rgba(255, 255, 255, 0.98) !important; backdrop-filter: blur(20px) !important; border-radius: 24px !important; padding: 2em !important; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2) !important; }
    .swal2-title { color: #1f2937 !important; font-weight: 700 !important; }
    .swal2-confirm { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-radius: 12px !important; padding: 10px 24px !important; font-weight: 600 !important; color: white !important; }

    /* Modal Styling */
    .modal-content-custom { border-radius: 24px !important; border: none !important; box-shadow: 0 20px 60px rgba(0,0,0,0.15) !important; }
    .modal-header-custom { padding: 24px !important; border-bottom: 1px solid #f3f4f6 !important; }
    .modal-body-custom { padding: 32px !important; }
    .form-control, .form-select { border-radius: 12px !important; border: 1px solid #e5e7eb !important; padding: 12px 16px !important; }

    /* Responsive */
    @media (max-width: 768px) {
        #sidebar { margin-left: -250px !important; position: fixed !important; z-index: 1000 !important; height: 100vh !important; }
        #sidebar.active { margin-left: 0 !important; }
        #content, .main-content { width: 100% !important; }
        .container-fluid { padding: 16px !important; }
        .welcome-title { font-size: 1.3rem; }
        .btn-primary-custom, .btn-action-minimal { width: 100%; justify-content: center; margin-bottom: 10px; }
        .stat-number { font-size: 1.1rem; }
        .header-actions { display: flex; flex-direction: column; }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Portofolio Aset</h1>
                    <p class="welcome-subtitle">Kelola seluruh investasi Anda secara profesional</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0 header-actions">
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
                    <div class="stat-icon-circle" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">
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
                                        <button class="dropdown-item" onclick="editAsset(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', '<?= $a['type'] ?>', '<?= htmlspecialchars($a['symbol'] ?: '') ?>')">
                                            <i class="fas fa-pencil-alt me-2 text-primary"></i> Edit Aset
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" onclick="viewTransactions(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>')">
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
                                <div class="asset-stat-value"><?= formatRupiah($current_value) ?></div>
                            </div>
                            <div>
                                <div class="asset-stat-label">Modal</div>
                                <div class="asset-stat-value"><?= formatRupiah($a['total_buy']) ?></div>
                            </div>
                            <div>
                                <div class="asset-stat-label">Profit/Loss</div>
                                <div class="asset-stat-value <?= $profit_loss >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($profit_percent, 2) ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn-buy-asset" onclick="openTransactionModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', <?= $a['current_price'] ?>, 'buy')">
                                <i class="fas fa-plus-circle me-1"></i> BELI
                            </button>
                            <button class="btn-sell-asset" onclick="openTransactionModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', <?= $a['current_price'] ?>, 'sell')" <?= $a['total_quantity'] <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-minus-circle me-1"></i> JUAL
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="card p-5">
                        <i class="fas fa-chart-line fa-4x mb-3 opacity-20" style="color: #667eea;"></i>
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
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Aset</label>
                        <input type="text" name="name" id="add_asset_name" class="form-control" placeholder="Contoh: Bitcoin, Saham BBCA" required>
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
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mata Uang Pembelian</label>
                        <select id="buy_currency" class="form-select">
                            <option value="idr">IDR (Rupiah)</option>
                            <option value="usd">USD (Dollar)</option>
                        </select>
                    </div>
                    <div id="usd_fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kurs (1 USD ke IDR)</label>
                            <input type="text" id="trans_exchange_rate" class="form-control currency-input" value="16.000">
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

    function parseLoc(str) { return parseInt(str.replace(/[^0-9]/g, ''), 10) || 0; }

    // Currency Input Handling
    document.querySelectorAll('.currency-input').forEach(function(input) {
        input.addEventListener('input', function() {
            var num = parseLoc(this.value);
            if (this.id === 'price_per_unit_display') document.getElementById('price_per_unit_hidden').value = num;
            this.value = num > 0 ? num.toLocaleString('id-ID') : '';
            
            // Converter & USD Purchase Logic
            if (this.id === 'val_usd' || this.id === 'val_idr' || this.id === 'exchange_rate') {
                var rate = parseLoc(document.getElementById('exchange_rate').value);
                if (this.id === 'val_usd') {
                    document.getElementById('val_idr').value = (parseLoc(this.value) * rate).toLocaleString('id-ID');
                } else if (this.id === 'val_idr') {
                    document.getElementById('val_usd').value = rate > 0 ? Math.round(parseLoc(this.value) / rate).toLocaleString('id-ID') : '0';
                }
            }
            
            if (this.id === 'price_usd' || this.id === 'trans_exchange_rate') {
                var rate = parseLoc(document.getElementById('trans_exchange_rate').value);
                var usd = parseLoc(document.getElementById('price_usd').value);
                var idr = usd * rate;
                document.getElementById('price_per_unit_display').value = idr.toLocaleString('id-ID');
                document.getElementById('price_per_unit_hidden').value = idr;
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
    var buyCurrency = document.getElementById('buy_currency');
    if (buyCurrency) {
        buyCurrency.addEventListener('change', function() {
            document.getElementById('usd_fields').style.display = this.value === 'usd' ? 'block' : 'none';
        });
    }
});

function editAsset(id, name, type, symbol) {
    document.getElementById('edit_asset_id').value = id;
    document.getElementById('edit_asset_name_input').value = name;
    document.getElementById('edit_asset_type_input').value = type;
    document.getElementById('edit_asset_symbol_input').value = symbol;
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

function openTransactionModal(id, name, price, type) {
    document.getElementById('transaction_asset_id').value = id;
    document.getElementById('transaction_asset_name').value = name;
    document.getElementById('transaction_type').value = type;
    new bootstrap.Modal(document.getElementById('addTransactionModal')).show();
}

function viewTransactions(assetId, assetName) {
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
                    html += '<div class="list-group-item d-flex justify-content-between align-items-center border-0 mb-2 rounded-3" style="background: ' + (isBuy ? '#f0fdf4' : '#fef2f2') + '">';
                    html += '<div><div class="fw-bold ' + (isBuy ? 'text-success' : 'text-danger') + '">' + (isBuy ? 'BELI' : 'JUAL') + '</div><small class="text-muted">' + t.transaction_date + '</small></div>';
                    html += '<div class="text-end"><div class="fw-bold">' + parseFloat(t.quantity).toLocaleString('id-ID') + ' unit</div><small>@ Rp ' + parseInt(t.price_per_unit).toLocaleString('id-ID') + '</small></div></div>';
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