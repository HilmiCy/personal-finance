<?php
require_once '../../vendor/autoload.php';
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

use Dompdf\Dompdf;
use Dompdf\Options;

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

// Start Output Buffering
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; border-bottom: 2px solid #444; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18pt; color: #1a2a6c; }
        .header p { margin: 5px 0; color: #666; font-size: 10pt; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; word-wrap: break-word; }
        th { background-color: #f2f2f2; font-weight: bold; color: #444; }
        
        .section-title { background-color: #1a2a6c; color: white; padding: 5px 10px; font-weight: bold; margin: 20px 0 10px 0; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-success { color: #28a745; font-weight: bold; }
        .text-danger { color: #dc3545; font-weight: bold; }
        .text-warning { color: #f39c12; font-weight: bold; }
        
        .summary-table td { border: none; padding: 4px 8px; }
        .summary-table .label { font-weight: bold; width: 50%; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        
        .page-break { page-break-after: always; }
        
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 8pt; display: inline-block; }
        .badge-income { background-color: #d4edda; color: #155724; }
        .badge-expense { background-color: #f8d7da; color: #721c24; }

        .ratio-box { display: inline-block; width: 30%; border: 1px solid #ddd; padding: 10px; text-align: center; margin-right: 2%; }
        .ratio-val { font-size: 14pt; font-weight: bold; margin-bottom: 5px; }
        .ratio-lab { font-size: 8pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KEUANGAN SUPER LENGKAP</h1>
        <p>Periode: <?= $report_type == 'monthly' ? bulanIndonesia($current_month) . ' ' . $current_year : 'Tahun ' . $current_year ?></p>
        <p>Nama Pengguna: <?= htmlspecialchars($_SESSION['user_name']) ?> | Dicetak pada: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="section-title">KEKAYAAN BERSIH (NET WORTH) & RASIO</div>
    <table class="summary-table">
        <tr>
            <td class="label">Total Saldo Akun (Cash):</td>
            <td class="text-right"><?= formatRupiah($total_account_balance) ?></td>
        </tr>
        <tr>
            <td class="label">Total Nilai Portofolio Aset:</td>
            <td class="text-right"><?= formatRupiah($portfolio['total_value']) ?></td>
        </tr>
        <tr>
            <td class="label">Total Hutang/Cicilan (Sisa):</td>
            <td class="text-right text-danger">-<?= formatRupiah($installment_summary['total_remaining'] ?? 0) ?></td>
        </tr>
        <tr style="border-top: 1px solid #1a2a6c;">
            <td class="label" style="font-size: 14pt;">KEKAYAAN BERSIH:</td>
            <td class="text-right" style="font-size: 14pt; font-weight: bold; color: #1a2a6c;"><?= formatRupiah($net_worth) ?></td>
        </tr>
    </table>

    <div style="margin-top: 10px; text-align: center;">
        <div class="ratio-box">
            <div class="ratio-val <?= $savings_rate >= 20 ? 'text-success' : 'text-danger' ?>"><?= number_format($savings_rate, 1) ?>%</div>
            <div class="ratio-lab">Savings Rate</div>
        </div>
        <div class="ratio-box">
            <div class="ratio-val <?= $debt_service_ratio <= 35 ? 'text-success' : 'text-danger' ?>"><?= number_format($debt_service_ratio, 1) ?>%</div>
            <div class="ratio-lab">Debt Service</div>
        </div>
        <div class="ratio-box" style="margin-right: 0;">
            <div class="ratio-val <?= $emergency_ratio >= 6 ? 'text-success' : 'text-warning' ?>"><?= number_format($emergency_ratio, 1) ?> bln</div>
            <div class="ratio-lab">EF Coverage</div>
        </div>
    </div>

    <div class="section-title">RINGKASAN ARUS KAS</div>
    <table class="summary-table">
        <tr>
            <td class="label">Total Pemasukan (Reguler + Deposit EF):</td>
            <td class="text-right text-success"><?= formatRupiah($total_income_overall) ?></td>
        </tr>
        <tr>
            <td class="label">Total Pengeluaran (Reguler + Tarik EF + Cicilan):</td>
            <td class="text-right text-danger"><?= formatRupiah($total_expense_overall) ?></td>
        </tr>
        <tr style="border-top: 1px solid #ccc;">
            <td class="label">SALDO BERSIH PERIODE INI:</td>
            <td class="text-right <?= $balance_overall >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= formatRupiah($balance_overall) ?>
            </td>
        </tr>
    </table>

    <div class="section-title">PORTOFOLIO ASET</div>
    <table>
        <thead>
            <tr>
                <th>Total Aset</th>
                <th>Total Investasi</th>
                <th>Nilai Saat Ini</th>
                <th>Profit/Loss</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center"><?= $portfolio['total_assets'] ?></td>
                <td class="text-right"><?= formatRupiah($portfolio['total_investment']) ?></td>
                <td class="text-right"><?= formatRupiah($portfolio['total_value']) ?></td>
                <td class="text-right <?= $portfolio['profit_loss'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= formatRupiah($portfolio['profit_loss']) ?> (<?= number_format($portfolio['profit_loss_percent'], 1) ?>%)
                </td>
            </tr>
        </tbody>
    </table>

    <?php if ($report_type == 'monthly' && count($monthly_budgets) > 0): ?>
    <div class="section-title">ANGGARAN VS REALISASI</div>
    <table>
        <thead>
            <tr>
                <th width="30%">Kategori</th>
                <th width="20%" class="text-right">Budget</th>
                <th width="20%" class="text-right">Terpakai</th>
                <th width="20%" class="text-right">Sisa</th>
                <th width="10%" class="text-center">%</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthly_budgets as $b): 
                $remaining = $b['budget_amount'] - $b['spent_amount'];
                $percent = $b['budget_amount'] > 0 ? ($b['spent_amount'] / $b['budget_amount']) * 100 : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($b['category_name']) ?></td>
                <td class="text-right"><?= formatRupiah($b['budget_amount']) ?></td>
                <td class="text-right <?= $percent > 100 ? 'text-danger' : '' ?>"><?= formatRupiah($b['spent_amount']) ?></td>
                <td class="text-right <?= $remaining < 0 ? 'text-danger' : 'text-success' ?>"><?= formatRupiah($remaining) ?></td>
                <td class="text-center"><?= number_format($percent, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="page-break"></div>

    <div class="section-title">BREAKDOWN KATEGORI</div>
    <table border="0" style="border: none;">
        <tr>
            <td style="border: none; vertical-align: top; padding: 0 10px 0 0;">
                <p><strong>Pemasukan per Kategori</strong></p>
                <table>
                    <thead>
                        <tr><th>Kategori</th><th class="text-right">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($income_categories as $cat): ?>
                        <tr><td><?= htmlspecialchars($cat['name']) ?></td><td class="text-right"><?= formatRupiah($cat['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </td>
            <td style="border: none; vertical-align: top; padding: 0 0 0 10px;">
                <p><strong>Pengeluaran per Kategori</strong></p>
                <table>
                    <thead>
                        <tr><th>Kategori</th><th class="text-right">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expense_categories as $cat): ?>
                        <tr><td><?= htmlspecialchars($cat['name']) ?></td><td class="text-right"><?= formatRupiah($cat['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <?php if (count($transfers) > 0): ?>
    <div class="section-title">RINGKASAN TRANSFER ANTAR AKUN</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Dari</th>
                <th>Ke</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transfers as $tr): ?>
            <tr>
                <td><?= formatDate($tr['transfer_date']) ?></td>
                <td><?= htmlspecialchars($tr['from_account_name']) ?></td>
                <td><?= htmlspecialchars($tr['to_account_name']) ?></td>
                <td class="text-right"><?= formatRupiah($tr['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (count($emergency_transactions) > 0): ?>
    <div class="section-title">RIWAYAT DANA DARURAT</div>
    <table>
        <thead>
            <tr>
                <th width="20%">Tanggal</th>
                <th width="20%">Jenis</th>
                <th width="25%" class="text-right">Jumlah</th>
                <th width="35%">Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($emergency_transactions as $et): ?>
            <tr>
                <td><?= formatDate($et['transaction_date']) ?></td>
                <td><?= $et['type'] == 'deposit' ? 'Deposit' : 'Penarikan' ?></td>
                <td class="text-right"><?= formatRupiah($et['amount']) ?></td>
                <td><?= htmlspecialchars($et['description'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (count($installment_payments) > 0): ?>
    <div class="section-title">PEMBAYARAN CICILAN</div>
    <table>
        <thead>
            <tr>
                <th width="20%">Tanggal</th>
                <th width="30%">Nama Cicilan</th>
                <th width="15%" class="text-center">Ke-</th>
                <th width="35%" class="text-right">Total Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($installment_payments as $ip): ?>
            <tr>
                <td><?= formatDate($ip['payment_date']) ?></td>
                <td><?= htmlspecialchars($ip['installment_name']) ?></td>
                <td class="text-center"><?= $ip['payment_number'] ?></td>
                <td class="text-right text-danger"><?= formatRupiah($ip['total_paid']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="page-break"></div>
    <div class="section-title">DAFTAR TRANSAKSI DETAIL</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Tanggal</th>
                <th width="30%">Deskripsi</th>
                <th width="20%">Kategori</th>
                <th width="15%" class="text-center">Tipe</th>
                <th width="20%" class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= formatDate($t['transaction_date']) ?></td>
                <td><?= htmlspecialchars($t['description']) ?></td>
                <td><?= htmlspecialchars($t['category_name']) ?></td>
                <td class="text-center">
                    <span class="badge <?= $t['type'] == 'income' ? 'badge-income' : 'badge-expense' ?>">
                        <?= $t['type'] == 'income' ? 'Masuk' : 'Keluar' ?>
                    </span>
                </td>
                <td class="text-right <?= $t['type'] == 'income' ? 'text-success' : 'text-danger' ?>">
                    <?= formatRupiah($t['amount']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Laporan Keuangan - <?= APP_NAME ?>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Setup Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Set Paper Size
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Add Page Numbers (Canvas method)
$canvas = $dompdf->getCanvas();
$font = $dompdf->getFontMetrics()->get_font("helvetica", "normal");
$size = 10;
$t = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
$width = $dompdf->getFontMetrics()->get_text_width($t, $font, $size);
$canvas->page_text(595 - $width - 40, 810, $t, $font, $size, array(0.5, 0.5, 0.5));

// Output PDF to Browser
$filename = "Laporan_Keuangan_Super_Lengkap_" . ($report_type == 'monthly' ? bulanIndonesia($current_month) : "Tahun") . "_" . $current_year . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
?>