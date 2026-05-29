<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';
require_once '../../classes/Account.php';
require_once '../../classes/Category.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Daftar Transaksi';
$current_page = 'transactions';

$db = Database::getInstance()->getConnection();
$transaction = new Transaction();
$account = new Account();
$category = new Category();

// Handle filters
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_account = isset($_GET['account']) ? $_GET['account'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Pagination settings
$per_page = isset($_GET['per_page']) && $_GET['per_page'] != 'all' ? (int)$_GET['per_page'] : 999999;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Get total transactions count with filters
$total_transactions = $transaction->getCountWithFilters(
    $_SESSION['user_id'],
    $filter_type,
    $filter_account,
    $filter_category,
    $filter_date_from,
    $filter_date_to
);

$total_pages = ($per_page == 999999) ? 1 : ceil($total_transactions / $per_page);

// Get transactions with filters and pagination
$transactions = $transaction->getAllWithFiltersPaginated(
    $_SESSION['user_id'],
    $filter_type,
    $filter_account,
    $filter_category,
    $filter_date_from,
    $filter_date_to,
    $per_page,
    $offset
);

// Get accounts and categories for filters and forms
$accounts = $account->getAll($_SESSION['user_id']);
$categories = $category->getAll($_SESSION['user_id']);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .filter-section { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 30px; box-shadow: var(--card-shadow); }
    .filter-title { font-size: 16px; font-weight: 600; color: var(--fg); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
    .summary-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius-lg); padding: 24px; text-align: center; margin-bottom: 20px; box-shadow: var(--card-shadow); }
    .stat-icon-circle { width: 50px; height: 50px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 20px; }
    .transactions-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--card-shadow); }

    .btn-apply-filter { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: var(--radius-full); font-weight: 700; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; }
    .btn-apply-filter:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); color: white; }
    .btn-reset-filter { background: transparent; color: var(--muted); border: 1px solid var(--border); padding: 10px 24px; border-radius: var(--radius-full); font-weight: 600; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-reset-filter:hover { background: #fce8e6; color: var(--danger); border-color: #fce8e6; }

    .header-income { background: #d1fae5; }
    .header-expense { background: #fee2e2; }
    .header-transfer { background: #dbeafe; }

    .detail-header-gradient { padding: 24px; border-radius: var(--radius-lg) var(--radius-lg) 0 0; }
    .detail-body { padding: 24px; }
    .detail-row { display: flex; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid var(--border); }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { font-size: 13px; color: var(--muted); font-weight: 600; }
    .detail-value { font-size: 14px; font-weight: 600; color: var(--fg); text-align: right; }

    .btn-detail-edit { background: var(--primary); color: white; border: none; padding: 12px 28px; border-radius: var(--radius-full); font-weight: 700; font-size: 14px; transition: var(--transition); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-detail-edit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); color: white; }
    .btn-detail-close { background: transparent; color: var(--muted); border: 1px solid var(--border); padding: 12px 28px; border-radius: var(--radius-full); font-weight: 600; font-size: 14px; transition: var(--transition); }
    .btn-detail-close:hover { background: var(--surface); color: var(--fg); }

    /* Action Buttons in Table */
    .btn-detail, .btn-edit, .btn-delete {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: var(--transition);
        font-size: 14px;
        cursor: pointer;
    }

    .btn-detail { background: rgba(66, 133, 244, 0.1); color: var(--info); }
    .btn-detail:hover { background: var(--info); color: white; }

    .btn-edit { background: rgba(251, 188, 5, 0.1); color: var(--warning); }
    .btn-edit:hover { background: var(--warning); color: white; }

    .btn-delete { background: rgba(234, 67, 53, 0.1); color: var(--danger); }
    .btn-delete:hover { background: var(--danger); color: white; }

    @media (max-width: 768px) {
        .filter-section { padding: 16px; }
        .btn-apply-filter, .btn-reset-filter { width: 100%; justify-content: center; }
        .btn-detail-edit, .btn-detail-close { width: 100%; justify-content: center; }
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Daftar Transaksi</h1>
                    <p class="welcome-subtitle">Kelola semua transaksi keuangan Anda</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="btn-primary-custom" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Tambah Transaksi
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <?php
        $total_income = 0;
        $total_expense = 0;
        $total_transfer = 0;

        // Untuk summary, kita hitung dari semua transaksi (tanpa pagination)
        $all_transactions = $transaction->getAllWithFilters(
            $_SESSION['user_id'],
            $filter_type,
            $filter_account,
            $filter_category,
            $filter_date_from,
            $filter_date_to
        );

        foreach ($all_transactions as $trans) {
            if ($trans['type'] == 'income') {
                $total_income += $trans['amount'];
            } elseif ($trans['type'] == 'expense') {
                $total_expense += $trans['amount'];
            } elseif ($trans['type'] == 'transfer') {
                $total_transfer += $trans['amount'];
            }
        }

        $balance = $total_income - $total_expense - $total_transfer;
        ?>
        
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="summary-card animated" style="animation-delay: 0.1s">
                    <div class="summary-label">Total Pemasukan</div>
                    <div class="summary-value summary-income"><?= formatRupiah($total_income) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="summary-card animated" style="animation-delay: 0.2s">
                    <div class="summary-label">Total Pengeluaran</div>
                    <div class="summary-value summary-expense"><?= formatRupiah($total_expense) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="summary-card animated" style="animation-delay: 0.25s">
                    <div class="summary-label">Total Transfer</div>
                    <div class="summary-value summary-transfer"><?= formatRupiah($total_transfer) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="summary-card animated" style="animation-delay: 0.3s">
                    <div class="summary-label">Saldo Akhir</div>
                    <div class="summary-value <?= $balance >= 0 ? 'summary-income' : 'summary-expense' ?>">
                        <?= formatRupiah($balance) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section animated" style="animation-delay: 0.4s">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Transaksi
            </div>
            <form method="GET" action="" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select">
                            <option value="all" <?= $filter_type == 'all' ? 'selected' : '' ?>>Semua</option>
                            <option value="income" <?= $filter_type == 'income' ? 'selected' : '' ?>>Pemasukan</option>
                            <option value="expense" <?= $filter_type == 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
                            <option value="transfer" <?= $filter_type == 'transfer' ? 'selected' : '' ?>>Transfer</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Akun</label>
                        <select name="account" class="form-select">
                            <option value="">Semua Akun</option>
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>" <?= $filter_account == $acc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc['name']) ?> (<?= formatRupiah($acc['balance']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filter_category == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control" value="<?= $filter_date_from ?>">
                    </div>
                    
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control" value="<?= $filter_date_to ?>">
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn-apply-filter">
                            <i class="fas fa-filter"></i> Terapkan Filter
                        </button>
                        <a href="index.php" class="btn-reset-filter">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </div>
                
                <!-- Simpan per_page untuk dikirim via form -->
                <input type="hidden" name="per_page" id="filterPerPage" value="<?= $per_page ?>">
                <input type="hidden" name="page" id="filterPage" value="1">
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="animated" style="animation-delay: 0.5s">
            <div class="transactions-card">
                <div class="card-header-custom">
                    <div class="card-title-custom">
                        <i class="fas fa-list"></i>
                        Daftar Transaksi
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <div class="pagination-info">
                            <i class="fas fa-chart-line"></i> 
                            Menampilkan <?= count($transactions) ?> dari <?= $total_transactions ?> transaksi
                        </div>
                        <select id="perPageSelect" class="per-page-select">
                            <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10 per halaman</option>
                            <option value="20" <?= $per_page == 20 ? 'selected' : '' ?>>20 per halaman</option>
                            <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 per halaman</option>
                            <option value="all" <?= $per_page == 999999 ? 'selected' : '' ?>>Semua</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Kategori</th>
                                <th>Akun</th>
                                <th>Tipe</th>
                                <th>Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) > 0): ?>
                                <?php foreach ($transactions as $trans): ?>
                                <tr>
                                    <td data-label="Tanggal">
                                        <div class="fw-bold text-muted" style="font-size: 0.85rem;"><?= formatDate($trans['transaction_date']) ?></div>
                                    </td>
                                    <td data-label="Deskripsi">
                                        <div class="fw-bold"><?= htmlspecialchars($trans['description']) ?></div>
                                    </td>
                                    <td data-label="Kategori">
                                        <span class="<?= $trans['type'] == 'income' ? 'income-badge' : ($trans['type'] == 'expense' ? 'expense-badge' : 'badge-account') ?>" style="font-size: 11px; padding: 4px 10px;">
                                            <i class="fas fa-tag me-1" style="opacity: 0.8;"></i>
                                            <?= htmlspecialchars($trans['category_name'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td data-label="Akun">
                                        <span class="badge-account">
                                            <i class="fas fa-wallet me-1"></i>
                                            <?= htmlspecialchars($trans['account_name']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Tipe">
                                        <?php if ($trans['type'] == 'income'): ?>
                                            <span class="income-badge" style="padding: 4px 12px; border-radius: 20px;">
                                                <i class="fas fa-arrow-up"></i> Pemasukan
                                            </span>
                                        <?php elseif ($trans['type'] == 'expense'): ?>
                                            <span class="expense-badge" style="padding: 4px 12px; border-radius: 20px;">
                                                <i class="fas fa-arrow-down"></i> Pengeluaran
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-account" style="padding: 4px 12px; border-radius: 20px;">
                                                <i class="fas fa-exchange-alt"></i> Transfer
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Jumlah">
                                        <span class="<?= $trans['type'] == 'income' ? 'income-badge' : ($trans['type'] == 'expense' ? 'expense-badge' : 'badge-account') ?>" style="font-weight: 800; font-size: 1rem; min-width: 120px; justify-content: flex-end;">
                                            <i class="fas fa-<?= $trans['type'] == 'income' ? 'plus-circle' : ($trans['type'] == 'expense' ? 'minus-circle' : 'exchange-alt') ?> me-1"></i>
                                            <?= formatRupiah($trans['amount']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="d-flex gap-2">
                                            <button class="btn-detail" onclick="openDetailModal(<?= $trans['id'] ?>)" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-edit" onclick="openEditModal(<?= $trans['id'] ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-delete" onclick="deleteTransaction(<?= $trans['id'] ?>)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-receipt"></i>
                                            <p>Belum ada transaksi</p>
                                            <button class="btn-primary-custom mt-2" onclick="openAddModal()">
                                                <i class="fas fa-plus"></i> Tambah Transaksi Sekarang
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($per_page != 999999 && $total_pages > 1): ?>
                <div class="pagination-custom">
                    <div class="me-auto">
                        <span class="pagination-info">
                            Halaman <?= $page ?> dari <?= $total_pages ?>
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <?php
                        // Build query string without page
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $base_url = '?' . http_build_query($query_params);
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?= $base_url ?>&page=<?= $page - 1 ?>" class="page-link-custom">
                                <i class="fas fa-chevron-left"></i> Sebelumnya
                            </a>
                        <?php else: ?>
                            <span class="page-link-custom disabled">
                                <i class="fas fa-chevron-left"></i> Sebelumnya
                            </span>
                        <?php endif; ?>
                        
                        <?php
                        // Tampilkan maksimal 5 nomor halaman
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="<?= $base_url ?>&page=<?= $i ?>" class="page-link-custom <?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="<?= $base_url ?>&page=<?= $page + 1 ?>" class="page-link-custom">
                                Selanjutnya <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link-custom disabled">
                                Selanjutnya <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH TRANSAKSI -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Transaksi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addTransactionForm">
                <div class="modal-body modal-body-custom">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipe Transaksi <span class="text-danger">*</span></label>
                            <select name="type" id="add_type" class="form-select" required>
                                <option value="income">Pemasukan</option>
                                <option value="expense">Pengeluaran</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                            <select name="category_id" id="add_category_id" class="form-select" required>
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $income_categories = [];
                                $expense_categories = [];
                                foreach ($categories as $cat):
                                    if ($cat['type'] == 'income'):
                                        $income_categories[] = $cat;
                                    elseif ($cat['type'] == 'expense'):
                                        $expense_categories[] = $cat;
                                    endif;
                                endforeach;
                                ?>
                                <optgroup label="Kategori Pemasukan" id="income-categories-group">
                                    <?php foreach ($income_categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" data-type="income"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Kategori Pengeluaran" id="expense-categories-group">
                                    <?php foreach ($expense_categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" data-type="expense"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Akun <span class="text-danger">*</span></label>
                            <select name="account_id" id="add_account_id" class="form-select" required>
                                <option value="">Pilih Akun</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= formatRupiah($acc['balance']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12" id="add_transfer_account_div" style="display: none;">
                            <label class="form-label fw-bold">Akun Tujuan (Transfer)</label>
                            <select name="to_account_id" id="add_to_account_id" class="form-select">
                                <option value="">Pilih Akun Tujuan</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= formatRupiah($acc['balance']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Khusus untuk transaksi transfer</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="add_amount" class="form-control" placeholder="Masukkan jumlah" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi transaksi"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT TRANSAKSI -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit Transaksi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTransactionForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body modal-body-custom">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipe Transaksi <span class="text-danger">*</span></label>
                            <select name="type" id="edit_type" class="form-select" required>
                                <option value="income">Pemasukan</option>
                                <option value="expense">Pengeluaran</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kategori</label>
                            <select name="category_id" id="edit_category_id" class="form-select">
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $all_income_cats = [];
                                $all_expense_cats = [];
                                foreach ($categories as $cat):
                                    if ($cat['type'] == 'income'):
                                        $all_income_cats[] = $cat;
                                    elseif ($cat['type'] == 'expense'):
                                        $all_expense_cats[] = $cat;
                                    endif;
                                endforeach;
                                ?>
                                <optgroup label="Kategori Pemasukan" id="edit-income-group">
                                    <?php foreach ($all_income_cats as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" data-type="income"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Kategori Pengeluaran" id="edit-expense-group">
                                    <?php foreach ($all_expense_cats as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" data-type="expense"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Akun <span class="text-danger">*</span></label>
                            <select name="account_id" id="edit_account_id" class="form-select" required>
                                <option value="">Pilih Akun</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= formatRupiah($acc['balance']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12" id="edit_transfer_account_div" style="display: none;">
                            <label class="form-label fw-bold">Akun Tujuan (Transfer)</label>
                            <select name="to_account_id" id="edit_to_account_id" class="form-select">
                                <option value="">Pilih Akun Tujuan</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= formatRupiah($acc['balance']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Khusus untuk transaksi transfer</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="edit_amount" class="form-control" placeholder="Masukkan jumlah" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3" placeholder="Deskripsi transaksi"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DETAIL TRANSAKSI REDESIGNED -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-detail">
        <div class="modal-content modal-content-custom">
            <div id="detail_header_bg" class="detail-header-gradient">
                <div class="detail-type-text" id="detail_type_label">Transaksi</div>
                <div class="detail-amount-display" id="detail_amount">Rp 0</div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="detail-info-container">
                <div class="row">
                    <div class="col-6">
                        <div class="info-group">
                            <span class="info-label-sm">Tanggal</span>
                            <div class="info-value-md" id="detail_date">
                                <i class="fas fa-calendar-alt"></i> -
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-group">
                            <span class="info-label-sm">Kategori</span>
                            <div class="info-value-md" id="detail_category">
                                <i class="fas fa-tag"></i> -
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="info-group">
                            <span class="info-label-sm">Akun</span>
                            <div class="info-value-md" id="detail_account">
                                <i class="fas fa-wallet"></i> -
                            </div>
                        </div>
                    </div>
                    <div class="col-12" id="detail_transfer_row" style="display: none;">
                        <div class="info-group">
                            <span class="info-label-sm">Transfer Ke</span>
                            <div class="info-value-md" id="detail_transfer_account">
                                <i class="fas fa-exchange-alt"></i> -
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="info-group">
                            <span class="info-label-sm">Deskripsi</span>
                            <div class="info-value-md" id="detail_description" style="font-weight: 500; color: var(--muted);">
                                <i class="fas fa-align-left"></i> -
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="p-2 rounded bg-light" style="font-size: 11px; color: var(--text-light); text-align: center;">
                            Dibuat pada: <span id="detail_created_at">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="detail-footer-custom">
                <button type="button" class="btn-detail-action btn-detail-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
                <button type="button" class="btn-detail-action btn-detail-edit" id="detail_edit_btn">
                    <i class="fas fa-edit"></i> Edit Transaksi
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
<script>
    // Fungsi untuk filter kategori berdasarkan tipe transaksi (ADD MODAL)
    function filterAddCategoriesByType(selectedType) {
        const incomeGroup = document.getElementById('income-categories-group');
        const expenseGroup = document.getElementById('expense-categories-group');
        const categorySelect = document.getElementById('add_category_id');
        
        if (!categorySelect) return;
        
        if (selectedType === 'income') {
            if (incomeGroup) incomeGroup.style.display = '';
            if (expenseGroup) expenseGroup.style.display = 'none';
        } else if (selectedType === 'expense') {
            if (incomeGroup) incomeGroup.style.display = 'none';
            if (expenseGroup) expenseGroup.style.display = '';
        } else {
            if (incomeGroup) incomeGroup.style.display = '';
            if (expenseGroup) expenseGroup.style.display = '';
        }
        
        // Reset selected category
        categorySelect.value = '';
    }
    
    // Fungsi untuk filter kategori berdasarkan tipe transaksi (EDIT MODAL)
    function filterEditCategoriesByType(selectedType) {
        const incomeGroup = document.getElementById('edit-income-group');
        const expenseGroup = document.getElementById('edit-expense-group');
        const categorySelect = document.getElementById('edit_category_id');
        
        if (!categorySelect) return;
        
        if (selectedType === 'income') {
            if (incomeGroup) incomeGroup.style.display = '';
            if (expenseGroup) expenseGroup.style.display = 'none';
        } else if (selectedType === 'expense') {
            if (incomeGroup) incomeGroup.style.display = 'none';
            if (expenseGroup) expenseGroup.style.display = '';
        } else {
            if (incomeGroup) incomeGroup.style.display = '';
            if (expenseGroup) expenseGroup.style.display = '';
        }
    }
    
    // Toggle transfer field
    function toggleTransferField(formType) {
        const typeSelect = document.getElementById(`${formType}_type`);
        const transferDiv = document.getElementById(`${formType}_transfer_account_div`);
        const toAccountSelect = document.getElementById(`${formType}_to_account_id`);
        
        if (typeSelect && typeSelect.value === 'transfer') {
            if (transferDiv) transferDiv.style.display = 'block';
            if (toAccountSelect) toAccountSelect.required = true;
        } else {
            if (transferDiv) transferDiv.style.display = 'none';
            if (toAccountSelect) toAccountSelect.required = false;
        }
    }
    
    // Event listener untuk add modal
    document.getElementById('add_type')?.addEventListener('change', function() {
        toggleTransferField('add');
        filterAddCategoriesByType(this.value);
    });
    
    // Event listener untuk edit modal
    document.getElementById('edit_type')?.addEventListener('change', function() {
        toggleTransferField('edit');
        filterEditCategoriesByType(this.value);
    });
    
    // Open Add Modal
    function openAddModal() {
        document.getElementById('addTransactionForm').reset();
        const typeSelect = document.getElementById('add_type');
        if (typeSelect) {
            typeSelect.value = 'income';
            toggleTransferField('add');
            filterAddCategoriesByType('income');
        }
        new bootstrap.Modal(document.getElementById('addModal')).show();
    }
    
    // Open Edit Modal
    function openEditModal(id) {
        Swal.fire({
            title: 'Loading...',
            text: 'Mengambil data transaksi',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch(`get_detail.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    const trans = data.transaction;
                    document.getElementById('edit_id').value = trans.id;
                    document.getElementById('edit_type').value = trans.type;
                    document.getElementById('edit_transaction_date').value = trans.transaction_date;
                    document.getElementById('edit_amount').value = trans.amount;
                    document.getElementById('edit_description').value = trans.description || '';
                    
                    // Filter kategori berdasarkan tipe transaksi
                    filterEditCategoriesByType(trans.type);
                    
                    // Set nilai kategori setelah filter diterapkan
                    setTimeout(() => {
                        const categorySelect = document.getElementById('edit_category_id');
                        if (categorySelect) {
                            categorySelect.value = trans.category_id || '';
                        }
                    }, 50);
                    
                    document.getElementById('edit_account_id').value = trans.account_id;
                    
                    if (trans.to_account_id) {
                        const toAccountSelect = document.getElementById('edit_to_account_id');
                        if (toAccountSelect) toAccountSelect.value = trans.to_account_id;
                    }
                    
                    toggleTransferField('edit');
                    new bootstrap.Modal(document.getElementById('editModal')).show();
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    }
    
    // DOM Ready initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial filter for add modal
        const addType = document.getElementById('add_type');
        if (addType) {
            filterAddCategoriesByType(addType.value);
        }
        
        // Per page select change
        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                let perPage = this.value;
                let url = new URL(window.location.href);
                url.searchParams.set('per_page', perPage);
                url.searchParams.set('page', 1); // Reset ke halaman 1
                window.location.href = url.toString();
            });
        }
    });
    
    // Submit Add Transaction
    document.getElementById('addTransactionForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('save.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                if (modal) modal.hide();
                
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
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
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan: ' + error.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });
    });
    
    // Submit Edit Transaction
    document.getElementById('editTransactionForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Sedang mengupdate transaksi',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('update.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editModal'))?.hide();
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
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
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan: ' + error.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });
    });
    
    // Delete Transaction - VERSION DEBUG
function deleteTransaction(id) {
    console.log("Delete clicked for ID:", id);
    
    if (!id) {
        Swal.fire({
            title: 'Error!',
            text: 'ID transaksi tidak valid',
            icon: 'error'
        });
        return;
    }
    
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus transaksi ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Tampilkan loading
            Swal.fire({
                title: 'Memproses...',
                text: 'Menghapus transaksi',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Panggil API delete
            fetch(`delete.php?id=${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log("Response status:", response.status);
                return response.text(); // Ambil sebagai text dulu untuk debug
            })
            .then(text => {
                console.log("Raw response:", text);
                
                // Coba parse JSON
                try {
                    const data = JSON.parse(text);
                    console.log("Parsed data:", data);
                    
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (e) {
                    console.error("JSON parse error:", e);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Response tidak valid: ' + text.substring(0, 200),
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal terhubung ke server: ' + error.message,
                    icon: 'error'
                });
            });
        }
    });
}
    
    // Open Detail Modal Redesigned
    function openDetailModal(id) {
        if (!id) return;
        
        Swal.fire({
            title: 'Loading...',
            text: 'Mengambil detail transaksi',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        fetch(`get_detail.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success && data.transaction) {
                    const trans = data.transaction;
                    
                    // Header Amount & Type
                    const amountEl = document.getElementById('detail_amount');
                    const headerBg = document.getElementById('detail_header_bg');
                    const typeLabel = document.getElementById('detail_type_label');
                    
                    amountEl.innerHTML = formatRupiah(trans.amount);
                    headerBg.className = 'detail-header-gradient'; // Reset
                    
                    if (trans.type === 'income') {
                        headerBg.classList.add('header-income');
                        typeLabel.innerHTML = '<i class="fas fa-arrow-up me-1"></i> Pemasukan';
                    } else if (trans.type === 'expense') {
                        headerBg.classList.add('header-expense');
                        typeLabel.innerHTML = '<i class="fas fa-arrow-down me-1"></i> Pengeluaran';
                    } else {
                        headerBg.classList.add('header-transfer');
                        typeLabel.innerHTML = '<i class="fas fa-exchange-alt me-1"></i> Transfer Saldo';
                    }
                    
                    // Info Details
                    document.getElementById('detail_date').innerHTML = `<i class="fas fa-calendar-alt"></i> ${trans.transaction_date || '-'}`;
                    document.getElementById('detail_category').innerHTML = `<i class="fas fa-tag"></i> ${trans.category_name || '-'}`;
                    document.getElementById('detail_account').innerHTML = `<i class="fas fa-wallet"></i> ${trans.account_name || '-'}`;
                    document.getElementById('detail_description').innerHTML = `<i class="fas fa-align-left"></i> ${trans.description || 'Tidak ada deskripsi'}`;
                    document.getElementById('detail_created_at').innerHTML = trans.created_at || '-';
                    
                    // Transfer Row
                    const transferRow = document.getElementById('detail_transfer_row');
                    if (trans.type === 'transfer' && trans.to_account_id) {
                        transferRow.style.display = 'block';
                        fetch(`get_account_name.php?id=${trans.to_account_id}`)
                            .then(res => res.json())
                            .then(accData => {
                                document.getElementById('detail_transfer_account').innerHTML = 
                                    `<i class="fas fa-exchange-alt"></i> ${accData.success ? accData.account_name : 'Akun Tujuan'}`;
                            });
                    } else {
                        transferRow.style.display = 'none';
                    }
                    
                    // Edit Button Link
                    document.getElementById('detail_edit_btn').onclick = function() {
                        bootstrap.Modal.getInstance(document.getElementById('detailModal'))?.hide();
                        setTimeout(() => openEditModal(id), 300);
                    };
                    
                    new bootstrap.Modal(document.getElementById('detailModal')).show();
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
            });
    }
    
    function formatRupiah(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
    }
</script>

<?php include '../../includes/footer.php'; ?>