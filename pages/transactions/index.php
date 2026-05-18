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
    /* ========== LAYOUT UTAMA ========== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        overflow-x: hidden;
        width: 100%;
        position: relative;
    }
    
    .wrapper {
        display: flex;
        width: 100%;
        align-items: stretch;
        overflow-x: hidden;
    }
    
    #sidebar {
        min-width: 250px;
        max-width: 250px;
        width: 250px;
        transition: all 0.3s;
        flex-shrink: 0;
        background: #2c3e50;
        color: #fff;
    }
    
    #content, .main-content {
        width: calc(100% - 250px);
        min-height: 100vh;
        transition: all 0.3s;
        overflow-x: hidden;
        flex: 1;
        background: #f0f2f5;
    }
    
    .container-fluid {
        width: 100%;
        max-width: 100%;
        padding: 20px;
        margin: 0;
        overflow-x: hidden;
    }
    
    /* ========== CARD STYLES ========== */
    .card {
        border-radius: 20px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        margin-bottom: 20px;
        overflow: hidden;
        background: white;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
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
        color: #1f2937;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 0.9rem;
        color: #6b7280;
    }
    
    /* Button Styles */
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-filter {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-reset {
        background: #f3f4f6;
        color: #4b5563;
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-reset:hover {
        background: #e5e7eb;
        color: #1f2937;
        text-decoration: none;
    }
    
    .btn-edit {
        background: #e0e7ff;
        color: #4f46e5;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .btn-edit:hover {
        background: #4f46e5;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-delete {
        background: #fee2e2;
        color: #dc2626;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        margin-left: 5px;
    }
    
    .btn-delete:hover {
        background: #dc2626;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Badge Styles */
    .badge-income {
        background: #d1fae5;
        color: #059669;
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
        color: #dc2626;
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
        color: #4f46e5;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Summary Cards */
    .summary-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        text-align: center;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .summary-label {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .summary-value {
        font-size: 24px;
        font-weight: 800;
    }
    
    .summary-income {
        color: #10b981;
    }
    
    .summary-expense {
        color: #ef4444;
    }
    
    .summary-transfer {
        color: #4f46e5;
    }
    
    .filter-section {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .filter-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-control, .form-select {
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        padding: 10px 14px;
        transition: all 0.2s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }
    
    .form-label {
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 8px;
    }
    
    .transactions-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .card-header-custom {
        padding: 20px 24px;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .card-title-custom {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1e293b;
    }
    
    .table-custom {
        margin-bottom: 0;
    }
    
    .table-custom thead {
        background: #f8f9fa;
    }
    
    .table-custom thead th {
        padding: 15px 16px;
        font-weight: 600;
        color: #4b5563;
        border-bottom: 2px solid #e5e7eb;
        font-size: 14px;
    }
    
    .table-custom tbody td {
        padding: 16px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .table-custom tbody tr:hover {
        background: #f9fafb;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
    }
    
    /* Modal Styles */
    .modal-content-custom {
        background: white;
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }
    
    .modal-header-custom {
        border-bottom: 1px solid #e5e7eb;
        padding: 20px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .modal-header-custom .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .modal-body-custom {
        padding: 24px;
    }
    
    .modal-footer-custom {
        border-top: 1px solid #e5e7eb;
        padding: 20px 24px;
        background: #f9fafb;
    }
    
    .btn-secondary-custom {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 600;
        color: #4b5563;
        transition: all 0.3s ease;
    }
    
    .btn-secondary-custom:hover {
        background: #e5e7eb;
        color: #1f2937;
    }
    
    .transaction-type {
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .income-badge { color: #10b981; }
    .expense-badge { color: #ef4444; }
    .transfer-badge { color: #4f46e5; }
    
    /* Pagination Styles */
    .pagination-custom {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        margin-top: 20px;
        padding: 20px 24px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    
    .pagination-custom .page-link-custom {
        padding: 8px 14px;
        border-radius: 10px;
        background: white;
        color: #4b5563;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .pagination-custom .page-link-custom:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
        transform: translateY(-2px);
    }
    
    .pagination-custom .active .page-link-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
    }
    
    .pagination-custom .disabled .page-link-custom {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .pagination-info {
        color: #6b7280;
        font-size: 14px;
        margin: 0;
    }
    
    .per-page-select {
        width: auto;
        padding: 8px 12px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: white;
        font-size: 14px;
        cursor: pointer;
    }
    
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
    
    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes iconPop {
        0% {
            transform: scale(0);
            opacity: 0;
        }
        80% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .animated {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    
    /* SweetAlert2 Professional Style */
    .swal2-popup {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 24px !important;
        padding: 2em !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: fadeInScale 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .swal2-title {
        color: #1f2937 !important;
        font-weight: 700 !important;
        font-size: 1.5rem !important;
    }
    
    .swal2-html-container {
        color: #4b5563 !important;
        font-size: 0.95rem !important;
    }
    
    .swal2-confirm {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        border: none !important;
        transition: all 0.3s ease !important;
    }
    
    .swal2-confirm:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
    }
    
    .swal2-cancel {
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        background: rgba(107, 114, 128, 0.1) !important;
        color: #6b7280 !important;
        border: 1px solid rgba(107, 114, 128, 0.2) !important;
        transition: all 0.3s ease !important;
    }
    
    .swal2-cancel:hover {
        background: rgba(107, 114, 128, 0.2) !important;
        transform: translateY(-2px) !important;
    }
    
    .swal2-icon {
        animation: iconPop 0.5s ease !important;
    }
    
    .swal2-icon.swal2-warning {
        border-color: #f59e0b !important;
        color: #f59e0b !important;
    }
    
    .swal2-icon.swal2-success {
        border-color: #10b981 !important;
    }
    
    .swal2-icon.swal2-error {
        border-color: #ef4444 !important;
    }
    
    .swal2-icon.swal2-question {
        border-color: #667eea !important;
        color: #667eea !important;
    }
    
    .swal2-loader {
        border-color: #667eea !important;
        border-top-color: transparent !important;
        animation: spin 0.8s linear infinite !important;
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 768px) {
        #sidebar {
            margin-left: -250px;
            position: fixed;
            z-index: 1000;
            height: 100vh;
        }
        
        #sidebar.active {
            margin-left: 0;
        }
        
        #content, .main-content {
            width: 100%;
        }
        
        .container-fluid {
            padding: 15px;
        }

        .summary-card {
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .table-custom thead {
            display: none;
        }
        
        .table-custom tbody td {
            display: block;
            padding: 12px 16px;
            padding-left: 45%;
            position: relative;
            text-align: right;
            border-bottom: none;
        }
        
        .table-custom tbody td:before {
            content: attr(data-label);
            position: absolute;
            left: 16px;
            width: calc(45% - 20px);
            font-weight: 600;
            color: #6b7280;
            text-align: left;
            font-size: 13px;
        }
        
        .table-custom tbody tr {
            border-bottom: 1px solid #e5e7eb;
            display: block;
            margin-bottom: 15px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-custom tbody tr:last-child {
            margin-bottom: 0;
        }

        /* Adjust badges in mobile table */
        .badge, .badge-income, .badge-expense, .badge-transfer, .transaction-type {
            justify-content: flex-end;
        }
        
        .summary-value {
            font-size: 18px;
        }
        
        .pagination-custom {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .card-header-custom {
            flex-direction: column;
            align-items: flex-start;
        }

        .pagination-info {
            font-size: 12px;
        }
    }
    
    /* ========== DETAIL MODAL GLASSMORPHISM ========== */
    .modal-detail {
        max-width: 500px;
    }
    
    .modal-content-glass {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 32px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        overflow: hidden;
    }
    
    .modal-header-glass {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 28px 28px;
        border-bottom: none;
        position: relative;
    }
    
    .modal-header-glass .modal-title {
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: white;
        margin: 0;
    }
    
    .modal-header-glass .btn-close-white {
        filter: brightness(0) invert(1);
        opacity: 0.9;
        transition: all 0.3s ease;
        background: transparent;
    }
    
    .modal-header-glass .btn-close-white:hover {
        opacity: 1;
        transform: rotate(90deg);
    }
    
    .modal-body-glass {
        padding: 28px;
        background: rgba(248, 250, 252, 0.6);
    }
    
    .detail-card {
        background: white;
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .detail-card:last-child {
        margin-bottom: 0;
    }
    
    .detail-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .detail-label i {
        font-size: 13px;
        color: #667eea;
    }
    
    .detail-amount {
        font-size: 42px;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin-top: 8px;
    }
    
    .detail-amount-income {
        color: #10b981;
    }
    
    .detail-amount-expense {
        color: #ef4444;
    }
    
    .detail-amount-transfer {
        color: #6366f1;
    }
    
    .detail-icon {
        width: 52px;
        height: 52px;
        border-radius: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.15);
    }
    
    .detail-icon-income {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .detail-icon-expense {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .detail-icon-transfer {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
    }
    
    .detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 13px;
    }
    
    .detail-badge-income {
        background: #d1fae5;
        color: #065f46;
    }
    
    .detail-badge-expense {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .detail-badge-transfer {
        background: #e0e7ff;
        color: #3730a3;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .info-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .info-row:first-child {
        padding-top: 0;
    }
    
    .info-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .info-label i {
        font-size: 14px;
        width: 20px;
        color: #667eea;
    }
    
    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        text-align: right;
        word-break: break-word;
        max-width: 60%;
    }
    
    .modal-footer-glass {
        background: white;
        border-top: 1px solid #f1f5f9;
        padding: 20px 28px;
        gap: 12px;
    }
    
    .btn-glass {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 10px 24px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        color: #475569;
    }
    
    .btn-glass:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
        color: #1e293b;
    }
    
    .btn-edit-glass {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 10px 28px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .btn-edit-glass:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-detail {
        background: #e0f2fe;
        color: #0284c7;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        margin-right: 5px;
    }
    
    .btn-detail:hover {
        background: #0284c7;
        color: white;
        transform: translateY(-2px);
    }
</style>

<div id="content" class="main-content">
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
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        <a href="index.php" class="btn-reset">
                            <i class="fas fa-redo"></i> Reset
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
                                        <strong><?= formatDate($trans['transaction_date']) ?></strong>
                                    </td>
                                    <td data-label="Deskripsi">
                                        <strong><?= htmlspecialchars($trans['description']) ?></strong>
                                    </td>
                                    <td data-label="Kategori">
                                        <span class="badge" style="background: #f3f4f6; color: #4b5563; padding: 5px 10px;">
                                            <i class="fas fa-tag"></i> <?= htmlspecialchars($trans['category_name'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td data-label="Akun">
                                        <span class="badge" style="background: #e0e7ff; color: #4f46e5; padding: 5px 10px;">
                                            <i class="fas fa-wallet"></i> <?= htmlspecialchars($trans['account_name']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Tipe">
                                        <?php if ($trans['type'] == 'income'): ?>
                                            <span class="badge-income">
                                                <i class="fas fa-arrow-up"></i> Pemasukan
                                            </span>
                                        <?php elseif ($trans['type'] == 'expense'): ?>
                                            <span class="badge-expense">
                                                <i class="fas fa-arrow-down"></i> Pengeluaran
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-transfer">
                                                <i class="fas fa-exchange-alt"></i> Transfer
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Jumlah">
                                        <?php if ($trans['type'] == 'income'): ?>
                                            <span class="transaction-type income-badge">
                                                <i class="fas fa-plus-circle"></i>
                                                <?= formatRupiah($trans['amount']) ?>
                                            </span>
                                        <?php elseif ($trans['type'] == 'expense'): ?>
                                            <span class="transaction-type expense-badge">
                                                <i class="fas fa-minus-circle"></i>
                                                <?= formatRupiah($trans['amount']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="transaction-type transfer-badge">
                                                <i class="fas fa-exchange-alt"></i>
                                                <?= formatRupiah($trans['amount']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Aksi">
                                        <button class="btn-detail" onclick="openDetailModal(<?= $trans['id'] ?>)">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                        <button class="btn-edit" onclick="openEditModal(<?= $trans['id'] ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" onclick="deleteTransaction(<?= $trans['id'] ?>)">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
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

<!-- MODAL DETAIL TRANSAKSI GLASSMORPHISM -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-detail">
        <div class="modal-content modal-content-glass">
            <div class="modal-header-glass d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="detail-icon" id="detail_icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h5 class="modal-title">Detail Transaksi</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body-glass">
                <!-- Header Amount -->
                <div class="detail-card text-center">
                    <div class="detail-label justify-content-center">
                        <i class="fas fa-chart-line"></i>
                        <span>TOTAL TRANSAKSI</span>
                    </div>
                    <div class="detail-amount" id="detail_amount">
                        Rp 0
                    </div>
                </div>
                
                <!-- Info Detail -->
                <div class="detail-card">
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-calendar-alt"></i> Tanggal
                        </div>
                        <div class="info-value" id="detail_date">-</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-tag"></i> Tipe
                        </div>
                        <div class="info-value" id="detail_type_badge">-</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-folder"></i> Kategori
                        </div>
                        <div class="info-value" id="detail_category">-</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-wallet"></i> Akun
                        </div>
                        <div class="info-value" id="detail_account">-</div>
                    </div>
                    <div class="info-row" id="detail_transfer_row" style="display: none;">
                        <div class="info-label">
                            <i class="fas fa-exchange-alt"></i> Transfer ke
                        </div>
                        <div class="info-value" id="detail_transfer_account">-</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-align-left"></i> Deskripsi
                        </div>
                        <div class="info-value" id="detail_description">-</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-clock"></i> Dibuat
                        </div>
                        <div class="info-value" id="detail_created_at">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer-glass">
                <button type="button" class="btn-glass" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Tutup
                </button>
                <button type="button" class="btn-edit-glass" id="detail_edit_btn">
                    <i class="fas fa-edit me-2"></i> Edit
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
    
    // Open Detail Modal
    function openDetailModal(id) {
        console.log("Open detail modal called with ID:", id);
        
        if (!id) {
            Swal.fire({
                title: 'Error!',
                text: 'ID transaksi tidak valid',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        Swal.fire({
            title: 'Loading...',
            text: 'Mengambil detail transaksi',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch(`get_detail.php?id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                Swal.close();
                console.log("Detail data received:", data);
                
                if (data.success && data.transaction) {
                    const trans = data.transaction;
                    
                    // Format amount
                    const formattedAmount = formatRupiah(trans.amount);
                    document.getElementById('detail_amount').innerHTML = formattedAmount;
                    document.getElementById('detail_date').innerHTML = trans.transaction_date || '-';
                    document.getElementById('detail_category').innerHTML = trans.category_name || '-';
                    document.getElementById('detail_account').innerHTML = trans.account_name || '-';
                    document.getElementById('detail_description').innerHTML = trans.description || '-';
                    document.getElementById('detail_created_at').innerHTML = trans.created_at || '-';
                    
                    // Set type badge dan icon
                    const typeBadge = document.getElementById('detail_type_badge');
                    const detailIcon = document.getElementById('detail_icon');
                    const amountSpan = document.getElementById('detail_amount');
                    
                    if (trans.type === 'income') {
                        typeBadge.innerHTML = '<span class="detail-badge detail-badge-income"><i class="fas fa-arrow-up"></i> Pemasukan</span>';
                        detailIcon.className = 'detail-icon detail-icon-income';
                        detailIcon.innerHTML = '<i class="fas fa-arrow-up"></i>';
                        amountSpan.className = 'detail-amount detail-amount-income';
                    } else if (trans.type === 'expense') {
                        typeBadge.innerHTML = '<span class="detail-badge detail-badge-expense"><i class="fas fa-arrow-down"></i> Pengeluaran</span>';
                        detailIcon.className = 'detail-icon detail-icon-expense';
                        detailIcon.innerHTML = '<i class="fas fa-arrow-down"></i>';
                        amountSpan.className = 'detail-amount detail-amount-expense';
                    } else {
                        typeBadge.innerHTML = '<span class="detail-badge detail-badge-transfer"><i class="fas fa-exchange-alt"></i> Transfer</span>';
                        detailIcon.className = 'detail-icon detail-icon-transfer';
                        detailIcon.innerHTML = '<i class="fas fa-exchange-alt"></i>';
                        amountSpan.className = 'detail-amount detail-amount-transfer';
                    }
                    
                    // Tampilkan transfer account jika ada
                    const transferRow = document.getElementById('detail_transfer_row');
                    const transferAccount = document.getElementById('detail_transfer_account');
                    
                    if (trans.type === 'transfer' && trans.to_account_id) {
                        transferRow.style.display = 'flex';
                        // Fetch account name untuk transfer
                        fetch(`get_account_name.php?id=${trans.to_account_id}`)
                            .then(res => res.json())
                            .then(accData => {
                                if (accData.success && transferAccount) {
                                    transferAccount.innerHTML = accData.account_name;
                                } else {
                                    transferAccount.innerHTML = 'Akun Tujuan';
                                }
                            })
                            .catch(() => {
                                transferAccount.innerHTML = 'Akun Tujuan';
                            });
                    } else {
                        transferRow.style.display = 'none';
                    }
                    
                    // Set edit button untuk langsung edit
                    const editBtn = document.getElementById('detail_edit_btn');
                    if (editBtn) {
                        editBtn.onclick = function() {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('detailModal'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                openEditModal(id);
                            }, 300);
                        };
                    }
                    
                    // Show modal
                    const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
                    detailModal.show();
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: data.message || 'Gagal mengambil detail transaksi',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat mengambil detail: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    }
    
    function formatRupiah(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
    }
</script>

<?php include '../../includes/footer.php'; ?>