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

$page_title = 'Laporan Keuangan';
$current_page = 'reports';

$db = Database::getInstance()->getConnection();
$transaction = new Transaction();
$category = new Category();
$account = new Account();
$emergencyFund = new EmergencyFund();
$installment = new Installment();
$budget = new Budget();
$asset = new Asset();

// Validasi input
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$report_type = isset($_GET['type']) ? $_GET['type'] : 'monthly';

// Validasi range
if ($current_month < 1 || $current_month > 12) $current_month = date('n');
if ($current_year < 2000 || $current_year > 2100) $current_year = date('Y');

// Get data based on report type
if ($report_type == 'monthly') {
    $start_date = "$current_year-$current_month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // 1. Regular Transactions
    $transactions = $transaction->getByDateRange($_SESSION['user_id'], $start_date, $end_date);
    
    // 2. Emergency Fund Transactions for this month
    $emergency_transactions = $emergencyFund->getHistory($_SESSION['user_id'], 100, 0);
    $emergency_transactions = array_filter($emergency_transactions, function($et) use ($current_month, $current_year) {
        $date = date('Y-m', strtotime($et['transaction_date']));
        return $date == "$current_year-" . str_pad($current_month, 2, '0', STR_PAD_LEFT);
    });
    
    // 3. Installment Payments for this month
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

    // 4. Budgets for this month
    $monthly_budgets = $budget->getByMonth($_SESSION['user_id'], $current_month, $current_year);
    
    // Get daily summary for chart
    $daily_data = $transaction->getDailySummary($_SESSION['user_id'], $start_date, $end_date);
    
    // Get category breakdown
    $income_categories = $transaction->getSummaryByCategory($_SESSION['user_id'], $current_month, $current_year, 'income');
    $expense_categories = $transaction->getSummaryByCategory($_SESSION['user_id'], $current_month, $current_year, 'expense');
    
    // Get account breakdown
    $stmt = $db->prepare("
    SELECT a.name, a.balance, a.currency,
        SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as income,
        SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as expense
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    WHERE t.user_id = ? 
    AND MONTH(t.transaction_date) = ? 
    AND YEAR(t.transaction_date) = ?
    GROUP BY a.id, a.name, a.balance, a.currency
");
    $stmt->execute([$_SESSION['user_id'], $current_month, $current_year]);
    $account_rows = $stmt->fetchAll();
    
    $account_breakdown = [];
    foreach ($account_rows as $row) {
        $account_breakdown[] = [
            'name' => $row['name'],
            'balance' => CurrencyService::convertToIDR($row['balance'], $row['currency']),
            'income' => CurrencyService::convertToIDR($row['income'], $row['currency']),
            'expense' => CurrencyService::convertToIDR($row['expense'], $row['currency']),
            'display_balance' => formatCurrency($row['balance'], $row['currency'])
        ];
    }
    
    // Get transfers for this month
    $transfers = $account->getTransferHistoryByDate($_SESSION['user_id'], $start_date, $end_date . ' 23:59:59');
    
    // Get top transactions
    $top_income_trans = $transaction->getTopTransactions($_SESSION['user_id'], $start_date, $end_date, 'income', 5);
    $top_expense_trans = $transaction->getTopTransactions($_SESSION['user_id'], $start_date, $end_date, 'expense', 5);
    
} else {
    $start_date = "$current_year-01-01";
    $end_date = "$current_year-12-31";
    
    // 1. Regular Transactions
    $transactions = $transaction->getByDateRange($_SESSION['user_id'], $start_date, $end_date);
    
    // 2. Emergency Fund Transactions for this year
    $emergency_transactions = $emergencyFund->getHistory($_SESSION['user_id'], 1000, 0);
    $emergency_transactions = array_filter($emergency_transactions, function($et) use ($current_year) {
        return date('Y', strtotime($et['transaction_date'])) == $current_year;
    });
    
    // 3. Installment Payments for this year
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
    
    // Get monthly summary for chart
    $monthly_data = [];
    for ($i = 1; $i <= 12; $i++) {
        $summary = $transaction->getMonthlySummary($_SESSION['user_id'], $current_year, $i);
        $monthly_data[] = [
            'month' => $i,
            'income' => $summary['total_income'],
            'expense' => $summary['total_expense']
        ];
    }
    
    // Get yearly category breakdown
    $stmt = $db->prepare("
        SELECT c.name, c.type, t.amount, a.currency
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        JOIN accounts a ON t.account_id = a.id
        WHERE t.user_id = ? 
        AND YEAR(t.transaction_date) = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $current_year]);
    $rows = $stmt->fetchAll();
    
    $income_summary = [];
    $expense_summary = [];
    
    foreach ($rows as $row) {
        $amount_idr = CurrencyService::convertToIDR($row['amount'], $row['currency']);
        if ($row['type'] == 'income') {
            $income_summary[$row['name']] = ($income_summary[$row['name']] ?? 0) + $amount_idr;
        } else {
            $expense_summary[$row['name']] = ($expense_summary[$row['name']] ?? 0) + $amount_idr;
        }
    }
    
    $income_categories = [];
    foreach ($income_summary as $name => $total) $income_categories[] = ['name' => $name, 'total' => $total];
    usort($income_categories, function($a, $b) { return $b['total'] <=> $a['total']; });
    
    $expense_categories = [];
    foreach ($expense_summary as $name => $total) $expense_categories[] = ['name' => $name, 'total' => $total];
    usort($expense_categories, function($a, $b) { return $b['total'] <=> $a['total']; });
    
    // Get transfers for this year
    $transfers = $account->getTransferHistoryByDate($_SESSION['user_id'], $start_date, $end_date . ' 23:59:59');
    
    // Get top transactions for the year
    $top_income_trans = $transaction->getTopTransactions($_SESSION['user_id'], $start_date, $end_date, 'income', 5);
    $top_expense_trans = $transaction->getTopTransactions($_SESSION['user_id'], $start_date, $end_date, 'expense', 5);
    
    $account_breakdown = [];
}

// Get current emergency fund status
$current_emergency = $emergencyFund->getEmergencyFund($_SESSION['user_id']);
$emergency_target = $current_emergency ? (float)$current_emergency['target_amount'] : 0;
$emergency_current = $current_emergency ? (float)$current_emergency['current_amount'] : 0;
$emergency_percentage = $emergency_target > 0 ? ($emergency_current / $emergency_target) * 100 : 0;

// Get active installments summary
$installment_summary = $installment->getSummary($_SESSION['user_id']);
if (!$installment_summary) {
    $installment_summary = [
        'total_installments' => 0,
        'active_installments' => 0,
        'completed_installments' => 0,
        'total_remaining' => 0,
        'total_paid' => 0
    ];
}

// 5. Asset Portfolio Summary
$portfolio = $asset->getPortfolioSummary($_SESSION['user_id']);

// 6. Account Total Balance
$total_account_balance = $account->getTotalBalance($_SESSION['user_id']);

// Calculate Net Worth
$net_worth = $total_account_balance + $portfolio['total_value'] - ($installment_summary['total_remaining'] ?? 0);

// Calculate totals for REGULAR TRANSACTIONS
$total_income = 0;
$total_expense = 0;
foreach ($transactions as $t) {
    if ($t['type'] == 'income') {
        $total_income += $t['amount'];
    } elseif ($t['type'] == 'expense') {
        $total_expense += $t['amount'];
    }
}

// Calculate EMERGENCY FUND totals
$total_emergency_deposit = 0;
$total_emergency_withdraw = 0;
foreach ($emergency_transactions as $et) {
    if ($et['type'] == 'deposit') {
        $total_emergency_deposit += $et['amount'];
    } else {
        $total_emergency_withdraw += $et['amount'];
    }
}

// Calculate INSTALLMENT totals
$total_installment_paid = 0;
foreach ($installment_payments as $ip) {
    $total_installment_paid += $ip['total_paid'];
}

// TOTAL PEMASUKAN KESELURUHAN
$total_income_overall = $total_income + $total_emergency_deposit;

// TOTAL PENGELUARAN KESELURUHAN
$total_expense_overall = $total_expense + $total_emergency_withdraw + $total_installment_paid;

// SALDO BERSIH (OPERASIONAL)
$balance_overall = $total_income_overall - $total_expense_overall;

// FINANCIAL RATIOS
$savings_rate = $total_income_overall > 0 ? (($total_income_overall - $total_expense_overall) / $total_income_overall) * 100 : 0;
$debt_service_ratio = $total_income_overall > 0 ? ($total_installment_paid / $total_income_overall) * 100 : 0;
$emergency_ratio = $total_expense_overall > 0 ? $emergency_current / ($total_expense_overall / ($report_type == 'monthly' ? 1 : 12)) : 0;

$transaction_count = count($transactions);
$emergency_count = count($emergency_transactions);
$installment_count = count($installment_payments);

$avg_transaction = $transaction_count > 0 ? ($total_income + $total_expense) / $transaction_count : 0;
$avg_income = $transaction_count > 0 ? $total_income / $transaction_count : 0;
$avg_expense = $transaction_count > 0 ? $total_expense / $transaction_count : 0;

// Prepare data for PDF
$pdf_data = [
    'report_type' => $report_type,
    'month' => $current_month,
    'year' => $current_year,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'total_income' => $total_income,
    'total_expense' => $total_expense,
    'total_income_overall' => $total_income_overall,
    'total_expense_overall' => $total_expense_overall,
    'balance_overall' => $balance_overall,
    'net_worth' => $net_worth,
    'total_account_balance' => $total_account_balance,
    'savings_rate' => $savings_rate,
    'debt_service_ratio' => $debt_service_ratio,
    'emergency_ratio' => $emergency_ratio,
    'total_emergency_deposit' => $total_emergency_deposit,
    'total_emergency_withdraw' => $total_emergency_withdraw,
    'total_installment_paid' => $total_installment_paid,
    'emergency_current' => $emergency_current,
    'emergency_target' => $emergency_target,
    'emergency_percentage' => $emergency_percentage,
    'installment_active' => $installment_summary['active_installments'] ?? 0,
    'installment_remaining' => $installment_summary['total_remaining'] ?? 0,
    'transaction_count' => $transaction_count,
    'avg_transaction' => $avg_transaction,
    'avg_income' => $avg_income,
    'avg_expense' => $avg_expense,
    'income_categories' => $income_categories,
    'expense_categories' => $expense_categories,
    'top_income_trans' => $top_income_trans,
    'top_expense_trans' => $top_expense_trans,
    'transactions' => $transactions,
    'transfers' => $transfers,
    'emergency_transactions' => $emergency_transactions,
    'installment_payments' => $installment_payments,
    'daily_data' => isset($daily_data) ? $daily_data : [],
    'monthly_data' => isset($monthly_data) ? $monthly_data : [],
    'account_breakdown' => isset($account_breakdown) ? $account_breakdown : [],
    'monthly_budgets' => $monthly_budgets,
    'portfolio' => $portfolio
];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
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
    
    /* ========== REPORT TABS ========== */
    .report-tabs {
        background: white;
        border-radius: 16px;
        padding: 8px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: inline-flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .report-tab {
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    
    .report-tab.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .report-tab:not(.active) {
        color: #4b5563;
        background: #f3f4f6;
    }
    
    .report-tab:not(.active):hover {
        background: #e5e7eb;
        color: #1f2937;
        text-decoration: none;
    }
    
    /* ========== DATE NAVIGATION ========== */
    .date-navigation {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .btn-month {
        background: #f3f4f6;
        padding: 8px 20px;
        border-radius: 10px;
        text-decoration: none;
        color: #4b5563;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }
    
    .btn-month:hover {
        background: #e5e7eb;
        color: #1f2937;
        text-decoration: none;
    }
    
    .current-month {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .current-month i {
        color: #667eea;
        margin-right: 8px;
    }
    
    /* ========== REPORT CARDS ========== */
    .report-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .report-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-left: 4px solid #667eea;
        padding-left: 15px;
    }
    
    .report-title i {
        color: #667eea;
    }
    
    /* ========== STAT BOXES ========== */
    .stat-box {
        text-align: center;
        padding: 20px;
        border-radius: 16px;
        background: #f9fafb;
        transition: all 0.3s ease;
        height: 100%;
        border: 1px solid #e5e7eb;
    }
    
    .stat-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .stat-box .label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    
    .stat-box .value {
        font-size: 24px;
        font-weight: 800;
    }
    
    .stat-box.income .value { color: #10b981; }
    .stat-box.expense .value { color: #ef4444; }
    .stat-box.balance .value { color: #667eea; }
    .stat-box.emergency .value { color: #f59e0b; }
    .stat-box.installment .value { color: #8b5cf6; }
    .stat-box.net-worth .value { color: #1a2a6c; }
    
    /* ========== INFO CARD ========== */
    .info-card {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        border-left: 4px solid #6366f1;
    }
    
    .warning-card {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-left: 4px solid #f59e0b;
    }
    
    .dark-card {
        background: linear-gradient(135deg, #1a2a6c 0%, #2c3e50 100%);
        color: white;
        border-left: 4px solid #3498db;
    }
    
    .dark-card .report-title {
        color: white;
        border-left-color: #3498db;
    }
    
    .dark-card .text-muted {
        color: #bdc3c7 !important;
    }
    
    /* ========== CATEGORY ITEMS ========== */
    .category-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .category-item:last-child {
        border-bottom: none;
    }
    
    .category-name {
        font-weight: 500;
        color: #4b5563;
        font-size: 14px;
    }
    
    .category-amount {
        font-weight: 700;
        font-size: 14px;
    }
    
    .category-amount.income { color: #10b981; }
    .category-amount.expense { color: #ef4444; }
    
    /* ========== PROGRESS BAR ========== */
    .progress-bar-custom {
        height: 8px;
        border-radius: 10px;
        background: #e5e7eb;
        margin-top: 8px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }
    
    .progress-fill.income { background: #10b981; }
    .progress-fill.expense { background: #ef4444; }
    .progress-fill.emergency { background: #f59e0b; }
    
    /* ========== TABLES ========== */
    .table-report {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table-report th {
        background: #f9fafb;
        padding: 12px;
        font-weight: 600;
        color: #4b5563;
        font-size: 13px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .table-report td {
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
    }
    
    .table-report tr:hover td {
        background: #f9fafb;
    }
    
    /* ========== BADGES ========== */
    .badge-income {
        background: #d1fae5;
        color: #065f46;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .badge-expense {
        background: #fee2e2;
        color: #991b1b;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .badge-transfer {
        background: #e0e7ff;
        color: #4338ca;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    /* ========== EMPTY STATE ========== */
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #9ca3af;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
    }
    
    /* ========== EXPORT BUTTONS ========== */
    .export-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    
    .btn-export {
        background: #f3f4f6;
        color: #4b5563;
        border: none;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-export:hover {
        background: #e5e7eb;
        transform: translateY(-2px);
    }
    
    /* ========== LOADING OVERLAY ========== */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-spinner {
        background: white;
        padding: 20px 40px;
        border-radius: 20px;
        text-align: center;
    }
    
    .loading-spinner i {
        font-size: 48px;
        color: #667eea;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* ========== ANIMATIONS ========== */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animated {
        animation: fadeInUp 0.5s ease forwards;
        opacity: 0;
    }
    
    /* ========== RATIO CARD ========== */
    .ratio-card {
        background: #fff;
        border-radius: 16px;
        padding: 15px;
        height: 100%;
        border: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .ratio-value {
        font-size: 20px;
        font-weight: 800;
        margin: 5px 0;
    }
    
    .ratio-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 600;
    }
    
    /* ========== RESPONSIVE ========== */
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
        
        .stat-box .value {
            font-size: 18px !important;
        }
        
        .report-title {
            font-size: 16px;
        }
        
        .current-month {
            font-size: 16px;
        }
        
        .btn-month {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .report-tab {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .table-report th,
        .table-report td {
            padding: 8px;
            font-size: 12px;
        }
        
        .export-buttons {
            justify-content: center;
            margin-top: 10px;
        }
    }
    
    @media (max-width: 576px) {
        .report-tabs {
            width: 100%;
            justify-content: center;
        }
        
        .stat-box {
            padding: 15px;
        }
        
        .stat-box .value {
            font-size: 16px !important;
        }
    }
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="mt-2 mb-0">Mengekspor data...</p>
            </div>
        </div>

        <!-- Header -->
        <div class="welcome-card animated" style="animation-delay: 0s">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title text-dark">Laporan Keuangan</h1>
                    <p class="welcome-subtitle text-muted">Analisis lengkap arus kas, dana darurat, dan cicilan</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="export-buttons">
                        <button class="btn-export" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button class="btn-export" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <button class="btn-export" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Tabs -->
        <div class="animated" style="animation-delay: 0.1s">
            <div class="report-tabs">
                <a href="?type=monthly&month=<?= $current_month ?>&year=<?= $current_year ?>" 
                   class="report-tab <?= $report_type == 'monthly' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i> Laporan Bulanan
                </a>
                <a href="?type=yearly&year=<?= $current_year ?>" 
                   class="report-tab <?= $report_type == 'yearly' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-year"></i> Laporan Tahunan
                </a>
            </div>
        </div>

        <!-- Date Navigation -->
        <div class="date-navigation animated" style="animation-delay: 0.15s">
            <?php if ($report_type == 'monthly'): ?>
            <div class="row align-items-center">
                <div class="col-md-4 text-md-start text-center mb-3 mb-md-0">
                    <a href="?type=monthly&month=<?= $current_month == 1 ? 12 : $current_month - 1 ?>&year=<?= $current_month == 1 ? $current_year - 1 : $current_year ?>" 
                       class="btn-month">
                        <i class="fas fa-chevron-left"></i> Bulan Sebelumnya
                    </a>
                </div>
                <div class="col-md-4 text-center">
                    <span class="current-month">
                        <i class="fas fa-calendar-alt"></i> <?= bulanIndonesia($current_month) ?> <?= $current_year ?>
                    </span>
                </div>
                <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
                    <a href="?type=monthly&month=<?= $current_month == 12 ? 1 : $current_month + 1 ?>&year=<?= $current_month == 12 ? $current_year + 1 : $current_year ?>" 
                       class="btn-month">
                        Bulan Selanjutnya <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="row align-items-center">
                <div class="col-md-4 text-md-start text-center mb-3 mb-md-0">
                    <a href="?type=yearly&year=<?= $current_year - 1 ?>" class="btn-month">
                        <i class="fas fa-chevron-left"></i> Tahun Sebelumnya
                    </a>
                </div>
                <div class="col-md-4 text-center">
                    <span class="current-month">
                        <i class="fas fa-calendar-alt"></i> Tahun <?= $current_year ?>
                    </span>
                </div>
                <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
                    <a href="?type=yearly&year=<?= $current_year + 1 ?>" class="btn-month">
                        Tahun Selanjutnya <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Summary Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-box animated" style="animation-delay: 0.2s">
                    <div class="label">Net Worth (Kekayaan Bersih)</div>
                    <div class="value net-worth"><?= formatRupiah($net_worth) ?></div>
                    <div class="mt-2">
                        <i class="fas fa-university"></i>
                        <span class="small">Total Aset & Saldo - Hutang</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-box animated" style="animation-delay: 0.25s">
                    <div class="label">Total Pemasukan</div>
                    <div class="value income"><?= formatRupiah($total_income_overall) ?></div>
                    <div class="mt-2">
                        <i class="fas fa-arrow-up text-success"></i>
                        <span class="text-success small">+<?= formatRupiah($total_income_overall) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-box animated" style="animation-delay: 0.3s">
                    <div class="label">Total Pengeluaran</div>
                    <div class="value expense"><?= formatRupiah($total_expense_overall) ?></div>
                    <div class="mt-2">
                        <i class="fas fa-arrow-down text-danger"></i>
                        <span class="text-danger small">-<?= formatRupiah($total_expense_overall) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-box animated" style="animation-delay: 0.35s">
                    <div class="label">Saldo Bersih Periode Ini</div>
                    <div class="value balance"><?= formatRupiah($balance_overall) ?></div>
                    <div class="mt-2">
                        <i class="fas <?= $balance_overall >= 0 ? 'fa-smile text-success' : 'fa-frown text-danger' ?>"></i>
                        <span class="small"><?= $balance_overall >= 0 ? 'Surplus' : 'Defisit' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Worth & Health Ratios Card -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="report-card animated dark-card" style="animation-delay: 0.4s">
                    <div class="report-title">
                        <i class="fas fa-heartbeat"></i>
                        Kesehatan Keuangan & Kekayaan Bersih
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="mb-4">
                                <small class="text-muted d-block">Komposisi Kekayaan Bersih:</small>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Saldo Akun</span>
                                    <span><?= formatRupiah($total_account_balance) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Nilai Portofolio Aset</span>
                                    <span><?= formatRupiah($portfolio['total_value']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-1 text-warning">
                                    <span>Total Cicilan (Hutang)</span>
                                    <span>-<?= formatRupiah($installment_summary['total_remaining'] ?? 0) ?></span>
                                </div>
                                <hr style="border-color: rgba(255,255,255,0.1)">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>KEKAYAAN BERSIH</span>
                                    <span style="font-size: 1.2rem;"><?= formatRupiah($net_worth) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="ratio-card text-dark">
                                        <div class="ratio-label">Savings Rate</div>
                                        <div class="ratio-value <?= $savings_rate >= 20 ? 'text-success' : ($savings_rate >= 10 ? 'text-warning' : 'text-danger') ?>">
                                            <?= number_format($savings_rate, 1) ?>%
                                        </div>
                                        <small class="text-muted">Target: >20%</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="ratio-card text-dark">
                                        <div class="ratio-label">Debt Service</div>
                                        <div class="ratio-value <?= $debt_service_ratio <= 35 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($debt_service_ratio, 1) ?>%
                                        </div>
                                        <small class="text-muted">Max: 35%</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="ratio-card text-dark">
                                        <div class="ratio-label">EF Coverage</div>
                                        <div class="ratio-value <?= $emergency_ratio >= 6 ? 'text-success' : ($emergency_ratio >= 3 ? 'text-warning' : 'text-danger') ?>">
                                            <?= number_format($emergency_ratio, 1) ?> bln
                                        </div>
                                        <small class="text-muted">Target: 6-12 bln</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="ratio-card text-dark">
                                        <div class="ratio-label">Asset Liquidity</div>
                                        <div class="ratio-value text-primary">
                                            <?= number_format(($total_account_balance / ($net_worth > 0 ? $net_worth : 1)) * 100, 1) ?>%
                                        </div>
                                        <small class="text-muted">Cash vs Total</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="report-card animated info-card" style="animation-delay: 0.45s">
                    <div class="report-title">
                        <i class="fas fa-coins"></i>
                        Alokasi Pemasukan
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pengeluaran (Reguler)</span>
                            <span class="fw-bold text-danger"><?= formatRupiah($total_expense) ?></span>
                        </div>
                        <div class="progress-bar-custom mb-3">
                            <div class="progress-fill bg-danger" style="width: <?= $total_income_overall > 0 ? ($total_expense / $total_income_overall) * 100 : 0 ?>%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pembayaran Cicilan</span>
                            <span class="fw-bold text-warning"><?= formatRupiah($total_installment_paid) ?></span>
                        </div>
                        <div class="progress-bar-custom mb-3">
                            <div class="progress-fill bg-warning" style="width: <?= $total_income_overall > 0 ? ($total_installment_paid / $total_income_overall) * 100 : 0 ?>%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tabungan (EF/Surplus)</span>
                            <span class="fw-bold text-success"><?= formatRupiah($balance_overall) ?></span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill bg-success" style="width: <?= $total_income_overall > 0 ? (max(0, $balance_overall) / $total_income_overall) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Portfolio Summary Card -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="report-card animated" style="animation-delay: 0.42s; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #22c55e;">
                    <div class="report-title" style="border-left-color: #22c55e;">
                        <i class="fas fa-briefcase text-success"></i>
                        Ringkasan Portofolio Aset
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3 mb-md-0">
                            <div class="text-center">
                                <small class="text-muted">Total Aset</small>
                                <h5 class="mb-0"><?= $portfolio['total_assets'] ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3 mb-md-0">
                            <div class="text-center">
                                <small class="text-muted">Total Investasi</small>
                                <h5 class="mb-0"><?= formatRupiah($portfolio['total_investment']) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <small class="text-muted">Nilai Saat Ini</small>
                                <h5 class="text-primary mb-0"><?= formatRupiah($portfolio['total_value']) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <small class="text-muted">Profit/Loss</small>
                                <h5 class="<?= $portfolio['profit_loss'] >= 0 ? 'text-success' : 'text-danger' ?> mb-0">
                                    <?= formatRupiah($portfolio['profit_loss']) ?> 
                                    <small>(<?= number_format($portfolio['profit_loss_percent'], 1) ?>%)</small>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget vs Actual (Only Monthly) -->
        <?php if ($report_type == 'monthly' && count($monthly_budgets) > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="report-card animated" style="animation-delay: 0.44s">
                    <div class="report-title">
                        <i class="fas fa-tasks"></i>
                        Budget vs Realisasi Pengeluaran
                    </div>
                    <div class="table-responsive">
                        <table class="table-report table">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Budget</th>
                                    <th>Terpakai</th>
                                    <th>Sisa</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_budgets as $b): 
                                    $remaining = $b['budget_amount'] - $b['spent_amount'];
                                    $percent = $b['budget_amount'] > 0 ? ($b['spent_amount'] / $b['budget_amount']) * 100 : 0;
                                    $status_class = $percent > 100 ? 'text-danger' : ($percent > 80 ? 'text-warning' : 'text-success');
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['category_name']) ?></strong></td>
                                    <td><?= formatRupiah($b['budget_amount']) ?></td>
                                    <td class="<?= $status_class ?> fw-bold"><?= formatRupiah($b['spent_amount']) ?></td>
                                    <td class="<?= $remaining < 0 ? 'text-danger' : 'text-success' ?>"><?= formatRupiah($remaining) ?></td>
                                    <td>
                                        <div class="progress-bar-custom" style="width: 100px;">
                                            <div class="progress-fill <?= $percent > 100 ? 'bg-danger' : ($percent > 80 ? 'bg-warning' : 'bg-success') ?>" 
                                                 style="width: <?= min($percent, 100) ?>%"></div>
                                        </div>
                                        <small><?= number_format($percent, 1) ?>%</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Emergency Fund Progress -->
        <?php if ($emergency_target > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="report-card animated warning-card" style="animation-delay: 0.45s">
                    <div class="report-title">
                        <i class="fas fa-umbrella-beach"></i>
                        Progress Dana Darurat
                    </div>
                    <div class="d-flex flex-wrap justify-content-between mb-2">
                        <span class="me-3">Terkumpul: <strong><?= formatRupiah($emergency_current) ?></strong></span>
                        <span class="me-3">Target: <strong><?= formatRupiah($emergency_target) ?></strong></span>
                        <span>Persentase: <strong><?= number_format($emergency_percentage, 1) ?>%</strong></span>
                    </div>
                    <div class="progress-bar-custom" style="height: 12px;">
                        <div class="progress-fill emergency" style="width: <?= $emergency_percentage ?>%;"></div>
                    </div>
                    <?php if ($emergency_current < $emergency_target * 0.5): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Dana darurat masih jauh dari target. Prioritaskan untuk menabung dana darurat!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Chart Section -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="report-card animated" style="animation-delay: 0.5s">
                    <div class="report-title">
                        <i class="fas fa-chart-line"></i>
                        Tren Keuangan (Transaksi Reguler)
                    </div>
                    <div style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="report-card animated" style="animation-delay: 0.55s">
                    <div class="report-title">
                        <i class="fas fa-chart-pie"></i>
                        Ringkasan Statistik
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Rata-rata Transaksi</span>
                            <span class="fw-bold"><?= formatRupiah($avg_transaction) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Rata-rata Pemasukan</span>
                            <span class="fw-bold text-success"><?= formatRupiah($avg_income) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Rata-rata Pengeluaran</span>
                            <span class="fw-bold text-danger"><?= formatRupiah($avg_expense) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Periode Laporan</span>
                            <span class="fw-bold"><?= formatDate($start_date) ?> - <?= formatDate($end_date) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Status Keuangan</span>
                            <span class="fw-bold <?= $balance_overall >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $balance_overall >= 0 ? 'Sehat' : 'Perlu Perhatian' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Financial Breakdown -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="report-card animated" style="animation-delay: 0.6s">
                    <div class="report-title">
                        <i class="fas fa-hand-holding-usd"></i>
                        Komponen Pengeluaran
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Transaksi Reguler</td>
                                    <td class="text-danger text-end"><?= formatRupiah($total_expense) ?></td>
                                    <td class="text-end"><?= $total_expense_overall > 0 ? number_format(($total_expense / $total_expense_overall) * 100, 1) : 0 ?>%</td>
                                </tr>
                                <tr>
                                    <td>Penarikan Dana Darurat</td>
                                    <td class="text-danger text-end"><?= formatRupiah($total_emergency_withdraw) ?></td>
                                    <td class="text-end"><?= $total_expense_overall > 0 ? number_format(($total_emergency_withdraw / $total_expense_overall) * 100, 1) : 0 ?>%</td>
                                </tr>
                                <tr>
                                    <td>Pembayaran Cicilan</td>
                                    <td class="text-danger text-end"><?= formatRupiah($total_installment_paid) ?></td>
                                    <td class="text-end"><?= $total_expense_overall > 0 ? number_format(($total_installment_paid / $total_expense_overall) * 100, 1) : 0 ?>%</td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>TOTAL PENGELUARAN</strong></td>
                                    <td class="text-danger text-end"><strong><?= formatRupiah($total_expense_overall) ?></strong></td>
                                    <td class="text-end"><strong>100%</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card animated" style="animation-delay: 0.65s">
                    <div class="report-title">
                        <i class="fas fa-chart-line"></i>
                        Komponen Pemasukan
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Transaksi Reguler</td>
                                    <td class="text-success text-end"><?= formatRupiah($total_income) ?></td>
                                    <td class="text-end"><?= $total_income_overall > 0 ? number_format(($total_income / $total_income_overall) * 100, 1) : 0 ?>%</td>
                                </tr>
                                <tr>
                                    <td>Deposit Dana Darurat</td>
                                    <td class="text-success text-end"><?= formatRupiah($total_emergency_deposit) ?></td>
                                    <td class="text-end"><?= $total_income_overall > 0 ? number_format(($total_emergency_deposit / $total_income_overall) * 100, 1) : 0 ?>%</td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>TOTAL PEMASUKAN</strong></td>
                                    <td class="text-success text-end"><strong><?= formatRupiah($total_income_overall) ?></strong></td>
                                    <td class="text-end"><strong>100%</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer History Summary -->
        <?php if (count($transfers) > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="report-card animated" style="animation-delay: 0.68s">
                    <div class="report-title">
                        <i class="fas fa-exchange-alt"></i>
                        Ringkasan Transfer Antar Akun
                    </div>
                    <div class="table-responsive">
                        <table class="table-report table">
                            <thead>
                                <tr><th>Tanggal</th><th>Dari Akun</th><th>Ke Akun</th><th>Jumlah</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transfers as $tr): ?>
                                <tr>
                                    <td><?= formatDate($tr['transfer_date']) ?></td>
                                    <td><?= htmlspecialchars($tr['from_account_name']) ?></td>
                                    <td><?= htmlspecialchars($tr['to_account_name']) ?></td>
                                    <td class="fw-bold"><?= formatRupiah($tr['amount']) ?></td>
                                    <td><?= htmlspecialchars($tr['description'] ?: '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Category Breakdown -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="report-card animated" style="animation-delay: 0.7s">
                    <div class="report-title">
                        <i class="fas fa-chart-pie"></i>
                        Kategori Pemasukan Teratas
                    </div>
                    <?php if (count($income_categories) > 0): ?>
                        <div style="height: 220px;">
                            <canvas id="incomeChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <?php 
                            $top_income = array_slice($income_categories, 0, 5);
                            foreach ($top_income as $cat): 
                            ?>
                            <div class="category-item">
                                <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                <span class="category-amount income"><?= formatRupiah($cat['total']) ?></span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-fill income" style="width: <?= ($cat['total'] / ($total_income > 0 ? $total_income : 1)) * 100 ?>%"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-pie"></i>
                            <p>Belum ada data pemasukan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card animated" style="animation-delay: 0.75s">
                    <div class="report-title">
                        <i class="fas fa-chart-pie"></i>
                        Kategori Pengeluaran Teratas
                    </div>
                    <?php if (count($expense_categories) > 0): ?>
                        <div style="height: 220px;">
                            <canvas id="expenseChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <?php 
                            $top_expense = array_slice($expense_categories, 0, 5);
                            foreach ($top_expense as $cat): 
                            ?>
                            <div class="category-item">
                                <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                <span class="category-amount expense"><?= formatRupiah($cat['total']) ?></span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-fill expense" style="width: <?= ($cat['total'] / ($total_expense > 0 ? $total_expense : 1)) * 100 ?>%"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-pie"></i>
                            <p>Belum ada data pengeluaran</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Emergency Fund Transactions -->
        <?php if (count($emergency_transactions) > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="report-card animated" style="animation-delay: 0.8s">
                    <div class="report-title">
                        <i class="fas fa-umbrella"></i>
                        Riwayat Dana Darurat
                    </div>
                    <div class="table-responsive">
                        <table class="table-report table">
                            <thead>
                                <tr><th>Tanggal</th><th>Jenis</th><th>Jumlah</th><th>Deskripsi</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emergency_transactions as $et): ?>
                                <tr>
                                    <td><?= formatDate($et['transaction_date']) ?></td>
                                    <td>
                                        <span class="<?= $et['type'] == 'deposit' ? 'badge-income' : 'badge-expense' ?>">
                                            <i class="fas fa-<?= $et['type'] == 'deposit' ? 'arrow-down' : 'arrow-up' ?>"></i>
                                            <?= $et['type'] == 'deposit' ? 'Deposit' : 'Penarikan' ?>
                                        </span>
                                    </td>
                                    <td class="<?= $et['type'] == 'deposit' ? 'text-success' : 'text-danger' ?> fw-bold">
                                        <?= formatRupiah($et['amount']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($et['description'] ?: '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Installment Payments -->
        <?php if (count($installment_payments) > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="report-card animated" style="animation-delay: 0.85s">
                    <div class="report-title">
                        <i class="fas fa-hand-holding-usd"></i>
                        Pembayaran Cicilan
                    </div>
                    <div class="table-responsive">
                        <table class="table-report table">
                            <thead>
                                <tr><th>Tanggal</th><th>Nama Cicilan</th><th>Ke-</th><th>Jumlah</th><th>Denda</th><th>Total</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($installment_payments as $ip): ?>
                                <tr>
                                    <td><?= formatDate($ip['payment_date']) ?></td>
                                    <td><strong><?= htmlspecialchars($ip['installment_name']) ?></strong></td>
                                    <td><?= $ip['payment_number'] ?></td>
                                    <td><?= formatRupiah($ip['amount']) ?></td>
                                    <td class="text-danger"><?= formatRupiah($ip['penalty_amount']) ?></td>
                                    <td class="text-danger fw-bold"><?= formatRupiah($ip['total_paid']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Transactions -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="report-card animated" style="animation-delay: 0.9s">
                    <div class="report-title">
                        <i class="fas fa-trophy"></i>
                        Transaksi Pemasukan Terbesar
                    </div>
                    <?php if (count($top_income_trans) > 0): ?>
                        <div class="table-responsive">
                            <table class="table-report table table-sm">
                                <thead>
                                    <tr><th>Tanggal</th><th>Deskripsi</th><th>Jumlah</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_income_trans as $t): ?>
                                    <tr>
                                        <td><?= formatDate($t['transaction_date']) ?></td>
                                        <td><?= htmlspecialchars($t['description']) ?></td>
                                        <td class="text-success fw-bold"><?= formatRupiah($t['amount']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-trophy"></i>
                            <p>Belum ada transaksi pemasukan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card animated" style="animation-delay: 0.95s">
                    <div class="report-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Transaksi Pengeluaran Terbesar
                    </div>
                    <?php if (count($top_expense_trans) > 0): ?>
                        <div class="table-responsive">
                            <table class="table-report table table-sm">
                                <thead>
                                    <tr><th>Tanggal</th><th>Deskripsi</th><th>Jumlah</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_expense_trans as $t): ?>
                                    <tr>
                                        <td><?= formatDate($t['transaction_date']) ?></td>
                                        <td><?= htmlspecialchars($t['description']) ?></td>
                                        <td class="text-danger fw-bold"><?= formatRupiah($t['amount']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Belum ada transaksi pengeluaran</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Account Breakdown -->
        <?php if ($report_type == 'monthly' && isset($account_breakdown) && count($account_breakdown) > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="report-card animated" style="animation-delay: 1.0s">
                    <div class="report-title">
                        <i class="fas fa-wallet"></i>
                        Breakdown per Akun
                    </div>
                    <div class="table-responsive">
                        <table class="table-report table">
                            <thead>
                                <tr><th>Akun</th><th>Pemasukan</th><th>Pengeluaran</th><th>Saldo</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($account_breakdown as $acc): 
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($acc['name']) ?></strong></td>
                                    <td class="text-success"><?= formatRupiah($acc['income']) ?></td>
                                    <td class="text-danger"><?= formatRupiah($acc['expense']) ?></td>
                                    <td class="fw-bold">
                                        <?= $acc['display_balance'] ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transaction List -->
        <div class="report-card animated" style="animation-delay: 1.05s">
            <div class="report-title">
                <i class="fas fa-list"></i>
                Daftar Transaksi Detail
            </div>
            <div class="table-responsive">
                <table class="table-report table" id="transactionTable">
                    <thead>
                        <tr><th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th>Akun</th><th>Tipe</th><th>Jumlah</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= formatDate($t['transaction_date']) ?></td>
                                <td><?= htmlspecialchars($t['description']) ?></td>
                                <td><?= htmlspecialchars($t['category_name']) ?></td>
                                <td><?= htmlspecialchars($t['account_name']) ?></td>
                                <td>
                                    <span class="<?= $t['type'] == 'income' ? 'badge-income' : 'badge-expense' ?>">
                                        <i class="fas fa-<?= $t['type'] == 'income' ? 'arrow-up' : 'arrow-down' ?>"></i>
                                        <?= $t['type'] == 'income' ? 'Pemasukan' : 'Pengeluaran' ?>
                                    </span>
                                </td>
                                <td class="<?= $t['type'] == 'income' ? 'text-success' : 'text-danger' ?> fw-bold">
                                    <?= formatRupiah($t['amount']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-receipt"></i>
                                        <p>Belum ada transaksi</p>
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

<!-- Include Required Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Initialize Charts
    <?php if ($report_type == 'monthly'): ?>
    // Monthly chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const dailyData = <?= json_encode($daily_data) ?>;
    const labels = dailyData.map(d => {
        const date = new Date(d.transaction_date);
        return date.getDate();
    });
    const incomeData = dailyData.map(d => d.income);
    const expenseData = dailyData.map(d => d.expense);
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: incomeData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Pengeluaran',
                    data: expenseData,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 12, weight: '600' },
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
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
                        font: { size: 11 }
                    },
                    grid: { color: '#e5e7eb' }
                },
                x: {
                    ticks: { font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
    <?php else: ?>
    // Yearly chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const monthlyData = <?= json_encode($monthly_data) ?>;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const monthlyIncome = monthlyData.map(d => d.income);
    const monthlyExpense = monthlyData.map(d => d.expense);
    
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: monthlyIncome,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 8
                },
                {
                    label: 'Pengeluaran',
                    data: monthlyExpense,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 8
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 12, weight: '600' },
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
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
                        font: { size: 11 }
                    },
                    grid: { color: '#e5e7eb' }
                },
                x: {
                    ticks: { font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (count($income_categories) > 0): ?>
    // Income category chart
    const incomeCtx = document.getElementById('incomeChart').getContext('2d');
    const incomeCategories = <?= json_encode(array_slice(array_values($income_categories), 0, 5)) ?>;
    new Chart(incomeCtx, {
        type: 'doughnut',
        data: {
            labels: incomeCategories.map(c => c.name),
            datasets: [{
                data: incomeCategories.map(c => c.total),
                backgroundColor: ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { size: 11 },
                        boxWidth: 10,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = ((value / total) * 100).toFixed(1);
                            return `${context.label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
    <?php endif; ?>
    
    <?php if (count($expense_categories) > 0): ?>
    // Expense category chart
    const expenseCtx = document.getElementById('expenseChart').getContext('2d');
    const expenseCategories = <?= json_encode(array_slice(array_values($expense_categories), 0, 5)) ?>;
    new Chart(expenseCtx, {
        type: 'doughnut',
        data: {
            labels: expenseCategories.map(c => c.name),
            datasets: [{
                data: expenseCategories.map(c => c.total),
                backgroundColor: ['#ef4444', '#f87171', '#fca5a5', '#fecaca', '#fee2e2'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { size: 11 },
                        boxWidth: 10,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = ((value / total) * 100).toFixed(1);
                            return `${context.label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
    <?php endif; ?>
    
    // Export to Excel
    function exportToExcel() {
        const params = new URLSearchParams(window.location.search);
        const type = params.get('type') || 'monthly';
        const month = params.get('month') || <?= $current_month ?>;
        const year = params.get('year') || <?= $current_year ?>;
        
        window.location.href = `export_excel.php?type=${type}&month=${month}&year=${year}`;
    }

    // Export to PDF
    function exportToPDF() {
        const params = new URLSearchParams(window.location.search);
        const type = params.get('type') || 'monthly';
        const month = params.get('month') || <?= $current_month ?>;
        const year = params.get('year') || <?= $current_year ?>;
        
        window.open(`export_pdf.php?type=${type}&month=${month}&year=${year}`, '_blank');
    }
    
    // Helper functions
    function formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>