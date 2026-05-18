<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($page_title)) {
    $page_title = APP_NAME;
}

// Set current page untuk sidebar
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - <?= $page_title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 24px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #7e7e7e 0%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            display: inline-block;
        }

        .user-info-sidebar {
            text-align: center;
        }

        .user-avatar-sidebar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #34d399 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 32px;
            font-weight: 600;
        }

        .user-name-sidebar {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .user-email-sidebar {
            font-size: 12px;
            color: #6b7280;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }

        .nav-link-sidebar i {
            width: 24px;
            font-size: 18px;
        }

        .nav-link-sidebar:hover {
            background: linear-gradient(135deg, #667eea10, #764ba210);
            color: #667eea;
            padding-left: 28px;
        }

        .nav-link-sidebar.active {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
            border-right: 3px solid #667eea;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 20px;
            transition: all 0.3s ease;
        }

        /* Mobile Top Nav */
        .mobile-header {
            display: none;
            background: #fff;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
            justify-content: space-between;
            align-items: center;
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 24px;
            color: #4b5563;
            cursor: pointer;
        }

        /* Stats Cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .stat-title {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .stat-change {
            font-size: 12px;
            color: #10b981;
        }

        /* Chart Cards */
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            height: 100%;
        }

        .card-title-custom {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title-custom i {
            color: #667eea;
        }

        /* Transaction Table */
        .transactions-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
        }

        .card-header-custom {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-custom {
            margin-bottom: 0;
        }

        .table-custom thead th {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 16px 20px;
            font-weight: 600;
            color: #4b5563;
        }

        .table-custom tbody td {
            padding: 16px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }

        .transaction-type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .income-badge {
            background: #d1fae5;
            color: #059669;
        }

        .expense-badge {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
            border: none;
        }

        .btn-delete:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* Category Items */
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .category-name {
            font-weight: 500;
            color: #4b5563;
        }

        .category-amount {
            font-weight: 700;
            color: #dc2626;
        }

        /* Welcome Section */
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 30px;
        }

        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 260px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .stat-value {
                font-size: 22px;
            }
            
            .welcome-title {
                font-size: 22px;
            }

            .welcome-card {
                padding: 20px;
            }
            
            .table-custom {
                font-size: 12px;
            }
            
            .table-custom thead th,
            .table-custom tbody td {
                padding: 12px;
            }
        }

        /* Animation */
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
            animation: fadeInUp 0.6s ease-out;
        }

        /* Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 5px;
        }

        .text-success {
            color: #10b981 !important;
        }

        .text-danger {
            color: #ef4444 !important;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="mobile-header">
        <div class="logo-mobile" style="font-weight: 800; font-size: 20px; color: #667eea;">
            <i class="fas fa-chart-line"></i> <?= APP_NAME ?>
        </div>
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>