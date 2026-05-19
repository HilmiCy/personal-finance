<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';
require_once '../../classes/Category.php';
require_once '../../classes/Account.php';
require_once '../../classes/EmergencyFund.php';
require_once '../../classes/Installment.php';
require_once '../../classes/Budget.php';
require_once '../../classes/Asset.php';

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
$account = new Account();
$emergencyFund = new EmergencyFund();
$installment = new Installment();
$budget = new Budget();
$asset = new Asset();

// Fetch Data (Same logic as index.php)
if ($report_type == 'monthly') {
    $start_date = "$current_year-$current_month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $transactions = $transaction->getByDateRange($_SESSION['user_id'], $start_date, $end_date);
    
    $emergency_transactions = $emergencyFund->getHistory($_SESSION['user_id'], 1000, 0);
    $emergency_transactions = array_filter($emergency_transactions, function($et) use ($current_month, $current_year) {
        $date = date('Y-m', strtotime($et['transaction_date']));
        return $date == "$current_year-" . str_pad($current_month, 2, '0', STR_PAD_LEFT);
    });
    
    $stmt = $db->prepare("
        SELECT ip.*, i.name as installment_name, i.remaining_amount, i.total_amount
        FROM installment_payments ip
        JOIN installments i ON ip.installment_id = i.id
        WHERE ip.user_id = ? 
        ORDER BY ip.payment_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $all_installment_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $installment_payments = array_filter($all_installment_payments, function($ip) use ($current_month, $current_year) {
        $date = date('Y-m', strtotime($ip['payment_date']));
        return $date == "$current_year-" . str_pad($current_month, 2, '0', STR_PAD_LEFT);
    });

    $monthly_budgets = $budget->getByMonth($_SESSION['user_id'], $current_month, $current_year);
    
    $income_categories = $transaction->getSummaryByCategory($_SESSION['user_id'], $current_month, $current_year, 'income');
    $expense_categories = $transaction->getSummaryByCategory($_SESSION['user_id'], $current_month, $current_year, 'expense');
    
    $stmt = $db->prepare("
        SELECT a.name, 
            SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as income,
            SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as expense,
            a.balance
        FROM transactions t
        JOIN accounts a ON t.account_id = a.id
        WHERE t.user_id = ? 
        AND MONTH(t.transaction_date) = ? 
        AND YEAR(t.transaction_date) = ?
        GROUP BY a.id, a.name, a.balance
    ");
    $stmt->execute([$_SESSION['user_id'], $current_month, $current_year]);
    $account_breakdown = $stmt->fetchAll();
    
    $transfers = $account->getTransferHistoryByDate($_SESSION['user_id'], $start_date, $end_date . ' 23:59:59');
    
} else {
    $start_date = "$current_year-01-01";
    $end_date = "$current_year-12-31";
    
    $transactions = $transaction->getByDateRange($_SESSION['user_id'], $start_date, $end_date);
    
    $emergency_transactions = $emergencyFund->getHistory($_SESSION['user_id'], 1000, 0);
    $emergency_transactions = array_filter($emergency_transactions, function($et) use ($current_year) {
        return date('Y', strtotime($et['transaction_date'])) == $current_year;
    });
    
    $stmt = $db->prepare("
        SELECT ip.*, i.name as installment_name, i.remaining_amount, i.total_amount
        FROM installment_payments ip
        JOIN installments i ON ip.installment_id = i.id
        WHERE ip.user_id = ? 
        ORDER BY ip.payment_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $all_installment_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $installment_payments = array_filter($all_installment_payments, function($ip) use ($current_year) {
        return date('Y', strtotime($ip['payment_date'])) == $current_year;
    });

    $monthly_budgets = [];
    
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
    
    $income_categories = array_filter($yearly_categories, function($cat) { return $cat['type'] == 'income'; });
    $expense_categories = array_filter($yearly_categories, function($cat) { return $cat['type'] == 'expense'; });
    
    $account_breakdown = [];
    $transfers = $account->getTransferHistoryByDate($_SESSION['user_id'], $start_date, $end_date . ' 23:59:59');
}

$portfolio = $asset->getPortfolioSummary($_SESSION['user_id']);
$total_account_balance = $account->getTotalBalance($_SESSION['user_id']);
$installment_summary = $installment->getSummary($_SESSION['user_id']);
$current_emergency = $emergencyFund->getEmergencyFund($_SESSION['user_id']);
$emergency_current = $current_emergency ? $current_emergency['current_amount'] : 0;

$net_worth = $total_account_balance + $portfolio['total_value'] - ($installment_summary['total_remaining'] ?? 0);

// Calculate totals
$total_income = 0;
$total_expense = 0;
foreach ($transactions as $t) {
    if ($t['type'] == 'income') $total_income += $t['amount'];
    elseif ($t['type'] == 'expense') $total_expense += $t['amount'];
}

$total_emergency_deposit = 0;
$total_emergency_withdraw = 0;
foreach ($emergency_transactions as $et) {
    if ($et['type'] == 'deposit') $total_emergency_deposit += $et['amount'];
    else $total_emergency_withdraw += $et['amount'];
}

$total_installment_paid = 0;
foreach ($installment_payments as $ip) {
    $total_installment_paid += $ip['total_paid'];
}

$total_income_overall = $total_income + $total_emergency_deposit;
$total_expense_overall = $total_expense + $total_emergency_withdraw + $total_installment_paid;
$balance_overall = $total_income_overall - $total_expense_overall;

// Health Ratios
$savings_rate = $total_income_overall > 0 ? (($total_income_overall - $total_expense_overall) / $total_income_overall) * 100 : 0;
$debt_service_ratio = $total_income_overall > 0 ? ($total_installment_paid / $total_income_overall) * 100 : 0;
$emergency_ratio = $total_expense_overall > 0 ? $emergency_current / ($total_expense_overall / ($report_type == 'monthly' ? 1 : 12)) : 0;

// Set header for Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_keuangan_lengkap_' . $report_type . '_' . $current_year . '.xls"');
header('Cache-Control: max-age=0');

?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        th { background-color: #f2f2f2; border: 1px solid #000; }
        td { border: 1px solid #000; }
        .text-right { text-align: right; }
        .text-success { color: green; }
        .text-danger { color: red; }
        .header-title { font-size: 18px; font-weight: bold; text-align: center; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 20px; background-color: #1a2a6c; color: white; text-align: center; }
        .sub-section { background-color: #e0e0e0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header-title">LAPORAN KEUANGAN SUPER LENGKAP</div>
    <p>Periode: <?= $report_type == 'monthly' ? bulanIndonesia($current_month) . ' ' . $current_year : 'Tahun ' . $current_year ?></p>
    <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
    
    <table border="1" cellpadding="5" cellspacing="0">
        <tr class="section-title"><th colspan="2">KEKAYAAN BERSIH (NET WORTH)</th></tr>
        <tr><td>Total Saldo Akun (Cash)</td><td class="text-right"><?= formatRupiah($total_account_balance) ?></td></tr>
        <tr><td>Total Nilai Aset (Investasi)</td><td class="text-right"><?= formatRupiah($portfolio['total_value']) ?></td></tr>
        <tr><td>Total Sisa Cicilan (Liabilitas)</td><td class="text-right text-danger">-<?= formatRupiah($installment_summary['total_remaining'] ?? 0) ?></td></tr>
        <tr class="sub-section"><td><strong>TOTAL KEKAYAAN BERSIH</strong></td><td class="text-right"><strong><?= formatRupiah($net_worth) ?></strong></td></tr>
    </table>

    <br>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr class="section-title"><th colspan="2">RASIO KESEHATAN KEUANGAN</th></tr>
        <tr><td>Savings Rate (Target > 20%)</td><td class="text-right"><?= number_format($savings_rate, 1) ?>%</td></tr>
        <tr><td>Debt Service Ratio (Max 35%)</td><td class="text-right"><?= number_format($debt_service_ratio, 1) ?>%</td></tr>
        <tr><td>Emergency Fund Coverage</td><td class="text-right"><?= number_format($emergency_ratio, 1) ?> Bulan Pengeluaran</td></tr>
    </table>

    <br>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr class="section-title"><th colspan="2">RINGKASAN ARUS KAS PERIODE INI</th></tr>
        <tr><td>Total Pemasukan Reguler</td><td class="text-right"><?= formatRupiah($total_income) ?></td></tr>
        <tr><td>Total Deposit Dana Darurat</td><td class="text-right"><?= formatRupiah($total_emergency_deposit) ?></td></tr>
        <tr class="sub-section"><td><strong>TOTAL PEMASUKAN</strong></td><td class="text-right"><strong><?= formatRupiah($total_income_overall) ?></strong></td></tr>
        
        <tr><td>Total Pengeluaran Reguler</td><td class="text-right"><?= formatRupiah($total_expense) ?></td></tr>
        <tr><td>Total Penarikan Dana Darurat</td><td class="text-right"><?= formatRupiah($total_emergency_withdraw) ?></td></tr>
        <tr><td>Total Pembayaran Cicilan</td><td class="text-right"><?= formatRupiah($total_installment_paid) ?></td></tr>
        <tr class="sub-section"><td><strong>TOTAL PENGELUARAN</strong></td><td class="text-right"><strong><?= formatRupiah($total_expense_overall) ?></strong></td></tr>
        
        <tr class="sub-section"><td><strong>SALDO BERSIH PERIODE INI</strong></td><td class="text-right"><strong><?= formatRupiah($balance_overall) ?></strong></td></tr>
    </table>

    <br>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr class="section-title"><th colspan="4">RINGKASAN PORTOFOLIO ASET</th></tr>
        <tr class="sub-section">
            <th>Total Aset</th>
            <th>Total Investasi</th>
            <th>Nilai Saat Ini</th>
            <th>Profit/Loss</th>
        </tr>
        <tr>
            <td class="text-center"><?= $portfolio['total_assets'] ?></td>
            <td class="text-right"><?= formatRupiah($portfolio['total_investment']) ?></td>
            <td class="text-right"><?= formatRupiah($portfolio['total_value']) ?></td>
            <td class="text-right"><?= formatRupiah($portfolio['profit_loss']) ?> (<?= number_format($portfolio['profit_loss_percent'], 1) ?>%)</td>
        </tr>
    </table>

    <?php if ($report_type == 'monthly' && count($monthly_budgets) > 0): ?>
    <br>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr class="section-title"><th colspan="5">ANGGARAN VS REALISASI</th></tr>
        <tr class="sub-section">
            <th>Kategori</th>
            <th>Budget</th>
            <th>Terpakai</th>
            <th>Sisa</th>
            <th>%</th>
        </tr>
        <?php foreach ($monthly_budgets as $b): 
            $remaining = $b['budget_amount'] - $b['spent_amount'];
            $percent = $b['budget_amount'] > 0 ? ($b['spent_amount'] / $b['budget_amount']) * 100 : 0;
        ?>
        <tr>
            <td><?= htmlspecialchars($b['category_name']) ?></td>
            <td class="text-right"><?= formatRupiah($b['budget_amount']) ?></td>
            <td class="text-right"><?= formatRupiah($b['spent_amount']) ?></td>
            <td class="text-right"><?= formatRupiah($remaining) ?></td>
            <td class="text-right"><?= number_format($percent, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <br>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr class="section-title"><th colspan="3">BREAKDOWN KATEGORI</th></tr>
        <tr class="sub-section"><th colspan="3">Pemasukan per Kategori</th></tr>
        <tr><th>Kategori</th><th>Jumlah</th><th>%</th></tr>
        <?php foreach ($income_categories as $cat): ?>
        <tr>
            <td><?= htmlspecialchars($cat['name']) ?></td>
            <td class="text-right"><?= formatRupiah($cat['total']) ?></td>
            <td class="text-right"><?= $total_income > 0 ? number_format(($cat['total'] / $total_income) * 100, 1) : 0 ?>%</td>
        </tr>
        <?php endforeach; ?>
        
        <tr class="sub-section"><th colspan="3">Pengeluaran per Kategori</th></tr>
        <tr><th>Kategori</th><th>Jumlah</th><th>%</th></tr>
        <?php foreach ($expense_categories as $cat): ?>
        <tr>
            <td><?= htmlspecialchars($cat['name']) ?></td>
            <td class="text-right"><?= formatRupiah($cat['total']) ?></td>
            <td class="text-right"><?= $total_expense > 0 ? number_format(($cat['total'] / $total_expense) * 100, 1) : 0 ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (count($transfers) > 0): ?>
    <br>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr class="section-title"><th colspan="5">RINGKASAN TRANSFER ANTAR AKUN</th></tr>
        <tr class="sub-section">
            <th>Tanggal</th>
            <th>Dari Akun</th>
            <th>Ke Akun</th>
            <th>Jumlah</th>
            <th>Keterangan</th>
        </tr>
        <?php foreach ($transfers as $tr): ?>
        <tr>
            <td><?= formatDate($tr['transfer_date']) ?></td>
            <td><?= htmlspecialchars($tr['from_account_name']) ?></td>
            <td><?= htmlspecialchars($tr['to_account_name']) ?></td>
            <td class="text-right"><?= formatRupiah($tr['amount']) ?></td>
            <td><?= htmlspecialchars($tr['description']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <br>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr class="section-title"><th colspan="6">DAFTAR TRANSAKSI DETAIL</th></tr>
        <tr class="sub-section">
            <th>Tanggal</th>
            <th>Deskripsi</th>
            <th>Kategori</th>
            <th>Akun</th>
            <th>Tipe</th>
            <th>Jumlah</th>
        </tr>
        <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= formatDate($t['transaction_date']) ?></td>
            <td><?= htmlspecialchars($t['description']) ?></td>
            <td><?= htmlspecialchars($t['category_name']) ?></td>
            <td><?= htmlspecialchars($t['account_name']) ?></td>
            <td><?= $t['type'] == 'income' ? 'Pemasukan' : 'Pengeluaran' ?></td>
            <td class="text-right"><?= formatRupiah($t['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
<?php exit; ?>