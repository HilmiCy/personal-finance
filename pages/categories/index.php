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
    /* ========== CARD STYLES ========== */
    .card {
        border-radius: 20px !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        margin-bottom: 20px !important;
        overflow: hidden !important;
        background: white !important;
    }
    
    /* ========== WELCOME CARD ========== */
    .welcome-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
        color: #1f2937;
        position: relative;
        overflow: hidden;
        width: 100%;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    
    .welcome-title {
        font-size: 1.6rem;
        font-weight: 700;
        margin: 0;
        color: #1f2937;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        color: #6b7280;
        font-size: 0.95rem;
    }
    
    /* Button Styles */
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
        border: none;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    /* ========== CATEGORY SECTIONS ========== */
    .category-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        border: 1px solid #f3f4f6;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
    }
    
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
    }
    
    .category-header {
        padding-bottom: 15px;
        margin-bottom: 20px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .category-header h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1f2937;
    }
    
    .header-income i { color: #10b981; }
    .header-expense i { color: #ef4444; }
    
    .category-pill {
        background: #f3f4f6;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .category-pill.income { background: #d1fae5; color: #059669; }
    .category-pill.expense { background: #fee2e2; color: #dc2626; }
    
    /* Category List Item */
    .category-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        margin-bottom: 10px;
        background: #f9fafb;
        border-radius: 14px;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    
    .category-item:hover {
        background: #ffffff;
        border-color: #e5e7eb;
        transform: translateX(4px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    
    .category-name {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .category-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .dot-income { background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
    .dot-expense { background: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1); }
    
    .dropdown-minimal .btn-link {
        color: #9ca3af;
        padding: 0;
        font-size: 16px;
        text-decoration: none;
    }

    .dropdown-minimal .btn-link:hover { color: #4b5563; }
    
    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeInScale {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    
    @keyframes iconPop {
        0% { transform: scale(0); opacity: 0; }
        80% { transform: scale(1.1); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .animated { animation: fadeInUp 0.5s ease-out forwards; }
    
    /* SweetAlert2 Professional Style */
    .swal2-popup {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 24px !important;
        padding: 2em !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2) !important;
    }
    
    .swal2-title { color: #1f2937 !important; font-weight: 700 !important; }
    .swal2-confirm {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        color: white !important;
    }

    /* Modal Styling */
    .modal-content-custom { border-radius: 24px !important; border: none !important; box-shadow: 0 20px 60px rgba(0,0,0,0.15) !important; }
    .modal-header-custom { padding: 24px !important; border-bottom: 1px solid #f3f4f6 !important; }
    .modal-body-custom { padding: 32px !important; }
    
    /* Form Controls */
    .form-control, .form-select {
        border-radius: 12px !important;
        border: 1px solid #e5e7eb !important;
        padding: 12px 16px !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        #sidebar {
            margin-left: -250px !important;
            position: fixed !important;
            z-index: 1000 !important;
            height: 100vh !important;
        }
        
        #sidebar.active { margin-left: 0 !important; }
        #content, .main-content { width: 100% !important; }
        .container-fluid { padding: 16px !important; }
        
        .welcome-title { font-size: 1.3rem; }
        .btn-primary-custom { width: 100%; justify-content: center; }
    }
</style>

<div id="content" class="main-content">
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
                    <div class="category-header header-income">
                        <h3><i class="fas fa-arrow-up"></i> Pemasukan</h3>
                        <span class="category-pill income"><?= count($income_categories) ?> Item</span>
                    </div>
                    
                    <?php if (count($income_categories) > 0): ?>
                        <div class="category-list">
                            <?php foreach ($income_categories as $cat): ?>
                            <div class="category-item">
                                <div class="category-name">
                                    <span class="category-dot dot-income"></span>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </div>
                                <div class="dropdown dropdown-minimal">
                                    <button class="btn btn-link" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
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
                    <div class="category-header header-expense">
                        <h3><i class="fas fa-arrow-down"></i> Pengeluaran</h3>
                        <span class="category-pill expense"><?= count($expense_categories) ?> Item</span>
                    </div>
                    
                    <?php if (count($expense_categories) > 0): ?>
                        <div class="category-list">
                            <?php foreach ($expense_categories as $cat): ?>
                            <div class="category-item">
                                <div class="category-name">
                                    <span class="category-dot dot-expense"></span>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </div>
                                <div class="dropdown dropdown-minimal">
                                    <button class="btn btn-link" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
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