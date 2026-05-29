<?php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Installment.php';
require_once '../../classes/Account.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$installment = new Installment();
$account = new Account();

$installment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($installment_id <= 0) {
    header('Location: index.php');
    exit;
}

// Ambil data cicilan
$installmentData = $installment->getById($installment_id, $_SESSION['user_id']);

if (!$installmentData) {
    header('Location: index.php');
    exit;
}

// Cek apakah cicilan masih aktif
if ($installmentData['status'] != 'active') {
    header('Location: history.php?id=' . $installment_id);
    exit;
}

// Ambil daftar akun user
$accounts = $account->getAll($_SESSION['user_id']);

// Hitung jatuh tempo
$due_date = $installment->getDueDate($installmentData);
$current_payment_number = $installmentData['current_tenor'] + 1;

$page_title = 'Bayar Cicilan';
$current_page = 'installments';

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* ========== PAYMENT SPECIFIC STYLES ========== */
    .content-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        overflow: hidden; 
        backdrop-filter: blur(10px);
        margin-bottom: 30px;
        transition: var(--transition);
    }
    
    .content-card .card-header { 
        background: rgba(255, 255, 255, 0.2); 
        border-bottom: 1px solid rgba(0, 0, 0, 0.05); 
        padding: 30px 35px; 
    }
    
    .content-card .card-header h5 { 
        margin: 0; 
        font-weight: 800; 
        color: var(--fg); 
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .info-card {
        background: var(--fg);
        color: white;
        border-radius: 32px;
        padding: 35px;
        margin-bottom: 30px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    .info-card::after {
        content: '\f0d0';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: -10px; bottom: -10px;
        font-size: 100px;
        opacity: 0.1;
        color: white;
    }
    
    .info-card .label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.7; margin-bottom: 10px; display: block; }
    .info-card h3 { font-size: 28px; font-weight: 800; margin-bottom: 5px; letter-spacing: -0.02em; }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .summary-item:last-child { border-bottom: none; }
    .summary-label { font-size: 13px; color: var(--muted); font-weight: 600; }
    .summary-value { font-size: 14px; font-weight: 750; color: var(--fg); }
    
    .progress-glass { height: 8px; background: rgba(0, 0, 0, 0.04); border-radius: 10px; overflow: hidden; }
    .progress-bar { transition: width 1s ease; }
    
    .spinner-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="welcome-title">Bayar Cicilan</h1>
                    <p class="welcome-subtitle">
                        <a href="history.php?id=<?php echo $installment_id; ?>" class="text-decoration-none" style="color: inherit;">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Riwayat
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Form Pembayaran -->
                <div class="content-card animated" style="animation-delay: 0.1s">
                    <div class="card-header">
                        <h5><i class="fas fa-credit-card"></i> Form Pembayaran</h5>
                    </div>
                    <div class="card-body p-5">
                        <form id="paymentForm">
                            <input type="hidden" id="installment_id" name="installment_id" value="<?php echo $installment_id; ?>">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Tanggal Pembayaran</label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Jumlah Bayar</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                    <input type="text" class="form-control border-start-0 ps-0" id="amount" name="amount" required value="<?php echo number_format($installmentData['amount_per_tenor'], 0, ',', '.'); ?>">
                                </div>
                                <div class="mt-2" style="font-size: 12px; font-weight: 700; color: var(--muted);">Minimal: Rp <?php echo number_format($installmentData['amount_per_tenor'], 0, ',', '.'); ?></div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Sumber Dana</label>
                                <select class="form-select" id="account_id" name="account_id" required>
                                    <option value="">Pilih Akun</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?php echo $acc['id']; ?>" data-balance="<?php echo $acc['balance']; ?>">
                                        <?php echo htmlspecialchars($acc['name']); ?> (Rp <?php echo number_format($acc['balance'], 0, ',', '.'); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-5">
                                <label class="form-label fw-bold">Catatan</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Contoh: Pembayaran bulan ini"></textarea>
                            </div>
                            
                            <div class="d-flex gap-3">
                                <button type="button" class="btn btn-secondary flex-grow-1 py-3 rounded-pill fw-bold" onclick="window.location.href='history.php?id=<?php echo $installment_id; ?>'">Batal</button>
                                <button type="submit" class="btn btn-primary-custom flex-grow-2 py-3 rounded-pill fw-bold" id="submitBtn">Bayar Sekarang</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Info Status -->
                <div class="info-card animated" style="animation-delay: 0.2s">
                    <span class="label">Tenor Ke-</span>
                    <h3><?php echo $current_payment_number; ?> / <?php echo $installmentData['tenor']; ?></h3>
                    <div class="mt-3 d-flex align-items-center gap-2" style="font-size: 13px; font-weight: 600;">
                        <i class="fas fa-calendar-check"></i>
                        Jatuh Tempo: <?= date('d M Y', strtotime($due_date)) ?>
                    </div>
                </div>
                
                <!-- Ringkasan -->
                <div class="content-card animated" style="animation-delay: 0.3s">
                    <div class="card-header py-4">
                        <h5><i class="fas fa-receipt"></i> Detail Cicilan</h5>
                    </div>
                    <div class="card-body px-5 pb-5">
                        <div class="summary-item">
                            <span class="summary-label">Nama</span>
                            <span class="summary-value text-end"><?= htmlspecialchars($installmentData['name']) ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Total Pinjaman</span>
                            <span class="summary-value text-end">Rp <?= number_format($installmentData['total_amount'], 0, ',', '.') ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Sisa Hutang</span>
                            <span class="summary-value text-end text-danger">Rp <?= number_format($installmentData['remaining_amount'], 0, ',', '.') ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Progress</span>
                            <span class="summary-value text-end">
                                <?php 
                                $progress = ($installmentData['total_amount'] > 0) ? ($installmentData['paid_amount'] / $installmentData['total_amount']) * 100 : 0;
                                echo round($progress, 1); ?>%
                            </span>
                        </div>
                        <div class="progress-glass mt-3">
                            <div class="progress-bar" style="width: <?= min(100, $progress) ?>%; background: var(--info);"></div>
                        </div>
                    </div>
                </div>
                
                <div class="alert animated" style="animation-delay: 0.4s; background: rgba(251,188,5,0.08); border: 1px solid rgba(251,188,5,0.2); border-radius: 20px; padding: 25px; color: #b45309;">
                    <div class="fw-bold mb-2"><i class="fas fa-info-circle me-2"></i> Ketentuan Denda</div>
                    <p class="mb-0" style="font-size: 13px; line-height: 1.6; font-weight: 600;">Pembayaran lewat jatuh tempo dikenakan denda 2% dari jumlah tagihan per bulan.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="spinner-overlay" id="spinnerOverlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#amount').on('input', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        if (value) $(this).val(new Intl.NumberFormat('id-ID').format(value));
    });
    
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        formData.append('action', 'make_payment');
        formData.append('amount', $('#amount').val().replace(/[^0-9]/g, ''));
        
        $('#spinnerOverlay').fadeIn();
        $('#submitBtn').prop('disabled', true);
        
        $.ajax({
            url: 'process_payment.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#spinnerOverlay').fadeOut();
                if (response.success) {
                    Swal.fire('Berhasil!', response.message, 'success').then(() => {
                        window.location.href = 'history.php?id=' + $('#installment_id').val();
                    });
                } else {
                    Swal.fire('Gagal!', response.message, 'error');
                    $('#submitBtn').prop('disabled', false);
                }
            },
            error: function() {
                $('#spinnerOverlay').fadeOut();
                Swal.fire('Error!', 'Terjadi kesalahan sistem', 'error');
                $('#submitBtn').prop('disabled', false);
            }
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>