<?php
// Sidebar Navigation
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}
?>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="sidebar">
    <div class="sidebar-header">
        <a href="/keuangan/dashboard.php" class="logo">
            <i class="fas fa-chart-line"></i>
            <span><?= APP_NAME ?></span>
        </a>
    </div>
    
    <div class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="/keuangan/dashboard.php" class="nav-link-sidebar <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" title="Dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/keuangan/pages/transactions/index.php" class="nav-link-sidebar <?= strpos($current_page, 'transactions') !== false ? 'active' : '' ?>" title="Transaksi">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/keuangan/pages/emergency_fund/index.php" class="nav-link-sidebar <?= strpos($current_page, 'emergency') !== false ? 'active' : '' ?>" title="Dana Darurat">
                    <i class="fas fa-shield-alt"></i>
                    <span>Dana Darurat</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/keuangan/pages/installments/index.php" class="nav-link-sidebar <?= strpos($current_page, 'installments') !== false || strpos($current_page, 'loans') !== false ? 'active' : '' ?>" title="Cicilan">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Cicilan</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/keuangan/pages/accounts/index.php" class="nav-link-sidebar <?= strpos($current_page, 'accounts') !== false ? 'active' : '' ?>" title="Akun">
                    <i class="fas fa-wallet"></i>
                    <span>Akun</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/keuangan/pages/budgets/index.php" class="nav-link-sidebar <?= strpos($current_page, 'budgets') !== false ? 'active' : '' ?>" title="Anggaran">
                    <i class="fas fa-chart-pie"></i>
                    <span>Anggaran</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/keuangan/pages/categories/index.php" class="nav-link-sidebar <?= strpos($current_page, 'categories') !== false ? 'active' : '' ?>" title="Kategori">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/keuangan/pages/assets/index.php" class="nav-link-sidebar <?= strpos($current_page, 'assets') !== false ? 'active' : '' ?>" title="Portofolio">
                    <i class="fas fa-chart-line"></i>
                    <span>Portofolio</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/keuangan/pages/reports/index.php" class="nav-link-sidebar <?= strpos($current_page, 'reports') !== false ? 'active' : '' ?>" title="Laporan">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- User Profile at bottom -->
    <div class="user-info-sidebar">
        <div class="user-avatar-sidebar">
            <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
        </div>
        <div class="user-details">
            <div class="user-name-sidebar"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
            <div class="user-email-sidebar"><?= htmlspecialchars($_SESSION['user_email']) ?></div>
        </div>
    </div>

    <!-- Profile & Logout Section -->
    <div class="sidebar-footer" style="padding-bottom: 10px;">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="/keuangan/pages/profile/index.php" class="nav-link-sidebar <?= $current_page == 'profile.php' ? 'active' : '' ?>" title="Profil">
                    <i class="fas fa-user-circle"></i>
                    <span>Profil Saya</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/keuangan/logout.php" class="nav-link-sidebar text-danger" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>