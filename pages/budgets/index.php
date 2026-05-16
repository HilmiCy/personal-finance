<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Budget.php';
require_once '../../classes/Category.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Manajemen Anggaran';
$current_page = 'budgets';

$db = Database::getInstance()->getConnection();
$budget = new Budget();
$category = new Category();

// Get current month and year
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get budgets for current month
$budgets = $budget->getByMonth($_SESSION['user_id'], $current_month, $current_year);

// Get all expense categories
$expense_categories = $category->getAll($_SESSION['user_id'], 'expense');

// Calculate totals
$total_budget = 0;
$total_spent = 0;
foreach ($budgets as $b) {
    $total_budget += $b['budget_amount'];
    $total_spent += $b['spent_amount'];
}
$remaining = $total_budget - $total_spent;

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
    
    /* ========== CARD STYLES ========== */
    .card {
        border-radius: 20px !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        margin-bottom: 20px !important;
        overflow: hidden !important;
        background: white !important;
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
        margin: 0;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    /* Button Styles */
    .btn {
        border-radius: 12px !important;
        padding: 10px 20px !important;
        font-weight: 500 !important;
        transition: all 0.2s !important;
    }
    
    .btn-primary-custom {
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
        text-decoration: none;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-secondary {
        background: rgba(107, 114, 128, 0.1);
        border: 1px solid rgba(107, 114, 128, 0.2);
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: rgba(107, 114, 128, 0.2);
        color: #4b5563;
    }
    
    .btn-add-budget {
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
    
    .btn-add-budget:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-edit-budget {
        background: #e0e7ff;
        color: #4f46e5;
        border: none;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-edit-budget:hover {
        background: #4f46e5;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-delete-budget {
        background: #fee2e2;
        color: #dc2626;
        border: none;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-delete-budget:hover {
        background: #dc2626;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-month {
        background: #f3f4f6;
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        color: #1f2937;
        display: inline-block;
    }
    
    .btn-month:hover {
        background: #e5e7eb;
        transform: translateY(-2px);
        color: #1f2937;
        text-decoration: none;
    }
    
    /* Budget Card */
    .budget-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 0;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        height: 100%;
    }
    
    .budget-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .budget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .budget-category {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .budget-category i {
        color: #667eea;
    }
    
    .budget-amounts {
        margin-bottom: 15px;
    }
    
    .budget-amount {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    
    .budget-label {
        font-size: 13px;
        color: #6b7280;
    }
    
    .budget-value {
        font-weight: 600;
    }
    
    .budget-value.primary {
        color: #667eea;
    }
    
    .budget-value.danger {
        color: #ef4444;
    }
    
    .budget-value.success {
        color: #10b981;
    }
    
    .progress {
        height: 8px;
        border-radius: 10px;
        background: #e5e7eb;
        margin-bottom: 12px;
    }
    
    .progress-bar {
        border-radius: 10px;
        transition: width 0.3s ease;
    }
    
    .progress-bar.safe {
        background: linear-gradient(90deg, #10b981, #34d399);
    }
    
    .progress-bar.warning {
        background: linear-gradient(90deg, #f59e0b, #fbbf24);
    }
    
    .progress-bar.danger {
        background: linear-gradient(90deg, #ef4444, #f87171);
    }
    
    .budget-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-safe {
        background: #d1fae5;
        color: #059669;
    }
    
    .status-warning {
        background: #fed7aa;
        color: #c2410c;
    }
    
    .status-danger {
        background: #fee2e2;
        color: #dc2626;
    }
    
    /* Summary Stats */
    .summary-stats {
        background: white;
        border-radius: 20px;
        padding: 24px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .summary-stats:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
    }
    
    .stat-title {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .stat-number {
        font-size: 28px;
        font-weight: 800;
    }
    
    .text-success {
        color: #10b981 !important;
    }
    
    .text-danger {
        color: #ef4444 !important;
    }
    
    /* Month Navigation */
    .month-navigation {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .current-month {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .current-month i {
        color: #667eea;
        margin-right: 8px;
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
        color: #667eea;
        margin-bottom: 20px;
    }
    
    .empty-state p {
        color: #6b7280;
        font-size: 16px;
        margin-bottom: 20px;
    }
    
    /* Modal */
    .modal-content-custom {
        background: white;
        border-radius: 20px;
        border: none;
    }
    
    .modal-header-custom {
        border-bottom: 1px solid #e5e7eb;
        padding: 20px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px 20px 0 0;
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
        border-radius: 0 0 20px 20px;
    }
    
    /* Form Controls */
    .form-control, .form-select {
        border-radius: 12px !important;
        border: 1px solid #e0e0e0 !important;
        padding: 12px 16px !important;
        transition: all 0.2s !important;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
    }
    
    .form-label {
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 8px;
    }
    
    .form-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 5px;
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
    
    /* SweetAlert2 Professional Style - Same as Emergency Fund */
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
    
    .swal2-icon.swal2-question {
        border-color: #667eea !important;
        color: #667eea !important;
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
            font-size: 24px !important;
        }
        
        .current-month {
            font-size: 16px !important;
        }
        
        .budget-category {
            font-size: 16px !important;
        }
        
        .budget-actions {
            flex-direction: column;
            gap: 5px;
        }
        
        .btn-month {
            padding: 6px 12px;
            font-size: 12px;
        }
    }
    
    @media (max-width: 576px) {
        .stat-icon {
            width: 50px;
            height: 50px;
        }
        
        .stat-number {
            font-size: 20px !important;
        }
        
        .budget-card {
            padding: 16px;
        }
        
        .budget-amounts {
            font-size: 12px;
        }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Manajemen Anggaran</h1>
                    <p class="welcome-subtitle">Kelola anggaran bulanan untuk setiap kategori pengeluaran</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="btn-add-budget" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                        <i class="fas fa-plus"></i> Tambah Anggaran
                    </button>
                </div>
            </div>
        </div>

        <!-- Month Navigation -->
        <div class="month-navigation animated" style="animation-delay: 0.1s">
            <div class="row align-items-center">
                <div class="col-md-4 text-md-start text-center mb-3 mb-md-0">
                    <a href="?month=<?= $current_month == 1 ? 12 : $current_month - 1 ?>&year=<?= $current_month == 1 ? $current_year - 1 : $current_year ?>" class="btn-month">
                        <i class="fas fa-chevron-left"></i> Bulan Sebelumnya
                    </a>
                </div>
                <div class="col-md-4 text-center">
                    <span class="current-month">
                        <i class="fas fa-calendar-alt"></i> <?= bulanIndonesia($current_month) ?> <?= $current_year ?>
                    </span>
                </div>
                <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
                    <a href="?month=<?= $current_month == 12 ? 1 : $current_month + 1 ?>&year=<?= $current_month == 12 ? $current_year + 1 : $current_year ?>" class="btn-month">
                        Bulan Selanjutnya <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="summary-stats animated" style="animation-delay: 0.2s">
                    <div class="stat-icon" style="background: #d1fae5;">
                        <i class="fas fa-chart-line" style="color: #10b981; font-size: 28px;"></i>
                    </div>
                    <div class="stat-title">Total Anggaran</div>
                    <div class="stat-number text-success" id="total-budget"><?= formatRupiah($total_budget) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-stats animated" style="animation-delay: 0.25s">
                    <div class="stat-icon" style="background: #fee2e2;">
                        <i class="fas fa-shopping-cart" style="color: #ef4444; font-size: 28px;"></i>
                    </div>
                    <div class="stat-title">Total Pengeluaran</div>
                    <div class="stat-number text-danger" id="total-spent"><?= formatRupiah($total_spent) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-stats animated" style="animation-delay: 0.3s">
                    <div class="stat-icon" style="background: #e0e7ff;">
                        <i class="fas fa-wallet" style="color: #667eea; font-size: 28px;"></i>
                    </div>
                    <div class="stat-title">Sisa Anggaran</div>
                    <div class="stat-number <?= $remaining >= 0 ? 'text-success' : 'text-danger' ?>" id="total-remaining">
                        <?= formatRupiah($remaining) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget List -->
        <div class="row g-4" id="budget-list">
            <?php if (count($budgets) > 0): ?>
                <?php foreach ($budgets as $b): 
                    $percentage = $b['budget_amount'] > 0 ? ($b['spent_amount'] / $b['budget_amount']) * 100 : 0;
                    $statusClass = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'safe');
                    $statusText = $percentage >= 100 ? 'Over Budget' : ($percentage >= 80 ? 'Mendekati Limit' : 'Aman');
                    $statusBadge = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'safe');
                ?>
                <div class="col-md-6 col-lg-4 budget-item" data-id="<?= $b['id'] ?>" style="animation: fadeInUp 0.5s ease <?= ($key + 1) * 0.05 ?>s both;">
                    <div class="budget-card">
                        <div class="budget-header">
                            <div class="budget-category">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($b['category_name']) ?>
                            </div>
                            <div class="budget-actions">
                                <button class="btn-edit-budget" onclick="editBudget(<?= $b['id'] ?>, <?= $b['category_id'] ?>, <?= $b['budget_amount'] ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete-budget" onclick="deleteBudget(<?= $b['id'] ?>, '<?= htmlspecialchars($b['category_name']) ?>')">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </div>
                        </div>
                        
                        <div class="budget-amounts">
                            <div class="budget-amount">
                                <span class="budget-label">Target Anggaran</span>
                                <span class="budget-value primary budget-amount-value"><?= formatRupiah($b['budget_amount']) ?></span>
                            </div>
                            <div class="budget-amount">
                                <span class="budget-label">Pengeluaran Saat Ini</span>
                                <span class="budget-value danger spent-amount-value"><?= formatRupiah($b['spent_amount']) ?></span>
                            </div>
                            <div class="budget-amount">
                                <span class="budget-label">Sisa Anggaran</span>
                                <span class="budget-value <?= ($b['budget_amount'] - $b['spent_amount']) >= 0 ? 'success' : 'danger' ?> remaining-amount-value">
                                    <?= formatRupiah($b['budget_amount'] - $b['spent_amount']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="progress">
                            <div class="progress-bar <?= $statusClass ?>" style="width: <?= min($percentage, 100) ?>%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="budget-status status-<?= $statusBadge ?>">
                                <i class="fas fa-<?= $percentage >= 100 ? 'exclamation-triangle' : ($percentage >= 80 ? 'clock' : 'check-circle') ?>"></i>
                                <?= $statusText ?>
                            </span>
                            <span class="budget-label percentage-value"><?= round($percentage, 1) ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state animated" style="animation-delay: 0.2s">
                        <i class="fas fa-chart-pie"></i>
                        <p>Belum ada anggaran untuk bulan <?= bulanIndonesia($current_month) ?> <?= $current_year ?></p>
                        <button class="btn-add-budget mt-3" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                            <i class="fas fa-plus"></i> Buat Anggaran Sekarang
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Tambah Anggaran -->
<div class="modal fade" id="addBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Anggaran Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addBudgetForm">
                <div class="modal-body modal-body-custom">
                    <input type="hidden" name="month" value="<?= $current_month ?>">
                    <input type="hidden" name="year" value="<?= $current_year ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori</label>
                        <select name="category_id" id="budget_category_id" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($expense_categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (count($expense_categories) == 0): ?>
                            <div class="form-text text-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Belum ada kategori pengeluaran. <a href="../categories/index.php">Tambahkan kategori</a> terlebih dahulu.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Anggaran (Rp)</label>
                        <input type="text" name="amount" id="budget_amount" class="form-control currency-input" placeholder="0" required>
                        <div class="form-text">Masukkan target anggaran untuk kategori ini</div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Simpan Anggaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Anggaran -->
<div class="modal fade" id="editBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit Anggaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBudgetForm">
                <input type="hidden" name="id" id="edit_budget_id">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori</label>
                        <select name="category_id" id="edit_category_id" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($expense_categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Anggaran (Rp)</label>
                        <input type="text" name="amount" id="edit_amount" class="form-control currency-input" placeholder="0" required>
                        <div class="form-text">Masukkan target anggaran untuk kategori ini</div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Update Anggaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Format currency input
    function formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }
    
    function parseNumber(str) {
        return parseInt(str.replace(/[^0-9]/g, '')) || 0;
    }
    
    document.querySelectorAll('.currency-input').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString('id-ID');
            } else {
                this.value = '';
            }
        });
    });
    
    // Update totals after add/edit/delete
    function updateTotals() {
        let totalBudget = 0;
        let totalSpent = 0;
        
        document.querySelectorAll('.budget-item').forEach(item => {
            const budgetAmount = parseNumber(item.querySelector('.budget-amount-value').innerText);
            const spentAmount = parseNumber(item.querySelector('.spent-amount-value').innerText);
            totalBudget += budgetAmount;
            totalSpent += spentAmount;
        });
        
        const remaining = totalBudget - totalSpent;
        
        document.getElementById('total-budget').innerHTML = formatRupiah(totalBudget);
        document.getElementById('total-spent').innerHTML = formatRupiah(totalSpent);
        document.getElementById('total-remaining').innerHTML = formatRupiah(remaining);
        
        const remainingElement = document.getElementById('total-remaining');
        if (remaining >= 0) {
            remainingElement.className = 'stat-number text-success';
        } else {
            remainingElement.className = 'stat-number text-danger';
        }
    }
    
    // ADD Budget
    document.getElementById('addBudgetForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let categoryId = document.getElementById('budget_category_id').value;
        let categoryName = document.getElementById('budget_category_id').options[document.getElementById('budget_category_id').selectedIndex]?.text;
        let amountRaw = document.getElementById('budget_amount').value;
        let amount = parseNumber(amountRaw);
        let month = document.querySelector('input[name="month"]').value;
        let year = document.querySelector('input[name="year"]').value;
        
        if (!categoryId) {
            Swal.fire({
                title: 'Oops!',
                text: 'Pilih kategori terlebih dahulu',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (amount <= 0) {
            Swal.fire({
                title: 'Oops!',
                text: 'Jumlah anggaran harus lebih dari 0',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        Swal.fire({
            title: 'Tambah Anggaran?',
            html: `
                <div style="text-align: left; background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 12px;">
                    <p><strong><i class="fas fa-tag"></i> Kategori:</strong> ${escapeHtml(categoryName)}</p>
                    <p><strong><i class="fas fa-money-bill-wave"></i> Jumlah:</strong> ${formatRupiah(amount)}</p>
                    <p><strong><i class="fas fa-calendar"></i> Periode:</strong> ${bulanIndonesia(parseInt(month))} ${year}</p>
                </div>
                <small class="mt-2 d-block">Apakah data sudah benar?</small>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-save"></i> Ya, Simpan!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menyimpan...',
                    text: 'Sedang menyimpan anggaran',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: 'add.php',
                    type: 'POST',
                    data: {
                        category_id: categoryId,
                        amount: amount,
                        month: month,
                        year: year
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            bootstrap.Modal.getInstance(document.getElementById('addBudgetModal')).hide();
                            document.getElementById('addBudgetForm').reset();
                            
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true,
                                didOpen: () => {
                                    canvasConfetti({
                                        particleCount: 100,
                                        spread: 70,
                                        origin: { y: 0.6 }
                                    });
                                }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Gagal!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menyimpan data',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
    
    // EDIT Budget
    document.getElementById('editBudgetForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let id = document.getElementById('edit_budget_id').value;
        let categoryId = document.getElementById('edit_category_id').value;
        let categoryName = document.getElementById('edit_category_id').options[document.getElementById('edit_category_id').selectedIndex]?.text;
        let amountRaw = document.getElementById('edit_amount').value;
        let amount = parseNumber(amountRaw);
        
        if (!categoryId) {
            Swal.fire({
                title: 'Oops!',
                text: 'Pilih kategori terlebih dahulu',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (amount <= 0) {
            Swal.fire({
                title: 'Oops!',
                text: 'Jumlah anggaran harus lebih dari 0',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        Swal.fire({
            title: 'Update Anggaran?',
            html: `
                <div style="text-align: left; background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 12px;">
                    <p><strong><i class="fas fa-tag"></i> Kategori:</strong> ${escapeHtml(categoryName)}</p>
                    <p><strong><i class="fas fa-money-bill-wave"></i> Jumlah Baru:</strong> ${formatRupiah(amount)}</p>
                </div>
                <small class="mt-2 d-block">Apakah Anda yakin ingin mengupdate anggaran ini?</small>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-save"></i> Ya, Update!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Mengupdate...',
                    text: 'Sedang mengupdate anggaran',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: 'edit.php',
                    type: 'POST',
                    data: {
                        id: id,
                        category_id: categoryId,
                        amount: amount
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            bootstrap.Modal.getInstance(document.getElementById('editBudgetModal')).hide();
                            
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true,
                                didOpen: () => {
                                    canvasConfetti({
                                        particleCount: 100,
                                        spread: 70,
                                        origin: { y: 0.6 }
                                    });
                                }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Gagal!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat mengupdate data',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
    
    // DELETE Budget
    function deleteBudget(id, categoryName) {
        Swal.fire({
            title: 'Hapus Anggaran?',
            html: `Apakah Anda yakin ingin menghapus anggaran untuk kategori <strong>"${escapeHtml(categoryName)}"</strong>?<br><small style="color: #ef4444;">⚠️ Data yang dihapus tidak dapat dikembalikan!</small>`,
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
                    text: 'Sedang menghapus anggaran',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: 'delete.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Gagal!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus data',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    }
    
    function editBudget(id, categoryId, amount) {
        document.getElementById('edit_budget_id').value = id;
        document.getElementById('edit_category_id').value = categoryId;
        document.getElementById('edit_amount').value = formatNumber(amount);
        
        var modal = new bootstrap.Modal(document.getElementById('editBudgetModal'));
        modal.show();
    }
    
    function bulanIndonesia(month) {
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return months[month - 1];
    }
    
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Success message with confetti animation
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
        window.location.href = 'index.php?month=<?= $current_month ?>&year=<?= $current_year ?>';
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