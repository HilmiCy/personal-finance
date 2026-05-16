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

// Get data (sama seperti di atas - copy dari export_pdf.php)
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

// Set header untuk Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_keuangan_' . $report_type . '_' . $current_year . '.xls"');
header('Cache-Control: max-age=0');

// Generate Excel (format HTML)
?>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    <style>
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-success { color: green; }
        .text-danger { color: red; }
        .badge-income { background: #d4edda; }
        .badge-expense { background: #f8d7da; }
    </style>
</head>
<body>
    <h2>LAPORAN KEUANGAN</h2>
    <p>Periode: <?= $report_type == 'monthly' ? bulanIndonesia($current_month) . ' ' . $current_year : 'Tahun ' . $current_year ?></p>
    <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
    
    <h3>RINGKASAN KEUANGAN</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr><th>Item</th><th>Nilai</th></tr>
        <tr><td>Total Pemasukan</td><td><?= formatRupiah($total_income) ?></td></tr>
        <tr><td>Total Pengeluaran</td><td><?= formatRupiah($total_expense) ?></td></tr>
        <tr><td>Saldo Bersih</td><td><?= formatRupiah($balance) ?></td></tr>
        <tr><td>Jumlah Transaksi</td><td><?= number_format($transaction_count, 0, ',', '.') ?></td></tr>
        <tr><td>Rata-rata Transaksi</td><td><?= formatRupiah($avg_transaction) ?></td></tr>
    </table>
    
    <h3>BREAKDOWN KATEGORI</h3>
    <h4>Pemasukan per Kategori</h4>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr><th>Kategori</th><th>Jumlah</th><th>Persentase</th></tr>
        <?php if (count($income_categories) > 0 && $total_income > 0): ?>
            <?php foreach ($income_categories as $cat): ?>
            <tr>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= formatRupiah($cat['total']) ?></td>
                <td><?= number_format(($cat['total'] / $total_income) * 100, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3">Tidak ada data</td></tr>
        <?php endif; ?>
    </table>
    
    <h4>Pengeluaran per Kategori</h4>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr><th>Kategori</th><th>Jumlah</th><th>Persentase</th></tr>
        <?php if (count($expense_categories) > 0 && $total_expense > 0): ?>
            <?php foreach ($expense_categories as $cat): ?>
            <tr>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= formatRupiah($cat['total']) ?></td>
                <td><?= number_format(($cat['total'] / $total_expense) * 100, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3">Tidak ada data</td></tr>
        <?php endif; ?>
    </table>
    
    <h3>TRANSAKSI TERBESAR</h3>
    <h4>Top 5 Pemasukan Terbesar</h4>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr><th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th>Jumlah</th></tr>
        <?php if (count($top_income_trans) > 0): ?>
            <?php foreach ($top_income_trans as $t): ?>
            <tr>
                <td><?= formatDate($t['transaction_date']) ?></td>
                <td><?= htmlspecialchars($t['description']) ?></td>
                <td><?= htmlspecialchars($t['category_name']) ?></td>
                <td><?= formatRupiah($t['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">Tidak ada data</td></tr>
        <?php endif; ?>
    </table>
    
    <h4>Top 5 Pengeluaran Terbesar</h4>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr><th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th>Jumlah</th></tr>
        <?php if (count($top_expense_trans) > 0): ?>
            <?php foreach ($top_expense_trans as $t): ?>
            <tr>
                <td><?= formatDate($t['transaction_date']) ?></td>
                <td><?= htmlspecialchars($t['description']) ?></td>
                <td><?= htmlspecialchars($t['category_name']) ?></td>
                <td><?= formatRupiah($t['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">Tidak ada data</td></tr>
        <?php endif; ?>
    </table>
    
    <?php if ($report_type == 'monthly' && count($account_breakdown) > 0): ?>
    <h3>BREAKDOWN PER AKUN</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr><th>Akun</th><th>Pemasukan</th><th>Pengeluaran</th><th>Saldo</th></tr>
        <?php foreach ($account_breakdown as $acc): ?>
        <tr>
            <td><?= htmlspecialchars($acc['name']) ?></td>
            <td><?= formatRupiah($acc['income']) ?></td>
            <td><?= formatRupiah($acc['expense']) ?></td>
            <td><?= formatRupiah($acc['income'] - $acc['expense']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    
    <h3>DAFTAR SELURUH TRANSAKSI</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Tanggal</th>
            <th>Deskripsi</th>
            <th>Kategori</th>
            <th>Akun</th>
            <th>Tipe</th>
            <th>Jumlah</th>
        </tr>
        <?php if (count($transactions) > 0): ?>
            <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= formatDate($t['transaction_date']) ?></td>
                <td><?= htmlspecialchars($t['description']) ?></td>
                <td><?= htmlspecialchars($t['category_name']) ?></td>
                <td><?= htmlspecialchars($t['account_name']) ?></td>
                <td><?= $t['type'] == 'income' ? 'Pemasukan' : 'Pengeluaran' ?></td>
                <td><?= formatRupiah($t['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">Tidak ada transaksi</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
<?php
exit;
?>