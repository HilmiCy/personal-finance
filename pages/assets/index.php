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
    /* ========== LAYOUT UTAMA ========== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        overflow-x: hidden !important;
        width: 100% !important;
        position: relative;
    }
    
    .wrapper {
        display: flex !important;
        width: 100% !important;
        align-items: stretch !important;
        overflow-x: hidden !important;
    }
    
    #sidebar {
        min-width: 250px !important;
        max-width: 250px !important;
        width: 250px !important;
        transition: all 0.3s;
        flex-shrink: 0 !important;
        background: #2c3e50;
        color: #fff;
    }
    
    #content, .main-content {
        width: calc(100% - 250px) !important;
        min-height: 100vh !important;
        transition: all 0.3s;
        overflow-x: hidden !important;
        flex: 1 !important;
        background: #f8f9fa;
    }
    
    .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        padding: 20px !important;
        margin: 0 !important;
        overflow-x: hidden !important;
    }
    
    @media (max-width: 768px) {
        #sidebar {
            margin-left: -250px !important;
            position: fixed !important;
            z-index: 1000 !important;
            height: 100vh !important;
        }
        
        #sidebar.active {
            margin-left: 0 !important;
        }
        
        #content, .main-content {
            width: 100% !important;
        }
        
        .container-fluid {
            padding: 15px !important;
        }
    }
    
    /* ========== WELCOME CARD ========== */
    .welcome-card {
    background: linear-gradient(135deg, #FFFFFF 0%, #FFFFFF 100%);
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 24px;
    color: white;
    position: relative;
    overflow: hidden;
    width: 100%;

    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
    
    .welcome-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    /* ========== SUMMARY CARDS ========== */
    .summary-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        height: 100%;
    }
    
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 55px;
        height: 55px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
    }
    
    .stat-title {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: 800;
        color: #1f2937;
    }
    
    .profit-positive {
        color: #10b981 !important;
    }
    
    .profit-negative {
        color: #ef4444 !important;
    }
    
    /* ========== ASSET CARDS ========== */
    .asset-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    
    .asset-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    
    .asset-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
    }
    
    .asset-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }
    
    .asset-info {
        flex: 1;
    }
    
    .asset-name {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        display: inline-block;
    }
    
    .asset-symbol {
        font-size: 12px;
        color: #9ca3af;
        margin-left: 6px;
    }
    
    .asset-type {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 8px;
    }
    
    .asset-type.crypto { 
        background: #fef3c7; 
        color: #d97706; 
    }
    .asset-type.stock { 
        background: #d1fae5; 
        color: #059669; 
    }
    .asset-type.gold { 
        background: #fed7aa; 
        color: #c2410c; 
    }
    .asset-type.reksadana { 
        background: #e0e7ff; 
        color: #4f46e5; 
    }
    .asset-type.other { 
        background: #f3f4f6; 
        color: #6b7280; 
    }
    
    /* Asset Stats Grid */
    .asset-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin: 16px 0;
        padding: 12px 0;
        border-top: 1px solid #f0f0f0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-label {
        font-size: 11px;
        color: #9ca3af;
        margin-bottom: 6px;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .stat-value {
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
    }
    
    /* Action Buttons */
    .asset-actions {
        display: flex;
        gap: 10px;
        margin-top: 16px;
    }
    
    .btn-buy {
        flex: 1;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .btn-buy:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        color: white;
    }
    
    .btn-sell {
        flex: 1;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .btn-sell:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        color: white;
    }
    
    .btn-sell:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    /* Dropdown Button */
    .dropdown-toggle-icon {
        background: transparent;
        border: none;
        color: #9ca3af;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .dropdown-toggle-icon:hover {
        background: #f3f4f6;
        color: #4b5563;
    }
    
    .dropdown-menu {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        padding: 8px;
        min-width: 160px;
    }
    
    .dropdown-item {
        border-radius: 8px;
        padding: 8px 14px;
        font-size: 13px;
        transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
        background: #f3f4f6;
        transform: translateX(3px);
    }
    
    .dropdown-item.text-danger:hover {
        background: #fee2e2;
    }
    
    /* Profit/Loss Badge */
    .profit-loss {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        background: #f9fafb;
    }
    
    .profit-loss.positive {
        color: #10b981;
    }
    
    .profit-loss.negative {
        color: #ef4444;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .empty-state i {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
    }
    
    .empty-state p {
        color: #6b7280;
        margin-bottom: 20px;
    }
    
    /* Modal Styles */
    .modal-content-custom {
        background: white;
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }
    
    .modal-header-custom {
        border-bottom: 1px solid #e5e7eb;
        padding: 20px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .modal-header-custom .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .modal-body-custom {
        padding: 24px;
    }
    
    .modal-footer-custom {
        border-top: 1px solid #e5e7eb;
        padding: 20px 24px;
        background: #f9fafb;
    }
    
    /* Form Controls */
    .form-control, .form-select {
        border-radius: 12px !important;
        border: 1px solid #e5e7eb !important;
        padding: 12px 16px !important;
        transition: all 0.2s ease !important;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-text {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 5px;
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-secondary-custom {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        color: #4b5563;
        transition: all 0.3s ease;
    }
    
    .btn-secondary-custom:hover {
        background: #e5e7eb;
        color: #1f2937;
    }
    
    .btn-add-asset {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-add-asset:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    /* Transaction List */
    .transaction-list {
        max-height: 450px;
        overflow-y: auto;
    }
    
    .transaction-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 10px;
        transition: all 0.2s ease;
    }
    
    .transaction-item:hover {
        transform: translateX(4px);
    }
    
    .transaction-buy {
        background: #d1fae5;
    }
    
    .transaction-sell {
        background: #fee2e2;
    }
    
    /* Alert */
    .alert-info {
        background: #e0e7ff;
        border: none;
        border-radius: 12px;
        color: #4f46e5;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes iconPop {
        0% {
            transform: scale(0);
            opacity: 0;
        }
        80% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    
    .animated {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    
    /* SweetAlert2 Professional Style */
    .swal2-popup {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 24px !important;
        padding: 2em !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: fadeInScale 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .swal2-title {
        color: #1f2937 !important;
        font-weight: 700 !important;
        font-size: 1.5rem !important;
    }
    
    .swal2-html-container {
        color: #4b5563 !important;
        font-size: 0.95rem !important;
    }
    
    .swal2-confirm {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        border: none !important;
        transition: all 0.3s ease !important;
    }
    
    .swal2-confirm:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
    }
    
    .swal2-cancel {
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        background: rgba(107, 114, 128, 0.1) !important;
        color: #6b7280 !important;
        border: 1px solid rgba(107, 114, 128, 0.2) !important;
        transition: all 0.3s ease !important;
    }
    
    .swal2-cancel:hover {
        background: rgba(107, 114, 128, 0.2) !important;
        transform: translateY(-2px) !important;
    }
    
    .swal2-icon {
        animation: iconPop 0.5s ease !important;
    }
    
    .swal2-icon.swal2-warning {
        border-color: #f59e0b !important;
        color: #f59e0b !important;
    }
    
    .swal2-icon.swal2-success {
        border-color: #10b981 !important;
    }
    
    .swal2-icon.swal2-error {
        border-color: #ef4444 !important;
    }
    
    .swal2-loader {
        border-color: #667eea !important;
        border-top-color: transparent !important;
        animation: spin 0.8s linear infinite !important;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 15px !important;
        }
        
        .welcome-title {
            font-size: 1.2rem !important;
        }
        
        .welcome-subtitle {
            font-size: 0.8rem !important;
        }
        
        .stat-number {
            font-size: 20px !important;
        }
        
        .asset-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .asset-actions {
            flex-direction: column;
        }
        
        .btn-buy, .btn-sell {
            width: 100%;
        }
    }
    
    @media (max-width: 576px) {
        .asset-name {
            font-size: 16px;
        }
        
        .stat-value {
            font-size: 13px;
        }
        
        .profit-loss {
            font-size: 11px;
        }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Portofolio Aset</h1>
                    <p class="welcome-subtitle">Kelola investasi crypto, saham, emas, dan lainnya</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="btn-add-asset" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="fas fa-plus"></i> Tambah Aset
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="summary-card animated" style="animation-delay: 0.1s">
                    <div class="stat-icon" style="background: #d1fae5;">
                        <i class="fas fa-chart-line" style="color: #10b981; font-size: 26px;"></i>
                    </div>
                    <div class="stat-title">Total Aset</div>
                    <div class="stat-number"><?= $summary['total_assets'] ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="summary-card animated" style="animation-delay: 0.2s">
                    <div class="stat-icon" style="background: #e0e7ff;">
                        <i class="fas fa-wallet" style="color: #667eea; font-size: 26px;"></i>
                    </div>
                    <div class="stat-title">Total Investasi</div>
                    <div class="stat-number"><?= formatRupiah($summary['total_investment']) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="summary-card animated" style="animation-delay: 0.3s">
                    <div class="stat-icon" style="background: #fee2e2;">
                        <i class="fas fa-chart-pie" style="color: #ef4444; font-size: 26px;"></i>
                    </div>
                    <div class="stat-title">Nilai Saat Ini</div>
                    <div class="stat-number"><?= formatRupiah($summary['total_value']) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="summary-card animated" style="animation-delay: 0.4s">
                    <div class="stat-icon" style="background: <?= $summary['profit_loss'] >= 0 ? '#d1fae5' : '#fee2e2' ?>;">
                        <i class="fas fa-chart-simple" style="color: <?= $summary['profit_loss'] >= 0 ? '#10b981' : '#ef4444' ?>; font-size: 26px;"></i>
                    </div>
                    <div class="stat-title">Profit / Loss</div>
                    <div class="stat-number <?= $summary['profit_loss'] >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                        <?= formatRupiah($summary['profit_loss']) ?>
                        <small>(<?= number_format($summary['profit_loss_percent'], 2) ?>%)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assets List -->
        <div class="row g-4">
            <?php if (count($assets) > 0): ?>
                <?php $index = 0; foreach ($assets as $a): 
                    $index++;
                    $current_value = $a['total_quantity'] * $a['current_price'];
                    $profit_loss = ($current_value + $a['total_sell']) - $a['total_buy'];
                    $profit_percent = $a['total_buy'] > 0 ? ($profit_loss / $a['total_buy']) * 100 : 0;        
                    $typeClass = strtolower($a['type']);
                ?>
                <div class="col-md-6 col-lg-4" style="animation: fadeInUp 0.5s ease <?= $index * 0.05 ?>s both;">
                    <div class="asset-card">
                        <div class="asset-header">
                            <div class="asset-info">
                                <div>
                                    <span class="asset-name"><?= htmlspecialchars($a['name']) ?></span>
                                    <?php if ($a['symbol']): ?>
                                        <span class="asset-symbol">(<?= htmlspecialchars($a['symbol']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <span class="asset-type <?= $typeClass ?>">
                                    <?= ucfirst($a['type']) ?>
                                </span>
                            </div>
                            <div class="dropdown">
                                <button class="dropdown-toggle-icon" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item" onclick="editAsset(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', '<?= $a['type'] ?>', '<?= htmlspecialchars($a['symbol']) ?>')">
                                            <i class="fas fa-edit me-2"></i> Edit Aset
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" onclick="viewTransactions(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>')">
                                            <i class="fas fa-history me-2"></i> Lihat Transaksi
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deleteAsset(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>')">
                                            <i class="fas fa-trash me-2"></i> Hapus Aset
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="asset-stats">
                            <div class="stat-item">
                                <div class="stat-label">Jumlah</div>
                                <div class="stat-value"><?= number_format($a['total_quantity'], 4) ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Harga Saat Ini</div>
                                <div class="stat-value"><?= formatRupiah($a['current_price']) ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Total Investasi</div>
                                <div class="stat-value"><?= formatRupiah($a['total_buy']) ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Nilai Saat Ini</div>
                                <div class="stat-value"><?= formatRupiah($current_value) ?></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="profit-loss <?= $profit_loss >= 0 ? 'positive' : 'negative' ?>">
                                <i class="fas fa-<?= $profit_loss >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= formatRupiah(abs($profit_loss)) ?> (<?= number_format(abs($profit_percent), 2) ?>%)
                            </div>
                        </div>
                        
                        <div class="asset-actions">
                            <button class="btn-buy" onclick="openTransactionModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', <?= $a['current_price'] ?>, 'buy')">
                                <i class="fas fa-plus-circle"></i> Beli
                            </button>
                            <button class="btn-sell" onclick="openTransactionModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>', <?= $a['current_price'] ?>, 'sell')" <?= $a['total_quantity'] <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-minus-circle"></i> Jual
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state animated" style="animation-delay: 0.2s">
                        <i class="fas fa-chart-line"></i>
                        <p>Belum ada aset. Mulai investasi dengan menambahkan aset pertama Anda!</p>
                        <button class="btn-add-asset mt-2" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                            <i class="fas fa-plus"></i> Tambah Aset Sekarang
                        </button>
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
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Aset Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Aset</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Bitcoin, Apple Inc, Emas Antam" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Aset</label>
                        <select name="type" class="form-select" required>
                            <option value="crypto">💎 Crypto Currency</option>
                            <option value="stock">📈 Saham</option>
                            <option value="gold">🥇 Emas</option>
                            <option value="reksadana">📊 Reksadana</option>
                            <option value="other">📦 Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Symbol (Opsional)</label>
                        <input type="text" name="symbol" class="form-control" placeholder="Contoh: BTC, AAPL, GOLD">
                        <div class="form-text">Kode/singkatan untuk memudahkan identifikasi</div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Simpan Aset
                    </button>
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
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit Aset
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAssetForm" action="edit.php" method="POST">
                <input type="hidden" name="id" id="edit_asset_id">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Aset</label>
                        <input type="text" name="name" id="edit_asset_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Aset</label>
                        <select name="type" id="edit_asset_type" class="form-select" required>
                            <option value="crypto">💎 Crypto Currency</option>
                            <option value="stock">📈 Saham</option>
                            <option value="gold">🥇 Emas</option>
                            <option value="reksadana">📊 Reksadana</option>
                            <option value="other">📦 Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Symbol</label>
                        <input type="text" name="symbol" id="edit_asset_symbol" class="form-control">
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Update Aset
                    </button>
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
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exchange-alt me-2"></i> Tambah Transaksi Aset
                </h5>
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
                        <label class="form-label fw-bold">Tipe Transaksi</label>
                        <select name="type" class="form-select" required>
                            <option value="buy">📥 Beli (Buy)</option>
                            <option value="sell">📤 Jual (Sell)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah</label>
                        <input type="text" name="quantity" class="form-control number-input" placeholder="0" required>
                        <div class="form-text">Jumlah unit/gram/keping yang dibeli/dijual</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Harga per Unit (Rp)</label>
                        <input type="text" name="price_per_unit" class="form-control currency-input" placeholder="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Total harga akan dihitung otomatis: Jumlah × Harga per Unit
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Simpan Transaksi
                    </button>
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
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-history me-2"></i> Riwayat Transaksi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modal-body-custom" id="transactionsList">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data transaksi...</p>
                </div>
            </div>
            <div class="modal-footer modal-footer-custom">
                <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function editAsset(id, name, type, symbol) {
        document.getElementById('edit_asset_id').value = id;
        document.getElementById('edit_asset_name').value = name;
        document.getElementById('edit_asset_type').value = type;
        document.getElementById('edit_asset_symbol').value = symbol;
        
        var modal = new bootstrap.Modal(document.getElementById('editAssetModal'));
        modal.show();
    }
    
    function deleteAsset(id, name) {
        Swal.fire({
            title: 'Hapus Aset?',
            html: `Apakah Anda yakin ingin menghapus aset <strong>"${escapeHtml(name)}"</strong>?<br><small style="color: #ef4444;">⚠️ Semua transaksi terkait akan ikut terhapus!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Sedang menghapus aset',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                setTimeout(() => {
                    window.location.href = 'delete.php?id=' + id;
                }, 500);
            }
        });
    }
    
    function openTransactionModal(assetId, assetName, currentPrice, type) {
        document.getElementById('transaction_asset_id').value = assetId;
        document.getElementById('transaction_asset_name').value = assetName;
        
        const typeSelect = document.querySelector('#addTransactionForm select[name="type"]');
        if (typeSelect) {
            typeSelect.value = type;
        }
        
        const priceInput = document.querySelector('#addTransactionForm input[name="price_per_unit"]');
        if (priceInput && currentPrice > 0) {
            priceInput.value = formatNumber(currentPrice);
        }
        
        if (type === 'sell') {
            const qtyInput = document.querySelector('#addTransactionForm input[name="quantity"]');
            if (qtyInput) {
                qtyInput.placeholder = 'Masukkan jumlah yang akan dijual';
            }
        } else {
            const qtyInput = document.querySelector('#addTransactionForm input[name="quantity"]');
            if (qtyInput) {
                qtyInput.placeholder = '0';
            }
        }
        
        var modal = new bootstrap.Modal(document.getElementById('addTransactionModal'));
        modal.show();
    }
    
    function viewTransactions(assetId, assetName) {
        const modal = new bootstrap.Modal(document.getElementById('transactionsModal'));
        const contentDiv = document.getElementById('transactionsList');
        
        contentDiv.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat transaksi untuk ${escapeHtml(assetName)}...</p>
            </div>
        `;
        
        modal.show();
        
        fetch(`transactions/get.php?asset_id=${assetId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.transactions && data.transactions.length > 0) {
                    let html = `
                        <div class="mb-3">
                            <h6 class="fw-bold">
                                <i class="fas fa-history"></i> Riwayat Transaksi - ${escapeHtml(assetName)}
                            </h6>
                            <span class="badge bg-secondary">${data.transactions.length} transaksi</span>
                            <hr>
                        </div>
                        <div class="transaction-list">
                    `;
                    
                    data.transactions.forEach((trans) => {
                        const isBuy = trans.type === 'buy';
                        const bgClass = isBuy ? 'transaction-buy' : 'transaction-sell';
                        const textColor = isBuy ? '#10b981' : '#ef4444';
                        const icon = isBuy ? 'fa-arrow-down' : 'fa-arrow-up';
                        const label = isBuy ? 'BELI' : 'JUAL';
                        
                        let transactionDate = trans.transaction_date;
                        if (transactionDate) {
                            const date = new Date(transactionDate);
                            transactionDate = date.toLocaleDateString('id-ID', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                            });
                        }
                        
                        html += `
                            <div class="transaction-item ${bgClass}">
                                <div>
                                    <span class="fw-bold" style="color: ${textColor};">
                                        <i class="fas ${icon}"></i> ${label}
                                    </span>
                                    <div class="small text-muted mt-1">
                                        <i class="far fa-calendar-alt"></i> ${transactionDate}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">${formatNumber(trans.quantity)} unit</div>
                                    <div class="small">@ ${formatRupiah(trans.price_per_unit)}</div>
                                    <div class="fw-bold mt-1" style="color: ${textColor}">
                                        Total: ${formatRupiah(trans.total_price)}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `</div>`;
                    contentDiv.innerHTML = html;
                } else {
                    contentDiv.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada transaksi untuk aset ini</p>
                            <button class="btn-primary-custom btn-sm" onclick="openTransactionModal(${assetId}, '${escapeHtml(assetName)}', 0, 'buy')">
                                <i class="fas fa-plus"></i> Tambah Transaksi Pertama
                            </button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="fas fa-exclamation-circle"></i> Gagal memuat transaksi: ${error.message}
                    </div>
                `;
            });
    }
    
    function formatNumber(number) {
        if (isNaN(number) || number === null || number === undefined) return '0';
        if (number % 1 !== 0 && number < 1000) {
            return number.toLocaleString('id-ID', {
                minimumFractionDigits: 4,
                maximumFractionDigits: 8
            });
        }
        return Math.floor(number).toLocaleString('id-ID');
    }
    
    function formatRupiah(number) {
        if (isNaN(number) || number === null) return 'Rp 0';
        return 'Rp ' + formatNumber(number);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Format currency and number inputs
    document.querySelectorAll('.currency-input, .number-input').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                let number = parseInt(value);
                this.value = new Intl.NumberFormat('id-ID').format(number);
            } else {
                this.value = '';
            }
        });
    });
    
    // Success message handling
    <?php if (isset($_SESSION['success_message'])): ?>
    Swal.fire({
        title: 'Berhasil!',
        text: '<?= $_SESSION['success_message'] ?>',
        icon: 'success',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'OK',
        didOpen: () => {
            canvasConfetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    }).then(() => {
        window.location.href = 'index.php';
    });
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    Swal.fire({
        title: 'Gagal!',
        text: '<?= $_SESSION['error_message'] ?>',
        icon: 'error',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'OK'
    });
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>