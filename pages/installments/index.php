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
    background: #f0f2f5;
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
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
}

.btn-primary-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    color: white;
}

.btn-success-glass {
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    margin-right: 0.5rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.btn-success-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    color: white;
}

.btn-info-glass {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    margin-right: 0.5rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.btn-info-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    color: white;
}

.btn-warning-glass {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    margin-right: 0.5rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-warning-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    color: white;
}

.btn-danger-glass {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-danger-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    color: white;
}

/* SweetAlert2 Style */
.swal2-popup {
    border-radius: 24px !important;
    padding: 2em !important;
}

.swal2-title {
    font-weight: 700 !important;
}

.swal2-confirm {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-radius: 12px !important;
    padding: 10px 24px !important;
    font-weight: 600 !important;
}

/* Responsive */
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

    .summary-card .card-value {
        font-size: 1.25rem;
    }
    
    /* Mobile Table */
    .table-custom thead {
        display: none;
    }
    
    .table-custom tbody td {
        display: block;
        padding: 12px 16px !important;
        text-align: right;
        position: relative;
        padding-left: 45% !important;
        border-bottom: none !important;
    }
    
    .table-custom tbody td::before {
        content: attr(data-label);
        position: absolute;
        left: 16px;
        width: calc(45% - 20px);
        font-weight: 600;
        color: #6b7280;
        text-align: left;
        font-size: 12px;
    }
    
    .table-custom tbody tr {
        display: block;
        border-bottom: 1px solid #e5e7eb !important;
        margin-bottom: 15px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .action-buttons {
        flex-direction: column;
        gap: 8px;
    }
    
    .btn-success-glass, .btn-info-glass, .btn-warning-glass, .btn-danger-glass {
        width: 100%;
        margin-right: 0 !important;
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
                                    <td data-label="Nama Cicilan">
                                        <strong><?php echo htmlspecialchars($item['name'] ?? '-'); ?></strong>
                                        <?php if (!empty($item['account_name'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-credit-card"></i> <?php echo htmlspecialchars($item['account_name']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Total" class="text-amount">
                                        Rp <?php 
                                        $total_amount = isset($item['total_amount']) ? (float)$item['total_amount'] : 0;
                                        echo number_format($total_amount, 0, ',', '.'); 
                                        ?>
                                    </td>
                                    <td data-label="Terbayar" class="text-amount">
                                        Rp <?php 
                                        $paid_amount = isset($item['paid_amount']) ? (float)$item['paid_amount'] : 0;
                                        echo number_format($paid_amount, 0, ',', '.'); 
                                        ?>
                                    </td>
                                    <td data-label="Sisa" class="text-amount" style="color: var(--warning);">
                                        Rp <?php 
                                        $remaining_amount = isset($item['remaining_amount']) ? (float)$item['remaining_amount'] : 0;
                                        echo number_format($remaining_amount, 0, ',', '.'); 
                                        ?>
                                    </td>
                                    <td data-label="Tenor">
                                        <span class="badge-glass">
                                            <?php 
                                            $current_tenor = isset($item['current_tenor']) ? (int)$item['current_tenor'] : 0;
                                            $tenor = isset($item['tenor']) ? (int)$item['tenor'] : 0;
                                            $tenor_type = isset($item['tenor_type']) ? htmlspecialchars($item['tenor_type']) : 'months';
                                            echo $current_tenor . '/' . $tenor . ' ' . $tenor_type; 
                                            ?>
                                        </span>
                                    </td>
                                    <td data-label="Per Tenor" class="text-amount">
                                        Rp <?php 
                                        $amount_per_tenor = isset($item['amount_per_tenor']) ? (float)$item['amount_per_tenor'] : 0;
                                        echo number_format($amount_per_tenor, 0, ',', '.'); 
                                        ?>
                                    </td>
                                    <td data-label="Progress" style="min-width: 120px;">
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
                                    <td data-label="Aksi">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
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
        let amountPerTenorStr = $('#amount_per_tenor').val().replace(/[^0-9]/g, '');
        let tenorStr = $('#tenor').val().replace(/[^0-9]/g, '');
        let amountPerTenor = parseInt(amountPerTenorStr) || 0;
        let tenor = parseInt(tenorStr) || 0;
        
        if (amountPerTenor > 0 && tenor > 0) {
            let total = amountPerTenor * tenor;
            $('#totalPreview').text('Rp ' + formatRupiah(total.toString()));
        } else {
            $('#totalPreview').text('Rp 0');
        }
    }
    
    // Fungsi hitung total untuk EDIT FORM
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
    
    function showSuccess(message) {
        Swal.fire({
            title: 'Berhasil!',
            text: message,
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
            location.reload();
        });
    }

    function showError(message) {
        Swal.fire({
            title: 'Gagal!',
            text: message,
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
    
    // Submit add form
    $('#addInstallmentForm').on('submit', function(e) {
        e.preventDefault();
        
        let amountPerTenor = $('#amount_per_tenor').val().replace(/[^0-9]/g, '');
        let tenor = $('#tenor').val().replace(/[^0-9]/g, '');
        let name = $('#name').val().trim();
        let startDate = $('#start_date').val();
        
        if (!name || !amountPerTenor || !tenor || !startDate) {
            showError('Harap isi semua kolom yang wajib!');
            return;
        }
        
        let formData = new FormData(this);
        formData.set('amount_per_tenor', amountPerTenor);
        formData.append('action', 'add_installment');
        
        $('#spinnerOverlay').fadeIn();
        
        $.ajax({
            url: 'add.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#spinnerOverlay').fadeOut();
                if (response.success) {
                    showSuccess(response.message);
                } else {
                    showError(response.message);
                }
            },
            error: function() {
                $('#spinnerOverlay').fadeOut();
                showError('Terjadi kesalahan koneksi!');
            }
        });
    });
    
    // Submit edit form
    $('#editInstallmentForm').on('submit', function(e) {
        e.preventDefault();
        
        let amountPerTenor = $('#edit_amount_per_tenor').val().replace(/[^0-9]/g, '');
        let formData = new FormData(this);
        formData.set('amount_per_tenor', amountPerTenor);
        formData.append('action', 'edit_installment');
        
        $('#spinnerOverlay').fadeIn();
        
        $.ajax({
            url: 'edit.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#spinnerOverlay').fadeOut();
                if (response.success) {
                    showSuccess(response.message);
                } else {
                    showError(response.message);
                }
            },
            error: function() {
                $('#spinnerOverlay').fadeOut();
                showError('Terjadi kesalahan koneksi!');
            }
        });
    });
});

// ============ FUNGSI GLOBAL ============

function formatRupiah(angka) {
    if (!angka) return '0';
    let number_string = angka.toString().replace(/[^0-9]/g, '');
    let sisa = number_string.length % 3;
    let rupiah = number_string.substr(0, sisa);
    let ribuan = number_string.substr(sisa).match(/\d{3}/g);
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    return rupiah;
}

function editInstallment(id) {
    $('#spinnerOverlay').fadeIn();
    $.ajax({
        url: 'get_installment.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            $('#spinnerOverlay').fadeOut();
            if (data.success) {
                $('#edit_id').val(data.data.id);
                $('#edit_name').val(data.data.name);
                $('#edit_amount_per_tenor').val(formatRupiah(data.data.amount_per_tenor));
                $('#edit_tenor').val(data.data.tenor);
                $('#edit_tenor_type').val(data.data.tenor_type);
                $('#edit_start_date').val(data.data.start_date);
                $('#edit_interest_rate').val(data.data.interest_rate);
                $('#edit_notes').val(data.data.notes || '');
                
                // Trigger preview
                let total = data.data.amount_per_tenor * data.data.tenor;
                $('#editTotalPreview').text('Rp ' + formatRupiah(total.toString()));
                
                $('#editInstallmentModal').modal('show');
            }
        }
    });
}

function deleteInstallment(id) {
    Swal.fire({
        title: 'Hapus Cicilan?',
        text: "Seluruh data riwayat pembayaran cicilan ini juga akan dihapus!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#spinnerOverlay').fadeIn();
            $.ajax({
                url: 'delete.php',
                type: 'POST',
                data: { id: id, action: 'delete_installment' },
                dataType: 'json',
                success: function(response) {
                    $('#spinnerOverlay').fadeOut();
                    if (response.success) {
                        Swal.fire('Terhapus!', response.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal!', response.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>