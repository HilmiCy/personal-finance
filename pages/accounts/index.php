<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Account.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Manajemen Akun';
$current_page = 'accounts';

$account = new Account();

// Get all accounts
$accounts = $account->getAll($_SESSION['user_id']);
$total_balance = $account->getTotalBalance($_SESSION['user_id']);
$transfer_history = $account->getTransferHistory($_SESSION['user_id'], 10);

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
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }
    
    .card-body {
        padding: 24px !important;
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
        cursor: pointer;
        border: none;
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
        cursor: pointer;
    }
    
    .btn-secondary:hover {
        background: rgba(107, 114, 128, 0.2);
        color: #4b5563;
    }
    
    /* Stat Card */
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #667eea20, #764ba220);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    
    .stat-icon i {
        font-size: 32px;
        color: #667eea;
    }
    
    .stat-title {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .stat-change {
        font-size: 12px;
        color: #9ca3af;
    }
    
    /* Account Card */
    .account-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        transition: all 0.3s ease;
        margin-bottom: 0;
        border-left: 4px solid #667eea;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        height: 100%;
    }
    
    .account-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .account-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea20, #764ba220);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #667eea;
    }
    
    .account-name {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 5px;
    }
    
    .account-balance {
        font-size: 24px;
        font-weight: 800;
        color: #667eea;
    }
    
    /* Transfer History Card */
    .history-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        margin-top: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .history-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }
    
    .history-list {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .history-item {
        padding: 16px 24px;
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s ease;
    }
    
    .history-item:hover {
        background: #f9fafb;
    }
    
    .history-amount {
        font-weight: 700;
        font-size: 16px;
    }
    
    .text-success-custom {
        color: #10b981;
    }
    
    .text-danger-custom {
        color: #ef4444;
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
    
    /* Alert */
    .alert-info-custom {
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        border-radius: 12px;
        padding: 12px 16px;
        color: #0369a1;
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
    
    .animated {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    
    /* Loading Spinner */
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.6s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
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
        
        .stat-value {
            font-size: 24px !important;
        }
        
        .account-balance {
            font-size: 20px !important;
        }
        
        .account-name {
            font-size: 16px !important;
        }
        
        .history-item {
            padding: 12px 16px;
        }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Manajemen Akun</h1>
                    <p class="welcome-subtitle">Kelola dompet, rekening bank, dan e-wallet Anda</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <?php if (count($accounts) >= 2): ?>
                    <button class="btn-primary-custom me-2" data-bs-toggle="modal" data-bs-target="#transferModal">
                        <i class="fas fa-exchange-alt"></i> Transfer
                    </button>
                    <?php endif; ?>
                    <a href="historytransfer.php" class="btn-primary-custom me-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fas fa-history"></i> History Transfer
                    </a>
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                        <i class="fas fa-plus"></i> Tambah Akun
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stat-card animated" style="animation-delay: 0.1s">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div>
                            <div class="stat-title">Total Seluruh Saldo</div>
                            <div class="stat-value"><?= formatRupiah($total_balance) ?></div>
                            <div class="stat-change">
                                <i class="fas fa-wallet"></i> Dari <?= count($accounts) ?> akun
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div class="row g-4">
            <?php if (count($accounts) > 0): ?>
                <?php foreach ($accounts as $index => $acc): ?>
                <div class="col-md-6 col-lg-4" style="animation: fadeInUp 0.5s ease <?= $index * 0.05 ?>s both;">
                    <div class="account-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="account-icon">
                                <i class="fas fa-<?= getAccountIcon($acc['name']) ?>"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link text-secondary p-0" data-bs-toggle="dropdown" style="font-size: 18px;">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item" onclick="editAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars($acc['name']) ?>', <?= $acc['balance'] ?>)">
                                            <i class="fas fa-edit me-2"></i> Edit
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deleteAccount(<?= $acc['id'] ?>)">
                                            <i class="fas fa-trash me-2"></i> Hapus
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="account-name"><?= htmlspecialchars($acc['name']) ?></div>
                        <div class="account-balance mt-2"><?= formatRupiah($acc['balance']) ?></div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i> Dibuat: <?= formatDate($acc['created_at']) ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state animated" style="animation-delay: 0.2s">
                        <i class="fas fa-wallet"></i>
                        <p>Belum ada akun. Tambahkan akun pertama Anda!</p>
                        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                            <i class="fas fa-plus"></i> Tambah Akun Sekarang
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Transfer History Section -->
        <?php if (count($transfer_history) > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="history-card animated" style="animation-delay: 0.3s">
                    <div class="history-header">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-history me-2" style="color: #667eea;"></i>
                            Riwayat Transfer Terakhir
                        </h5>
                    </div>
                    <div class="history-list">
                        <?php foreach ($transfer_history as $history): ?>
                        <div class="history-item d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-light p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-exchange-alt" style="color: #667eea;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($history['from_account_name']) ?> 
                                            <i class="fas fa-arrow-right mx-1 text-muted"></i> 
                                            <?= htmlspecialchars($history['to_account_name']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="far fa-clock me-1"></i> <?= formatDateTime($history['transfer_date']) ?>
                                        </small>
                                        <?php if ($history['description']): ?>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-pencil-alt me-1"></i> <?= htmlspecialchars($history['description']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="history-amount text-danger-custom">
                                    -<?= formatRupiah($history['amount']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah Akun -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Akun Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addAccountForm" action="add.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Akun</label>
                        <input type="text" name="name" id="add_account_name" class="form-control" placeholder="Contoh: Bank BCA, Cash, OVO, Dana" required>
                        <small class="text-muted">Masukkan nama akun yang mudah diingat</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Saldo Awal</label>
                        <input type="text" name="balance_display" id="balance_display" class="form-control currency-input" placeholder="0" value="0">
                        <input type="hidden" name="balance" id="balance_hidden">
                        <small class="text-muted">Masukkan saldo awal akun ini</small>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Akun -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit Akun
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAccountForm" action="edit.php" method="POST">
                <input type="hidden" name="id" id="edit_account_id">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Akun</label>
                        <input type="text" name="name" id="edit_account_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Saldo</label>
                        <input type="text" id="edit_balance_display" class="form-control currency-input">
                        <input type="hidden" name="balance" id="edit_balance_hidden">
                        <small class="text-muted">Perubahan saldo akan mempengaruhi laporan keuangan</small>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Update Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Transfer Antar Akun -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exchange-alt me-2"></i> Transfer Antar Akun
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="transferForm">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dari Akun</label>
                        <select name="from_account_id" id="from_account_id" class="form-select" required>
                            <option value="">Pilih Akun Sumber</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>" data-balance="<?= $acc['balance'] ?>">
                                    <?= htmlspecialchars($acc['name']) ?> (<?= formatRupiah($acc['balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Pilih akun sumber dana</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ke Akun</label>
                        <select name="to_account_id" id="to_account_id" class="form-select" required>
                            <option value="">Pilih Akun Tujuan</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>">
                                    <?= htmlspecialchars($acc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Pilih akun tujuan transfer</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Transfer</label>
                        <input type="text" id="transfer_amount_display" class="form-control currency-input" placeholder="0" required>
                        <input type="hidden" name="amount" id="transfer_amount_hidden">
                        <small class="text-muted">Masukkan jumlah yang akan ditransfer</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi (Opsional)</label>
                        <textarea name="description" id="transfer_description" class="form-control" rows="2" placeholder="Contoh: Transfer untuk keperluan..."></textarea>
                    </div>
                    <div class="alert alert-info-custom" id="balanceAlert" style="display: none;">
                        <i class="fas fa-info-circle"></i> Saldo tersedia: <strong id="availableBalance">Rp 0</strong>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom" id="btnTransfer">
                        <i class="fas fa-paper-plane me-1"></i> Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
<script>
    // Format currency input
    document.querySelectorAll('.currency-input').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^\d]/g, '');
            
            if (value) {
                let numericValue = parseInt(value);
                
                // ADD
                if (this.id === 'balance_display') {
                    document.getElementById('balance_hidden').value = numericValue;
                }
                
                // EDIT
                if (this.id === 'edit_balance_display') {
                    document.getElementById('edit_balance_hidden').value = numericValue;
                }
                
                // TRANSFER
                if (this.id === 'transfer_amount_display') {
                    document.getElementById('transfer_amount_hidden').value = numericValue;
                }
                
                this.value = numericValue.toLocaleString('id-ID');
            } else {
                if (this.id === 'balance_display') {
                    document.getElementById('balance_hidden').value = 0;
                }
                
                if (this.id === 'edit_balance_display') {
                    document.getElementById('edit_balance_hidden').value = 0;
                }
                
                if (this.id === 'transfer_amount_display') {
                    document.getElementById('transfer_amount_hidden').value = 0;
                }
                
                this.value = '0';
            }
        });
    });
    
    // Transfer functionality
    const fromAccountSelect = document.getElementById('from_account_id');
    const toAccountSelect = document.getElementById('to_account_id');
    const balanceAlert = document.getElementById('balanceAlert');
    const availableBalanceSpan = document.getElementById('availableBalance');
    
    if (fromAccountSelect) {
        fromAccountSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const balance = selectedOption.getAttribute('data-balance');
            
            if (balance && this.value) {
                const formattedBalance = new Intl.NumberFormat('id-ID').format(balance);
                availableBalanceSpan.textContent = 'Rp ' + formattedBalance;
                balanceAlert.style.display = 'block';
            } else {
                balanceAlert.style.display = 'none';
            }
        });
    }
    
    // Prevent selecting same account for from and to
    if (toAccountSelect) {
        toAccountSelect.addEventListener('change', function() {
            const fromAccount = fromAccountSelect.value;
            const toAccount = this.value;
            
            if (fromAccount && toAccount && fromAccount === toAccount) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Akun sumber dan tujuan tidak boleh sama',
                    icon: 'warning',
                    confirmButtonColor: '#667eea'
                });
                this.value = '';
            }
        });
    }
    
    // Handle transfer form submission
const transferForm = document.getElementById('transferForm');
if (transferForm) {
    transferForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fromAccount = fromAccountSelect.value;
        const toAccount = toAccountSelect.value;
        const amountHidden = document.getElementById('transfer_amount_hidden');
        const amountValue = amountHidden.value;
        const description = document.getElementById('transfer_description').value;
        const fromAccountName = fromAccountSelect.options[fromAccountSelect.selectedIndex]?.text.split(' (')[0];
        const toAccountName = toAccountSelect.options[toAccountSelect.selectedIndex]?.text;
        const formattedAmount = document.getElementById('transfer_amount_display').value;
        
        if (!fromAccount || !toAccount || !amountValue || parseFloat(amountValue) <= 0) {
            Swal.fire({
                title: 'Oops!',
                text: 'Harap lengkapi semua data dengan benar',
                icon: 'error',
                confirmButtonColor: '#667eea'
            });
            return;
        }
        
        if (fromAccount === toAccount) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Akun sumber dan tujuan tidak boleh sama',
                icon: 'warning',
                confirmButtonColor: '#667eea'
            });
            return;
        }
        
        // Check balance
        const selectedFromOption = fromAccountSelect.options[fromAccountSelect.selectedIndex];
        const currentBalance = parseFloat(selectedFromOption.getAttribute('data-balance'));
        const transferAmount = parseFloat(amountValue);
        
        if (transferAmount > currentBalance) {
            Swal.fire({
                title: 'Saldo Tidak Cukup!',
                text: `Saldo ${fromAccountName} hanya ${new Intl.NumberFormat('id-ID').format(currentBalance)}`,
                icon: 'error',
                confirmButtonColor: '#667eea'
            });
            return;
        }
        
        Swal.fire({
            title: 'Konfirmasi Transfer',
            html: `
                <div style="text-align: left; background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 12px;">
                    <p><strong><i class="fas fa-arrow-right"></i> Dari:</strong> ${escapeHtml(fromAccountName)}</p>
                    <p><strong><i class="fas fa-arrow-left"></i> Ke:</strong> ${escapeHtml(toAccountName)}</p>
                    <p><strong><i class="fas fa-money-bill-wave"></i> Jumlah:</strong> ${formattedAmount}</p>
                    ${description ? `<p><strong><i class="fas fa-pencil-alt"></i> Deskripsi:</strong> ${escapeHtml(description)}</p>` : ''}
                </div>
                <small class="mt-2 d-block text-danger">⚠️ Transfer tidak dapat dibatalkan!</small>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Ya, Transfer!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Memproses Transfer...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit via AJAX
                const formData = new FormData(this);
                
                fetch('transfer.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    // Cek apakah response OK
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.text(); // Ambil sebagai text dulu
                })
                .then(text => {
                    // Coba parse JSON
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            Swal.fire({
                                title: 'Transfer Berhasil!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#667eea',
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
                                title: 'Transfer Gagal!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#667eea'
                            });
                        }
                    } catch (e) {
                        // Jika bukan JSON, tampilkan error HTML
                        console.error('Response text:', text);
                        Swal.fire({
                            title: 'Error!',
                            html: `
                                <div class="text-start">
                                    <p>Terjadi kesalahan pada server</p>
                                    <pre class="bg-light p-2 rounded" style="font-size: 12px; max-height: 200px; overflow: auto;">${escapeHtml(text.substring(0, 500))}</pre>
                                </div>
                            `,
                            icon: 'error',
                            confirmButtonColor: '#667eea'
                        });
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Tidak dapat terhubung ke server: ' + error.message,
                        icon: 'error',
                        confirmButtonColor: '#667eea'
                    });
                });
            }
        });
    });
}
    
    // Add Account Form
    const addAccountForm = document.getElementById('addAccountForm');
    if (addAccountForm) {
        addAccountForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let accountName = document.getElementById('add_account_name').value;
            let balanceDisplay = document.getElementById('balance_display').value;
            
            if (!accountName.trim()) {
                Swal.fire({
                    title: 'Oops!',
                    text: 'Nama akun harus diisi',
                    icon: 'error',
                    confirmButtonColor: '#667eea'
                });
                return;
            }
            
            Swal.fire({
                title: 'Tambah Akun Baru?',
                html: `
                    <div style="text-align: left; background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 12px;">
                        <p><strong><i class="fas fa-tag"></i> Nama Akun:</strong> ${escapeHtml(accountName)}</p>
                        <p><strong><i class="fas fa-money-bill-wave"></i> Saldo Awal:</strong> ${balanceDisplay}</p>
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
                    let balanceHidden = document.getElementById('balance_hidden');
                    if (!balanceHidden.value) {
                        balanceHidden.value = 0;
                    }
                    document.getElementById('balance_display').removeAttribute('name');
                    addAccountForm.submit();
                }
            });
        });
    }
    
    // Edit Account Form
    const editAccountForm = document.getElementById('editAccountForm');
    if (editAccountForm) {
        editAccountForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let accountName = document.getElementById('edit_account_name').value;
            let balanceDisplay = document.getElementById('edit_balance_display').value;
            
            if (!accountName.trim()) {
                Swal.fire({
                    title: 'Oops!',
                    text: 'Nama akun harus diisi',
                    icon: 'error',
                    confirmButtonColor: '#667eea'
                });
                return;
            }
            
            Swal.fire({
                title: 'Update Akun?',
                html: `
                    <div style="text-align: left; background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 12px;">
                        <p><strong><i class="fas fa-tag"></i> Nama Akun:</strong> ${escapeHtml(accountName)}</p>
                        <p><strong><i class="fas fa-money-bill-wave"></i> Saldo:</strong> ${balanceDisplay}</p>
                    </div>
                    <small class="mt-2 d-block">Apakah Anda yakin ingin mengupdate akun ini?</small>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-save"></i> Ya, Update!',
                cancelButtonText: '<i class="fas fa-times"></i> Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    let balanceHidden = document.getElementById('edit_balance_hidden');
                    if (!balanceHidden.value) {
                        balanceHidden.value = 0;
                    }
                    document.getElementById('edit_balance_display').removeAttribute('name');
                    editAccountForm.submit();
                }
            });
        });
    }
    
    function editAccount(id, name, balance) {
        document.getElementById('edit_account_id').value = id;
        document.getElementById('edit_account_name').value = name;
        document.getElementById('edit_balance_display').value = numberFormat(balance);
        document.getElementById('edit_balance_hidden').value = balance;
        
        var modal = new bootstrap.Modal(document.getElementById('editAccountModal'));
        modal.show();
    }
    
    function deleteAccount(id) {
        Swal.fire({
            title: 'Hapus Akun?',
            html: 'Apakah Anda yakin ingin menghapus akun ini?<br><small style="color: #ef4444;">⚠️ Semua transaksi yang terkait akan terpengaruh!</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete.php?id=' + id;
            }
        });
    }
    
    function numberFormat(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
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
    
    // Reset transfer modal when closed
    const transferModal = document.getElementById('transferModal');
    if (transferModal) {
        transferModal.addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('transferForm');
            if (form) form.reset();
            if (balanceAlert) balanceAlert.style.display = 'none';
            const amountHidden = document.getElementById('transfer_amount_hidden');
            const amountDisplay = document.getElementById('transfer_amount_display');
            if (amountHidden) amountHidden.value = '';
            if (amountDisplay) amountDisplay.value = '0';
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>