<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'classes/Database.php';

// Cek apakah sudah login, jika ya redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = strtolower(trim($_POST['security_answer'] ?? ''));
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validasi
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($security_question) || empty($security_answer)) {
        $error = 'Yuk lengkapi dulu semua datanya!';
    } elseif (strlen($name) < 3) {
        $error = 'Nama lengkapnya minimal 3 huruf ya';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format emailnya kurang tepat, coba cek lagi';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter biar lebih aman';
    } elseif ($password !== $confirm_password) {
        $error = 'Waduh, kata sandi dan konfirmasinya nggak sama nih';
    } elseif (!$terms) {
        $error = 'Setuju dulu ya sama syarat & ketentuan kami';
    } else {
        $db = Database::getInstance()->getConnection();
        
        // Cek apakah email sudah terdaftar
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar. Coba pakai email lain atau langsung login!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru dengan pertanyaan keamanan
            $stmt = $db->prepare("INSERT INTO users (name, email, password, security_question, security_answer, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$name, $email, $hashed_password, $security_question, $security_answer])) {
                $success = 'Yeay! Akunmu berhasil dibuat. Yuk langsung login! 🎉';
                // Clear form
                $name = $email = '';
            } else {
                $error = 'Maaf, ada kendala teknis. Coba lagi ya!';
            }
        }
    }
}

$page_title = 'Daftar - Keuangan Pribadi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= APP_NAME ?> - Mulai Atur Keuanganmu | Daftar</title>
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
            background: linear-gradient(145deg, #e0f2fe 0%, #fef3c7 50%, #fce7f3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Decorative elements */
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
        .register-container {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 1;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            padding: 40px 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .register-card:hover {
            transform: translateY(-4px);
        }

        /* Header */
        .header-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .icon-badge {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.3);
        }

        .icon-badge i {
            font-size: 32px;
            color: white;
        }

        .register-card h2 {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: #9ca3af;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s ease;
            background: #f9fafb;
            outline: none;
        }

        .form-select {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 18px;
        }

        .form-input:focus, .form-select:focus {
            border-color: #10b981;
            background: white;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-input:focus + .input-icon,
        .form-select:focus + .input-icon {
            color: #10b981;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            font-size: 16px;
            padding: 0;
        }

        .password-toggle:hover {
            color: #10b981;
        }

        /* Password Strength */
        .strength-container {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .strength-bar {
            flex: 1;
            height: 4px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .strength-text {
            font-size: 11px;
            font-weight: 500;
            min-width: 80px;
        }

        /* Match Indicator */
        .match-indicator {
            font-size: 12px;
            margin-top: 6px;
            margin-left: 48px;
        }

        .info-hint {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 6px;
            margin-left: 48px;
        }

        /* Terms */
        .terms-group {
            margin-bottom: 24px;
        }

        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.4;
        }

        .checkbox-label input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #10b981;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .checkbox-label a {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
        }

        /* Button */
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 24px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
        }

        /* Divider */
        .divider {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: calc(50% - 70px);
            height: 1px;
            background: #e5e7eb;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .divider span {
            background: white;
            padding: 0 16px;
            color: #9ca3af;
            font-size: 12px;
        }

        /* Login Link */
        .login-link {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }

        .login-link a {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: #d1fae5;
            border-left: 3px solid #10b981;
            color: #059669;
        }

        .alert-error {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            color: #dc2626;
        }

        /* Loading Overlay */
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

        .celebrate-spinner {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            position: relative;
        }

        .celebrate-spinner i {
            position: absolute;
            font-size: 32px;
            color: white;
            animation: floatHappy 1s ease-in-out infinite;
        }

        .celebrate-spinner i:nth-child(1) {
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            animation-delay: 0s;
        }

        .celebrate-spinner i:nth-child(2) {
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            animation-delay: 0.25s;
        }

        .celebrate-spinner i:nth-child(3) {
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            animation-delay: 0.5s;
        }

        .celebrate-spinner i:nth-child(4) {
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            animation-delay: 0.75s;
        }

        @keyframes floatHappy {
            0%, 100% {
                transform: translateY(-50%) scale(1);
                opacity: 1;
            }
            50% {
                transform: translateY(-50%) scale(1.2);
                opacity: 0.7;
            }
        }

        .success-circle {
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

        .success-circle i {
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

        /* Shake animation */
        .shake {
            animation: shakeAnim 0.3s ease-in-out;
        }

        @keyframes shakeAnim {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .register-card {
                padding: 28px 24px;
            }
            
            .register-card h2 {
                font-size: 24px;
            }
            
            .strength-container {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="bg-bubble bubble-1"></div>
    <div class="bg-bubble bubble-2"></div>
    <div class="bg-bubble bubble-3"></div>

    <div class="register-container">
        <div class="register-card">
            <div class="header-section">
                <div class="icon-badge">
                    <i class="fas fa-smile-wink"></i>
                </div>
                <h2>Mulai Atur Keuanganmu</h2>
                <p class="subtitle">Yuk, daftar dulu gratis! ✨</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-smile-wink"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               name="name" 
                               class="form-input" 
                               placeholder="Nama lengkap"
                               value="<?= htmlspecialchars($name ?? '') ?>"
                               required 
                               autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               name="email" 
                               id="emailInput"
                               class="form-input" 
                               placeholder="Email"
                               value="<?= htmlspecialchars($email ?? '') ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="form-input" 
                               placeholder="Kata sandi"
                               required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="strength-container">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Kekuatan</span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-check-circle input-icon"></i>
                        <input type="password" 
                               name="confirm_password" 
                               id="confirmPassword"
                               class="form-input" 
                               placeholder="Konfirmasi kata sandi"
                               required>
                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="match-indicator" id="matchIndicator"></div>
                </div>

                <!-- Pertanyaan Keamanan -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-question-circle input-icon"></i>
                        <select name="security_question" class="form-select" required>
                            <option value="">Pilih pertanyaan keamanan</option>
                            <option value="Apa nama hewan peliharaan pertama Anda?">🐾 Nama hewan peliharaan pertamaku?</option>
                            <option value="Apa nama sekolah dasar Anda?">🏫 Nama SD-ku dulu?</option>
                            <option value="Siapa nama pahlawan favorit Anda?">🦸 Pahlawan favoritku?</option>
                            <option value="Apa makanan favorit Anda?">🍕 Makanan favoritku?</option>
                            <option value="Apa kota kelahiran Anda?">🏙️ Kota kelahiranku?</option>
                            <option value="Apa nama ibu kandung Anda?">👩 Nama ibuku?</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input type="text" 
                               name="security_answer" 
                               id="securityAnswer"
                               class="form-input" 
                               placeholder="Jawaban"
                               required>
                    </div>
                    <div class="info-hint">
                        <i class="fas fa-info-circle"></i> Jawaban dipakai kalau lupa sandi
                    </div>
                </div>

                <div class="terms-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span>Aku setuju dengan <a href="terms.php">Syarat & Ketentuan</a> dan <a href="privacy.php">Kebijakan Privasi</a></span>
                    </label>
                </div>

                <button type="submit" class="btn-register" id="registerBtn">
                    Daftar Sekarang
                </button>

                <div class="divider">
                    <span>atau</span>
                </div>

                <div class="login-link">
                    Sudah punya akun? <a href="login.php">Masuk sini yuk</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-card">
            <div id="spinnerArea">
                <div class="celebrate-spinner">
                    <i class="fas fa-smile"></i>
                    <i class="fas fa-heart"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-gem"></i>
                </div>
            </div>
            <div id="successArea" style="display: none;">
                <div class="success-circle">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h3 id="loadingTitle">Mendaftarkan Akun...</h3>
            <p id="loadingMessage">Tunggu sebentar ya, lagi kami proses ✨</p>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <script>
        // Toggle Password
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirmPassword');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmInput.type === 'password' ? 'text' : 'password';
            confirmInput.type = type;
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Password Strength Checker
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        function checkStrength(password) {
            let score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 10) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[$@#&!]/.test(password)) score++;
            
            const levels = {
                0: { width: '0%', text: '🤔 Minimal 6 karakter', color: '#9ca3af' },
                1: { width: '20%', text: '😅 Lemah', color: '#ef4444' },
                2: { width: '40%', text: '🙂 Lumayan', color: '#f59e0b' },
                3: { width: '60%', text: '😊 Baik', color: '#10b981' },
                4: { width: '80%', text: '💪 Kuat', color: '#10b981' },
                5: { width: '100%', text: '🔥 Sangat Kuat!', color: '#10b981' }
            };
            
            let index = Math.min(Math.floor(score / 1.2), 5);
            const level = levels[index];
            strengthFill.style.width = level.width;
            strengthFill.style.backgroundColor = level.color;
            strengthText.textContent = level.text;
            strengthText.style.color = level.color;
            
            return index >= 3;
        }
        
        passwordInput.addEventListener('input', function() {
            checkStrength(this.value);
            checkMatch();
        });

        // Password Match Checker
        const matchIndicator = document.getElementById('matchIndicator');
        
        function checkMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length === 0) {
                matchIndicator.innerHTML = '';
                return;
            }
            
            if (password === confirm) {
                matchIndicator.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> <span style="color: #10b981;">Yess, sandinya cocok!</span>';
            } else {
                matchIndicator.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i> <span style="color: #ef4444;">Waduh, sandinya nggak sama</span>';
            }
        }
        
        confirmInput.addEventListener('input', checkMatch);

        // Email validation realtime
        const emailInput = document.getElementById('emailInput');
        emailInput.addEventListener('input', function() {
            const email = this.value;
            const regex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
            if (email.length > 0 && !regex.test(email)) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });

        // Auto lowercase for security answer
        const securityAnswer = document.getElementById('securityAnswer');
        securityAnswer.addEventListener('input', function() {
            this.value = this.value.toLowerCase();
        });

        // Form Submit with Loading Animation
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const spinnerArea = document.getElementById('spinnerArea');
        const successArea = document.getElementById('successArea');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingMessage = document.getElementById('loadingMessage');
        
        let formSubmitted = false;
        
        registerForm.addEventListener('submit', function(e) {
            // Validasi tambahan sebelum submit
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            const terms = document.querySelector('input[name="terms"]').checked;
            
            if (password !== confirm) {
                e.preventDefault();
                showSweetAlert('Waduh', 'Kata sandi dan konfirmasinya nggak sama nih!', 'error');
                confirmInput.classList.add('shake');
                setTimeout(() => confirmInput.classList.remove('shake'), 300);
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showSweetAlert('Oops!', 'Kata sandi minimal 6 karakter biar lebih aman', 'error');
                passwordInput.classList.add('shake');
                setTimeout(() => passwordInput.classList.remove('shake'), 300);
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                showSweetAlert('Info', 'Setuju dulu ya sama syarat & ketentuan kami', 'info');
                return;
            }
            
            if (formSubmitted) {
                e.preventDefault();
                return;
            }
            
            formSubmitted = true;
            
            // Show loading animation
            loadingOverlay.classList.add('active');
            
            // Update loading message
            setTimeout(() => {
                loadingTitle.textContent = 'Sedikit lagi...';
                loadingMessage.textContent = 'Kami siapin akun kamu';
            }, 800);
            
            setTimeout(() => {
                spinnerArea.style.display = 'none';
                successArea.style.display = 'block';
                loadingTitle.textContent = 'Yeay! 🎉';
                loadingMessage.textContent = 'Akunmu berhasil dibuat!';
            }, 1500);
            
            // Submit form normally
            setTimeout(() => {
                registerForm.submit();
            }, 2000);
        });
        
        function showSweetAlert(title, message, type) {
            // Simple alert alternative
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'error' : 'success'}`;
            alertDiv.innerHTML = `<i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i><span>${message}</span>`;
            const form = document.querySelector('form');
            const firstGroup = form.querySelector('.form-group');
            form.insertBefore(alertDiv, firstGroup);
            setTimeout(() => alertDiv.remove(), 3000);
        }
        
        <?php if($success): ?>
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>