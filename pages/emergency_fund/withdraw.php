<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/EmergencyFund.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Tarik Dana Darurat';
$current_page = 'emergency_fund';

$user_id = $_SESSION['user_id'];
$emergencyFund = new EmergencyFund();

// Ambil data dana darurat saat ini
$fund = $emergencyFund->getEmergencyFund($user_id);
$current_amount = isset($fund['current_amount']) ? $fund['current_amount'] : 0;
$target = $emergencyFund->getEmergencyFundTarget($user_id);
$status = $emergencyFund->getStatus($user_id);

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = str_replace(['Rp', '.', ' ', ','], '', $_POST['amount']);
    $description = $_POST['description'] ?? 'Penarikan dana darurat';
    
    if ($amount <= 0) {
        $_SESSION['error_message'] = "Jumlah penarikan harus lebih dari 0!";
    } elseif ($amount > $current_amount) {
        $_SESSION['error_message'] = "Saldo tidak mencukupi! Saldo saat ini: Rp " . number_format($current_amount, 0, ',', '.');
    } else {
        if ($emergencyFund->addTransaction($user_id, 'withdraw', $amount, $description)) {
            $_SESSION['success_message'] = "Berhasil menarik dana darurat sebesar Rp " . number_format($amount, 0, ',', '.');
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['error_message'] = "Gagal menarik dana darurat!";
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- SweetAlert2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .withdraw-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 30px;
        animation: fadeInUp 0.5s ease;
        border-left: 4px solid #ef4444;
    }
    
    .current-balance {
        background: rgba(234, 67, 53, 0.08);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        margin-bottom: 30px;
        border: 1px solid rgba(234, 67, 53, 0.1);
    }
    
    .current-balance .label {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 8px;
    }
    
    .current-balance .amount {
        font-size: 32px;
        font-weight: 800;
        color: #ef4444;
    }
    
    .warning-box {
        background: rgba(245, 158, 11, 0.1);
        border-left: 4px solid #f59e0b;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .warning-box i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .warning-box .title {
        font-weight: 600;
        color: #f59e0b;
        margin-bottom: 5px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .btn-submit {
        background: var(--danger);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
    }
    
    .btn-back {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
        border: 1px solid #e5e7eb;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: rgba(107, 114, 128, 0.2);
        text-decoration: none;
        color: #4b5563;
    }
    
    .info-box {
        background: rgba(59, 130, 246, 0.1);
        border-radius: 12px;
        padding: 15px;
        margin-top: 20px;
    }
    
    .info-box i {
        color: #3b82f6;
        margin-right: 8px;
    }
    
    .info-box small {
        color: #6b7280;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .status-badge.danger {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }
    
    .status-badge.warning {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }
    
    .status-badge.success {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Header -->
                <div class="welcome-card animated mb-4">
                    <div class="row align-items-center">
                        <div class="col-12">
                            <h1 class="welcome-title" style="animation: fadeInDown 0.5s ease;">
                                <i class="fas fa-minus-circle" style="color: #ef4444;"></i> Tarik Dana Darurat
                            </h1>
                            <p class="welcome-subtitle" style="animation: fadeInUp 0.5s ease 0.2s both;">
                                Hanya gunakan untuk keadaan darurat yang sesungguhnya
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="withdraw-card">
                    <div class="current-balance">
                        <div class="label">Saldo Dana Darurat Saat Ini</div>
                        <div class="amount">Rp <?= number_format($current_amount, 0, ',', '.') ?></div>
                        <div class="mt-2">
                            <span class="status-badge <?= $status['status'] ?>">
                                <i class="fas <?= $status['icon'] ?>"></i> <?= $status['message'] ?>
                            </span>
                        </div>
                    </div>

                    <div class="warning-box">
                        <div class="title">
                            <i class="fas fa-exclamation-triangle"></i> Perhatian!
                        </div>
                        <small>Dana darurat hanya boleh digunakan untuk keperluan mendesak dan tidak terduga seperti:</small>
                        <ul class="mt-2 mb-0">
                            <li><small>Keperluan medis darurat</small></li>
                            <li><small>Kehilangan pekerjaan</small></li>
                            <li><small>Perbaikan rumah mendesak</small></li>
                        </ul>
                    </div>

                    <form method="POST" id="withdrawForm">
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Jumlah Penarikan</label>
                            <input type="text" name="amount_display" id="amount_display" class="form-control currency-input" placeholder="Rp 0" required autofocus>
                            <input type="hidden" name="amount" id="amount_hidden">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-info-circle"></i> Keterangan (Opsional)</label>
                            <input type="text" name="description" class="form-control" placeholder="Contoh: Biaya rumah sakit darurat">
                        </div>

                        <div class="info-box">
                            <i class="fas fa-lightbulb"></i>
                            <small>Setelah penarikan, usahakan segera mengisi kembali dana darurat Anda untuk tetap aman.</small>
                        </div>

                        <div class="d-flex gap-3 mt-4">
                            <a href="index.php" class="btn-back flex-grow-1 text-center">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn-submit flex-grow-1">
                                <i class="fas fa-check"></i> Tarik Dana
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Format currency input
    const amountDisplay = document.getElementById('amount_display');
    const amountHidden = document.getElementById('amount_hidden');
    const currentBalance = <?= $current_amount ?>;
    
    amountDisplay.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, '');
        
        if (value) {
            let numericValue = parseInt(value);
            
            // Cek jika melebihi saldo
            if (numericValue > currentBalance) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
            
            amountHidden.value = numericValue;
            this.value = 'Rp ' + numericValue.toLocaleString('id-ID');
        } else {
            amountHidden.value = 0;
            this.value = '';
            this.style.borderColor = '#e5e7eb';
        }
    });

    // SweetAlert2 confirmation
    document.getElementById('withdrawForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let amount = amountHidden.value;
        let amountDisplay = document.getElementById('amount_display').value;
        let description = document.querySelector('input[name="description"]').value || '-';
        
        if (!amount || amount == 0) {
            Swal.fire({
                title: '<span style="font-size: 24px;">😅 Oops!</span>',
                text: 'Jumlah penarikan harus diisi',
                icon: 'error',
                confirmButtonText: '<i class="fas fa-check"></i> OK'
            });
            return;
        }
        
        if (parseInt(amount) > currentBalance) {
            Swal.fire({
                title: '<span style="font-size: 24px;">⚠️ Saldo Tidak Cukup!</span>',
                text: `Saldo Anda hanya Rp ${currentBalance.toLocaleString('id-ID')}`,
                icon: 'warning',
                confirmButtonText: '<i class="fas fa-check"></i> OK'
            });
            return;
        }
        
        Swal.fire({
            title: '<span style="font-size: 24px;">💸 Tarik Dana Darurat?</span>',
            html: `
                <div style="animation: fadeInUp 0.4s ease;">
                    <div style="text-align: left; background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 12px;">
                        <p><strong><i class="fas fa-money-bill-wave"></i> Jumlah:</strong> <span style="color: #ef4444; font-size: 20px;">${amountDisplay}</span></p>
                        <p><strong><i class="fas fa-info-circle"></i> Keterangan:</strong> ${description}</p>
                    </div>
                    <div style="margin-top: 15px; padding: 10px; background: rgba(245, 158, 11, 0.1); border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                        <small style="color: #f59e0b;">Sisa saldo setelah penarikan: Rp ${(currentBalance - parseInt(amount)).toLocaleString('id-ID')}</small>
                    </div>
                    <small style="display: block; margin-top: 12px; color: #ef4444;">⚠️ Pastikan ini benar-benar untuk keadaan darurat!</small>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check"></i> Ya, Tarik!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(0, 0, 0, 0.4)',
            customClass: {
                popup: 'swal2-popup',
                confirmButton: 'swal2-confirm'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '<span style="font-size: 24px;">🔄 Memproses...</span>',
                    html: '<div style="animation: pulse 1s ease infinite;">Sedang menarik dana</div>',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                setTimeout(() => {
                    document.getElementById('withdrawForm').submit();
                }, 500);
            }
        });
    });
    
    <?php if (isset($_SESSION['error_message'])): ?>
    Swal.fire({
        title: '<span style="font-size: 24px;">😔 Gagal!</span>',
        text: '<?= $_SESSION['error_message'] ?>',
        icon: 'error',
        confirmButtonText: '<i class="fas fa-check"></i> OK'
    });
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>