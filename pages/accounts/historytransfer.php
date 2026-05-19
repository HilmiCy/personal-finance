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
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }
    
    /* ========== WELCOME CARD ========== */
    .welcome-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFFFFF 100%);
        border-radius: 20px;
        padding: 20px 24px;
        margin-bottom: 24px;
        color: #1f2937;
        position: relative;
        overflow: hidden;
        width: 100%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .welcome-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
        color: #1f2937;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.7;
        font-size: 0.9rem;
        color: #6b7280;
    }
    
    /* Button Styles */
    .btn {
        border-radius: 12px !important;
        padding: 10px 20px !important;
        font-weight: 500 !important;
        transition: all 0.2s !important;
    }
    
    .btn-primary-custom {
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
        text-decoration: none;
        cursor: pointer;
        border: none;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-secondary {
        background: rgba(107, 114, 128, 0.1);
        border: 1px solid rgba(107, 114, 128, 0.2);
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        color: #6b7280;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-secondary:hover {
        background: rgba(107, 114, 128, 0.2);
        color: #4b5563;
    }
    
    /* Filter Card */
    .filter-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .form-control, .form-select {
        border-radius: 12px !important;
        border: 1px solid #e0e0e0 !important;
        padding: 12px 16px !important;
        transition: all 0.2s !important;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
    }
    
    .form-label {
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 8px;
    }
    
    /* Stat Summary */
    .stat-summary {
        background: linear-gradient(135deg, #10b98120, #05966920);
        border-radius: 20px;
        padding: 20px 24px;
        margin-bottom: 20px;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .stat-icon-sm {
        width: 50px;
        height: 50px;
        background: rgba(255,255,255,0.9);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon-sm i {
        font-size: 24px;
        color: #10b981;
    }
    
    /* Table Container */
    .table-container {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .table-custom {
        margin-bottom: 0;
    }
    
    .table-custom thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        padding: 16px;
        font-weight: 600;
        color: #495057;
        font-size: 14px;
    }
    
    .table-custom tbody td {
        padding: 16px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .table-custom tbody tr:hover {
        background: #f8f9fa;
    }
    
    .transfer-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f3f4f6;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
    }
    
    .amount-negative {
        color: #dc3545;
        font-weight: 700;
        font-size: 15px;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .empty-state i {
        font-size: 64px;
        color: #667eea;
        margin-bottom: 20px;
    }
    
    .empty-state p {
        color: #6b7280;
        font-size: 16px;
        margin-bottom: 20px;
    }
    
    /* Pagination */
    .pagination-custom {
        padding: 20px;
        display: flex;
        justify-content: center;
        gap: 8px;
        border-top: 1px solid #e5e7eb;
    }
    
    .pagination-custom .page-link {
        border-radius: 10px;
        padding: 8px 15px;
        color: #667eea;
        border: 1px solid #e0e0e0;
        background: white;
    }
    
    .pagination-custom .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        color: white;
    }
    
    .pagination-custom .page-link:hover {
        background: #f3f4f6;
        color: #667eea;
    }
    
    /* Alert Info */
    .alert-info-custom {
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        border-radius: 12px;
        padding: 12px 16px;
        color: #0369a1;
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
    
    .animated {
        animation: fadeInUp 0.5s ease-out forwards;
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
        
        .table-custom thead th,
        .table-custom tbody td {
            padding: 10px;
            font-size: 12px;
        }
        
        .transfer-badge {
            font-size: 11px;
            padding: 4px 8px;
        }
        
        .stat-summary {
            padding: 15px;
        }
        
        .stat-icon-sm {
            width: 40px;
            height: 40px;
        }
        
        .stat-icon-sm i {
            font-size: 18px;
        }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">
                        <i class="fas fa-history me-3" style="color: #667eea;"></i> Riwayat Transfer
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
                th { background: #667eea; color: white; padding: 10px; }
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
            confirmButtonColor: '#667eea',
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
        confirmButtonColor: '#667eea',
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
        confirmButtonColor: '#667eea',
        confirmButtonText: 'OK'
    });
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>