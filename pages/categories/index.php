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
    /* ========== LAYOUT UTAMA ========== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        overflow-x: hidden !important;
        width: 100% !important;
        position: relative;
    }
    
    .wrapper {
        display: flex !important;
        width: 100% !important;
        align-items: stretch !important;
        overflow-x: hidden !important;
    }
    
    #sidebar {
        min-width: 250px !important;
        max-width: 250px !important;
        width: 250px !important;
        transition: all 0.3s;
        flex-shrink: 0 !important;
        background: #2c3e50;
        color: #fff;
    }
    
    #content, .main-content {
        width: calc(100% - 250px) !important;
        min-height: 100vh !important;
        transition: all 0.3s;
        overflow-x: hidden !important;
        flex: 1 !important;
        background: #f8f9fa;
    }
    
    .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        padding: 20px !important;
        margin: 0 !important;
        overflow-x: hidden !important;
    }
    
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
        
        .container-fluid {
            padding: 15px !important;
        }
    }
    
    /* ========== WELCOME CARD ========== */
    .welcome-card {
    background: linear-gradient(135deg, #FFFFFF 0%, #FFFFFF 100%);
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 24px;
    color: white;
    position: relative;
    overflow: hidden;
    width: 100%;

    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
    
    .welcome-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    /* ========== CATEGORY SECTIONS ========== */
    .category-section {
        background: white;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 0;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        height: 100%;
    }
    
    .category-section:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .category-header {
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 15px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .category-header h3 {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .category-header.income h3 i {
        color: #10b981;
    }
    
    .category-header.expense h3 i {
        color: #ef4444;
    }
    
    .category-count {
        background: #f3f4f6;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
    }
    
    .category-count.income {
        background: #d1fae5;
        color: #059669;
    }
    
    .category-count.expense {
        background: #fee2e2;
        color: #dc2626;
    }
    
    /* Category List */
    .category-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .category-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 12px;
        margin-bottom: 8px;
        background: #f9fafb;
        border-radius: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .category-item:hover {
        background: #f3f4f6;
        transform: translateX(4px);
    }
    
    .category-info {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
    }
    
    .category-badge {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        transition: all 0.2s ease;
    }
    
    .category-item:hover .category-badge {
        transform: scale(1.2);
    }
    
    .badge-income {
        background: #10b981;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }
    
    .badge-expense {
        background: #ef4444;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
    }
    
    .category-name {
        font-size: 15px;
        font-weight: 500;
        color: #1f2937;
    }
    
    .category-actions {
        display: flex;
        gap: 8px;
        opacity: 0.6;
        transition: opacity 0.2s ease;
    }
    
    .category-item:hover .category-actions {
        opacity: 1;
    }
    
    /* Button Styles */
    .btn-add-category {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-add-category:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-edit, .btn-delete {
        background: transparent;
        border: none;
        padding: 6px 10px;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-size: 13px;
        cursor: pointer;
    }
    
    .btn-edit {
        color: #667eea;
    }
    
    .btn-edit:hover {
        background: rgba(102, 126, 234, 0.1);
        transform: scale(1.1);
    }
    
    .btn-delete {
        color: #ef4444;
    }
    
    .btn-delete:hover {
        background: rgba(239, 68, 68, 0.1);
        transform: scale(1.1);
    }
    
    /* Empty State */
    .empty-category {
        text-align: center;
        padding: 50px 20px;
        background: #f9fafb;
        border-radius: 16px;
    }
    
    .empty-category i {
        font-size: 48px;
        color: #d1d5db;
        margin-bottom: 15px;
    }
    
    .empty-category p {
        color: #6b7280;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .empty-category small {
        color: #9ca3af;
        font-size: 12px;
    }
    
    /* Modal Styles */
    .modal-content-custom {
        background: white;
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }
    
    .modal-header-custom {
        border-bottom: 1px solid #e5e7eb;
        padding: 20px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .modal-header-custom .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .modal-body-custom {
        padding: 24px;
    }
    
    .modal-footer-custom {
        border-top: 1px solid #e5e7eb;
        padding: 20px 24px;
        background: #f9fafb;
    }
    
    /* Form Controls */
    .form-control, .form-select {
        border-radius: 12px !important;
        border: 1px solid #e5e7eb !important;
        padding: 12px 16px !important;
        transition: all 0.2s ease !important;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-secondary {
        background: rgba(107, 114, 128, 0.1);
        border: 1px solid rgba(107, 114, 128, 0.2);
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: rgba(107, 114, 128, 0.2);
        color: #4b5563;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes iconPop {
        0% {
            transform: scale(0);
            opacity: 0;
        }
        80% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .animated {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    
    /* SweetAlert2 Professional Style */
    .swal2-popup {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 24px !important;
        padding: 2em !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: fadeInScale 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .swal2-title {
        color: #1f2937 !important;
        font-weight: 700 !important;
        font-size: 1.5rem !important;
    }
    
    .swal2-html-container {
        color: #4b5563 !important;
        font-size: 0.95rem !important;
    }
    
    .swal2-confirm {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        border: none !important;
        transition: all 0.3s ease !important;
    }
    
    .swal2-confirm:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
    }
    
    .swal2-cancel {
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        background: rgba(107, 114, 128, 0.1) !important;
        color: #6b7280 !important;
        border: 1px solid rgba(107, 114, 128, 0.2) !important;
        transition: all 0.3s ease !important;
    }
    
    .swal2-cancel:hover {
        background: rgba(107, 114, 128, 0.2) !important;
        transform: translateY(-2px) !important;
    }
    
    .swal2-icon {
        animation: iconPop 0.5s ease !important;
    }
    
    .swal2-icon.swal2-warning {
        border-color: #f59e0b !important;
        color: #f59e0b !important;
    }
    
    .swal2-icon.swal2-success {
        border-color: #10b981 !important;
    }
    
    .swal2-icon.swal2-error {
        border-color: #ef4444 !important;
    }
    
    .swal2-icon.swal2-question {
        border-color: #667eea !important;
        color: #667eea !important;
    }
    
    .swal2-loader {
        border-color: #667eea !important;
        border-top-color: transparent !important;
        animation: spin 0.8s linear infinite !important;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 15px !important;
        }
        
        .welcome-title {
            font-size: 1.2rem !important;
        }
        
        .welcome-subtitle {
            font-size: 0.8rem !important;
        }
        
        .category-section {
            margin-bottom: 20px;
        }
        
        .category-header h3 {
            font-size: 18px;
        }
        
        .category-item {
            padding: 12px 10px;
        }
        
        .category-name {
            font-size: 14px;
        }
        
        .btn-edit, .btn-delete {
            padding: 4px 8px;
        }
    }
    
    @media (max-width: 576px) {
        .category-actions {
            opacity: 1;
        }
        
        .category-item {
            flex-wrap: wrap;
        }
        
        .category-actions {
            margin-top: 8px;
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Manajemen Kategori</h1>
                    <p class="welcome-subtitle">Kelola kategori pemasukan dan pengeluaran untuk transaksi Anda</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="btn-add-category" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Income Categories -->
            <div class="col-md-6">
                <div class="category-section animated" style="animation-delay: 0.1s">
                    <div class="category-header income">
                        <h3>
                            <i class="fas fa-arrow-up"></i> Kategori Pemasukan
                        </h3>
                        <span class="category-count income">
                            <i class="fas fa-tag"></i> <?= count($income_categories) ?> Kategori
                        </span>
                    </div>
                    
                    <?php if (count($income_categories) > 0): ?>
                        <ul class="category-list">
                            <?php $index = 0; foreach ($income_categories as $cat): $index++; ?>
                            <li class="category-item" data-id="<?= $cat['id'] ?>" style="animation: slideInRight 0.3s ease <?= $index * 0.05 ?>s both;">
                                <div class="category-info">
                                    <span class="category-badge badge-income"></span>
                                    <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                </div>
                                <div class="category-actions">
                                    <button class="btn-edit" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', 'income')" title="Edit Kategori">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-delete" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', 'income')" title="Hapus Kategori">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-category">
                            <i class="fas fa-folder-open"></i>
                            <p>Belum ada kategori pemasukan</p>
                            <small>Klik tombol tambah untuk membuat kategori baru</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Expense Categories -->
            <div class="col-md-6">
                <div class="category-section animated" style="animation-delay: 0.2s">
                    <div class="category-header expense">
                        <h3>
                            <i class="fas fa-arrow-down"></i> Kategori Pengeluaran
                        </h3>
                        <span class="category-count expense">
                            <i class="fas fa-tag"></i> <?= count($expense_categories) ?> Kategori
                        </span>
                    </div>
                    
                    <?php if (count($expense_categories) > 0): ?>
                        <ul class="category-list">
                            <?php $index = 0; foreach ($expense_categories as $cat): $index++; ?>
                            <li class="category-item" data-id="<?= $cat['id'] ?>" style="animation: slideInRight 0.3s ease <?= $index * 0.05 ?>s both;">
                                <div class="category-info">
                                    <span class="category-badge badge-expense"></span>
                                    <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                </div>
                                <div class="category-actions">
                                    <button class="btn-edit" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', 'expense')" title="Edit Kategori">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-delete" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', 'expense')" title="Hapus Kategori">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-category">
                            <i class="fas fa-folder-open"></i>
                            <p>Belum ada kategori pengeluaran</p>
                            <small>Klik tombol tambah untuk membuat kategori baru</small>
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
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Kategori Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCategoryForm">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kategori</label>
                        <input type="text" name="name" id="category_name" class="form-control" placeholder="Contoh: Makanan, Transportasi, Gaji, Investasi" required>
                        <small class="text-muted">Masukkan nama kategori yang mudah diingat</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Kategori</label>
                        <select name="type" id="category_type" class="form-select" required>
                            <option value="income">📈 Pemasukan (Income)</option>
                            <option value="expense">📉 Pengeluaran (Expense)</option>
                        </select>
                        <small class="text-muted">Pilih tipe kategori sesuai dengan jenis transaksi</small>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Simpan Kategori
                    </button>
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
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit Kategori
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm">
                <input type="hidden" name="id" id="edit_category_id">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kategori</label>
                        <input type="text" name="name" id="edit_category_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Kategori</label>
                        <select name="type" id="edit_category_type" class="form-select" required>
                            <option value="income">📈 Pemasukan (Income)</option>
                            <option value="expense">📉 Pengeluaran (Expense)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Update Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // ADD Category
    document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let categoryName = document.getElementById('category_name').value.trim();
        let categoryType = document.getElementById('category_type').value;
        let typeText = categoryType === 'income' ? 'Pemasukan' : 'Pengeluaran';
        
        if (!categoryName) {
            Swal.fire({
                title: 'Oops!',
                text: 'Nama kategori harus diisi',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        Swal.fire({
            title: 'Tambah Kategori Baru?',
            html: `
                <div style="text-align: left; background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 12px;">
                    <p><strong><i class="fas fa-tag"></i> Nama:</strong> ${escapeHtml(categoryName)}</p>
                    <p><strong><i class="fas fa-chart-line"></i> Tipe:</strong> ${typeText}</p>
                </div>
                <small class="mt-2 d-block">Apakah data sudah benar?</small>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-save"></i> Ya, Simpan!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menyimpan...',
                    text: 'Sedang menyimpan kategori baru',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: 'add.php',
                    type: 'POST',
                    data: {
                        name: categoryName,
                        type: categoryType
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                            document.getElementById('addCategoryForm').reset();
                            
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true,
                                didOpen: () => {
                                    canvasConfetti({
                                        particleCount: 100,
                                        spread: 70,
                                        origin: { y: 0.6 }
                                    });
                                }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Gagal!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menyimpan data',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
    
    // EDIT Category
    document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let categoryId = document.getElementById('edit_category_id').value;
        let categoryName = document.getElementById('edit_category_name').value.trim();
        let categoryType = document.getElementById('edit_category_type').value;
        let typeText = categoryType === 'income' ? 'Pemasukan' : 'Pengeluaran';
        
        if (!categoryName) {
            Swal.fire({
                title: 'Oops!',
                text: 'Nama kategori harus diisi',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        Swal.fire({
            title: 'Update Kategori?',
            html: `
                <div style="text-align: left; background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 12px;">
                    <p><strong><i class="fas fa-tag"></i> Nama:</strong> ${escapeHtml(categoryName)}</p>
                    <p><strong><i class="fas fa-chart-line"></i> Tipe:</strong> ${typeText}</p>
                </div>
                <small class="mt-2 d-block">Apakah Anda yakin ingin mengupdate kategori ini?</small>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-save"></i> Ya, Update!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Mengupdate...',
                    text: 'Sedang mengupdate kategori',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: 'edit.php',
                    type: 'POST',
                    data: {
                        id: categoryId,
                        name: categoryName,
                        type: categoryType
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
                            
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true,
                                didOpen: () => {
                                    canvasConfetti({
                                        particleCount: 100,
                                        spread: 70,
                                        origin: { y: 0.6 }
                                    });
                                }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Gagal!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat mengupdate data',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
    
    // DELETE Category
    function deleteCategory(id, name, type) {
        let typeText = type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        
        Swal.fire({
            title: 'Hapus Kategori?',
            html: `Apakah Anda yakin ingin menghapus kategori <strong>"${escapeHtml(name)}"</strong> (${typeText})?<br><small style="color: #ef4444;">⚠️ Transaksi dengan kategori ini akan kehilangan kategori!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Sedang menghapus kategori',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: 'delete.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Gagal!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus data',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    }
    
    function editCategory(id, name, type) {
        document.getElementById('edit_category_id').value = id;
        document.getElementById('edit_category_name').value = name;
        document.getElementById('edit_category_type').value = type;
        
        var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        modal.show();
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Success message handling
    <?php if (isset($_SESSION['success_message'])): ?>
    Swal.fire({
        title: 'Berhasil!',
        text: '<?= $_SESSION['success_message'] ?>',
        icon: 'success',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'OK',
        didOpen: () => {
            canvasConfetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    }).then(() => {
        window.location.href = 'index.php';
    });
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    Swal.fire({
        title: 'Gagal!',
        text: '<?= $_SESSION['error_message'] ?>',
        icon: 'error',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'OK'
    });
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>