<?php
require_once 'config/config.php';
require_once 'config/session.php';

// Jika sudah tidak ada session, redirect ke login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Logout - Keuangan Pribadi';

// Proses logout jika dikonfirmasi
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Hapus semua session
    $_SESSION = array();
    
    // Hapus session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Hancurkan session
    session_destroy();
    
    // Redirect ke login dengan pesan sukses via session flash
    session_start();
    $_SESSION['logout_success'] = 'Anda berhasil keluar. Sampai jumpa kembali! 👋';
    session_write_close();
    
    header('Location: login.php');
    exit;
}

// Jika batal, kembali ke dashboard
if (isset($_GET['cancel'])) {
    header('Location: dashboard.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Pengguna';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= APP_NAME ?> - Konfirmasi Keluar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #f8fafd;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at top, rgba(66, 133, 244, 0.05), transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* Main Container */
        .logout-container {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
        }

        .logout-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 32px;
            padding: 48px 40px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.08);
            border: 1px solid rgba(232, 234, 237, 0.6);
            transition: all 0.4s cubic-bezier(.22,1,.36,1);
            text-align: center;
        }

        .logout-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 30px 70px rgba(0,0,0,0.10);
        }

        /* Icon */
        .icon-badge {
            width: 72px;
            height: 72px;
            background: #f1f3f4;
            border: 1px solid #e8eaed;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .icon-badge i {
            font-size: 28px;
            color: #ea4335;
        }

        /* User Info Section */
        .user-section {
            background: #f8fafd;
            border: 1px solid #e8eaed;
            padding: 24px;
            border-radius: 24px;
            margin-bottom: 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .user-avatar {
            width: 64px;
            height: 64px;
            background: #4285f4;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            color: white;
            font-size: 28px;
            font-weight: 700;
        }

        .user-name {
            font-size: 16px;
            font-weight: 700;
            color: #202124;
            margin-bottom: 2px;
        }

        .user-email {
            font-size: 13px;
            color: #5f6368;
        }

        .logout-card h2 {
            font-size: 24px;
            font-weight: 700;
            color: #202124;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }

        .logout-message {
            color: #5f6368;
            font-size: 14px;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(.22,1,.36,1);
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-logout {
            background: #202124;
            color: white;
        }

        .btn-logout:hover {
            background: #2d2f33;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid #e8eaed;
            color: #5f6368;
        }

        .btn-cancel:hover {
            background: #f1f3f4;
            color: #202124;
            border-color: #5f6368;
        }

        /* Loading Animation Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(32, 33, 36, 0.95);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-card {
            text-align: center;
            animation: bounceIn 0.5s ease;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.8); opacity: 0; }
            70% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }

        .wave-spinner {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            position: relative;
        }

        .wave-spinner i {
            position: absolute;
            font-size: 32px;
            color: #8ab4f8;
            animation: waveFloat 1s ease-in-out infinite;
        }

        .wave-spinner i:nth-child(1) { top: 20%; left: 20%; animation-delay: 0s; }
        .wave-spinner i:nth-child(2) { top: 20%; right: 20%; animation-delay: 0.2s; }
        .wave-spinner i:nth-child(3) { bottom: 20%; left: 20%; animation-delay: 0.4s; }
        .wave-spinner i:nth-child(4) { bottom: 20%; right: 20%; animation-delay: 0.6s; }

        @keyframes waveFloat {
            0%, 100% { transform: translateY(0) scale(1); opacity: 1; }
            50% { transform: translateY(-15px) scale(1.1); opacity: 0.7; }
        }

        .goodbye-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: scaleSuccess 0.5s ease;
        }

        @keyframes scaleSuccess {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .goodbye-circle i {
            font-size: 50px;
            color: #34a853;
        }

        .loading-card h3 {
            color: white;
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .loading-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .loading-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            background: #8ab4f8;
            border-radius: 50%;
            animation: dotPulse 1.2s ease-in-out infinite;
        }

        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes dotPulse {
            0%, 100% { transform: scale(0.5); opacity: 0.5; }
            50% { transform: scale(1); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .logout-card {
                padding: 32px 24px;
            }

            .button-group {
                flex-direction: column;
            }

            .logout-card h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <div class="icon-badge">
                <i class="fas fa-sign-out-alt"></i>
            </div>

            <div class="user-section">
                <div class="user-avatar">
                    <?= strtoupper(substr($user_name, 0, 1)) ?>
                </div>
                <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="user-email"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
            </div>

            <h2>Yakin ingin keluar?</h2>
            <p class="logout-message">
                Kamu akan keluar dari akun ini. Jangan khawatir, semua data keuanganmu tetap aman.
            </p>

            <div class="button-group">
                <a href="?cancel=1" class="btn btn-cancel">
                    Batal
                </a>
                <a href="?confirm=yes" class="btn btn-logout" id="logoutBtn">
                    Keluar Sekarang
                </a>
            </div>
        </div>
    </div>

    <!-- Loading Animation Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-card">
            <div id="spinnerArea">
                <div class="wave-spinner">
                    <i class="fas fa-smile-wink"></i>
                    <i class="fas fa-heart"></i>
                    <i class="fas fa-coins"></i>
                    <i class="fas fa-hand-peace"></i>
                </div>
            </div>
            <div id="successArea" style="display: none;">
                <div class="goodbye-circle">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h3 id="loadingTitle">Sampai Jumpa! 👋</h3>
            <p id="loadingMessage">Terima kasih sudah menggunakan aplikasi keuangan pribadi...</p>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <script>
        const logoutBtn = document.getElementById('logoutBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const spinnerArea = document.getElementById('spinnerArea');
        const successArea = document.getElementById('successArea');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingMessage = document.getElementById('loadingMessage');

        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show loading animation
                loadingOverlay.classList.add('active');
                
                // Animate loading messages
                setTimeout(() => {
                    loadingTitle.textContent = 'Menutup sesi...';
                    loadingMessage.textContent = 'Kami simpan progres keuanganmu';
                }, 500);
                
                setTimeout(() => {
                    spinnerArea.style.display = 'none';
                    successArea.style.display = 'block';
                    loadingTitle.textContent = 'Berhasil Keluar! 🎉';
                    loadingMessage.textContent = 'Sampai jumpa lagi! Jaga keuanganmu ya';
                }, 1200);
                
                // Redirect to logout confirmation
                setTimeout(() => {
                    window.location.href = '?confirm=yes';
                }, 2000);
            });
        }
    </script>
</body>
</html>