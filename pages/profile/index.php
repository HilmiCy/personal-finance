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
    /* ========== PROFILE SPECIFIC STYLES ========== */
    .profile-header-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 45px; 
        text-align: center; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        transition: var(--transition);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        margin-bottom: 30px;
    }
    
    .avatar-wrapper {
        width: 140px;
        height: 140px;
        margin: 0 auto 30px;
        position: relative;
    }
    
    .profile-avatar-custom { 
        width: 100%; 
        height: 100%; 
        background: var(--fg); 
        border-radius: 50px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        color: white; 
        font-size: 60px; 
        font-weight: 800; 
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        transform: rotate(-5deg);
        transition: var(--transition);
    }
    .profile-header-card:hover .profile-avatar-custom { transform: rotate(0deg) scale(1.05); }
    
    .profile-name { font-size: 32px; font-weight: 850; color: var(--fg); margin-bottom: 8px; letter-spacing: -0.03em; }
    .profile-email { font-size: 15px; color: var(--muted); font-weight: 600; }
    
    .profile-info-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 40px; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        height: 100%;
        backdrop-filter: blur(10px);
    }
    
    .info-section-title {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--muted);
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .info-section-title i { color: var(--info); font-size: 16px; }
    
    .info-grid-row { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 18px 0; 
        border-bottom: 1px solid rgba(0,0,0,0.04); 
    }
    .info-grid-row:last-child { border-bottom: none; }
    
    .info-label { font-size: 13px; font-weight: 700; color: var(--muted); }
    .info-value { font-size: 14px; font-weight: 800; color: var(--fg); }
    
    .stat-pill-group { display: flex; gap: 15px; margin-top: 35px; justify-content: center; }
    .stat-pill-item { 
        background: var(--surface); 
        padding: 15px 25px; 
        border-radius: 20px; 
        text-align: center; 
        border: 1px solid rgba(0,0,0,0.03);
        min-width: 120px;
    }
    .stat-pill-number { font-size: 20px; font-weight: 850; color: var(--fg); display: block; }
    .stat-pill-label { font-size: 10px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }

    .btn-profile {
        padding: 16px 30px;
        border-radius: 18px;
        font-weight: 800;
        font-size: 14px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border: none;
        width: 100%;
    }
    .btn-edit-main { background: var(--fg); color: white; }
    .btn-edit-main:hover { background: #000; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }
    
    .btn-pass-alt { background: var(--surface); color: var(--fg); margin-top: 15px; border: 1px solid rgba(0,0,0,0.05); }
    .btn-pass-alt:hover { background: var(--border); transform: translateY(-2px); }

    .efficiency-card {
        background: var(--fg);
        border-radius: 24px;
        padding: 30px;
        color: white;
        margin-top: 35px;
        position: relative;
        overflow: hidden;
    }
    .efficiency-card::after {
        content: '\f201';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: -10px; bottom: -10px;
        font-size: 80px;
        opacity: 0.1;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-12">
                    <h1 class="welcome-title">Profil Pengguna</h1>
                    <p class="welcome-subtitle">Atur preferensi akun dan pantau ringkasan performa finansial Anda</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Primary Card -->
            <div class="col-lg-5">
                <div class="profile-header-card animated" style="animation-delay: 0.1s">
                    <div class="avatar-wrapper">
                        <div class="profile-avatar-custom" style="background: #1e293b; color: #ffffff;">
                            <?= strtoupper(substr($user_data['name'] ?? 'U', 0, 1)) ?>
                        </div>
                    </div>
                    
                    <h2 class="profile-name"><?= htmlspecialchars($user_data['name']) ?></h2>
                    <p class="profile-email"><?= htmlspecialchars($user_data['email']) ?></p>
                    
                    <div class="stat-pill-group">
                        <div class="stat-pill-item">
                            <span class="stat-pill-number text-primary"><?= number_format($total_transactions) ?></span>
                            <span class="stat-pill-label">Transaksi</span>
                        </div>
                        <div class="stat-pill-item">
                            <span class="stat-pill-number text-success"><?= number_format($stats['total_accounts'] ?? 0) ?></span>
                            <span class="stat-pill-label">Akun</span>
                        </div>
                    </div>

                    <div style="margin: 35px 0; padding: 20px; background: var(--surface); border-radius: 20px; font-size: 13px; font-weight: 700; color: var(--muted);">
                        <i class="fas fa-shield-alt me-2 text-success"></i>
                        Bergabung sejak <?= date('d M Y', strtotime($user_data['created_at'])) ?>
                    </div>

                    <button class="btn-profile btn-edit-main" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fas fa-pen-nib"></i> Edit Informasi Profil
                    </button>
                    <button class="btn-profile btn-pass-alt" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-lock"></i> Keamanan & Password
                    </button>
                </div>
            </div>

            <!-- Right Column: Details -->
            <div class="col-lg-7">
                <div class="profile-info-card animated" style="animation-delay: 0.2s">
                    <div class="info-section-title">
                        <i class="fas fa-user-shield"></i> Validitas Akun
                    </div>
                    
                    <div class="info-grid-row">
                        <span class="info-label">User ID Internal</span>
                        <span class="info-value">#<?= str_pad($user_data['id'], 5, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="info-grid-row">
                        <span class="info-label">Status Akun</span>
                        <span class="info-value text-success">
                            <i class="fas fa-check-circle me-1"></i> AKTIF & TERVERIFIKASI
                        </span>
                    </div>
                    <div class="info-grid-row">
                        <span class="info-label">Email Utama</span>
                        <span class="info-value"><?= htmlspecialchars($user_data['email']) ?></span>
                    </div>

                    <div class="info-section-title" style="margin-top: 50px;">
                        <i class="fas fa-chart-line"></i> Ringkasan Kumulatif
                    </div>

                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="info-grid-row">
                                <span class="info-label">Total Pemasukan</span>
                                <span class="info-value text-success"><?= formatRupiah($total_income) ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-grid-row">
                                <span class="info-label">Total Pengeluaran</span>
                                <span class="info-value text-danger"><?= formatRupiah($total_expense) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="efficiency-card">
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <div>
                                <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.7; margin-bottom: 5px;">Rasio Efisiensi</div>
                                <div style="font-size: 24px; font-weight: 850;">Tabungan Bersih</div>
                            </div>
                            <div style="font-size: 28px; font-weight: 900;">
                                <?= $total_income > 0 ? round(($balance / $total_income) * 100, 1) : 0 ?>%
                            </div>
                        </div>
                        <div class="progress" style="height: 10px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                            <div class="progress-bar bg-white" style="width: <?= $total_income > 0 ? max(0, min(100, ($balance / $total_income) * 100)) : 0 ?>%; border-radius: 10px;"></div>
                        </div>
                        <div style="font-size: 12px; margin-top: 15px; font-weight: 600; opacity: 0.8;">
                            Akumulasi saldo mengendap: <?= formatRupiah($balance) ?>
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
            confirmButtonColor: '#4285f4',
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
            confirmButtonColor: '#4285f4',
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
        confirmButtonColor: '#4285f4',
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
        confirmButtonColor: '#4285f4',
        confirmButtonText: 'OK'
    });
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>