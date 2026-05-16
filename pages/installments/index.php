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
$active_installments = $installment->getAll($_SESSION['user_id'], 'active');
$completed_installments = $installment->getAll($_SESSION['user_id'], 'completed');
$summary = $installment->getSummary($_SESSION['user_id']);

include '../../includes/header.php';
?>

<!-- Pastikan jQuery dan Bootstrap JS dimuat -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

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

html, body {
    height: 100%;
    margin: 0;
}

body {
    background: #f8f9fa;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.container-fluid {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.container-fluid > .row {
    flex: 1;
}

main {
    display: flex;
    flex-direction: column;
}


/* Summary Cards */
.summary-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--info));
}

.summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
}

.summary-card .card-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.summary-card .card-title {
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.summary-card .card-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 0;
}

/* Main Content Card */
.content-card {
    background: white;
    border-radius: 24px;
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    overflow: hidden;
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

/* Progress Bar */
.progress-glass {
    height: 8px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
}

.progress-glass .progress-bar {
    background: linear-gradient(90deg, var(--primary), var(--info));
    border-radius: 10px;
    position: relative;
    transition: width 0.6s ease;
}

.progress-text {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary);
    margin-top: 0.25rem;
    display: inline-block;
}

/* Buttons */
.btn-primary-glass {
    background: linear-gradient(135deg, var(--primary), #3a56d4);
    border: none;
    border-radius: 12px;
    padding: 0.5rem 1.25rem;
    font-weight: 500;
    color: white;
    transition: all 0.2s ease;
}

.btn-primary-glass:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    color: white;
}

.btn-success-glass {
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
    margin-right: 0.5rem;
}

.btn-info-glass {
    background: linear-gradient(135deg, var(--info), #2563eb);
    border: none;
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
    margin-right: 0.5rem;
}

.btn-warning-glass {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border: none;
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
    margin-right: 0.5rem;
}

.btn-danger-glass {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: none;
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
}

.btn-secondary-glass {
    background: #6c757d;
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1.25rem;
    font-weight: 500;
    color: white;
    transition: all 0.2s ease;
}

.btn-secondary-glass:hover {
    background: #5a6268;
}

/* Modal Styles */
.modal-content-glass {
    background: white;
    border-radius: 24px;
    border: none;
    box-shadow: var(--shadow-md);
}

.modal-header-glass {
    background: linear-gradient(135deg, var(--primary), var(--info));
    color: white;
    border-radius: 24px 24px 0 0;
    padding: 1.25rem 1.5rem;
}

.modal-header-glass .btn-close {
    filter: brightness(0) invert(1);
}

.modal-body-glass {
    padding: 1.5rem;
}

.modal-footer-glass {
    border-top: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
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

/* Helper functions */
.text-amount {
    font-weight: 600;
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

/* Action Buttons Group */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 768px) {
    .summary-card .card-value {
        font-size: 1.25rem;
    }
    
    .table-custom thead th {
        font-size: 0.75rem;
        padding: 0.75rem;
    }
    
    .table-custom tbody td {
        padding: 0.75rem;
        font-size: 0.875rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-success-glass, .btn-info-glass, .btn-warning-glass, .btn-danger-glass {
        width: 100%;
        margin-bottom: 0.25rem;
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
                    <h1 class="h2 mb-1" style="font-weight: 700; color: #1a1a2e;">Manajemen Cicilan</h1>
                    <p class="text-muted mb-0">Kelola dan pantau cicilan Anda dengan mudah</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary-glass" data-bs-toggle="modal" data-bs-target="#addInstallmentModal">
                        <i class="fas fa-plus me-2"></i>Tambah Cicilan
                    </button>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertContainer"></div>
            
            <!-- Summary Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                        </div>
                        <div class="card-title">Total Cicilan</div>
                        <div class="card-value">
                            <?php echo isset($summary['total_installments']) ? (int)$summary['total_installments'] : 0; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-play-circle" style="color: var(--success);"></i>
                        </div>
                        <div class="card-title">Cicilan Aktif</div>
                        <div class="card-value">
                            <?php echo isset($summary['active_installments']) ? (int)$summary['active_installments'] : 0; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-check-circle" style="color: var(--secondary);"></i>
                        </div>
                        <div class="card-title">Cicilan Selesai</div>
                        <div class="card-value">
                            <?php echo isset($summary['completed_installments']) ? (int)$summary['completed_installments'] : 0; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-wallet" style="color: var(--warning);"></i>
                        </div>
                        <div class="card-title">Sisa Tagihan</div>
                        <div class="card-value">
                            Rp <?php 
                            $remaining = isset($summary['total_remaining']) ? (float)$summary['total_remaining'] : 0;
                            echo number_format($remaining, 0, ',', '.'); 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Active Installments Table -->
            <div class="content-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-list me-2" style="color: var(--primary);"></i>
                        Cicilan Aktif
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom" id="installmentsTable">
                            <thead>
                                <tr>
                                    <th>Nama Cicilan</th>
                                    <th>Total</th>
                                    <th>Terbayar</th>
                                    <th>Sisa</th>
                                    <th>Tenor</th>
                                    <th>Per Tenor</th>
                                    <th>Progress</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="installmentsTableBody">
                                <?php if (empty($active_installments)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-inbox fa-2x mb-3 text-muted"></i>
                                        <p class="text-muted mb-0">Belum ada cicilan aktif</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($active_installments as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name'] ?? '-'); ?></strong>
                                        <?php if (!empty($item['account_name'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-credit-card"></i> <?php echo htmlspecialchars($item['account_name']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-amount">
                                        Rp <?php 
                                        $total_amount = isset($item['total_amount']) ? (float)$item['total_amount'] : 0;
                                        echo number_format($total_amount, 0, ',', '.'); 
                                        ?>
                                    </td>
                                    <td class="text-amount">
                                        Rp <?php 
                                        $paid_amount = isset($item['paid_amount']) ? (float)$item['paid_amount'] : 0;
                                        echo number_format($paid_amount, 0, ',', '.'); 
                                        ?>
                                    </td>
                                    <td class="text-amount" style="color: var(--warning);">
                                        Rp <?php 
                                        $remaining_amount = isset($item['remaining_amount']) ? (float)$item['remaining_amount'] : 0;
                                        echo number_format($remaining_amount, 0, ',', '.'); 
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge-glass">
                                            <?php 
                                            $current_tenor = isset($item['current_tenor']) ? (int)$item['current_tenor'] : 0;
                                            $tenor = isset($item['tenor']) ? (int)$item['tenor'] : 0;
                                            $tenor_type = isset($item['tenor_type']) ? htmlspecialchars($item['tenor_type']) : 'months';
                                            echo $current_tenor . '/' . $tenor . ' ' . $tenor_type; 
                                            ?>
                                        </span>
                                    </td>
                                    <td class="text-amount">
                                        Rp <?php 
                                        $amount_per_tenor = isset($item['amount_per_tenor']) ? (float)$item['amount_per_tenor'] : 0;
                                        echo number_format($amount_per_tenor, 0, ',', '.'); 
                                        ?>
                                    </td>
                                    <td style="min-width: 120px;">
                                        <?php 
                                        $progress = 0;
                                        if (isset($item['total_amount']) && $item['total_amount'] > 0 && isset($item['paid_amount'])) {
                                            $progress = ($item['paid_amount'] / $item['total_amount']) * 100;
                                        }
                                        ?>
                                        <div class="progress-glass">
                                            <div class="progress-bar" style="width: <?php echo min(100, $progress); ?>%"></div>
                                        </div>
                                        <span class="progress-text"><?php echo round($progress); ?>%</span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="pay.php?id=<?php echo $item['id']; ?>" class="btn btn-success-glass">
                                                <i class="fas fa-money-bill-wave me-1"></i> Bayar
                                            </a>
                                            <a href="history.php?id=<?php echo $item['id']; ?>" class="btn btn-info-glass">
                                                <i class="fas fa-history me-1"></i> Detail
                                            </a>
                                            <button onclick="editInstallment(<?php echo $item['id']; ?>)" class="btn btn-warning-glass">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <button onclick="deleteInstallment(<?php echo $item['id']; ?>)" class="btn btn-danger-glass">
                                                <i class="fas fa-trash me-1"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah Cicilan (Tanpa Pilih Akun) -->
<div class="modal fade" id="addInstallmentModal" tabindex="-1" aria-labelledby="addInstallmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-content-glass">
            <div class="modal-header modal-header-glass">
                <h5 class="modal-title" id="addInstallmentModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Cicilan Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addInstallmentForm">
                <div class="modal-body modal-body-glass">
                    <div class="alert alert-info alert-glass">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Informasi:</strong> Akun akan dibuat otomatis dengan nama cicilan.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Nama Cicilan *</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Contoh: Cicilan Motor, KPR, dll">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="amount_per_tenor" class="form-label">Jumlah per Tenor *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="amount_per_tenor" name="amount_per_tenor" required placeholder="0">
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tenor" class="form-label">Tenor *</label>
                            <input type="number" class="form-control" id="tenor" name="tenor" required min="1" placeholder="Jumlah">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="tenor_type" class="form-label">Jenis Tenor *</label>
                            <select class="form-select" id="tenor_type" name="tenor_type" required>
                                <option value="days">Hari</option>
                                <option value="months" selected>Bulan</option>
                                <option value="years">Tahun</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="start_date" class="form-label">Tanggal Mulai *</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="interest_rate" class="form-label">Bunga (%)</label>
                            <input type="number" class="form-control" id="interest_rate" name="interest_rate" step="0.01" placeholder="0" value="0">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Catatan tambahan..."></textarea>
                        </div>
                    </div>
                    
                    <div class="alert alert-info alert-glass">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Informasi:</strong> Total cicilan akan dihitung otomatis: <span id="totalPreview">Rp 0</span>
                    </div>
                </div>
                <div class="modal-footer modal-footer-glass">
                    <button type="button" class="btn btn-secondary-glass" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-glass" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Simpan Cicilan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Cicilan -->
<div class="modal fade" id="editInstallmentModal" tabindex="-1" aria-labelledby="editInstallmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-content-glass">
            <div class="modal-header modal-header-glass">
                <h5 class="modal-title" id="editInstallmentModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Cicilan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editInstallmentForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body modal-body-glass">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_name" class="form-label">Nama Cicilan *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_amount_per_tenor" class="form-label">Jumlah per Tenor *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="edit_amount_per_tenor" name="amount_per_tenor" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_tenor" class="form-label">Tenor *</label>
                            <input type="number" class="form-control" id="edit_tenor" name="tenor" required min="1">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_tenor_type" class="form-label">Jenis Tenor *</label>
                            <select class="form-select" id="edit_tenor_type" name="tenor_type" required>
                                <option value="days">Hari</option>
                                <option value="months">Bulan</option>
                                <option value="years">Tahun</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_start_date" class="form-label">Tanggal Mulai *</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_interest_rate" class="form-label">Bunga (%)</label>
                            <input type="number" class="form-control" id="edit_interest_rate" name="interest_rate" step="0.01" value="0">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="edit_notes" class="form-label">Catatan</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="alert alert-info alert-glass">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Informasi:</strong> Total cicilan akan dihitung otomatis: <span id="editTotalPreview">Rp 0</span>
                    </div>
                </div>
                <div class="modal-footer modal-footer-glass">
                    <button type="button" class="btn btn-secondary-glass" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-glass" id="editSubmitBtn">
                        <i class="fas fa-save me-2"></i>Update Cicilan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div class="spinner-overlay" id="spinnerOverlay">
    <div class="spinner-border spinner-border-custom text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content modal-content-glass">
            <div class="modal-header modal-header-glass" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-glass">
                <p>Apakah Anda yakin ingin menghapus cicilan ini?</p>
                <p class="text-danger mb-0"><small>Tindakan ini tidak dapat dibatalkan!</small></p>
            </div>
            <div class="modal-footer modal-footer-glass">
                <button type="button" class="btn btn-secondary-glass" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger-glass" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variabel global
let deleteId = null;

$(document).ready(function() {
    console.log("jQuery loaded successfully");
    
    // Event untuk format currency dan hitung total (ADD FORM)
    $('#amount_per_tenor').on('input', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(formatRupiah(value));
        }
        calculateTotal();
    });
    
    $('#tenor').on('change keyup', function() {
        calculateTotal();
    });
    
    // Event untuk format currency dan hitung total (EDIT FORM)
    $('#edit_amount_per_tenor').on('input', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(formatRupiah(value));
        }
        calculateEditTotal();
    });
    
    $('#edit_tenor').on('change keyup', function() {
        calculateEditTotal();
    });
    
    // Fungsi hitung total untuk ADD FORM
    function calculateTotal() {
        // Ambil angka bersih tanpa titik
        let amountPerTenorStr = $('#amount_per_tenor').val().replace(/[^0-9]/g, '');
        let tenorStr = $('#tenor').val().replace(/[^0-9]/g, '');
        
        let amountPerTenor = parseInt(amountPerTenorStr) || 0;
        let tenor = parseInt(tenorStr) || 0;
        
        console.log("Calculate Total - Amount:", amountPerTenor, "Tenor:", tenor);
        
        if (amountPerTenor > 0 && tenor > 0) {
            let total = amountPerTenor * tenor;
            $('#totalPreview').text('Rp ' + formatRupiah(total.toString()));
            console.log("Total:", total);
        } else {
            $('#totalPreview').text('Rp 0');
        }
    }
    
    // Fungsi hitung total untuk EDIT FORM
    function calculateEditTotal() {
        // Ambil angka bersih tanpa titik
        let amountPerTenorStr = $('#edit_amount_per_tenor').val().replace(/[^0-9]/g, '');
        let tenorStr = $('#edit_tenor').val().replace(/[^0-9]/g, '');
        
        let amountPerTenor = parseInt(amountPerTenorStr) || 0;
        let tenor = parseInt(tenorStr) || 0;
        
        console.log("Calculate Edit Total - Amount:", amountPerTenor, "Tenor:", tenor);
        
        if (amountPerTenor > 0 && tenor > 0) {
            let total = amountPerTenor * tenor;
            $('#editTotalPreview').text('Rp ' + formatRupiah(total.toString()));
            console.log("Edit Total:", total);
        } else {
            $('#editTotalPreview').text('Rp 0');
        }
    }
    
    // Submit add form
    $('#addInstallmentForm').on('submit', function(e) {
        e.preventDefault();
        
        // Ambil nilai bersih (tanpa titik)
        let amountPerTenor = $('#amount_per_tenor').val().replace(/[^0-9]/g, '');
        let tenor = $('#tenor').val().replace(/[^0-9]/g, '');
        let name = $('#name').val().trim();
        let startDate = $('#start_date').val();
        
        if (!name) {
            showAlert('Nama cicilan harus diisi!', 'danger');
            return;
        }
        
        if (!amountPerTenor || parseInt(amountPerTenor) <= 0) {
            showAlert('Jumlah per tenor harus diisi!', 'danger');
            return;
        }
        
        if (!tenor || parseInt(tenor) <= 0) {
            showAlert('Tenor harus diisi!', 'danger');
            return;
        }
        
        if (!startDate) {
            showAlert('Tanggal mulai harus diisi!', 'danger');
            return;
        }
        
        let formData = new FormData();
        formData.append('action', 'add_installment');
        formData.append('name', name);
        formData.append('amount_per_tenor', amountPerTenor);
        formData.append('tenor', tenor);
        formData.append('tenor_type', $('#tenor_type').val());
        formData.append('start_date', startDate);
        formData.append('interest_rate', $('#interest_rate').val().replace(/[^0-9.-]/g, ''));
        formData.append('notes', $('#notes').val());
        
        $('#spinnerOverlay').fadeIn();
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
        
        $.ajax({
            url: 'add.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#spinnerOverlay').fadeOut();
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Cicilan');
                
                if (response.success) {
                    showAlert(response.message, 'success');
                    $('#addInstallmentModal').modal('hide');
                    $('#addInstallmentForm')[0].reset();
                    $('#totalPreview').text('Rp 0');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                $('#spinnerOverlay').fadeOut();
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Cicilan');
                showAlert('Terjadi kesalahan: ' + error, 'danger');
            }
        });
    });
    
    // Submit edit form
    $('#editInstallmentForm').on('submit', function(e) {
        e.preventDefault();
        
        // Ambil nilai bersih (tanpa titik)
        let amountPerTenor = $('#edit_amount_per_tenor').val().replace(/[^0-9]/g, '');
        let tenor = $('#edit_tenor').val().replace(/[^0-9]/g, '');
        let id = $('#edit_id').val();
        let name = $('#edit_name').val().trim();
        let startDate = $('#edit_start_date').val();
        
        if (!name) {
            showAlert('Nama cicilan harus diisi!', 'danger');
            return;
        }
        
        if (!amountPerTenor || parseInt(amountPerTenor) <= 0) {
            showAlert('Jumlah per tenor harus diisi!', 'danger');
            return;
        }
        
        if (!tenor || parseInt(tenor) <= 0) {
            showAlert('Tenor harus diisi!', 'danger');
            return;
        }
        
        if (!startDate) {
            showAlert('Tanggal mulai harus diisi!', 'danger');
            return;
        }
        
        let formData = new FormData();
        formData.append('action', 'edit_installment');
        formData.append('id', id);
        formData.append('name', name);
        formData.append('amount_per_tenor', amountPerTenor);
        formData.append('tenor', tenor);
        formData.append('tenor_type', $('#edit_tenor_type').val());
        formData.append('start_date', startDate);
        formData.append('interest_rate', $('#edit_interest_rate').val().replace(/[^0-9.-]/g, ''));
        formData.append('notes', $('#edit_notes').val());
        
        $('#spinnerOverlay').fadeIn();
        $('#editSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengupdate...');
        
        $.ajax({
            url: 'edit.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#spinnerOverlay').fadeOut();
                $('#editSubmitBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Update Cicilan');
                
                if (response.success) {
                    showAlert(response.message, 'success');
                    $('#editInstallmentModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                $('#spinnerOverlay').fadeOut();
                $('#editSubmitBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Update Cicilan');
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
        
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Reset form saat modal ditutup
    $('#addInstallmentModal').on('hidden.bs.modal', function() {
        $('#addInstallmentForm')[0].reset();
        $('#totalPreview').text('Rp 0');
        $('.is-invalid').removeClass('is-invalid');
    });
});

// ============ FUNGSI GLOBAL ============

// Fungsi format Rupiah (angka ke format dengan titik)
function formatRupiah(angka) {
    if (!angka) return '0';
    let number_string = angka.toString().replace(/[^0-9]/g, '');
    if (!number_string) return '0';
    
    let sisa = number_string.length % 3;
    let rupiah = number_string.substr(0, sisa);
    let ribuan = number_string.substr(sisa).match(/\d{3}/g);
    
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    return rupiah;
}

// Fungsi untuk edit installment
function editInstallment(id) {
    console.log("Edit function called with ID:", id);
    $('#spinnerOverlay').fadeIn();
    
    $.ajax({
        url: 'get_installment.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            $('#spinnerOverlay').fadeOut();
            
            if (data.success) {
                // Isi form dengan data yang diterima
                $('#edit_id').val(data.data.id);
                $('#edit_name').val(data.data.name);
                
                // Format amount_per_tenor ke format Rupiah
                let amountPerTenor = parseInt(data.data.amount_per_tenor) || 0;
                $('#edit_amount_per_tenor').val(formatRupiah(amountPerTenor.toString()));
                
                // Isi tenor (pastikan tidak ada leading zero)
                let tenor = parseInt(data.data.tenor) || 0;
                $('#edit_tenor').val(tenor);
                
                $('#edit_tenor_type').val(data.data.tenor_type);
                $('#edit_start_date').val(data.data.start_date);
                
                let interestRate = parseFloat(data.data.interest_rate) || 0;
                $('#edit_interest_rate').val(interestRate);
                
                $('#edit_notes').val(data.data.notes || '');
                
                // Hitung dan tampilkan total preview
                calculateEditTotal();
                
                // Tampilkan modal
                $('#editInstallmentModal').modal('show');
            } else {
                showAlertGlobal(data.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            $('#spinnerOverlay').fadeOut();
            console.error("AJAX Error:", error);
            showAlertGlobal('Gagal mengambil data cicilan!', 'danger');
        }
    });
}

// Fungsi hitung total untuk edit (global)
function calculateEditTotal() {
    let amountPerTenorStr = $('#edit_amount_per_tenor').val().replace(/[^0-9]/g, '');
    let tenorStr = $('#edit_tenor').val().replace(/[^0-9]/g, '');
    
    let amountPerTenor = parseInt(amountPerTenorStr) || 0;
    let tenor = parseInt(tenorStr) || 0;
    
    if (amountPerTenor > 0 && tenor > 0) {
        let total = amountPerTenor * tenor;
        $('#editTotalPreview').text('Rp ' + formatRupiah(total.toString()));
    } else {
        $('#editTotalPreview').text('Rp 0');
    }
}

// Fungsi show alert global
function showAlertGlobal(message, type) {
    let alertHtml = `
        <div class="alert alert-${type} alert-glass alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    $('#alertContainer').html(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}

// Fungsi delete installment
function deleteInstallment(id) {
    deleteId = id;
    $('#deleteConfirmModal').modal('show');
}

// Event confirm delete
$('#confirmDeleteBtn').on('click', function() {
    if (deleteId) {
        $('#spinnerOverlay').fadeIn();
        
        $.ajax({
            url: 'delete.php',
            type: 'POST',
            data: { id: deleteId, action: 'delete_installment' },
            dataType: 'json',
            success: function(response) {
                $('#spinnerOverlay').fadeOut();
                $('#deleteConfirmModal').modal('hide');
                
                if (response.success) {
                    showAlertGlobal(response.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlertGlobal(response.message, 'danger');
                }
            },
            error: function() {
                $('#spinnerOverlay').fadeOut();
                $('#deleteConfirmModal').modal('hide');
                showAlertGlobal('Gagal menghapus cicilan!', 'danger');
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>