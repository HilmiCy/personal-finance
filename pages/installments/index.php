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

$page_title = 'Cicilan';
$current_page = 'installments';

$installment = new Installment();
$account = new Account();

$summary = $installment->getSummary($_SESSION['user_id']);
$active_installments = $installment->getAll($_SESSION['user_id'], 'active');
$completed_installments = $installment->getAll($_SESSION['user_id'], 'completed');
$accounts = $account->getAll($_SESSION['user_id']);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Pastikan jQuery dimuat -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
    padding: 1.25rem 1rem;
    vertical-align: middle;
    color: #495057;
    border-bottom: 1px solid #f1f3f5;
}

.table-custom tbody tr:hover {
    background-color: #f8f9ff;
}

/* Badge Styles */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

/* Modal Styling */
.modal-content {
    border: none;
    border-radius: 24px;
    box-shadow: var(--shadow-md);
}

.modal-header {
    border-bottom: 1px solid #f1f3f5;
    padding: 1.5rem 2rem;
}

.modal-body {
    padding: 2rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border-radius: 12px;
    padding: 0.75rem 1rem;
    border: 1px solid #dee2e6;
}

.form-control:focus {
    box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
    border-color: var(--primary);
}

/* Animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animated {
    animation: fadeInUp 0.5s ease forwards;
}

@media (max-width: 768px) {
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
}
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <main class="col-12 px-md-4">
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
                        <div class="summary-card animated">
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
                        <div class="summary-card animated" style="animation-delay: 0.1s">
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
                        <div class="summary-card animated" style="animation-delay: 0.2s">
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
                        <div class="summary-card animated" style="animation-delay: 0.3s">
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
                <div class="content-card animated" style="animation-delay: 0.4s">
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
                                        <td data-label="Total">
                                            Rp <?php 
                                            $total_amount = isset($item['total_amount']) ? (float)$item['total_amount'] : 0;
                                            echo number_format($total_amount, 0, ',', '.'); 
                                            ?>
                                        </td>
                                        <td data-label="Terbayar">
                                            Rp <?php 
                                            $paid_amount = isset($item['paid_amount']) ? (float)$item['paid_amount'] : 0;
                                            echo number_format($paid_amount, 0, ',', '.'); 
                                            ?>
                                        </td>
                                        <td data-label="Sisa" style="color: var(--warning); font-weight: 600;">
                                            Rp <?php 
                                            $remaining_amount = isset($item['remaining_amount']) ? (float)$item['remaining_amount'] : 0;
                                            echo number_format($remaining_amount, 0, ',', '.'); 
                                            ?>
                                        </td>
                                        <td data-label="Tenor">
                                            <?php 
                                            $current_tenor = isset($item['current_tenor']) ? (int)$item['current_tenor'] : 0;
                                            $tenor = isset($item['tenor']) ? (int)$item['tenor'] : 0;
                                            $tenor_type = isset($item['tenor_type']) ? htmlspecialchars($item['tenor_type']) : 'months';
                                            echo $current_tenor . '/' . $tenor . ' ' . $tenor_type; 
                                            ?>
                                        </td>
                                        <td data-label="Per Tenor">
                                            Rp <?php 
                                            $amount_per_tenor = isset($item['amount_per_tenor']) ? (float)$item['amount_per_tenor'] : 0;
                                            echo number_format($amount_per_tenor, 0, ',', '.'); 
                                            ?>
                                        </td>
                                        <td data-label="Progress">
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
                                            <div class="d-flex gap-2">
                                                <a href="pay.php?id=<?php echo $item['id']; ?>" class="btn btn-success-glass" title="Bayar">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </a>
                                                <a href="history.php?id=<?php echo $item['id']; ?>" class="btn btn-info-glass" title="Riwayat">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                                <button onclick="editInstallment(<?php echo $item['id']; ?>)" class="btn btn-warning-glass" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteInstallment(<?php echo $item['id']; ?>)" class="btn btn-danger-glass" title="Hapus">
                                                    <i class="fas fa-trash"></i>
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
</div>

<!-- Modal Add -->
<div class="modal fade" id="addInstallmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Cicilan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addInstallmentForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Cicilan</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: Cicilan Motor">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Akun Pembayaran</label>
                            <select name="account_id" class="form-select" required>
                                <option value="">Pilih Akun</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= formatRupiah($acc['balance']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Pinjaman/Harga</label>
                            <input type="number" name="total_amount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tenor (Jumlah Bulan/Tahun)</label>
                            <input type="number" name="tenor" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipe Tenor</label>
                            <select name="tenor_type" class="form-select" required>
                                <option value="months">Bulan</option>
                                <option value="years">Tahun</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Angsuran per Tenor</label>
                            <input type="number" name="amount_per_tenor" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jatuh Tempo (Tanggal)</label>
                            <input type="number" name="due_date" class="form-control" min="1" max="31" required placeholder="Contoh: 15">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-glass">Simpan Cicilan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editInstallmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Cicilan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editInstallmentForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Cicilan</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Akun Pembayaran</label>
                            <select name="account_id" id="edit_account_id" class="form-select" required>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Pinjaman</label>
                            <input type="number" name="total_amount" id="edit_total_amount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tenor</label>
                            <input type="number" name="tenor" id="edit_tenor" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Angsuran per Tenor</label>
                            <input type="number" name="amount_per_tenor" id="edit_amount_per_tenor" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jatuh Tempo (Tanggal)</label>
                            <input type="number" name="due_date" id="edit_due_date" class="form-control" min="1" max="31" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Aktif</option>
                                <option value="completed">Selesai</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-glass">Update Cicilan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add Installment
    $('#addInstallmentForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'add.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', response.message, 'error');
                }
            }
        });
    });

    // Edit Installment Form Submit
    $('#editInstallmentForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'edit.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Berhasil!', response.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', response.message, 'error');
                }
            }
        });
    });
});

function editInstallment(id) {
    $.get('get_installment.php', { id: id }, function(data) {
        const item = JSON.parse(data);
        $('#edit_id').val(item.id);
        $('#edit_name').val(item.name);
        $('#edit_account_id').val(item.account_id);
        $('#edit_total_amount').val(item.total_amount);
        $('#edit_tenor').val(item.tenor);
        $('#edit_amount_per_tenor').val(item.amount_per_tenor);
        $('#edit_due_date').val(item.due_date);
        $('#edit_status').val(item.status);
        $('#editInstallmentModal').modal('show');
    });
}

function deleteInstallment(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data cicilan dan riwayat pembayarannya akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'delete.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
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