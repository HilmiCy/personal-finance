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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(145deg, #e0f2fe 0%, #fef3c7 50%, #fce7f3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Decorative bubbles */
        .bg-bubble {
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            filter: blur(60px);
            pointer-events: none;
        }

        .bubble-1 {
            width: 400px;
            height: 400px;
            top: -200px;
            left: -200px;
            background: #fbbf24;
        }

        .bubble-2 {
            width: 500px;
            height: 500px;
            bottom: -250px;
            right: -250px;
            background: #34d399;
        }

        .bubble-3 {
            width: 300px;
            height: 300px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #60a5fa;
            opacity: 0.3;
        }

        /* Main Container */
        .logout-container {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }

        .logout-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            padding: 48px 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            text-align: center;
        }

        .logout-card:hover {
            transform: translateY(-4px);
        }

        /* Icon */
        .icon-badge {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.3);
        }

        .icon-badge i {
            font-size: 36px;
            color: white;
        }

        /* User Avatar */
        .user-avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .user-avatar i {
            font-size: 32px;
            color: white;
        }

        .user-name {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .user-email {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 24px;
        }

        .logout-card h2 {
            font-size: 24px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .logout-message {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-logout {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);
        }

        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        /* Loading Animation Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(5, 150, 105, 0.95);
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
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            70% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .wave-spinner {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            position: relative;
        }

        .wave-spinner i {
            position: absolute;
            font-size: 36px;
            color: white;
            animation: waveFloat 1s ease-in-out infinite;
        }

        .wave-spinner i:nth-child(1) {
            top: 20%;
            left: 20%;
            animation-delay: 0s;
        }

        .wave-spinner i:nth-child(2) {
            top: 20%;
            right: 20%;
            animation-delay: 0.2s;
        }

        .wave-spinner i:nth-child(3) {
            bottom: 20%;
            left: 20%;
            animation-delay: 0.4s;
        }

        .wave-spinner i:nth-child(4) {
            bottom: 20%;
            right: 20%;
            animation-delay: 0.6s;
        }

        @keyframes waveFloat {
            0%, 100% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            50% {
                transform: translateY(-15px) scale(1.1);
                opacity: 0.7;
            }
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
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .goodbye-circle i {
            font-size: 50px;
            color: #10b981;
        }

        .loading-card h3 {
            color: white;
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .loading-card p {
            color: rgba(255, 255, 255, 0.9);
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
            background: white;
            border-radius: 50%;
            animation: dotPulse 1.2s ease-in-out infinite;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes dotPulse {
            0%, 100% {
                transform: scale(0.5);
                opacity: 0.5;
            }
            50% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .logout-card {
                padding: 32px 24px;
            }

            .button-group {
                flex-direction: column;
                gap: 12px;
            }

            .logout-card h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-bubble bubble-1"></div>
    <div class="bg-bubble bubble-2"></div>
    <div class="bg-bubble bubble-3"></div>

    <div class="logout-container">
        <div class="logout-card">
            <div class="icon-badge">
                <i class="fas fa-sign-out-alt"></i>
            </div>

            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
            <div class="user-email"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>

            <h2>Yakin ingin keluar? 😢</h2>
            <p class="logout-message">
                Kamu akan keluar dari akun ini. Jangan khawatir, semua data keuanganmu tetap aman. <br>
                Sampai jumpa kembali ya! 👋
            </p>

            <div class="button-group">
                <a href="?cancel=1" class="btn btn-cancel">
                    <i class="fas fa-arrow-left"></i> Batal
                </a>
                <a href="?confirm=yes" class="btn btn-logout" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Keluar
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