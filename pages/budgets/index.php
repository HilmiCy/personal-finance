<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Budget.php';
require_once '../../classes/Category.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Manajemen Anggaran';
$current_page = 'budgets';

$db = Database::getInstance()->getConnection();
$budget = new Budget();
$category = new Category();

// Get current month and year
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get budgets for current month
$budgets = $budget->getByMonth($_SESSION['user_id'], $current_month, $current_year);

// Get all expense categories
$expense_categories = $category->getAll($_SESSION['user_id'], 'expense');

// Calculate totals
$total_budget = 0;
$total_spent = 0;
foreach ($budgets as $b) {
    $total_budget += $b['budget_amount'];
    $total_spent += $b['spent_amount'];
}
$remaining = $total_budget - $total_spent;

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* ========== BUDGETS SPECIFIC STYLES ========== */
    .btn-month {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        color: var(--text-muted);
        display: inline-block;
    }
    
    .btn-month:hover {
        background: var(--bg-hover);
        transform: translateY(-1px);
        color: var(--text-main);
    }
    
    .budget-card {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 24px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        position: relative;
        border: 1px solid var(--border-color);
        color: var(--text-main);
        box-shadow: var(--shadow-card);
    }
    
    .budget-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        border-color: var(--accent-primary);
    }
    
    .budget-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }
    
    .budget-category {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .budget-amount {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .budget-label {
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .budget-value {
        font-weight: 700;
        color: var(--text-main);
    }
    
    .progress {
        height: 10px;
        border-radius: 10px;
        background: var(--bg-hover);
        margin-bottom: 15px;
        overflow: hidden;
    }
    
    .budget-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-safe { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .status-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .status-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

    .summary-stats {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 24px;
        text-align: center;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        box-shadow: var(--shadow-card);
    }
    
    .summary-stats:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
    }
    
    .month-navigation {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 16px 24px;
        margin-bottom: 24px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-card);
    }
    
    .current-month-display {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        #sidebar { margin-left: -250px !important; position: fixed !important; z-index: 1000 !important; height: 100vh !important; }
        #sidebar.active { margin-left: 0 !important; }
        #content, .main-content { width: 100% !important; }
        .container-fluid { padding: 16px !important; }
        .btn-primary-custom { width: 100%; justify-content: center; }
        .month-nav-row { flex-direction: column; gap: 15px; }
        .btn-month { width: 100%; text-align: center; }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="welcome-title">Manajemen Anggaran</h1>
                    <p class="welcome-subtitle">Pantau batasan pengeluaran bulanan Anda</p>
                </div>
                <div class="col-md-5 text-md-end header-actions">
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                        <i class="fas fa-plus"></i> Tambah Anggaran
                    </button>
                </div>
            </div>
        </div>

        <!-- Month Navigation -->
        <div class="month-navigation animated" style="animation-delay: 0.1s">
            <div class="row align-items-center month-nav-row">
                <div class="col-md-4 text-start">
                    <a href="?month=<?= $current_month == 1 ? 12 : $current_month - 1 ?>&year=<?= $current_month == 1 ? $current_year - 1 : $current_year ?>" class="btn-month">
                        <i class="fas fa-chevron-left me-2"></i> Sebelumnya
                    </a>
                </div>
                <div class="col-md-4 text-center">
                    <div class="current-month-display">
                        <i class="fas fa-calendar-alt"></i> 
                        <span><?= bulanIndonesia($current_month) ?> <?= $current_year ?></span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="?month=<?= $current_month == 12 ? 1 : $current_month + 1 ?>&year=<?= $current_month == 12 ? $current_year + 1 : $current_year ?>" class="btn-month">
                        Selanjutnya <i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-4">
                <div class="summary-stats animated" style="animation-delay: 0.15s">
                    <div class="stat-icon-circle" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="stat-title">Total Target</div>
                    <div class="stat-number"><?= formatRupiah($total_budget) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="summary-stats animated" style="animation-delay: 0.2s">
                    <div class="stat-icon-circle" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-title">Terpakai</div>
                    <div class="stat-number"><?= formatRupiah($total_spent) ?></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="summary-stats animated" style="animation-delay: 0.25s">
                    <div class="stat-icon-circle" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-title">Sisa Saldo</div>
                    <div class="stat-number <?= $remaining >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatRupiah($remaining) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget List -->
        <div class="row g-4">
            <?php if (count($budgets) > 0): ?>
                <?php foreach ($budgets as $index => $b): 
                    $percentage = $b['budget_amount'] > 0 ? ($b['spent_amount'] / $b['budget_amount']) * 100 : 0;
                    $statusClass = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'safe');
                    $statusText = $percentage >= 100 ? 'Over Budget' : ($percentage >= 80 ? 'Limit' : 'Aman');
                ?>
                <div class="col-md-6 col-xl-4" style="animation: fadeInUp 0.5s ease <?= $index * 0.05 ?>s both;">
                    <div class="budget-card">
                        <div class="budget-header">
                            <div class="budget-category">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($b['category_name']) ?>
                            </div>
                            <div class="dropdown dropdown-minimal">
                                <button class="btn btn-link" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                    <li>
                                        <button class="dropdown-item" onclick="editBudget(<?= $b['id'] ?>, <?= $b['category_id'] ?>, <?= $b['budget_amount'] ?>)">
                                            <i class="fas fa-pencil-alt me-2 text-primary"></i> Edit Anggaran
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deleteBudget(<?= $b['id'] ?>, '<?= htmlspecialchars($b['category_name']) ?>')">
                                            <i class="fas fa-trash-alt me-2"></i> Hapus Anggaran
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="budget-amounts">
                            <div class="budget-amount">
                                <span class="budget-label">Target</span>
                                <span class="budget-value"><?= formatRupiah($b['budget_amount']) ?></span>
                            </div>
                            <div class="budget-amount">
                                <span class="budget-label">Terpakai</span>
                                <span class="budget-value text-danger"><?= formatRupiah($b['spent_amount']) ?></span>
                            </div>
                        </div>
                        
                        <div class="progress">
                            <div class="progress-bar bg-<?= $statusClass ?>" style="width: <?= min($percentage, 100) ?>%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="budget-status-pill status-<?= $statusClass ?>">
                                <i class="fas fa-<?= $percentage >= 100 ? 'exclamation-triangle' : ($percentage >= 80 ? 'clock' : 'check-circle') ?>"></i>
                                <?= $statusText ?>
                            </span>
                            <span class="budget-label"><?= round($percentage) ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="card p-5">
                        <i class="fas fa-chart-pie fa-4x mb-3 opacity-20" style="color: #667eea;"></i>
                        <p class="text-muted">Belum ada anggaran untuk bulan ini.</p>
                        <div class="d-flex justify-content-center">
                            <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                                Mulai Buat Anggaran
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Tambah Anggaran -->
<div class="modal fade" id="addBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Tambah Anggaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addBudgetForm">
                <div class="modal-body modal-body-custom">
                    <input type="hidden" name="month" value="<?= $current_month ?>">
                    <input type="hidden" name="year" value="<?= $current_year ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori Pengeluaran</label>
                        <select name="category_id" id="budget_category_id" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($expense_categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Anggaran (Rp)</label>
                        <input type="text" id="budget_amount_display" class="form-control currency-input" placeholder="0" required>
                        <input type="hidden" name="amount" id="budget_amount_hidden">
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-custom">Simpan Anggaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Anggaran -->
<div class="modal fade" id="editBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Edit Anggaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBudgetForm">
                <input type="hidden" name="id" id="edit_budget_id">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori</label>
                        <select name="category_id" id="edit_category_id" class="form-select" required>
                            <?php foreach ($expense_categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Anggaran (Rp)</label>
                        <input type="text" id="edit_amount_display" class="form-control currency-input" required>
                        <input type="hidden" name="amount" id="edit_amount_hidden">
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-custom">Update Anggaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var swalBaseConfig = {
        customClass: {
            popup: 'swal2-popup',
            title: 'swal2-title',
            confirmButton: 'swal2-confirm',
            cancelButton: 'swal2-cancel'
        },
        buttonsStyling: false
    };

    function getSwalConfig(overrides) {
        var config = {};
        for (var key in swalBaseConfig) config[key] = swalBaseConfig[key];
        if (overrides) for (var key in overrides) config[key] = overrides[key];
        return config;
    }

    // Currency Formatting
    var currencyInputs = document.querySelectorAll('.currency-input');
    for (var i = 0; i < currencyInputs.length; i++) {
        currencyInputs[i].addEventListener('input', function() {
            var val = this.value.replace(/[^\d]/g, '');
            var num = val ? parseInt(val, 10) : 0;
            if (this.id === 'budget_amount_display') document.getElementById('budget_amount_hidden').value = num;
            if (this.id === 'edit_amount_display') document.getElementById('edit_amount_hidden').value = num;
            this.value = val ? num.toLocaleString('id-ID') : '0';
        });
    }

    // Add Budget
    var addForm = document.getElementById('addBudgetForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var amount = document.getElementById('budget_amount_hidden').value;
            if (!amount || amount <= 0) {
                Swal.fire(getSwalConfig({ title: 'Oops!', text: 'Masukkan jumlah anggaran yang valid', icon: 'error' }));
                return;
            }
            
            Swal.fire(getSwalConfig({ title: 'Simpan Anggaran?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Simpan!' }))
            .then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire(getSwalConfig({ title: 'Memproses...', didOpen: function() { Swal.showLoading(); } }));
                    var formData = new FormData(addForm);
                    fetch('add.php', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) {
                            Swal.fire(getSwalConfig({ title: 'Berhasil!', text: d.message, icon: 'success' })).then(function() { window.location.reload(); });
                        } else {
                            Swal.fire(getSwalConfig({ title: 'Gagal!', text: d.message, icon: 'error' }));
                        }
                    })
                    .catch(function() { Swal.fire(getSwalConfig({ title: 'Error!', text: 'Terjadi kesalahan sistem', icon: 'error' })); });
                }
            });
        });
    }

    // Edit Budget
    var editForm = document.getElementById('editBudgetForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var amount = document.getElementById('edit_amount_hidden').value;
            if (!amount || amount <= 0) {
                Swal.fire(getSwalConfig({ title: 'Oops!', text: 'Masukkan jumlah anggaran yang valid', icon: 'error' }));
                return;
            }

            Swal.fire(getSwalConfig({ title: 'Update Anggaran?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Update!' }))
            .then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire(getSwalConfig({ title: 'Memproses...', didOpen: function() { Swal.showLoading(); } }));
                    var formData = new FormData(editForm);
                    fetch('edit.php', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) {
                            Swal.fire(getSwalConfig({ title: 'Berhasil!', text: d.message, icon: 'success' })).then(function() { window.location.reload(); });
                        } else {
                            Swal.fire(getSwalConfig({ title: 'Gagal!', text: d.message, icon: 'error' }));
                        }
                    })
                    .catch(function() { Swal.fire(getSwalConfig({ title: 'Error!', text: 'Terjadi kesalahan sistem', icon: 'error' })); });
                }
            });
        });
    }

    // Success Alerts from Session
    <?php if (isset($_SESSION['success_message']) || isset($_SESSION['success'])): ?>
    <?php $msg = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : $_SESSION['success']; ?>
    Swal.fire(getSwalConfig({ title: 'Berhasil!', text: <?= json_encode($msg) ?>, icon: 'success' })).then(function() {
        window.location.href = 'index.php?month=<?= $current_month ?>&year=<?= $current_year ?>';
    });
    <?php unset($_SESSION['success_message'], $_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message']) || isset($_SESSION['error'])): ?>
    <?php $msg = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : $_SESSION['error']; ?>
    Swal.fire(getSwalConfig({ title: 'Gagal!', text: <?= json_encode($msg) ?>, icon: 'error' }));
    <?php unset($_SESSION['error_message'], $_SESSION['error']); ?>
    <?php endif; ?>
});

// Global Helpers
function editBudget(id, categoryId, amount) {
    document.getElementById('edit_budget_id').value = id;
    document.getElementById('edit_category_id').value = categoryId;
    document.getElementById('edit_amount_display').value = new Intl.NumberFormat('id-ID').format(amount);
    document.getElementById('edit_amount_hidden').value = amount;
    var modal = new bootstrap.Modal(document.getElementById('editBudgetModal'));
    modal.show();
}

function deleteBudget(id, categoryName) {
    Swal.fire({
        customClass: {
            popup: 'swal2-popup',
            title: 'swal2-title',
            confirmButton: 'swal2-confirm bg-danger',
            cancelButton: 'swal2-cancel'
        },
        buttonsStyling: false,
        title: 'Hapus Anggaran?',
        html: 'Hapus anggaran untuk <strong>' + categoryName + '</strong>?<br><small class="text-danger">Data tidak bisa dikembalikan!</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Menghapus...', customClass: { popup: 'swal2-popup' }, buttonsStyling: false, didOpen: function() { Swal.showLoading(); } });
            var formData = new FormData();
            formData.append('id', id);
            fetch('delete.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    Swal.fire({ title: 'Berhasil!', text: d.message, icon: 'success', customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm' }, buttonsStyling: false })
                    .then(function() { window.location.reload(); });
                } else {
                    Swal.fire({ title: 'Gagal!', text: d.message, icon: 'error', customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm' }, buttonsStyling: false });
                }
            });
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>