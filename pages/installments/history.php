<?php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Installment.php';
require_once '../../includes/functions.php';

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

$page_title = 'Riwayat Cicilan';
$current_page = 'installments';

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* ========== HISTORY SPECIFIC STYLES ========== */
    .summary-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 25px; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        transition: var(--transition); 
        height: 100%; 
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        display: flex;
        flex-direction: column;
    }
    .summary-card:hover { transform: translateY(-3px); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.06); }
    
    .summary-card .card-icon { 
        width: 44px;
        height: 44px;
        background: var(--surface);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px; 
        margin-bottom: 15px; 
        color: var(--info);
        border: 1px solid var(--border);
    }
    
    .summary-card .card-title { font-size: 10px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
    .summary-card .card-value { font-size: 18px; font-weight: 800; color: var(--fg); letter-spacing: -0.01em; }
    
    .content-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        overflow: hidden; 
        backdrop-filter: blur(10px);
        margin-bottom: 30px;
    }
    
    .content-card .card-header { 
        background: rgba(255, 255, 255, 0.2); 
        border-bottom: 1px solid rgba(0, 0, 0, 0.05); 
        padding: 25px 35px; 
    }
    
    .content-card .card-header h5 { 
        margin: 0; 
        font-weight: 800; 
        color: var(--fg); 
        font-size: 16px;
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
    
    .status-badge-custom {
        padding: 8px 18px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .badge-active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .badge-completed { background: rgba(66, 133, 244, 0.1); color: #4285f4; }
    
    .table-custom thead th {
        background: rgba(0, 0, 0, 0.01);
        padding: 20px 25px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--muted);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .table-custom tbody td {
        padding: 22px 25px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        font-size: 14px;
    }
    
    .progress-glass { height: 6px; background: rgba(0, 0, 0, 0.04); border-radius: 10px; overflow: hidden; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="welcome-title">Riwayat Pembayaran</h1>
                    <p class="welcome-subtitle">
                        <a href="index.php" class="text-decoration-none" style="color: inherit;">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Cicilan
                        </a>
                    </p>
                </div>
                <div class="col-md-5 text-md-end">
                    <?php if ($installmentData['status'] == 'active'): ?>
                    <a href="pay.php?id=<?php echo $installment_id; ?>" class="btn-primary-custom">
                        <i class="fas fa-money-bill-wave me-2"></i> Bayar Sekarang
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Main Info -->
        <div class="info-card animated" style="animation-delay: 0.1s">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; opacity: 0.6; margin-bottom: 8px;">Detail Cicilan</div>
                    <h3 style="font-size: 32px; font-weight: 800; margin-bottom: 10px;"><?php echo htmlspecialchars($installmentData['name']); ?></h3>
                    <div class="d-flex align-items-center gap-3" style="opacity: 0.8; font-weight: 600; font-size: 14px;">
                        <span><i class="fas fa-credit-card me-2"></i> <?php echo htmlspecialchars($installmentData['account_name'] ?? '-'); ?></span>
                        <span>•</span>
                        <span><i class="fas fa-calendar-alt me-2"></i> Mulai: <?= date('d M Y', strtotime($installmentData['start_date'])) ?></span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="status-badge-custom <?= $installmentData['status'] == 'active' ? 'badge-active' : 'badge-completed' ?>">
                        <i class="fas fa-circle me-2" style="font-size: 8px;"></i>
                        <?= $installmentData['status'] == 'active' ? 'Cicilan Aktif' : 'Cicilan Lunas' ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="summary-card animated" style="animation-delay: 0.2s">
                    <div class="card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="card-title">Total Pinjaman</div>
                    <div class="card-value">Rp <?= number_format($installmentData['total_amount'], 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card animated" style="animation-delay: 0.3s">
                    <div class="card-icon" style="color: #10b981;"><i class="fas fa-check-double"></i></div>
                    <div class="card-title">Sudah Terbayar</div>
                    <div class="card-value text-success">Rp <?= number_format($installmentData['paid_amount'], 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card animated" style="animation-delay: 0.4s">
                    <div class="card-icon" style="color: #ea4335;"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="card-title">Sisa Tagihan</div>
                    <div class="card-value text-danger">Rp <?= number_format($installmentData['remaining_amount'], 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card animated" style="animation-delay: 0.5s">
                    <?php $progress = ($installmentData['total_amount'] > 0) ? ($installmentData['paid_amount'] / $installmentData['total_amount']) * 100 : 0; ?>
                    <div class="card-icon" style="color: #4285f4;"><i class="fas fa-chart-pie"></i></div>
                    <div class="card-title">Progress</div>
                    <div class="card-value"><?= round($progress, 1) ?>%</div>
                    <div class="progress-glass mt-3">
                        <div class="progress-bar" style="width: <?= min(100, $progress) ?>%; background: #4285f4; height: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Table -->
        <div class="content-card animated" style="animation-delay: 0.6s">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Log Pembayaran</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-5">No.</th>
                                <th>Tanggal Bayar</th>
                                <th>Jatuh Tempo</th>
                                <th>Jumlah</th>
                                <th>Denda</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="pe-5">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paymentHistory)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div style="opacity: 0.3; margin-bottom: 15px;"><i class="fas fa-receipt fa-4x"></i></div>
                                    <p class="text-muted mb-0 fw-bold">Belum ada riwayat pembayaran</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($paymentHistory as $payment): ?>
                            <tr>
                                <td class="ps-5"><span class="fw-bold text-muted">#<?= $payment['payment_number'] ?></span></td>
                                <td>
                                    <div class="fw-bold"><?= date('d M Y', strtotime($payment['payment_date'])) ?></div>
                                    <div style="font-size: 11px; color: var(--muted);"><?= date('H:i', strtotime($payment['payment_date'])) ?> WIB</div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;"><?= date('d M Y', strtotime($payment['due_date'])) ?></div>
                                    <?php if (strtotime($payment['payment_date']) > strtotime($payment['due_date'])): ?>
                                    <span class="text-danger" style="font-size: 10px; font-weight: 800;"><i class="fas fa-clock me-1"></i>TERLAMBAT</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></td>
                                <td class="text-danger fw-bold"><?= $payment['penalty_amount'] > 0 ? 'Rp '.number_format($payment['penalty_amount'], 0, ',', '.') : '-' ?></td>
                                <td class="text-primary fw-bold">Rp <?= number_format($payment['total_paid'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($payment['status'] == 'paid'): ?>
                                        <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 8px; padding: 6px 12px; font-weight: 700; font-size: 11px;">LUNAS</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(234, 67, 53, 0.1); color: #ea4335; border-radius: 8px; padding: 6px 12px; font-weight: 700; font-size: 11px;">TERLAMBAT</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-5">
                                    <span style="font-size: 12px; color: var(--muted);"><?= htmlspecialchars($payment['notes'] ?: '-') ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Action Footer -->
        <?php if ($installmentData['status'] == 'active'): ?>
        <div class="alert animated" style="animation-delay: 0.7s; background: rgba(66, 133, 244, 0.05); border: 1px solid rgba(66, 133, 244, 0.1); border-radius: 24px; padding: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 48px; height: 48px; background: #4285f4; color: white; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div>
                    <div style="font-weight: 800; color: var(--fg);">Pembayaran Berikutnya</div>
                    <div style="font-size: 13px; color: var(--muted);">Jatuh tempo pada <strong><?= date('d M Y', strtotime($installment->getDueDate($installmentData))) ?></strong></div>
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #4285f4;">
                Rp <?= number_format($installmentData['amount_per_tenor'], 0, ',', '.') ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>