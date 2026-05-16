<?php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Installment.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$installment = new Installment();
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

// Ambil riwayat pembayaran
$paymentHistory = $installment->getPaymentHistory($installment_id, $_SESSION['user_id']);

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
    --info: #3b82f6;
    --danger: #ef4444;
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

/* Info Cards */
.info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 1.5rem;
    color: white;
    margin-bottom: 1.5rem;
}

.info-card h3 {
    font-size: 2rem;
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

/* Progress Bar */
.progress-glass {
    height: 10px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-glass .progress-bar {
    background: white;
    border-radius: 10px;
    transition: width 0.6s ease;
}

/* Table Styles */
.table-custom {
    margin-bottom: 0;
}

.table-custom thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    color: #495057;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
}

.table-custom tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
    color: #2c3e50;
}

.table-custom tbody tr:hover {
    background: #f8f9fa;
}

/* Badge */
.badge-status {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-paid {
    background: #d1fae5;
    color: #065f46;
}

.badge-late {
    background: #fee2e2;
    color: #991b1b;
}

.badge-pending {
    background: #fed7aa;
    color: #92400e;
}

/* Buttons */
.btn-back {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 0.5rem 1.25rem;
    font-weight: 500;
    color: #495057;
    transition: all 0.2s ease;
}

.btn-back:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-pay {
    background: linear-gradient(135deg, var(--success), #059669);
    border: none;
    border-radius: 12px;
    padding: 0.5rem 1.25rem;
    font-weight: 500;
    color: white;
    transition: all 0.2s ease;
}

.btn-pay:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    color: white;
}

/* Summary Cards */
.summary-card {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.summary-card .card-icon {
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}

.summary-card .card-title {
    color: #6c757d;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.summary-card .card-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 0;
}

.text-amount {
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .info-card h3 {
        font-size: 1.5rem;
    }
    
    .table-custom thead th {
        font-size: 0.75rem;
        padding: 0.75rem;
    }
    
    .table-custom tbody td {
        padding: 0.75rem;
        font-size: 0.875rem;
    }
    
    .summary-card .card-value {
        font-size: 1rem;
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
                        <i class="fas fa-history me-2" style="color: var(--primary);"></i>
                        Riwayat Pembayaran
                    </h1>
                    <p class="text-muted mb-0">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Cicilan
                        </a>
                    </p>
                </div>
                <div>
                    <a href="pay.php?id=<?php echo $installment_id; ?>" class="btn btn-pay">
                        <i class="fas fa-money-bill-wave me-2"></i>Bayar Cicilan
                    </a>
                </div>
            </div>
            
            <!-- Info Cicilan -->
            <div class="info-card mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3><?php echo htmlspecialchars($installmentData['name']); ?></h3>
                        <p class="mb-0">
                            <i class="fas fa-credit-card me-1"></i> 
                            <?php echo htmlspecialchars($installmentData['account_name'] ?? 'Akun tidak ditemukan'); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <p class="mb-0">
                            <span class="label">Status</span><br>
                            <?php if ($installmentData['status'] == 'active'): ?>
                                <span class="badge-status" style="background: #fee2e2; color: #dc2626;">Aktif</span>
                            <?php elseif ($installmentData['status'] == 'completed'): ?>
                                <span class="badge-status" style="background: #d1fae5; color: #065f46;">Selesai</span>
                            <?php else: ?>
                                <span class="badge-status" style="background: #fed7aa; color: #92400e;"><?php echo $installmentData['status']; ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                        </div>
                        <div class="card-title">Total Cicilan</div>
                        <div class="card-value">
                            Rp <?php echo number_format($installmentData['total_amount'], 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        </div>
                        <div class="card-title">Sudah Dibayar</div>
                        <div class="card-value">
                            Rp <?php echo number_format($installmentData['paid_amount'], 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-clock" style="color: var(--warning);"></i>
                        </div>
                        <div class="card-title">Sisa Tagihan</div>
                        <div class="card-value">
                            Rp <?php echo number_format($installmentData['remaining_amount'], 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-alt" style="color: var(--info);"></i>
                        </div>
                        <div class="card-title">Progress</div>
                        <div class="card-value">
                            <?php 
                            $progress = ($installmentData['total_amount'] > 0) 
                                ? ($installmentData['paid_amount'] / $installmentData['total_amount']) * 100 
                                : 0;
                            echo round($progress, 1); ?>%
                        </div>
                        <div class="progress-glass">
                            <div class="progress-bar" style="width: <?php echo min(100, $progress); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detail Cicilan -->
            <div class="content-card mb-4">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-info-circle me-2" style="color: var(--primary);"></i>
                        Detail Cicilan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Tenor</small>
                            <strong><?php echo $installmentData['tenor'] . ' ' . $installmentData['tenor_type']; ?></strong>
                        </div>
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Cicilan per Tenor</small>
                            <strong>Rp <?php echo number_format($installmentData['amount_per_tenor'], 0, ',', '.'); ?></strong>
                        </div>
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Tanggal Mulai</small>
                            <strong><?php echo date('d M Y', strtotime($installmentData['start_date'])); ?></strong>
                        </div>
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Tanggal Selesai</small>
                            <strong><?php echo date('d M Y', strtotime($installmentData['end_date'])); ?></strong>
                        </div>
                        <?php if (!empty($installmentData['interest_rate']) && $installmentData['interest_rate'] > 0): ?>
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Bunga</small>
                            <strong><?php echo $installmentData['interest_rate']; ?>%</strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($installmentData['notes'])): ?>
                        <div class="col-md-12 mb-3">
                            <small class="text-muted d-block">Catatan</small>
                            <strong><?php echo nl2br(htmlspecialchars($installmentData['notes'])); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Riwayat Pembayaran -->
            <div class="content-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-list me-2" style="color: var(--primary);"></i>
                        Riwayat Pembayaran
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Jumlah Bayar</th>
                                    <th>Denda</th>
                                    <th>Total Dibayar</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paymentHistory)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-inbox fa-2x mb-3 text-muted"></i>
                                        <p class="text-muted mb-0">Belum ada riwayat pembayaran</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($paymentHistory as $index => $payment): ?>
                                <tr>
                                    <td>
                                        <span class="badge-status" style="background: #e9ecef; color: #495057;">
                                            #<?php echo $payment['payment_number']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($payment['payment_date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y', strtotime($payment['due_date'])); ?>
                                        <?php if (strtotime($payment['payment_date']) > strtotime($payment['due_date'])): ?>
                                        <br>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-circle"></i> Terlambat
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-amount">
                                        Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="text-amount text-danger">
                                        <?php if ($payment['penalty_amount'] > 0): ?>
                                        Rp <?php echo number_format($payment['penalty_amount'], 0, ',', '.'); ?>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-amount fw-bold">
                                        Rp <?php echo number_format($payment['total_paid'], 0, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['status'] == 'paid'): ?>
                                        <span class="badge-status badge-paid">
                                            <i class="fas fa-check-circle me-1"></i> Lunas
                                        </span>
                                        <?php elseif ($payment['status'] == 'late'): ?>
                                        <span class="badge-status badge-late">
                                            <i class="fas fa-exclamation-triangle me-1"></i> Terlambat
                                        </span>
                                        <?php else: ?>
                                        <span class="badge-status badge-pending">
                                            <?php echo $payment['status']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($payment['notes'])): ?>
                                        <small class="text-muted" title="<?php echo htmlspecialchars($payment['notes']); ?>">
                                            <i class="fas fa-comment"></i> <?php echo substr(htmlspecialchars($payment['notes']), 0, 30); ?>
                                            <?php if (strlen($payment['notes']) > 30): ?>...<?php endif; ?>
                                        </small>
                                        <?php else: ?>
                                        <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Informasi Tambahan -->
            <?php if ($installmentData['status'] == 'active'): ?>
            <div class="alert alert-info alert-glass mt-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informasi:</strong> 
                Cicilan ini masih aktif. 
                <?php 
                $next_payment = $installmentData['current_tenor'] + 1;
                $due_date = $installment->getDueDate($installmentData);
                ?>
                Pembayaran selanjutnya adalah cicilan ke-<?php echo $next_payment; ?> dari <?php echo $installmentData['tenor']; ?> 
                dengan jatuh tempo pada <strong><?php echo date('d M Y', strtotime($due_date)); ?></strong> 
                sebesar <strong>Rp <?php echo number_format($installmentData['amount_per_tenor'], 0, ',', '.'); ?></strong>.
            </div>
            <?php elseif ($installmentData['status'] == 'completed'): ?>
            <div class="alert alert-success alert-glass mt-4">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Selamat!</strong> Cicilan ini telah lunas pada <?php echo date('d M Y', strtotime($installmentData['updated_at'] ?? date('Y-m-d'))); ?>.
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>