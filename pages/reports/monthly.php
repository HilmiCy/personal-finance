<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Laporan Bulanan';
$current_page = 'reports';

$db = Database::getInstance()->getConnection();
$transaction = new Transaction();

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));

$transactions = $transaction->getByDateRange($_SESSION['user_id'], $start_date, $end_date);

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

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="welcome-card">
            <h1 class="welcome-title">Laporan Bulanan</h1>
            <p class="welcome-subtitle">Detail laporan keuangan bulan <?= bulanIndonesia($month) ?> <?= $year ?></p>
        </div>
        
        <!-- Content similar to index.php but focused on monthly -->
        <!-- You can customize this page for more detailed monthly reports -->
        
    </div>
</div>

<?php include '../../includes/footer.php'; ?>