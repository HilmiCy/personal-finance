<?php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Installment.php';
require_once '../../classes/Account.php';

if (!isset($_SESSION['user_id'])) {
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

include '../../includes/header.php';
?>

<style>
:root {
    --glass-bg: rgba(255, 255, 255, 0.95);
    --glass-border: rgba(255, 255, 255, 0.3);
    --shadow-sm: 0 8px 32px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 10px 40px rgba(0, 0, 0, 0.12);
    --primary: #4361ee;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --secondary: #6c757d;
}

body {
    background: #f8f9fa;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* Cards */
.content-card {
    background: white;
    border-radius: 24px;
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.content-card .card-header {
    background: white;
    border-bottom: 1px solid #e9ecef;
    padding: 1.25rem 1.5rem;
}

.content-card .card-header h5 {
    margin: 0;
    font-weight: 600;
    color: #1a1a2e;
    font-size: 1.1rem;
}

.content-card .card-body {
    padding: 1.5rem;
}

/* Info Card */
.info-card {
    background: linear-gradient(135deg, var(--primary), var(--info));
    border-radius: 20px;
    padding: 1.5rem;
    color: white;
    margin-bottom: 1.5rem;
}

.info-card h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.info-card p {
    margin-bottom: 0;
    opacity: 0.9;
}

.info-card .label {
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Payment Card */
.payment-card {
    background: linear-gradient(135deg, var(--success), #059669);
    border-radius: 20px;
    padding: 1.5rem;
    color: white;
}

.payment-card h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

/* Form Styles */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-control, .form-select {
    border-radius: 12px;
    border: 1px solid #e9ecef;
    padding: 0.625rem 1rem;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* Buttons */
.btn-primary-glass {
    background: linear-gradient(135deg, var(--primary), #3a56d4);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    color: white;
    transition: all 0.2s ease;
    width: 100%;
}

.btn-primary-glass:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    color: white;
}

.btn-secondary-glass {
    background: #6c757d;
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    color: white;
    transition: all 0.2s ease;
    width: 100%;
}

.btn-secondary-glass:hover {
    background: #5a6268;
    color: white;
}

/* Alert */
.alert-glass {
    border-radius: 12px;
    border: none;
    padding: 1rem;
    margin-bottom: 1rem;
}

/* Badge */
.badge-glass {
    background: #e9ecef;
    color: #495057;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Summary Item */
.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    color: #6c757d;
    font-weight: 500;
}

.summary-value {
    font-weight: 600;
    color: #1a1a2e;
}

/* Loading Spinner */
.spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    display: none;
}

.spinner-border-custom {
    width: 3rem;
    height: 3rem;
    border-width: 0.25rem;
}

/* Responsive */
@media (max-width: 768px) {
    .info-card h3 {
        font-size: 1.25rem;
    }
    
    .payment-card h2 {
        font-size: 1.5rem;
    }
    
    .btn-primary-glass, .btn-secondary-glass {
        padding: 0.5rem 1rem;
    }
}
</style>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-4 mb-4">
                <div>
                    <h1 class="h2 mb-1" style="font-weight: 700; color: #1a1a2e;">
                        <i class="fas fa-money-bill-wave me-2" style="color: var(--success);"></i>
                        Bayar Cicilan
                    </h1>
                    <p class="text-muted mb-0">
                        <a href="history.php?id=<?php echo $installment_id; ?>" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Riwayat
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertContainer"></div>
            
            <div class="row">
                <div class="col-lg-8">
                    <!-- Form Pembayaran -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5>
                                <i class="fas fa-credit-card me-2" style="color: var(--primary);"></i>
                                Form Pembayaran
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="paymentForm">
                                <input type="hidden" id="installment_id" name="installment_id" value="<?php echo $installment_id; ?>">
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="payment_date" class="form-label">Tanggal Pembayaran *</label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label for="amount" class="form-label">Jumlah Bayar *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control" id="amount" name="amount" required placeholder="0" value="<?php echo number_format($installmentData['amount_per_tenor'], 0, ',', '.'); ?>">
                                        </div>
                                        <small class="text-muted">Jumlah minimal: Rp <?php echo number_format($installmentData['amount_per_tenor'], 0, ',', '.'); ?></small>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label for="account_id" class="form-label">Akun Sumber Dana *</label>
                                        <select class="form-select" id="account_id" name="account_id" required>
                                            <option value="">Pilih Akun</option>
                                            <?php foreach ($accounts as $acc): ?>
                                            <option value="<?php echo $acc['id']; ?>" data-balance="<?php echo $acc['balance']; ?>">
                                                <?php echo htmlspecialchars($acc['name']); ?> - Rp <?php echo number_format($acc['balance'], 0, ',', '.'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label for="notes" class="form-label">Catatan Pembayaran</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Catatan tambahan..."></textarea>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-2">
                                        <button type="button" class="btn btn-secondary-glass" onclick="window.location.href='history.php?id=<?php echo $installment_id; ?>'">
                                            <i class="fas fa-times me-2"></i>Batal
                                        </button>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <button type="submit" class="btn btn-primary-glass" id="submitBtn">
                                            <i class="fas fa-check-circle me-2"></i>Bayar Sekarang
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Info Cicilan -->
                    <div class="info-card">
                        <div class="label">Pembayaran Ke-</div>
                        <h3><?php echo $current_payment_number; ?> / <?php echo $installmentData['tenor']; ?></h3>
                        <p class="mb-0">
                            <i class="fas fa-calendar-alt me-1"></i> Jatuh Tempo: <?php echo date('d M Y', strtotime($due_date)); ?>
                        </p>
                    </div>
                    
                    <!-- Ringkasan Cicilan -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5>
                                <i class="fas fa-chart-line me-2" style="color: var(--primary);"></i>
                                Ringkasan Cicilan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="summary-item">
                                <span class="summary-label">Nama Cicilan</span>
                                <span class="summary-value"><?php echo htmlspecialchars($installmentData['name']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total Cicilan</span>
                                <span class="summary-value">Rp <?php echo number_format($installmentData['total_amount'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Sudah Dibayar</span>
                                <span class="summary-value">Rp <?php echo number_format($installmentData['paid_amount'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Sisa Tagihan</span>
                                <span class="summary-value" style="color: var(--warning);">Rp <?php echo number_format($installmentData['remaining_amount'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Cicilan per Tenor</span>
                                <span class="summary-value">Rp <?php echo number_format($installmentData['amount_per_tenor'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Tenor</span>
                                <span class="summary-value"><?php echo $installmentData['tenor'] . ' ' . $installmentData['tenor_type']; ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Progress</span>
                                <span class="summary-value">
                                    <?php 
                                    $progress = ($installmentData['total_amount'] > 0) 
                                        ? ($installmentData['paid_amount'] / $installmentData['total_amount']) * 100 
                                        : 0;
                                    echo round($progress, 1); ?>%
                                </span>
                            </div>
                            <div class="progress-glass mt-2">
                                <div class="progress-bar" style="width: <?php echo min(100, $progress); ?>%; background: linear-gradient(90deg, var(--primary), var(--info));"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Denda -->
                    <div class="alert alert-warning alert-glass">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Informasi Denda:</strong><br>
                        Jika pembayaran melewati tanggal jatuh tempo, akan dikenakan denda 2% dari jumlah pembayaran.
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Loading Spinner -->
<div class="spinner-overlay" id="spinnerOverlay">
    <div class="spinner-border spinner-border-custom text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Format currency input
    $('#amount').on('input', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(formatRupiah(value));
        }
        validateAmount();
    });
    
    function formatRupiah(angka) {
        let number_string = angka.toString();
        let sisa = number_string.length % 3;
        let rupiah = number_string.substr(0, sisa);
        let ribuan = number_string.substr(sisa).match(/\d{3}/g);
        
        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
    
    function validateAmount() {
        let amount = $('#amount').val().replace(/[^0-9]/g, '');
        let requiredAmount = <?php echo $installmentData['amount_per_tenor']; ?>;
        
        if (amount && parseInt(amount) < requiredAmount) {
            $('#amount').addClass('is-invalid');
            return false;
        } else {
            $('#amount').removeClass('is-invalid');
            return true;
        }
    }
    
    // Validasi saldo akun saat dipilih
    $('#account_id').on('change', function() {
        let selectedOption = $(this).find('option:selected');
        let balance = selectedOption.data('balance');
        let amount = $('#amount').val().replace(/[^0-9]/g, '');
        
        if (amount && balance && parseInt(amount) > balance) {
            showAlert('Saldo akun tidak mencukupi! Saldo tersedia: Rp ' + formatRupiah(balance.toString()), 'danger');
            $('#submitBtn').prop('disabled', true);
        } else {
            $('#submitBtn').prop('disabled', false);
        }
    });
    
    // Submit form
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        
        let installmentId = $('#installment_id').val();
        let accountId = $('#account_id').val();
        let amount = $('#amount').val().replace(/[^0-9]/g, '');
        let paymentDate = $('#payment_date').val();
        let notes = $('#notes').val();
        
        // Validasi
        if (!accountId) {
            showAlert('Akun sumber dana harus dipilih!', 'danger');
            return;
        }
        
        if (!amount || parseInt(amount) <= 0) {
            showAlert('Jumlah bayar harus diisi!', 'danger');
            return;
        }
        
        let requiredAmount = <?php echo $installmentData['amount_per_tenor']; ?>;
        if (parseInt(amount) < requiredAmount) {
            showAlert('Jumlah bayar minimal Rp ' + formatRupiah(requiredAmount.toString()), 'danger');
            return;
        }
        
        // Tambahkan waktu sekarang ke payment_date
        if (paymentDate) {
            let now = new Date();
            let timeString = now.toTimeString().split(' ')[0]; // Format HH:MM:SS
            paymentDate = paymentDate + ' ' + timeString;
        }
        
        // Cek saldo akun
        let selectedOption = $('#account_id').find('option:selected');
        let balance = selectedOption.data('balance');
        if (balance && parseInt(amount) > balance) {
            showAlert('Saldo akun tidak mencukupi!', 'danger');
            return;
        }
        
        // Siapkan data
        let formData = new FormData();
        formData.append('action', 'make_payment');
        formData.append('installment_id', installmentId);
        formData.append('account_id', accountId);
        formData.append('amount', amount);
        formData.append('payment_date', paymentDate); // Kirim dengan format YYYY-MM-DD HH:MM:SS
        formData.append('notes', notes);
        
        // Tampilkan loading
        $('#spinnerOverlay').fadeIn();
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Memproses...');
        
        // Kirim AJAX
        $.ajax({
            url: 'process_payment.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#spinnerOverlay').fadeOut();
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Bayar Sekarang');
                
                if (response.success) {
                    showAlert(response.message, 'success');
                    
                    // Redirect ke history setelah 1.5 detik
                    setTimeout(function() {
                        window.location.href = 'history.php?id=' + installmentId;
                    }, 1500);
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                $('#spinnerOverlay').fadeOut();
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Bayar Sekarang');
                showAlert('Terjadi kesalahan: ' + error, 'danger');
            }
        });
    });
    
    function showAlert(message, type) {
        let alertHtml = `
            <div class="alert alert-${type} alert-glass alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#alertContainer').html(alertHtml);
        
        // Auto hide after 3 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Inisialisasi validasi awal
    validateAmount();
});
</script>

<?php include '../../includes/footer.php'; ?>