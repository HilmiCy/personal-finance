<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';
require_once '../../classes/Category.php';
require_once '../../classes/Account.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// Get parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validasi range
if ($current_month < 1 || $current_month > 12) $current_month = date('n');
if ($current_year < 2000 || $current_year > 2100) $current_year = date('Y');

$db = Database::getInstance()->getConnection();
$transaction = new Transaction();

// Get data based on report type
if ($report_type == 'monthly') {
    $start_date = "$current_year-$current_month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $transactions = $transaction->getByDateRange($_SESSION['user_id'], $start_date, $end_date);
    
    $income_categories = $transaction->getSummaryByCategory($_SESSION['user_id'], $current_month, $current_year, 'income');
    $expense_categories = $transaction->getSummaryByCategory($_SESSION['user_id'], $current_month, $current_year, 'expense');
    
    $stmt = $db->prepare("
        SELECT a.name, 
            SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as income,
            SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as expense
        FROM transactions t
        JOIN accounts a ON t.account_id = a.id
        WHERE t.user_id = ? 
        AND MONTH(t.transaction_date) = ? 
        AND YEAR(t.transaction_date) = ?
        GROUP BY a.id, a.name
    ");
    $stmt->execute([$_SESSION['user_id'], $current_month, $current_year]);
    $account_breakdown = $stmt->fetchAll();
    
    $top_income_trans = $transaction->getTopTransactions($_SESSION['user_id'], $start_date, $end_date, 'income', 5);
    $top_expense_trans = $transaction->getTopTransactions($_SESSION['user_id'], $start_date, $end_date, 'expense', 5);
    
} else {
    $start_date = "$current_year-01-01";
    $end_date = "$current_year-12-31";
    
    $transactions = $transaction->getByDateRange($_SESSION['user_id'], $start_date, $end_date);
    
    $stmt = $db->prepare("
        SELECT c.name, c.type, SUM(t.amount) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND YEAR(t.transaction_date) = ?
        GROUP BY c.id, c.name, c.type
        ORDER BY total DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $current_year]);
    $yearly_categories = $stmt->fetchAll();
    
    $income_categories = array_filter($yearly_categories, function($cat) {
        return $cat['type'] == 'income';
    });
    $expense_categories = array_filter($yearly_categories, function($cat) {
        return $cat['type'] == 'expense';
    });
    
    $top_income_trans = $transaction->getTopTransactions($_SESSION['user_id'], $start_date, $end_date, 'income', 5);
    $top_expense_trans = $transaction->getTopTransactions($_SESSION['user_id'], $start_date, $end_date, 'expense', 5);
    
    $account_breakdown = [];
}

// Calculate totals
$total_income = 0;
$total_expense = 0;
foreach ($transactions as $t) {
    if ($t['type'] == 'income') {
        $total_income += $t['amount'];
    } else {
        $total_expense += $t['amount'];
    }
}
$balance = $total_income - $total_expense;
$transaction_count = count($transactions);
$avg_transaction = $transaction_count > 0 ? ($total_income + $total_expense) / $transaction_count : 0;
$avg_income = $transaction_count > 0 ? $total_income / $transaction_count : 0;
$avg_expense = $transaction_count > 0 ? $total_expense / $transaction_count : 0;

// Set header untuk menampilkan halaman (bukan download langsung)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - Print Preview</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
            .no-print {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
            .header, .footer {
                position: fixed;
            }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            background: #f5f5f5;
        }
        
        .print-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #667eea;
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin: 25px 0 15px 0;
            border-left: 4px solid #667eea;
            padding-left: 12px;
        }
        
        .subsection-title {
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .badge-income {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            display: inline-block;
        }
        
        .badge-expense {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            display: inline-block;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #999;
        }
        
        .print-button {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .btn-print {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
        }
        
        .btn-print:hover {
            background: #5a67d8;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .summary-grid {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px 20px -10px;
        }
        
        .summary-card {
            flex: 1;
            margin: 0 10px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .summary-label {
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="print-button no-print">
            <button class="btn-print" onclick="printPDF()">
                <i class="fas fa-print"></i> Cetak / Save as PDF
            </button>
            <button class="btn-cancel" onclick="window.close()">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
        
        <div class="header">
            <h1>LAPORAN KEUANGAN</h1>
            <p>Periode: <?= $report_type == 'monthly' ? bulanIndonesia($current_month) . ' ' . $current_year : 'Tahun ' . $current_year ?></p>
            <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Total Pemasukan</div>
                <div class="summary-value text-success"><?= formatRupiah($total_income) ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Pengeluaran</div>
                <div class="summary-value text-danger"><?= formatRupiah($total_expense) ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Saldo Bersih</div>
                <div class="summary-value <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= formatRupiah($balance) ?>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Jumlah Transaksi</div>
                <div class="summary-value"><?= number_format($transaction_count, 0, ',', '.') ?></div>
            </div>
        </div>
        
        <!-- Detailed Summary Table -->
        <div class="section-title">RINGKASAN DETAIL</div>
        <table>
            <tr>
                <th width="50%">Item</th>
                <th width="50%">Nilai</th>
            </tr>
            <tr>
                <td>Total Pemasukan</td>
                <td class="text-right text-success"><?= formatRupiah($total_income) ?></td>
            </tr>
            <tr>
                <td>Total Pengeluaran</td>
                <td class="text-right text-danger"><?= formatRupiah($total_expense) ?></td>
            </tr>
            <tr>
                <td>Saldo Bersih</td>
                <td class="text-right <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>"><?= formatRupiah($balance) ?></td>
            </tr>
            <tr>
                <td>Jumlah Transaksi</td>
                <td class="text-right"><?= number_format($transaction_count, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Rata-rata Transaksi</td>
                <td class="text-right"><?= formatRupiah($avg_transaction) ?></td>
            </tr>
            <tr>
                <td>Rata-rata Pemasukan</td>
                <td class="text-right text-success"><?= formatRupiah($avg_income) ?></td>
            </tr>
            <tr>
                <td>Rata-rata Pengeluaran</td>
                <td class="text-right text-danger"><?= formatRupiah($avg_expense) ?></td>
            </tr>
        </table>
        
        <!-- Category Breakdown -->
        <div class="section-title">BREAKDOWN KATEGORI</div>
        
        <div class="subsection-title">Pemasukan per Kategori (Top 5)</div>
        <?php if (count($income_categories) > 0 && $total_income > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $top_income = array_slice($income_categories, 0, 5);
                foreach ($top_income as $cat): 
                    $percentage = ($cat['total'] / $total_income) * 100;
                ?>
                <tr>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td class="text-right"><?= formatRupiah($cat['total']) ?></td>
                    <td class="text-right"><?= number_format($percentage, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><em>Tidak ada data pemasukan</em></p>
        <?php endif; ?>
        
        <div class="subsection-title">Pengeluaran per Kategori (Top 5)</div>
        <?php if (count($expense_categories) > 0 && $total_expense > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $top_expense = array_slice($expense_categories, 0, 5);
                foreach ($top_expense as $cat): 
                    $percentage = ($cat['total'] / $total_expense) * 100;
                ?>
                <tr>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td class="text-right"><?= formatRupiah($cat['total']) ?></td>
                    <td class="text-right"><?= number_format($percentage, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><em>Tidak ada data pengeluaran</em></p>
        <?php endif; ?>
        
        <!-- Top Transactions -->
        <div class="section-title">TRANSAKSI TERBESAR</div>
        
        <div class="subsection-title">Top 5 Pemasukan Terbesar</div>
        <?php if (count($top_income_trans) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Kategori</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_income_trans as $t): ?>
                <tr>
                    <td><?= formatDate($t['transaction_date']) ?></td>
                    <td><?= htmlspecialchars($t['description']) ?></td>
                    <td><?= htmlspecialchars($t['category_name']) ?></td>
                    <td class="text-right text-success"><?= formatRupiah($t['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><em>Tidak ada data pemasukan</em></p>
        <?php endif; ?>
        
        <div class="subsection-title">Top 5 Pengeluaran Terbesar</div>
        <?php if (count($top_expense_trans) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Kategori</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_expense_trans as $t): ?>
                <tr>
                    <td><?= formatDate($t['transaction_date']) ?></td>
                    <td><?= htmlspecialchars($t['description']) ?></td>
                    <td><?= htmlspecialchars($t['category_name']) ?></td>
                    <td class="text-right text-danger"><?= formatRupiah($t['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><em>Tidak ada data pengeluaran</em></p>
        <?php endif; ?>
        
        <!-- Account Breakdown -->
        <?php if ($report_type == 'monthly' && count($account_breakdown) > 0): ?>
        <div class="section-title">BREAKDOWN PER AKUN</div>
        <table>
            <thead>
                <tr>
                    <th>Akun</th>
                    <th class="text-right">Pemasukan</th>
                    <th class="text-right">Pengeluaran</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($account_breakdown as $acc): 
                    $acc_balance = $acc['income'] - $acc['expense'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($acc['name']) ?></td>
                    <td class="text-right text-success"><?= formatRupiah($acc['income']) ?></td>
                    <td class="text-right text-danger"><?= formatRupiah($acc['expense']) ?></td>
                    <td class="text-right <?= $acc_balance >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatRupiah($acc_balance) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- All Transactions -->
        <div class="section-title">DAFTAR SELURUH TRANSAKSI</div>
        <?php if (count($transactions) > 0): ?>
        <table style="font-size: 10px;">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Kategori</th>
                    <th>Akun</th>
                    <th>Tipe</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): 
                    $type_text = $t['type'] == 'income' ? 'Pemasukan' : 'Pengeluaran';
                    $type_class = $t['type'] == 'income' ? 'badge-income' : 'badge-expense';
                ?>
                <tr>
                    <td><?= formatDate($t['transaction_date']) ?></td>
                    <td><?= htmlspecialchars($t['description']) ?></td>
                    <td><?= htmlspecialchars($t['category_name']) ?></td>
                    <td><?= htmlspecialchars($t['account_name']) ?></td>
                    <td class="text-center">
                        <span class="<?= $type_class ?>"><?= $type_text ?></span>
                    </td>
                    <td class="text-right <?= $t['type'] == 'income' ? 'text-success' : 'text-danger' ?>">
                        <?= formatRupiah($t['amount']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><em>Tidak ada transaksi</em></p>
        <?php endif; ?>
        
        <div class="footer">
            Laporan Keuangan - <?= $report_type == 'monthly' ? bulanIndonesia($current_month) . ' ' . $current_year : 'Tahun ' . $current_year ?>
        </div>
    </div>
    
    <script>
        function printPDF() {
            window.print();
        }
        
        // Auto trigger print dialog (optional)
        setTimeout(function() {
            // Uncomment if you want auto print
            // window.print();
        }, 500);
    </script>
</body>
</html>
<?php
exit;
?>