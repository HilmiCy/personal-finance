<?php
// Sidebar Navigation
// Pastikan variabel $current_page sudah didefinisikan di setiap halaman
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-chart-line"></i> <?= APP_NAME ?>
        </div>
        <div class="user-info-sidebar">
            <div class="user-avatar-sidebar">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
            <div class="user-name-sidebar">
                <?= htmlspecialchars($_SESSION['user_name']) ?>
            </div>
            <div class="user-email-sidebar">
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </div>
        </div>
    </div>
    
    <div class="sidebar-nav">
        <ul class="nav-menu">
            <!-- 1. DASHBOARD - Pusat Informasi -->
            <li class="nav-item">
                <a href="/keuangan/dashboard.php" class="nav-link-sidebar <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- 2. TRANSAKSI - Aktivitas Paling Sering -->
            <li class="nav-item">
                <a href="/keuangan/pages/transactions/index.php" class="nav-link-sidebar <?= strpos($current_page, 'transactions') !== false ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            
            <!-- 3. DANA DARURAT - PRIORITAS UTAMA (TAMBAHAN BARU) -->
            <li class="nav-item">
                <a href="/keuangan/pages/emergency_fund/index.php" class="nav-link-sidebar <?= strpos($current_page, 'emergency') !== false ? 'active' : '' ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Dana Darurat</span>
                </a>
            </li>
            
            <!-- 4. CICILAN - PRIORITAS UTAMA (TAMBAHAN BARU) -->
            <li class="nav-item">
                <a href="/keuangan/pages/installments/index.php" class="nav-link-sidebar <?= strpos($current_page, 'loans') !== false ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Cicilan</span>
                </a>
            </li>
            
            <!-- 5. AKUN - Kelola Sumber Dana -->
            <li class="nav-item">
                <a href="/keuangan/pages/accounts/index.php" class="nav-link-sidebar <?= strpos($current_page, 'accounts') !== false ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Akun</span>
                </a>
            </li>
            
            <!-- 6. ANGGARAN - Kontrol Pengeluaran -->
            <li class="nav-item">
                <a href="/keuangan/pages/budgets/index.php" class="nav-link-sidebar <?= strpos($current_page, 'budgets') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Anggaran</span>
                </a>
            </li>
            
            <!-- 7. KATEGORI - Pengelompokan Transaksi -->
            <li class="nav-item">
                <a href="/keuangan/pages/categories/index.php" class="nav-link-sidebar <?= strpos($current_page, 'categories') !== false ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
            </li>
            
            <!-- 8. PORTOFOLIO - Investasi (Setelah Dana Darurat & Cicilan Aman) -->
            <li class="nav-item">
                <a href="/keuangan/pages/assets/index.php" class="nav-link-sidebar <?= strpos($current_page, 'assets') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Portofolio</span>
                </a>
            </li>
            
            <!-- 9. LAPORAN - Analisis Berkala -->
            <li class="nav-item">
                <a href="/keuangan/pages/reports/index.php" class="nav-link-sidebar <?= strpos($current_page, 'reports') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
            </li>
            
            <!-- 10. PROFIL - Pengaturan Akun -->
            <li class="nav-item">
                <a href="/keuangan/pages/profile/index.php" class="nav-link-sidebar <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </a>
            </li>
            
            <!-- 11. LOGOUT - Keluar Aplikasi -->
            <li class="nav-item">
                <a href="/keuangan/logout.php" class="nav-link-sidebar">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>