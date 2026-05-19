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
    /* ========== WELCOME CARD ========== */
    .welcome-card {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 24px;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
    }
    
    .welcome-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 0.95rem;
    }
    
    /* ========== PROFILE HEADER ========== */
    .profile-header {
        background: white;
        border-radius: 24px;
        padding: 40px;
        margin-bottom: 30px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid #f3f4f6;
    }
    
    .profile-avatar {
        width: 130px;
        height: 130px;
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        color: white;
        font-size: 52px;
        font-weight: 700;
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.25);
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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .profile-email i {
        color: #6366f1;
    }
    
    /* Profile Stats */
    .profile-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 24px;
        margin-top: 32px;
        padding-top: 32px;
        border-top: 1px solid #f3f4f6;
    }
    
    .stat-item {
        text-align: center;
        padding: 15px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        transition: transform 0.2s;
    }

    .stat-item:hover {
        transform: translateY(-3px);
    }
    
    .stat-number {
        font-size: 22px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .stat-label {
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }
    
    /* Profile Cards */
    .profile-card {
        background: white;
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        height: 100%;
        border: 1px solid #f3f4f6;
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .card-title i {
        width: 36px;
        height: 36px;
        background: #f1f5ff;
        color: #6366f1;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    
    /* Info Rows */
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #64748b;
        font-size: 14px;
    }
    
    .info-value {
        color: #1f2937;
        font-weight: 600;
        font-size: 14px;
    }
    
    /* Badge Styles */
    .status-badge {
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .status-badge.active {
        background: #ecfdf5;
        color: #059669;
    }
    
    .status-badge.inactive {
        background: #f9fafb;
        color: #6b7280;
    }
    
    /* Member Since */
    .member-since {
        background: #f0fdf4;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        margin: 24px 0;
        border: 1px solid #dcfce7;
        color: #166534;
        font-weight: 500;
        font-size: 14px;
    }
    
    .member-since i {
        color: #10b981;
        font-size: 18px;
        margin-bottom: 8px;
        display: block;
    }
    
    /* Buttons */
    .btn-profile-action {
        width: 100%;
        padding: 14px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border: none;
        cursor: pointer;
    }
    
    .btn-edit {
        background: #6366f1;
        color: white;
    }
    
    .btn-edit:hover {
        background: #4f46e5;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
    }
    
    .btn-password {
        background: #f1f5f9;
        color: #475569;
    }
    
    .btn-password:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .profile-header {
            padding: 30px 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            font-size: 40px;
        }
        
        .profile-name {
            font-size: 24px;
        }
        
        .profile-stats {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .stat-item {
            padding: 12px;
        }
        
        .stat-number {
            font-size: 18px;
        }
        
        .info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        
        .info-value {
            width: 100%;
            text-align: left;
        }
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-12">
                    <h1 class="welcome-title text-white">Profil Saya</h1>
                    <p class="welcome-subtitle text-white">Kelola informasi akun dan pantau aktivitas Anda</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Avatar & Main Info -->
            <div class="col-lg-4">
                <div class="profile-header animated" style="animation-delay: 0.1s">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                    </div>
                    <h2 class="profile-name"><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                    <div class="profile-email">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($_SESSION['user_email']) ?>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number text-primary"><?= number_format($total_transactions) ?></div>
                            <div class="stat-label">Transaksi</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number text-success"><?= formatRupiah($balance) ?></div>
                            <div class="stat-label">Saldo Bersih</div>
                        </div>
                    </div>

                    <div class="member-since">
                        <i class="fas fa-calendar-check"></i>
                        Member sejak <?= formatDate($user_data['created_at']) ?>
                        <div class="mt-1 small opacity-75">
                            (Sudah 
                            <?php 
                            $created = new DateTime($user_data['created_at']);
                            $now = new DateTime();
                            $diff = $created->diff($now);
                            echo ($diff->y > 0 ? $diff->y . ' th ' : '') . $diff->m . ' bln';
                            ?>)
                        </div>
                    </div>

                    <div class="d-grid gap-3">
                        <button class="btn-profile-action btn-edit" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-user-edit"></i> Edit Profil
                        </button>
                        <button class="btn-profile-action btn-password" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key"></i> Ganti Password
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Details & Stats -->
            <div class="col-lg-8">
                <div class="row g-4">
                    <!-- Detail Info -->
                    <div class="col-12">
                        <div class="profile-card animated" style="animation-delay: 0.2s">
                            <div class="card-title">
                                <i class="fas fa-id-card"></i>
                                Informasi Detail Akun
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">ID Pengguna</span>
                                <span class="info-value text-muted">#<?= str_pad($_SESSION['user_id'], 5, '0', STR_PAD_LEFT) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status Verifikasi</span>
                                <span class="info-value">
                                    <span class="status-badge active">
                                        <i class="fas fa-check-circle"></i> Terverifikasi
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email Pemulihan</span>
                                <span class="info-value"><?= htmlspecialchars($user_data['email']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Terakhir Login</span>
                                <span class="info-value"><?= date('d M Y, H:i') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="col-12">
                        <div class="profile-card animated" style="animation-delay: 0.3s">
                            <div class="card-title">
                                <i class="fas fa-chart-pie"></i>
                                Ringkasan Aktivitas Finansial
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <span class="info-label">Total Akun Terdaftar</span>
                                        <span class="info-value"><?= number_format($stats['total_accounts'] ?? 0) ?> Akun</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Total Kategori</span>
                                        <span class="info-value"><?= number_format($stats['total_categories'] ?? 0) ?> Kategori</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <span class="info-label">Pemasukan (Total)</span>
                                        <span class="info-value text-success"><?= formatRupiah($total_income) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Pengeluaran (Total)</span>
                                        <span class="info-value text-danger"><?= formatRupiah($total_expense) ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small fw-bold text-muted">Efisiensi Tabungan</span>
                                    <span class="small fw-bold <?= $total_income > 0 ? 'text-primary' : '' ?>">
                                        <?= $total_income > 0 ? round(($balance / $total_income) * 100, 1) : 0 ?>%
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?= $total_income > 0 ? max(0, min(100, ($balance / $total_income) * 100)) : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
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