<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Category.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Manajemen Kategori';
$current_page = 'categories';

$db = Database::getInstance()->getConnection();
$category = new Category();

// Get all categories
$income_categories = $category->getAll($_SESSION['user_id'], 'income');
$expense_categories = $category->getAll($_SESSION['user_id'], 'expense');

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* ========== CATEGORIES SPECIFIC STYLES ========== */
    .category-card { 
        background: #ffffff; 
        border: 1px solid #cbd5e1; 
        border-radius: 32px; 
        padding: 35px; 
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); 
        height: 100%; 
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 4px 6px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .category-card:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
        border-color: var(--info); 
    }
    
    .category-header { 
        padding-bottom: 25px; 
        margin-bottom: 25px; 
        border-bottom: 1px solid #f1f5f9; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
    }
    
    .category-header h3 { 
        font-size: 11px; 
        font-weight: 800; 
        margin: 0; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }
    
    .category-list {
        background: #f8fafc;
        padding: 20px;
        border-radius: 20px;
        border: 1px solid #f1f5f9;
    }
    
    .category-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: #ffffff;
        border-radius: 16px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    .category-item:last-child { margin-bottom: 0; }
    .category-item:hover { 
        transform: translateY(-3px) translateX(5px); 
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }
    
    .category-name { display: flex; align-items: center; gap: 12px; }
    .category-dot { width: 10px; height: 10px; border-radius: 50%; }
    
    .badge-count {
        font-size: 11px;
        font-weight: 800;
        padding: 6px 16px;
        border-radius: 9999px;
        background: var(--surface);
        color: var(--muted);
        border: 1px solid rgba(0,0,0,0.05);
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="welcome-title">Manajemen Kategori</h1>
                    <p class="welcome-subtitle">Atur kategori transaksi untuk pelacakan yang lebih baik</p>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Income Categories -->
            <div class="col-md-6">
                <div class="category-card animated" style="animation-delay: 0.1s">
                    <div class="category-header">
                        <h3 class="text-success"><i class="fas fa-arrow-up"></i> Pemasukan</h3>
                        <span class="income-badge" style="font-size: 11px; padding: 4px 12px;"><?= count($income_categories) ?> Item</span>
                    </div>
                    
                    <?php if (count($income_categories) > 0): ?>
                        <div class="category-list">
                            <?php foreach ($income_categories as $cat): ?>
                            <div class="category-item">
                                <div class="category-name">
                                    <span class="category-dot" style="background-color: #10b981; color: #10b981;"></span>
                                    <span class="income-badge" style="padding: 4px 12px; border-radius: 8px;">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </span>
                                </div>
                                <div class="dropdown dropdown-minimal">
                                    <button class="btn btn-link" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', 'income')">
                                                <i class="fas fa-pencil-alt me-2 text-primary"></i> Edit
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', 'income')">
                                                <i class="fas fa-trash-alt me-2"></i> Hapus
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 opacity-50">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>Belum ada kategori</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Expense Categories -->
            <div class="col-md-6">
                <div class="category-card animated" style="animation-delay: 0.2s">
                    <div class="category-header">
                        <h3 class="text-danger"><i class="fas fa-arrow-down"></i> Pengeluaran</h3>
                        <span class="expense-badge" style="font-size: 11px; padding: 4px 12px;"><?= count($expense_categories) ?> Item</span>
                    </div>
                    
                    <?php if (count($expense_categories) > 0): ?>
                        <div class="category-list">
                            <?php foreach ($expense_categories as $cat): ?>
                            <div class="category-item">
                                <div class="category-name">
                                    <span class="category-dot" style="background-color: #ef4444; color: #ef4444;"></span>
                                    <span class="expense-badge" style="padding: 4px 12px; border-radius: 8px;">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </span>
                                </div>
                                <div class="dropdown dropdown-minimal">
                                    <button class="btn btn-link" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', 'expense')">
                                                <i class="fas fa-pencil-alt me-2 text-primary"></i> Edit
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', 'expense')">
                                                <i class="fas fa-trash-alt me-2"></i> Hapus
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 opacity-50">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>Belum ada kategori</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCategoryForm">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kategori</label>
                        <input type="text" id="category_name" class="form-control" placeholder="Contoh: Makanan, Gaji" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe</label>
                        <select id="category_type" class="form-select" required>
                            <option value="income">📈 Pemasukan</option>
                            <option value="expense">📉 Pengeluaran</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-custom">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm">
                <input type="hidden" id="edit_category_id">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kategori</label>
                        <input type="text" id="edit_category_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe</label>
                        <select id="edit_category_type" class="form-select" required>
                            <option value="income">📈 Pemasukan</option>
                            <option value="expense">📉 Pengeluaran</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-custom">Update Kategori</button>
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
        if (overrides) {
            for (var key in overrides) {
                if (key === 'customClass') {
                    for (var subKey in overrides[key]) config.customClass[subKey] = overrides[key][subKey];
                } else {
                    config[key] = overrides[key];
                }
            }
        }
        return config;
    }

    // Add Category
    var addForm = document.getElementById('addCategoryForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var name = document.getElementById('category_name').value;
            var type = document.getElementById('category_type').value;
            
            Swal.fire(getSwalConfig({ title: 'Simpan Kategori?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Simpan!' }))
            .then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire(getSwalConfig({ title: 'Memproses...', didOpen: function() { Swal.showLoading(); } }));
                    var formData = new FormData();
                    formData.append('name', name);
                    formData.append('type', type);
                    
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

    // Edit Category
    var editForm = document.getElementById('editCategoryForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var id = document.getElementById('edit_category_id').value;
            var name = document.getElementById('edit_category_name').value;
            var type = document.getElementById('edit_category_type').value;
            
            Swal.fire(getSwalConfig({ title: 'Update Kategori?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Update!' }))
            .then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire(getSwalConfig({ title: 'Mengupdate...', didOpen: function() { Swal.showLoading(); } }));
                    var formData = new FormData();
                    formData.append('id', id);
                    formData.append('name', name);
                    formData.append('type', type);
                    
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

    // Session handlers
    <?php if (isset($_SESSION['success_message'])): ?>
    Swal.fire(getSwalConfig({ title: 'Berhasil!', text: <?= json_encode($_SESSION['success_message']) ?>, icon: 'success' })).then(function() {
        window.location.href = 'index.php';
    });
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    Swal.fire(getSwalConfig({ title: 'Gagal!', text: <?= json_encode($_SESSION['error_message']) ?>, icon: 'error' }));
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
});

// Global Helpers
function editCategory(id, name, type) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('edit_category_type').value = type;
    var modalEl = document.getElementById('editCategoryModal');
    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function deleteCategory(id, name, type) {
    Swal.fire({
        title: 'Hapus Kategori?',
        html: 'Hapus kategori <strong>' + name + '</strong>?<br><small class="text-danger">Transaksi terkait akan kehilangan kategori!</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'swal2-popup',
            title: 'swal2-title',
            confirmButton: 'swal2-confirm bg-danger',
            cancelButton: 'swal2-cancel'
        },
        buttonsStyling: false
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Menghapus...', didOpen: function() { Swal.showLoading(); }, customClass: { popup: 'swal2-popup' }, buttonsStyling: false });
            var formData = new FormData();
            formData.append('id', id);
            fetch('delete.php', { method: 'POST', body: formData })
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
}
</script>

<?php include '../../includes/footer.php'; ?>