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
    /* ========== INSTALLMENTS SPECIFIC STYLES ========== */
    .summary-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 35px; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        transition: var(--transition); 
        height: 100%; 
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }
    .summary-card:hover { transform: translateY(-5px); box-shadow: 0 25px 60px rgba(0, 0, 0, 0.07); }
    
    .summary-card .card-icon { 
        width: 52px;
        height: 52px;
        background: var(--surface);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px; 
        margin-bottom: 20px; 
        color: var(--info);
        border: 1px solid var(--border);
    }
    
    .summary-card .card-title { 
        font-size: 11px; 
        font-weight: 800; 
        color: var(--muted); 
        text-transform: uppercase; 
        letter-spacing: 1.5px; 
        margin-bottom: 8px; 
    }
    
    .summary-card .card-value { 
        font-size: 26px; 
        font-weight: 800; 
        color: var(--fg); 
        letter-spacing: -0.02em; 
    }
    
    .content-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        overflow: hidden; 
        backdrop-filter: blur(10px);
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
    
    .progress-glass { 
        height: 8px; 
        background: rgba(0, 0, 0, 0.04); 
        border-radius: 10px; 
        overflow: hidden; 
        margin: 15px 0 5px;
    }
    .progress-glass .progress-bar { 
        background: var(--info); 
        border-radius: 10px; 
        transition: width 1s cubic-bezier(0.22, 1, 0.36, 1); 
    }
    
    .progress-text { font-size: 12px; font-weight: 700; color: var(--muted); }
    
    .btn-glass {
        padding: 12px 24px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 14px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .btn-primary-glass { background: var(--fg); color: white; }
    .btn-primary-glass:hover { background: #000; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
    
    .btn-action {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        border: 1px solid rgba(0,0,0,0.05);
        color: var(--fg);
        background: var(--surface);
    }
    .btn-action:hover { transform: translateY(-2px); background: var(--fg); color: white; }
    
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
    }
    
    .installment-name { font-size: 15px; font-weight: 750; color: var(--fg); display: block; }
    .installment-meta { font-size: 12px; color: var(--muted); margin-top: 4px; display: flex; align-items: center; gap: 6px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="welcome-title">Manajemen Cicilan</h1>
                    <p class="welcome-subtitle">Pantau dan kelola kewajiban pembayaran Anda</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addInstallmentModal">
                        <i class="fas fa-plus me-2"></i> Tambah Cicilan
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="summary-card animated" style="animation-delay: 0.1s">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-title">Total Cicilan</div>
                    <div class="card-value">
                        <?php echo isset($summary['total_installments']) ? (int)$summary['total_installments'] : 0; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card animated" style="animation-delay: 0.2s">
                    <div class="card-icon" style="color: #34a853;">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="card-title">Cicilan Aktif</div>
                    <div class="card-value">
                        <?php echo isset($summary['active_installments']) ? (int)$summary['active_installments'] : 0; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card animated" style="animation-delay: 0.3s">
                    <div class="card-icon" style="color: #4285f4;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-title">Cicilan Selesai</div>
                    <div class="card-value">
                        <?php echo isset($summary['completed_installments']) ? (int)$summary['completed_installments'] : 0; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card animated" style="animation-delay: 0.4s">
                    <div class="card-icon" style="color: #fbbc05;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="card-title">Sisa Tagihan</div>
                    <div class="card-value">
                        <span style="font-size: 18px;">Rp</span> <?php 
                        $remaining = isset($summary['total_remaining']) ? (float)$summary['total_remaining'] : 0;
                        echo number_format($remaining, 0, ',', '.'); 
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Active Installments Table -->
        <div class="content-card animated" style="animation-delay: 0.5s">
            <div class="card-header">
                <h5>
                    <i class="fas fa-list"></i>
                    Cicilan Aktif
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Informasi Cicilan</th>
                                <th>Total & Sisa</th>
                                <th>Tenor</th>
                                <th>Angsuran</th>
                                <th>Progress</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($active_installments)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div style="opacity: 0.3; margin-bottom: 15px;"><i class="fas fa-inbox fa-4x"></i></div>
                                    <p class="text-muted mb-0 fw-bold">Belum ada cicilan aktif</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($active_installments as $item): ?>
                            <tr>
                                <td>
                                    <span class="installment-name"><?php echo htmlspecialchars($item['name'] ?? '-'); ?></span>
                                    <?php if (!empty($item['account_name'])): ?>
                                    <span class="installment-meta">
                                        <i class="fas fa-credit-card"></i> <?php echo htmlspecialchars($item['account_name']); ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold" style="font-size: 14px;">Rp <?php echo number_format($item['total_amount'], 0, ',', '.'); ?></div>
                                    <div class="text-danger" style="font-size: 12px; font-weight: 700;">Sisa: Rp <?php echo number_format($item['remaining_amount'], 0, ',', '.'); ?></div>
                                </td>
                                <td>
                                    <span class="badge" style="background: var(--surface); color: var(--fg); border-radius: 8px; padding: 6px 12px;">
                                        <?php echo $item['current_tenor'] . '/' . $item['tenor'] . ' ' . $item['tenor_type']; ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-primary">
                                    Rp <?php echo number_format($item['amount_per_tenor'], 0, ',', '.'); ?>
                                </td>
                                <td style="min-width: 150px;">
                                    <?php 
                                    $progress = $item['total_amount'] > 0 ? ($item['paid_amount'] / $item['total_amount']) * 100 : 0;
                                    ?>
                                    <div class="progress-glass">
                                        <div class="progress-bar" style="width: <?php echo min(100, $progress); ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="progress-text"><?php echo round($progress); ?>%</span>
                                        <span style="font-size: 10px; font-weight: 800; color: var(--muted);"><?= number_format($item['paid_amount'], 0, ',', '.') ?> Terbayar</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="pay.php?id=<?php echo $item['id']; ?>" class="btn-action" title="Bayar">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                        <a href="history.php?id=<?php echo $item['id']; ?>" class="btn-action" title="Riwayat">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <button onclick="editInstallment(<?php echo $item['id']; ?>)" class="btn-action" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteInstallment(<?php echo $item['id']; ?>)" class="btn-action text-danger" title="Hapus">
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