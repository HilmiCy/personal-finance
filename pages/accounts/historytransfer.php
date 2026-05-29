<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Account.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Riwayat Transfer';
$current_page = 'accounts';

$account = new Account();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter by date
$start_date = (isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01')) . ' 00:00:00';
$end_date = (isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d')) . ' 23:59:59';

// Get transfer history with filters
if ($start_date && $end_date) {
    $transfers = $account->getTransferHistoryByDate($_SESSION['user_id'], $start_date, $end_date, 500);
} else {
    $transfers = $account->getTransferHistory($_SESSION['user_id'], 500, 0);
}

$total_transfers = count($transfers);

// Apply pagination manually
$paginated_transfers = array_slice($transfers, $offset, $limit);
$total_pages = ceil($total_transfers / $limit);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .card { border-radius: var(--radius-lg) !important; border: 1px solid var(--border) !important; box-shadow: var(--card-shadow) !important; transition: var(--transition) !important; margin-bottom: 20px !important; overflow: hidden !important; background: var(--card-bg) !important; backdrop-filter: blur(8px) !important; }
    .card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover) !important; }
    .filter-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 25px; box-shadow: var(--card-shadow); }
    .stat-summary { background: var(--surface); border-radius: var(--radius-lg); padding: 20px 24px; margin-bottom: 20px; border: 1px solid var(--border); }
    .stat-icon-sm { width: 50px; height: 50px; background: var(--card-bg); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
    .stat-icon-sm i { font-size: 24px; color: var(--success); }
    .table-container { background: var(--card-bg); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--card-shadow); border: 1px solid var(--card-border); }
    .transfer-badge { display: inline-flex; align-items: center; gap: 8px; background: var(--surface); padding: 6px 12px; border-radius: var(--radius-full); font-size: 13px; color: var(--fg); }
    .amount-negative { color: var(--danger); font-weight: 700; font-size: 15px; }
    .alert-info-custom { background: rgba(66,133,244,0.1); border: 1px solid rgba(66,133,244,0.15); border-radius: var(--radius-sm); padding: 12px 16px; color: var(--info); }
    .btn-primary-custom { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: var(--radius-full); font-weight: 600; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer; }
    .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); color: white; }
    .btn-secondary { background: transparent; border: 1px solid var(--border); padding: 10px 24px; border-radius: var(--radius-full); font-weight: 600; color: var(--muted); transition: var(--transition); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-secondary:hover { background: var(--surface); color: var(--fg); }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">
                        <i class="fas fa-history me-3" style="color: #4285f4;"></i> Riwayat Transfer
                    </h1>
                    <p class="welcome-subtitle">Riwayat lengkap transfer antar akun Anda</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <a href="index.php" class="btn-primary-custom">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Akun
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="filter-card animated" style="animation-delay: 0.05s">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-calendar-alt me-1"></i> Dari Tanggal
                    </label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars(date('Y-m-d', strtotime($start_date))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-calendar-check me-1"></i> Sampai Tanggal
                    </label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars(date('Y-m-d', strtotime($end_date))) ?>">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-primary-custom">
                            <i class="fas fa-search me-2"></i> Filter
                        </button>
                        <a href="historytransfer.php" class="btn-secondary" style="text-decoration: none;">
                            <i class="fas fa-sync-alt me-2"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Stat Summary -->
        <?php if ($total_transfers > 0): 
            $total_transfer_amount = array_sum(array_column($transfers, 'amount'));
        ?>
        <div class="stat-summary animated" style="animation-delay: 0.1s">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-sm">
                            <i class="fas fa-exchange-alt fa-lg"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Transaksi Transfer</div>
                            <div class="h4 fw-bold mb-0"><?= number_format($total_transfers, 0, ',', '.') ?> Transfer</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-sm">
                            <i class="fas fa-money-bill-wave fa-lg"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Nominal Transfer</div>
                            <div class="h4 fw-bold mb-0"><?= formatRupiah($total_transfer_amount) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Table Transfer History -->
        <div class="table-container animated" style="animation-delay: 0.15s">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th style="width: 5%">No</th>
                            <th style="width: 15%">Tanggal</th>
                            <th style="width: 20%">Dari Akun</th>
                            <th style="width: 20%">Ke Akun</th>
                            <th style="width: 15%">Jumlah</th>
                            <th style="width: 25%">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_transfers > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($paginated_transfers as $transfer): ?>
                            <tr>
                                <td class="fw-semibold"><?= $no++ ?></td>
                                <td>
                                    <span class="fw-semibold"><?= formatDateTime($transfer['transfer_date']) ?></span>
                                    <br>
                                    <small class="text-muted"><?= timeAgo($transfer['transfer_date']) ?></small>
                                </td>
                                <td>
                                    <div class="transfer-badge">
                                        <i class="fas fa-arrow-right text-danger"></i>
                                        <span><?= htmlspecialchars($transfer['from_account_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="transfer-badge">
                                        <i class="fas fa-arrow-left text-success"></i>
                                        <span><?= htmlspecialchars($transfer['to_account_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="amount-negative">-<?= formatRupiah($transfer['amount']) ?></span>
                                </td>
                                <td>
                                    <?php if ($transfer['description']): ?>
                                        <span class="text-muted" title="<?= htmlspecialchars($transfer['description']) ?>">
                                            <i class="fas fa-pencil-alt me-1"></i>
                                            <?= strlen($transfer['description']) > 50 ? substr(htmlspecialchars($transfer['description']), 0, 50) . '...' : htmlspecialchars($transfer['description']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-exchange-alt"></i>
                                        <h5 class="mt-3">Belum Ada Riwayat Transfer</h5>
                                        <p class="text-muted">Anda belum melakukan transfer antar akun</p>
                                        <a href="index.php" class="btn-primary-custom mt-3">
                                            <i class="fas fa-plus me-2"></i> Transfer Sekarang
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_transfers > $limit): ?>
            <div class="pagination-custom">
                <nav>
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page-1 ?>&start_date=<?= urlencode(date('Y-m-d', strtotime($start_date))) ?>&end_date=<?= urlencode(date('Y-m-d', strtotime($end_date))) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&start_date=<?= urlencode(date('Y-m-d', strtotime($start_date))) ?>&end_date=<?= urlencode(date('Y-m-d', strtotime($end_date))) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?>&start_date=<?= urlencode(date('Y-m-d', strtotime($start_date))) ?>&end_date=<?= urlencode(date('Y-m-d', strtotime($end_date))) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Export Button -->
        <?php if ($total_transfers > 0): ?>
        <div class="mt-4 text-end animated" style="animation-delay: 0.2s">
            <button onclick="exportToExcel()" class="btn-primary-custom">
                <i class="fas fa-file-excel me-2"></i> Export ke Excel
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
<script>
    function exportToExcel() {
        let table = document.querySelector('.table-custom');
        let html = table.outerHTML;
        
        // Add styles to exported data
        let style = `
            <style>
                th { background: #4285f4; color: white; padding: 10px; }
                td { padding: 8px; border: 1px solid #ddd; }
            </style>
        `;
        
        let exportHtml = `
            <html>
                <head>
                    <title>Riwayat Transfer</title>
                    ${style}
                </head>
                <body>
                    <h2>Riwayat Transfer</h2>
                    <p>Periode: <?= htmlspecialchars(date('d/m/Y', strtotime($start_date))) ?> s/d <?= htmlspecialchars(date('d/m/Y', strtotime($end_date))) ?></p>
                    ${html}
                </body>
            </html>
        `;
        
        let blob = new Blob([exportHtml], { type: 'application/vnd.ms-excel' });
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'riwayat_transfer_<?= date('Y-m-d') ?>.xls';
        link.click();
        URL.revokeObjectURL(link.href);
        
        Swal.fire({
            title: 'Berhasil!',
            text: 'File Excel berhasil diexport',
            icon: 'success',
            confirmButtonColor: '#4285f4',
            timer: 2000,
            showConfirmButton: true
        });
    }
    
    // Success message handling
    <?php if (isset($_SESSION['success_message'])): ?>
    Swal.fire({
        title: 'Berhasil!',
        text: '<?= $_SESSION['success_message'] ?>',
        icon: 'success',
        confirmButtonColor: '#4285f4',
        confirmButtonText: 'OK',
        didOpen: () => {
            canvasConfetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    });
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    Swal.fire({
        title: 'Gagal!',
        text: '<?= $_SESSION['error_message'] ?>',
        icon: 'error',
        confirmButtonColor: '#4285f4',
        confirmButtonText: 'OK'
    });
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>