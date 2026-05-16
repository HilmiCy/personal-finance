<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/User.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Profil Saya';
$current_page = 'profile';

$db = Database::getInstance()->getConnection();
$user = new User();

// Get user data
$user_data = $user->getById($_SESSION['user_id']);

// Get statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count,
        COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count,
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
        COUNT(DISTINCT account_id) as total_accounts,
        COUNT(DISTINCT category_id) as total_categories
    FROM transactions 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

$total_transactions = $stats['total_transactions'] ?? 0;
$total_income = $stats['total_income'] ?? 0;
$total_expense = $stats['total_expense'] ?? 0;
$balance = $total_income - $total_expense;

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
        margin: 0;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    /* ========== PROFILE HEADER ========== */
    .profile-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 24px;
        padding: 32px;
        margin-bottom: 30px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
    }
    
    .profile-header:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 48px;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
    }
    
    .profile-header:hover .profile-avatar {
        transform: scale(1.05);
    }
    
    .profile-name {
        font-size: 28px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .profile-email {
        font-size: 16px;
        color: #6b7280;
        margin-bottom: 20px;
    }
    
    .profile-email i {
        color: #667eea;
        margin-right: 6px;
    }
    
    /* Profile Stats */
    .profile-stats {
        display: flex;
        justify-content: center;
        gap: 40px;
        flex-wrap: wrap;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: 800;
        color: #667eea;
    }
    
    .stat-label {
        font-size: 12px;
        color: #6b7280;
        margin-top: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Profile Cards */
    .profile-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
        border: 1px solid #e5e7eb;
    }
    
    .profile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-left: 4px solid #667eea;
        padding-left: 15px;
    }
    
    .card-title i {
        color: #667eea;
    }
    
    /* Info Rows */
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #4b5563;
        font-size: 14px;
    }
    
    .info-value {
        color: #1f2937;
        font-weight: 500;
        font-size: 14px;
    }
    
    /* Badge Styles */
    .badge-income {
        background: #d1fae5;
        color: #059669;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .badge-expense {
        background: #fee2e2;
        color: #dc2626;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .badge-inactive {
        background: #f3f4f6;
        color: #6b7280;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Member Since */
    .member-since {
        background: #f0fdf4;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        margin-top: 20px;
        border: 1px solid #dcfce7;
    }
    
    .member-since i {
        color: #10b981;
        margin-right: 8px;
    }
    
    .member-since {
        color: #166534;
    }
    
    /* Buttons */
    .btn-edit-profile {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-edit-profile:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        color: white;
    }
    
    .btn-change-password {
        background: #f3f4f6;
        color: #4b5563;
        border: 1px solid #e5e7eb;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-change-password:hover {
        background: #e5e7eb;
        color: #1f2937;
        transform: translateY(-2px);
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
    .form-control {
        border-radius: 12px !important;
        border: 1px solid #e5e7eb !important;
        padding: 12px 16px !important;
        transition: all 0.2s ease !important;
    }
    
    .form-control:focus {
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
    
    /* Text Colors */
    .text-success {
        color: #10b981 !important;
    }
    
    .text-danger {
        color: #ef4444 !important;
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
        
        .profile-name {
            font-size: 22px !important;
        }
        
        .profile-avatar {
            width: 90px;
            height: 90px;
            font-size: 36px;
        }
        
        .profile-stats {
            gap: 20px;
        }
        
        .stat-number {
            font-size: 18px;
        }
        
        .stat-label {
            font-size: 10px;
        }
        
        .info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
    
    @media (max-width: 576px) {
        .profile-header {
            padding: 20px;
        }
        
        .profile-stats {
            gap: 15px;
        }
        
        .stat-number {
            font-size: 16px;
        }
        
        .card-title {
            font-size: 16px;
        }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-12">
                    <h1 class="welcome-title">Profil Saya</h1>
                    <p class="welcome-subtitle">Kelola informasi akun dan keamanan Anda</p>
                </div>
            </div>
        </div>

        <!-- Profile Header - Tanpa Background Ungu -->
        <div class="profile-header animated" style="animation-delay: 0.1s">
            <div class="profile-avatar">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
            <div class="profile-name">
                <?= htmlspecialchars($_SESSION['user_name']) ?>
            </div>
            <div class="profile-email">
                <i class="fas fa-envelope"></i> <?= htmlspecialchars($_SESSION['user_email']) ?>
            </div>
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($total_transactions) ?></div>
                    <div class="stat-label">Total Transaksi</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['total_accounts'] ?? 0) ?></div>
                    <div class="stat-label">Akun</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['total_categories'] ?? 0) ?></div>
                    <div class="stat-label">Kategori</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatRupiah($balance) ?>
                    </div>
                    <div class="stat-label">Saldo Bersih</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Profile Information -->
            <div class="col-md-6">
                <div class="profile-card animated" style="animation-delay: 0.2s">
                    <div class="card-title">
                        <i class="fas fa-user-circle"></i>
                        Informasi Profil
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Nama Lengkap</span>
                        <span class="info-value"><?= htmlspecialchars($user_data['name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($user_data['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Sejak</span>
                        <span class="info-value">
                            <?= formatDateTime($user_data['created_at']) ?>
                        </span>
                    </div>
                    
                    <div class="member-since">
                        <i class="fas fa-calendar-check"></i>
                        Terdaftar sebagai member selama 
                        <?php 
                        $created = new DateTime($user_data['created_at']);
                        $now = new DateTime();
                        $diff = $created->diff($now);
                        echo $diff->y . ' tahun, ' . $diff->m . ' bulan';
                        ?>
                    </div>
                    
                    <button class="btn-edit-profile mt-3" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fas fa-edit"></i> Edit Profil
                    </button>
                </div>
            </div>
            
            <!-- Security & Activity -->
            <div class="col-md-6">
                <div class="profile-card animated" style="animation-delay: 0.25s">
                    <div class="card-title">
                        <i class="fas fa-shield-alt"></i>
                        Keamanan Akun
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Status Akun</span>
                        <span class="info-value">
                            <span class="badge-income">
                                <i class="fas fa-check-circle"></i> Aktif
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Password</span>
                        <span class="info-value">••••••••</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Verifikasi 2 Langkah</span>
                        <span class="info-value">
                            <span class="badge-inactive">
                                <i class="fas fa-clock"></i> Belum Aktif
                            </span>
                        </span>
                    </div>
                    
                    <button class="btn-change-password mt-3" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-key"></i> Ganti Password
                    </button>
                </div>
                
                <!-- Quick Stats -->
                <div class="profile-card mt-4 animated" style="animation-delay: 0.3s">
                    <div class="card-title">
                        <i class="fas fa-chart-line"></i>
                        Statistik Keuangan
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Total Pemasukan</span>
                        <span class="info-value text-success">
                            <i class="fas fa-arrow-up"></i> <?= formatRupiah($total_income) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Pengeluaran</span>
                        <span class="info-value text-danger">
                            <i class="fas fa-arrow-down"></i> <?= formatRupiah($total_expense) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rata-rata Pemasukan</span>
                        <span class="info-value">
                            <?= $stats['income_count'] > 0 ? formatRupiah($total_income / $stats['income_count']) : 'Rp 0' ?>
                            <small class="text-muted">/transaksi</small>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rata-rata Pengeluaran</span>
                        <span class="info-value">
                            <?= $stats['expense_count'] > 0 ? formatRupiah($total_expense / $stats['expense_count']) : 'Rp 0' ?>
                            <small class="text-muted">/transaksi</small>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Profil -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user-edit me-2"></i> Edit Profil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProfileForm" action="update.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?= htmlspecialchars($user_data['name']) ?>" required>
                        <div class="form-text">Masukkan nama lengkap Anda</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($user_data['email']) ?>" required>
                        <div class="form-text">Email akan digunakan untuk login</div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ganti Password -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-key me-2"></i> Ganti Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm" action="change-password.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Lama</label>
                        <input type="password" name="current_password" class="form-control" required>
                        <div class="form-text">Masukkan password Anda saat ini</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Baru</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                        <div class="form-text">Minimal 6 karakter, gunakan kombinasi huruf dan angka</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        <div class="form-text">Ketik ulang password baru Anda</div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Ganti Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
<script>
    // Edit Profile Form with SweetAlert2
    document.getElementById('editProfileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let form = this;
        let formData = new FormData(form);
        
        Swal.fire({
            title: 'Update Profil?',
            text: 'Apakah Anda yakin ingin mengupdate informasi profil?',
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
                    text: 'Sedang mengupdate profil',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch('update.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editProfileModal'))?.hide();
                        
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
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
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat mengupdate profil',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            }
        });
    });
    
    // Change Password Form with SweetAlert2
    document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let newPassword = document.getElementById('new_password')?.value;
        let confirmPassword = document.getElementById('confirm_password')?.value;
        
        if (newPassword !== confirmPassword) {
            Swal.fire({
                title: 'Oops!',
                text: 'Password baru dan konfirmasi password tidak cocok!',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (newPassword.length < 6) {
            Swal.fire({
                title: 'Oops!',
                text: 'Password baru minimal 6 karakter!',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        let form = this;
        let formData = new FormData(form);
        
        Swal.fire({
            title: 'Ganti Password?',
            text: 'Apakah Anda yakin ingin mengganti password?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-key"></i> Ya, Ganti!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Sedang mengganti password',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch('change-password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'))?.hide();
                        document.getElementById('changePasswordForm')?.reset();
                        
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            didOpen: () => {
                                canvasConfetti({
                                    particleCount: 100,
                                    spread: 70,
                                    origin: { y: 0.6 }
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat mengganti password',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            }
        });
    });
    
    // Success message handling from server redirects
    <?php if (isset($_SESSION['success'])): ?>
    Swal.fire({
        title: 'Berhasil!',
        text: '<?= $_SESSION['success'] ?>',
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
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    Swal.fire({
        title: 'Gagal!',
        text: '<?= $_SESSION['error'] ?>',
        icon: 'error',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'OK'
    });
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>