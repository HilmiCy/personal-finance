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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Response untuk animasi loading modern
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
                exit;
            } else {
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Email atau password salah. Coba lagi ya!';
        }
    } else {
        $error = 'Yuk, isi dulu email dan passwordnya!';
    }
}

$page_title = 'Login - Keuangan Pribadi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= APP_NAME ?> - Kelola Keuangan Pribadi | Masuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #f8fafd;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle at top, rgba(66, 133, 244, 0.05), transparent 70%);
            pointer-events: none;
            z-index: -1;
            }

        .bg-circle {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }
        .circle-1 { width: 400px; height: 400px; top: -200px; left: -200px; background: rgba(66,133,244,0.08); }
        .circle-2 { width: 500px; height: 500px; bottom: -250px; right: -250px; background: rgba(52,168,83,0.06); }
        .circle-3 { width: 300px; height: 300px; top: 50%; left: 50%; transform: translate(-50%,-50%); background: rgba(251,188,5,0.05); }

        .login-container {
            width: 100%; max-width: 1000px;
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: var(--radius-xl, 32px);
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.08);
            display: flex; flex-wrap: wrap;
            position: relative; z-index: 1;
            border: 1px solid rgba(232,234,237,0.6);
        }

        .brand-side {
            flex: 1; min-width: 280px;
            background: #202124;
            padding: 48px 32px;
            color: white;
            display: flex; flex-direction: column; justify-content: center;
            position: relative; overflow: hidden;
        }
        .brand-side::after {
            content: '\f0d0';
            font-family: 'Font Awesome 6 Free'; font-weight: 900;
            position: absolute; right: -20px; bottom: -30px;
            font-size: 160px; opacity: 0.04; color: white;
        }

        .logo-area { margin-bottom: 32px; }
        .logo-icon {
            width: 64px; height: 64px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-md, 20px);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 24px;
        }
        .logo-icon i { font-size: 32px; color: #8ab4f8; }

        .brand-side h1 { font-size: 26px; font-weight: 700; margin-bottom: 12px; line-height: 1.3; }
        .tagline { font-size: 14px; opacity: 0.7; margin-bottom: 32px; line-height: 1.6; }

        .features { list-style: none; margin-bottom: 40px; }
        .features li {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 16px; font-size: 14px;
        }
        .features li i { width: 20px; font-size: 14px; opacity: 0.6; }

        .btn-register {
            display: inline-block; padding: 12px 24px;
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.2);
            border-radius: var(--radius-full, 9999px);
            color: white; text-decoration: none;
            font-weight: 600; font-size: 14px; text-align: center;
            transition: all 0.4s cubic-bezier(.22,1,.36,1);
        }
        .btn-register:hover {
            background: white; color: #202124;
            transform: translateY(-2px);
        }

        .form-side {
            flex: 1; min-width: 280px;
            padding: 48px 40px;
            background: transparent;
        }

        .form-header { margin-bottom: 32px; }
        .form-header h2 { font-size: 26px; font-weight: 700; color: #202124; margin-bottom: 8px; }
        .form-header p { color: #5f6368; font-size: 14px; }

        .input-group { margin-bottom: 24px; }
        .input-label {
            display: block; margin-bottom: 8px;
            font-size: 13px; font-weight: 600; color: #5f6368;
        }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon {
            position: absolute; left: 16px;
            color: #9aa0a6; font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control {
            width: 100%; padding: 14px 16px 14px 48px;
            font-size: 14px;
            border: 1.5px solid #e8eaed;
            border-radius: var(--radius-sm, 12px);
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s ease; outline: none;
            background: #f1f3f4;
        }
        .form-control:focus {
            border-color: #4285f4;
            background: white;
            box-shadow: 0 0 0 3px rgba(66,133,244,0.12);
        }
        .form-control:focus + .input-icon,
        .input-wrapper:focus-within .input-icon { color: #4285f4; }

        .password-toggle {
            position: absolute; right: 16px;
            background: none; border: none; cursor: pointer;
            color: #9aa0a6; font-size: 16px; padding: 0;
        }
        .password-toggle:hover { color: #4285f4; }

        .form-options {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 28px;
        }
        .checkbox-label {
            display: flex; align-items: center; gap: 8px;
            cursor: pointer; font-size: 13px; color: #5f6368;
        }
        .checkbox-label input { width: 16px; height: 16px; cursor: pointer; accent-color: #4285f4; }
        .forgot-link { color: #4285f4; text-decoration: none; font-size: 13px; font-weight: 500; }
        .forgot-link:hover { text-decoration: underline; }

        .btn-login {
            width: 100%; padding: 14px;
            background: #202124;
            color: white; border: none;
            border-radius: var(--radius-full, 9999px);
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: all 0.4s cubic-bezier(.22,1,.36,1);
            margin-bottom: 24px;
        }
        .btn-login:hover {
            background: #2d2f33;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .btn-login:active { transform: translateY(0); }

        .divider { text-align: center; margin-bottom: 24px; position: relative; }
        .divider::before, .divider::after {
            content: ''; position: absolute; top: 50%;
            width: calc(50% - 60px); height: 1px; background: #e8eaed;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        .divider span { background: transparent; padding: 0 16px; color: #9aa0a6; font-size: 12px; }

        .register-mobile { display: none; text-align: center; font-size: 13px; color: #5f6368; }
        .register-mobile a { color: #4285f4; text-decoration: none; font-weight: 600; }

        .alert {
            padding: 12px 16px; border-radius: var(--radius-sm, 12px);
            margin-bottom: 24px; display: flex; align-items: center; gap: 12px;
            font-size: 13px; background: #fce8e6; color: #ea4335;
        }
        .alert i { font-size: 16px; }

        .loading-overlay {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(32,33,36,0.92);
            backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
            opacity: 0; visibility: hidden;
            transition: all 0.4s ease;
        }
        .loading-overlay.active { opacity: 1; visibility: visible; }

        .loading-card { text-align: center; animation: bounceIn 0.5s ease; }
        @keyframes bounceIn {
            0% { transform: scale(0.8); opacity: 0; }
            70% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }

        .money-spinner {
            width: 100px; height: 100px; margin: 0 auto 24px; position: relative;
        }
        .money-spinner i {
            position: absolute; font-size: 40px; color: #8ab4f8;
            animation: floatMoney 1.2s ease-in-out infinite;
        }
        .money-spinner i:nth-child(1) { top: 0; left: 50%; transform: translateX(-50%); animation-delay: 0s; }
        .money-spinner i:nth-child(2) { bottom: 0; left: 50%; transform: translateX(-50%); animation-delay: 0.3s; }
        .money-spinner i:nth-child(3) { top: 50%; left: 0; transform: translateY(-50%); animation-delay: 0.6s; }
        .money-spinner i:nth-child(4) { top: 50%; right: 0; transform: translateY(-50%); animation-delay: 0.9s; }
        @keyframes floatMoney {
            0%, 100% { transform: translateY(-50%) scale(1); opacity: 1; }
            50% { transform: translateY(-50%) scale(1.2); opacity: 0.7; }
        }

        .success-circle {
            width: 100px; height: 100px;
            background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px; animation: scaleUp 0.5s ease;
        }
        @keyframes scaleUp { from { transform: scale(0); } to { transform: scale(1); } }
        .success-circle i { font-size: 50px; color: #34a853; }

        .loading-card h3 { color: white; font-size: 24px; margin-bottom: 12px; font-weight: 700; }
        .loading-card p { color: rgba(255,255,255,0.7); font-size: 14px; }

        .loading-dots { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
        .loading-dots span {
            width: 8px; height: 8px; background: #8ab4f8;
            border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite;
        }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dotPulse {
            0%, 100% { transform: scale(0.5); opacity: 0.5; }
            50% { transform: scale(1); opacity: 1; }
        }

        @media (max-width: 768px) {
            .login-container { flex-direction: column; border-radius: 28px; }
            .brand-side { padding: 32px 24px; text-align: center; }
            .logo-icon { margin: 0 auto 20px; }
            .features { text-align: left; max-width: 260px; margin: 0 auto 28px; }
            .btn-register { display: none; }
            .register-mobile { display: block; }
            .form-side { padding: 32px 24px; }
            .form-header h2 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="bg-circle circle-1"></div>
    <div class="bg-circle circle-2"></div>
    <div class="bg-circle circle-3"></div>

    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="brand-side">
            <div class="logo-area">
                <div class="logo-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h1>Keuangan Pribadi</h1>
                <p class="tagline">Kelola uangmu lebih bijak,<br>wujudkan kebebasan finansial!</p>
            </div>
            <ul class="features">
                <li><i class="fas fa-chart-line"></i> <span>Pantau pemasukan & pengeluaran</span></li>
                <li><i class="fas fa-piggy-bank"></i> <span>Target tabungan impianmu</span></li>
                <li><i class="fas fa-file-invoice"></i> <span>Laporan keuangan bulanan</span></li>
                <li><i class="fas fa-mobile-alt"></i> <span>Akses mudah di mana saja</span></li>
            </ul>
            <a href="register.php" class="btn-register">Daftar Sekarang →</a>
        </div>

        <!-- Right Side - Login Form -->
        <div class="form-side">
            <div class="form-header">
                <h2>Selamat Datang Kembali! 👋</h2>
                <p>Masuk untuk lanjut kelola keuanganmu</p>
            </div>

            <?php if($error): ?>
                <div class="alert">
                    <i class="fas fa-smile-wink"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="input-group">
                    <label class="input-label">Alamat Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               name="email" 
                               id="emailInput"
                               class="form-control" 
                               placeholder="contoh@email.com" 
                               required 
                               autocomplete="email"
                               autofocus>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Kata Sandi</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="form-control" 
                               placeholder="Masukkan kata sandi" 
                               required 
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" id="rememberMe">
                        <span>Ingat saya</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Lupa sandi?</a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    Masuk
                </button>

                <div class="divider">
                    <span>atau</span>
                </div>

                <div class="register-mobile">
                    Belum punya akun? <a href="register.php">Daftar sekarang</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modern Loading Animation -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-card">
            <div id="spinnerArea">
                <div class="money-spinner">
                    <i class="fas fa-coins"></i>
                    <i class="fas fa-coins"></i>
                    <i class="fas fa-coins"></i>
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            <div id="successArea" style="display: none;">
                <div class="success-circle">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h3 id="loadingText">Memproses masuk...</h3>
            <p id="loadingSubtext">Tunggu sebentar ya, lagi siapin dashboardmu</p>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // Remember me functionality
        const rememberCheckbox = document.getElementById('rememberMe');
        const emailInput = document.getElementById('emailInput');
        
        if (localStorage.getItem('saved_email')) {
            emailInput.value = localStorage.getItem('saved_email');
            if (rememberCheckbox) rememberCheckbox.checked = true;
        }
        
        if (rememberCheckbox) {
            rememberCheckbox.addEventListener('change', function() {
                if (this.checked && emailInput.value) {
                    localStorage.setItem('saved_email', emailInput.value);
                } else {
                    localStorage.removeItem('saved_email');
                }
            });
        }
        
        if (emailInput) {
            emailInput.addEventListener('change', function() {
                if (rememberCheckbox && rememberCheckbox.checked) {
                    localStorage.setItem('saved_email', this.value);
                }
            });
        }

        // Form submission with modern loading animation
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const spinnerArea = document.getElementById('spinnerArea');
        const successArea = document.getElementById('successArea');
        const loadingText = document.getElementById('loadingText');
        const loadingSubtext = document.getElementById('loadingSubtext');
        
        const loadingMessages = [
            { text: 'Memeriksa akunmu...', sub: 'Pastikan data sudah benar ya' },
            { text: 'Login berhasil!', sub: 'Kami arahkan ke dashboard...' }
        ];

        let formSubmitted = false;

        if (loginForm) {
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (formSubmitted) return;
                formSubmitted = true;
                
                const formData = new FormData(loginForm);
                
                // Show loading animation
                loadingOverlay.classList.add('active');
                
                // Change loading message after delay
                setTimeout(() => {
                    loadingText.textContent = loadingMessages[0].text;
                    loadingSubtext.textContent = loadingMessages[0].sub;
                }, 1000);
                
                loginBtn.classList.add('loading');
                const originalText = loginBtn.innerHTML;
                loginBtn.innerHTML = 'Memproses...';
                loginBtn.disabled = true;
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success animation
                        setTimeout(() => {
                            spinnerArea.style.display = 'none';
                            successArea.style.display = 'block';
                            loadingText.textContent = 'Berhasil Masuk! 🎉';
                            loadingSubtext.textContent = 'Selamat datang kembali!';
                        }, 500);
                        
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 2000);
                    } else {
                        window.location.reload();
                    }
                } catch (error) {
                    loginForm.submitted = true;
                    loginForm.submit();
                }
            });
        }
    </script>
</body>
</html>