<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'classes/Database.php';
require_once 'classes/Transaction.php';

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Dashboard';
$current_page = 'dashboard.php';
$db = Database::getInstance()->getConnection();

// Debug: Cek koneksi database
try {
    $db->query("SELECT 1");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Get summary data
$total_balance = getTotalBalance($db, $_SESSION['user_id']);
$monthly_income = getMonthlyIncome($db, $_SESSION['user_id']);
$monthly_expense = getMonthlyExpense($db, $_SESSION['user_id']);
$sisa = $monthly_income - $monthly_expense;

// Get recent transactions
$transaction = new Transaction();

// Gunakan query manual untuk memastikan data terambil
try {
    $manualStmt = $db->prepare("
        SELECT 
            t.*, 
            COALESCE(a.name, 'Tidak Ada Akun') as account_name, 
            COALESCE(c.name, 'Tidak Ada Kategori') as category_name 
        FROM transactions t
        LEFT JOIN accounts a ON t.account_id = a.id
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.transaction_date DESC, t.created_at DESC
        LIMIT 10
    ");
    $manualStmt->execute([$_SESSION['user_id']]);
    $recent_transactions = $manualStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Jika masih kosong, coba tanpa JOIN
    if (empty($recent_transactions)) {
        $simpleStmt = $db->prepare("
            SELECT * FROM transactions 
            WHERE user_id = ? 
            ORDER BY transaction_date DESC 
            LIMIT 10
        ");
        $simpleStmt->execute([$_SESSION['user_id']]);
        $simpleTransactions = $simpleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($simpleTransactions)) {
            $recent_transactions = $simpleTransactions;
            foreach ($recent_transactions as &$trans) {
                $trans['account_name'] = 'Akun tidak ditemukan';
                $trans['category_name'] = 'Kategori tidak ditemukan';
            }
        }
    }
} catch (PDOException $e) {
    $recent_transactions = [];
}

// Get chart data for the last 6 months
$chart_labels = [];
$income_data = [];
$expense_data = [];

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $chart_labels[] = date('M Y', strtotime($month . '-01'));
    
    try {
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
            FROM transactions 
            WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $month]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $income_data[] = $data['income'] ?? 0;
        $expense_data[] = $data['expense'] ?? 0;
    } catch (PDOException $e) {
        $income_data[] = 0;
        $expense_data[] = 0;
    }
}

// Get category breakdown for current month
$category_data = [];
try {
    $stmt = $db->prepare("
        SELECT c.name, SUM(t.amount) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
            AND t.type = 'expense'
            AND MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
            AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())
        GROUP BY c.id, c.name
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $category_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $category_data = [];
}

// Get AI Prediction
$prediction = $transaction->predictNextMonthExpense($_SESSION['user_id']);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
    /* ========== DASHBOARD CUSTOM STYLES ========== */
    
    .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        padding: 24px !important;
        margin: 0 !important;
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    /* Prediction Card */
    .prediction-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
        padding: 24px;
        color: white;
        position: relative;
        overflow: hidden;
        height: 100%;
        border: none;
    }

    .prediction-card::after {
        content: '\f0d0';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: -20px;
        bottom: -20px;
        font-size: 120px;
        opacity: 0.05;
        transform: rotate(-15deg);
    }

    .prediction-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.7;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .prediction-value {
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 15px;
    }

    .prediction-status {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.1);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 15px !important;
        }

        /* Mobile Table Transformation */
        .table-custom thead {
            display: none;
        }
        
        .table-custom tbody td {
            display: block;
            padding: 12px 16px;
            border-bottom: none;
            position: relative;
            padding-left: 40%;
            text-align: right;
        }
        
        .table-custom tbody td:before {
            content: attr(data-label);
            position: absolute;
            left: 16px;
            width: calc(40% - 20px);
            font-weight: 600;
            color: var(--text-muted);
            font-size: 12px;
            text-align: left;
        }
        
        .table-custom tbody tr {
            border-bottom: 1px solid var(--border-color);
            display: block;
            margin-bottom: 10px;
            background: var(--bg-card);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }
        
        .table-custom tbody tr:last-child {
            margin-bottom: 0;
        }

        .transaction-type {
            justify-content: flex-end;
        }
    }
</style>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Welcome Section -->
        <div class="welcome-card animated" style="animation-delay: 0s">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <h1 class="welcome-title">
                        Selamat datang, <?= htmlspecialchars($_SESSION['user_name']) ?>! 
                    </h1>
                    <p class="welcome-subtitle">
                        Senang bertemu dengan Anda lagi. Berikut adalah ringkasan keuangan Anda hari ini.
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card animated" style="animation-delay: 0.1s">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                        <i class="fas fa-wallet" style="color: #667eea;"></i>
                    </div>
                    <div class="stat-title">Total Saldo</div>
                    <div class="stat-value"><?= formatRupiah($total_balance) ?></div>
                    <div class="stat-change">
                        <i class="fas fa-chart-line"></i> Saldo terkini
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card animated" style="animation-delay: 0.2s">
                    <div class="stat-icon" style="background: #d1fae5;">
                        <i class="fas fa-arrow-up" style="color: #10b981;"></i>
                    </div>
                    <div class="stat-title">Pemasukan Bulan Ini</div>
                    <div class="stat-value"><?= formatRupiah($monthly_income) ?></div>
                    <div class="stat-change">
                        <i class="fas fa-calendar"></i> <?= date('F Y') ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card animated" style="animation-delay: 0.3s">
                    <div class="stat-icon" style="background: #fee2e2;">
                        <i class="fas fa-arrow-down" style="color: #ef4444;"></i>
                    </div>
                    <div class="stat-title">Pengeluaran Bulan Ini</div>
                    <div class="stat-value"><?= formatRupiah($monthly_expense) ?></div>
                    <div class="stat-change">
                        <i class="fas fa-calendar"></i> <?= date('F Y') ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card animated" style="animation-delay: 0.4s">
                    <div class="stat-icon" style="background: #fef3c7;">
                        <i class="fas fa-chart-pie" style="color: #f59e0b;"></i>
                    </div>
                    <div class="stat-title">Sisa Bulan Ini</div>
                    <div class="stat-value <?= $sisa >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatRupiah($sisa) ?>
                    </div>
                    <div class="stat-change">
                        <?php if($sisa >= 0): ?>
                            <i class="fas fa-check-circle"></i> Hemat positif
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle"></i> Defisit
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Insight Row -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="prediction-card animated" style="animation-delay: 0.45s">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="prediction-label">
                                <i class="fas fa-robot"></i> Smart AI Prediction
                            </div>
                            <h3 class="prediction-value">
                                Estimasi Pengeluaran Bulan Depan: 
                                <span class="text-info"><?= formatRupiah($prediction['amount']) ?></span>
                            </h3>
                            <div class="prediction-status">
                                <?php if($prediction['trend'] == 'up'): ?>
                                    <i class="fas fa-arrow-trend-up text-danger"></i> Tren pengeluaran meningkat. Pertimbangkan untuk berhemat!
                                <?php elseif($prediction['trend'] == 'down'): ?>
                                    <i class="fas fa-arrow-trend-down text-success"></i> Tren pengeluaran menurun. Kerja bagus!
                                <?php else: ?>
                                    <i class="fas fa-minus text-info"></i> Tren pengeluaran stabil.
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="small opacity-75">
                                Berdasarkan analisis data <?= $prediction['count'] ?> bulan terakhir
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="chart-card animated" style="animation-delay: 0.5s">
                    <div class="card-title-custom">
                        <i class="fas fa-chart-line"></i>
                        Tren Keuangan 6 Bulan Terakhir
                    </div>
                    <canvas id="trendChart" height="280"></canvas>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="chart-card animated" style="animation-delay: 0.6s">
                    <div class="card-title-custom">
                        <i class="fas fa-chart-pie"></i>
                        Kategori Pengeluaran Teratas
                    </div>
                    <?php if(!empty($category_data) && count($category_data) > 0): ?>
                        <canvas id="categoryChart" height="230"></canvas>
                        <div class="mt-3">
                            <?php foreach($category_data as $cat): ?>
                            <div class="category-item">
                                <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                <span class="category-amount"><?= formatRupiah($cat['total']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <p>Belum ada data pengeluaran bulan ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="animated" style="animation-delay: 0.7s">
            <div class="transactions-card">
                <div class="card-header-custom">
                    <div class="card-title-custom" style="margin-bottom: 0; border-left: none; padding-left: 0;">
                        <i class="fas fa-history"></i>
                        Transaksi Terbaru
                    </div>
                    <a href="pages/transactions/index.php" class="btn btn-sm" style="background: #f3f4f6; color: #4b5563; text-decoration: none; padding: 6px 14px; border-radius: 10px;">
                        Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Kategori</th>
                                <th>Akun</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($recent_transactions) && count($recent_transactions) > 0): ?>
                                <?php foreach($recent_transactions as $trans): ?>
                                <tr>
                                    <td data-label="Tanggal">
                                        <?php 
                                        if(isset($trans['transaction_date']) && !empty($trans['transaction_date'])):
                                            echo date('d/m/Y', strtotime($trans['transaction_date']));
                                        else:
                                            echo '-';
                                        endif;
                                        ?>
                                    </td>
                                    <td data-label="Deskripsi">
                                        <strong><?= htmlspecialchars($trans['description'] ?? '-') ?></strong>
                                    </td>
                                    <td data-label="Kategori">
                                        <span class="badge" style="background: #f3f4f6; color: #4b5563;">
                                            <?= htmlspecialchars($trans['category_name'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td data-label="Akun"><?= htmlspecialchars($trans['account_name'] ?? '-') ?></td>
                                    <td data-label="Jumlah">
                                        <span class="transaction-type <?= isset($trans['type']) && $trans['type'] == 'income' ? 'income-badge' : 'expense-badge' ?>">
                                            <i class="fas fa-<?= isset($trans['type']) && $trans['type'] == 'income' ? 'plus' : 'minus' ?>"></i>
                                            <?php 
                                            $amount = isset($trans['amount']) ? $trans['amount'] : 0;
                                            echo 'Rp ' . number_format($amount, 0, ',', '.');
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-receipt"></i>
                                            <p>Belum ada transaksi. Mulai tambahkan transaksi pertama Anda!</p>
                                            <a href="pages/transactions/add.php" class="btn-primary-custom mt-2" style="display: inline-block;">
                                                Tambah Transaksi
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Trend Chart
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: <?= json_encode($income_data) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                },
                {
                    label: 'Pengeluaran',
                    data: <?= json_encode($expense_data) ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            family: 'Plus Jakarta Sans',
                            size: 12,
                            weight: '600'
                        },
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.raw;
                            return label + ': Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        font: {
                            family: 'Plus Jakarta Sans',
                            size: 11
                        }
                    },
                    grid: {
                        color: '#e5e7eb'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            family: 'Plus Jakarta Sans',
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Category Chart (if data exists)
    <?php if(!empty($category_data) && count($category_data) > 0): ?>
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($category_data, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($category_data, 'total')) ?>,
                backgroundColor: [
                    '#667eea',
                    '#f59e0b',
                    '#10b981',
                    '#ef4444',
                    '#8b5cf6'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            family: 'Plus Jakarta Sans',
                            size: 10
                        },
                        boxWidth: 10,
                        padding: 8,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>