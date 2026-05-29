<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'classes/Database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$step = 1;
$user = null;

// Cek session untuk melanjutkan step
if (isset($_SESSION['reset_step'])) {
    $step = $_SESSION['reset_step'];
}

// Step 1: Cek email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_email'])) {
    $email = $_POST['email'] ?? '';
    
    if ($email) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, name, email, security_question FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $user['security_question']) {
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_user_name'] = $user['name'];
            $_SESSION['reset_question'] = $user['security_question'];
            $_SESSION['reset_step'] = 2;
            $step = 2;
            
            // Untuk AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'step' => 2, 'name' => $user['name'], 'question' => $user['security_question']]);
                exit;
            }
        } elseif ($user && !$user['security_question']) {
            $error = "Waduh, akun ini belum punya pertanyaan keamanan. Hubungi admin yaa!";
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        } else {
            $error = "Email tidak ditemukan. Coba cek lagi ya!";
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    } else {
        $error = "Yuk, isi dulu emailnya!";
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}

// Step 2: Verifikasi jawaban
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_answer'])) {
    $answer = strtolower(trim($_POST['answer'] ?? ''));
    
    if ($answer && isset($_SESSION['reset_user_id'])) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT security_answer FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['reset_user_id']]);
        $user = $stmt->fetch();
        
        if ($user && strtolower(trim($user['security_answer'])) === $answer) {
            $_SESSION['reset_step'] = 3;
            $step = 3;
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'step' => 3]);
                exit;
            }
        } else {
            $error = "Waduh, jawabannya kurang tepat. Coba ingat-ingat lagi ya!";
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    } else {
        $error = "Yuk, isi dulu jawabannya!";
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}

// Step 3: Reset password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = "Password minimal 6 karakter ya, biar lebih aman!";
    } elseif ($password !== $confirm) {
        $error = "Waduh, password dan konfirmasinya nggak sama nih!";
    } elseif (isset($_SESSION['reset_user_id'])) {
        $db = Database::getInstance()->getConnection();
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed, $_SESSION['reset_user_id']])) {
            $success = "Yeay! Password berhasil direset. Sekarang kamu bisa login pakai password baru ya! 🎉";
            // Hapus session reset
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_user_name']);
            unset($_SESSION['reset_question']);
            unset($_SESSION['reset_step']);
            $step = 4;
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'redirect' => 'login.php']);
                exit;
            }
        } else {
            $error = "Maaf, ada kendala teknis. Coba lagi ya!";
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    }
}

$page_title = 'Lupa Password - Keuangan Pribadi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= APP_NAME ?> - Lupa Password | Bantuan</title>
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

        .bg-bubble { position: fixed; border-radius: 50%; filter: blur(80px); pointer-events: none; }
        .bubble-1 { width: 400px; height: 400px; top: -200px; left: -200px; background: rgba(66,133,244,0.08); }
        .bubble-2 { width: 500px; height: 500px; bottom: -250px; right: -250px; background: rgba(52,168,83,0.06); }
        .bubble-3 { width: 300px; height: 300px; top: 50%; left: 50%; transform: translate(-50%,-50%); background: rgba(251,188,5,0.05); }

        .forgot-container { width: 100%; max-width: 480px; position: relative; z-index: 1; }

        .forgot-card {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 32px;
            padding: 40px 36px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.08);
            border: 1px solid rgba(232,234,237,0.6);
            transition: all 0.4s cubic-bezier(.22,1,.36,1);
        }
        .forgot-card:hover { transform: translateY(-2px); box-shadow: 0 30px 70px rgba(0,0,0,0.10); }

        .header-section { text-align: center; margin-bottom: 32px; }
        .icon-badge { width: 64px; height: 64px; background: #202124; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .icon-badge i { font-size: 28px; color: #8ab4f8; }
        .forgot-card h2 { font-size: 26px; font-weight: 700; color: #202124; margin-bottom: 8px; }
        .subtitle { color: #5f6368; font-size: 14px; }

        .info-box { background: #f1f3f4; padding: 16px 20px; border-radius: 16px; margin-bottom: 28px; text-align: center; border: 1px solid #e8eaed; }
        .info-box i { color: #4285f4; margin-right: 8px; }
        .info-box strong { color: #202124; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #5f6368; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 16px; color: #9aa0a6; font-size: 16px; transition: all 0.3s ease; }
        .form-input { width: 100%; padding: 14px 16px 14px 48px; font-size: 14px; border: 1.5px solid #e8eaed; border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; transition: all 0.3s ease; background: #f1f3f4; outline: none; }
        .form-input:focus { border-color: #4285f4; background: white; box-shadow: 0 0 0 3px rgba(66,133,244,0.12); }
        .form-input:focus + .input-icon { color: #4285f4; }
        .form-input-disabled { background: #e8eaed; cursor: not-allowed; color: #5f6368; }

        .password-toggle { position: absolute; right: 16px; background: none; border: none; cursor: pointer; color: #9aa0a6; font-size: 16px; padding: 0; }
        .password-toggle:hover { color: #4285f4; }

        .strength-container { margin-top: 8px; display: flex; gap: 8px; align-items: center; }
        .strength-bar { flex: 1; height: 4px; background: #e8eaed; border-radius: 4px; overflow: hidden; }
        .strength-fill { height: 100%; width: 0%; transition: all 0.3s ease; border-radius: 4px; }
        .strength-text { font-size: 11px; font-weight: 500; min-width: 90px; }
        .match-indicator { font-size: 12px; margin-top: 6px; margin-left: 48px; }

        .btn-submit { width: 100%; padding: 14px; background: #202124; color: white; border: none; border-radius: 9999px; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.4s cubic-bezier(.22,1,.36,1); margin-top: 8px; }
        .btn-submit:hover { background: #2d2f33; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }

        .back-link { text-align: center; margin-top: 24px; font-size: 13px; color: #5f6368; }
        .back-link a { color: #4285f4; text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }

        .alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-size: 13px; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .alert-success { background: #e6f4ea; border-left: 3px solid #34a853; color: #34a853; }
        .alert-success i { color: #34a853; font-size: 18px; }
        .alert-error { background: #fce8e6; border-left: 3px solid #ea4335; color: #ea4335; }
        .alert-error i { color: #ea4335; font-size: 18px; }

        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(32,33,36,0.92); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.4s ease; }
        .loading-overlay.active { opacity: 1; visibility: visible; }
        .loading-card { text-align: center; animation: bounceIn 0.5s ease; }
        @keyframes bounceIn { 0% { transform: scale(0.8); opacity: 0; } 70% { transform: scale(1.05); } 100% { transform: scale(1); opacity: 1; } }
        .question-spinner { width: 100px; height: 100px; margin: 0 auto 24px; position: relative; }
        .question-spinner i { position: absolute; font-size: 32px; color: #8ab4f8; animation: floatQuestion 1.2s ease-in-out infinite; }
        .question-spinner i:nth-child(1) { top: 0; left: 50%; transform: translateX(-50%); animation-delay: 0s; }
        .question-spinner i:nth-child(2) { bottom: 0; left: 50%; transform: translateX(-50%); animation-delay: 0.3s; }
        .question-spinner i:nth-child(3) { top: 50%; left: 0; transform: translateY(-50%); animation-delay: 0.6s; }
        .question-spinner i:nth-child(4) { top: 50%; right: 0; transform: translateY(-50%); animation-delay: 0.9s; }
        @keyframes floatQuestion { 0%,100% { transform: translateY(-50%) scale(1); opacity: 1; } 50% { transform: translateY(-50%) scale(1.2); opacity: 0.7; } }
        .success-circle { width: 100px; height: 100px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; animation: scaleSuccess 0.5s ease; }
        @keyframes scaleSuccess { from { transform: scale(0); } to { transform: scale(1); } }
        .success-circle i { font-size: 50px; color: #34a853; }
        .loading-card h3 { color: white; font-size: 24px; margin-bottom: 12px; font-weight: 700; }
        .loading-card p { color: rgba(255,255,255,0.7); font-size: 14px; }
        .loading-dots { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
        .loading-dots span { width: 8px; height: 8px; background: #8ab4f8; border-radius: 50%; animation: dotPulse 1.2s ease-in-out infinite; }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dotPulse { 0%,100% { transform: scale(0.5); opacity: 0.5; } 50% { transform: scale(1); opacity: 1; } }

        .shake { animation: shakeAnim 0.3s ease-in-out; }
        @keyframes shakeAnim { 0%,100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

        .step-container { transition: all 0.3s ease; }
        .step-hidden { display: none; }

        @media (max-width: 480px) { .forgot-card { padding: 28px 24px; } .forgot-card h2 { font-size: 24px; } }
    </style>
</head>
<body>
    <div class="bg-bubble bubble-1"></div>
    <div class="bg-bubble bubble-2"></div>
    <div class="bg-bubble bubble-3"></div>

    <div class="forgot-container">
        <div class="forgot-card">
            <div class="header-section">
                <div class="icon-badge">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h2>Lupa Password?</h2>
                <p class="subtitle">Tenang, kami bantu kamu reset password ya! 🔐</p>
            </div>

            <div id="errorContainer"></div>

            <!-- Step 1: Input Email -->
            <div id="step1" class="step-container">
                <form id="step1Form">
                    <div class="form-group">
                        <label class="form-label">Email Kamu</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" id="emailInput" class="form-input" placeholder="contoh@email.com" required autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Lanjutkan</button>
                </form>
            </div>

            <!-- Step 2: Jawab Pertanyaan -->
            <div id="step2" class="step-container" style="display: none;">
                <div class="info-box" id="userInfo">
                    <i class="fas fa-user-circle"></i> Halo, <strong id="userName"></strong>!
                </div>
                <form id="step2Form">
                    <div class="form-group">
                        <label class="form-label">Pertanyaan Keamanan:</label>
                        <div class="input-wrapper">
                            <i class="fas fa-comment input-icon"></i>
                            <input type="text" id="securityQuestion" class="form-input form-input-disabled" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jawaban Kamu</label>
                        <div class="input-wrapper">
                            <i class="fas fa-key input-icon"></i>
                            <input type="text" name="answer" id="answerInput" class="form-input" placeholder="Masukkan jawaban" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Verifikasi Jawaban</button>
                </form>
            </div>

            <!-- Step 3: Reset Password -->
            <div id="step3" class="step-container" style="display: none;">
                <div class="info-box">
                    <i class="fas fa-user-check"></i> Reset password untuk: <strong id="resetUserName"></strong>
                </div>
                <form id="step3Form">
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" class="form-input" placeholder="Minimal 6 karakter" required>
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
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <div class="input-wrapper">
                            <i class="fas fa-check-circle input-icon"></i>
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-input" placeholder="Ketik ulang password baru" required>
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="match-indicator" id="matchIndicator"></div>
                    </div>
                    <button type="submit" class="btn-submit">Reset Password</button>
                </form>
            </div>

            <!-- Step 4: Success -->
            <div id="step4" class="step-container" style="display: none;">
                <div class="alert alert-success" id="successMessage"></div>
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Yuk, langsung login!
                    </a>
                </div>
            </div>

            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Kembali ke Login
                </a>
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-card">
            <div id="spinnerArea">
                <div class="question-spinner">
                    <i class="fas fa-question-circle"></i>
                    <i class="fas fa-heart"></i>
                    <i class="fas fa-key"></i>
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            <div id="successArea" style="display: none;">
                <div class="success-circle">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h3 id="loadingTitle">Memproses...</h3>
            <p id="loadingMessage">Tunggu sebentar ya, lagi kami proses ✨</p>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <script>
        // Ambil data dari session PHP
        let currentStep = <?= $step ?>;
        let resetUserName = '<?= isset($_SESSION['reset_user_name']) ? addslashes($_SESSION['reset_user_name']) : '' ?>';
        let resetQuestion = '<?= isset($_SESSION['reset_question']) ? addslashes($_SESSION['reset_question']) : '' ?>';
        
        // Tampilkan step yang sesuai
        function showStep(step) {
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'none';
            document.getElementById('step4').style.display = 'none';
            
            if (step === 1) document.getElementById('step1').style.display = 'block';
            else if (step === 2) document.getElementById('step2').style.display = 'block';
            else if (step === 3) document.getElementById('step3').style.display = 'block';
            else if (step === 4) document.getElementById('step4').style.display = 'block';
        }
        
        // Set data user untuk step 2
        function setUserData(name, question) {
            document.getElementById('userName').innerHTML = name;
            document.getElementById('securityQuestion').value = question;
            document.getElementById('resetUserName').innerHTML = name;
        }
        
        // Tampilkan error
        function showError(message) {
            const errorContainer = document.getElementById('errorContainer');
            errorContainer.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-smile-wink"></i>
                    <span>${message}</span>
                </div>
            `;
            setTimeout(() => {
                errorContainer.innerHTML = '';
            }, 4000);
        }
        
        // Tampilkan success
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            if (successDiv) {
                successDiv.innerHTML = `<i class="fas fa-check-circle"></i> <span>${message}</span>`;
            }
        }
        
        // Loading overlay
        const loadingOverlay = document.getElementById('loadingOverlay');
        const spinnerArea = document.getElementById('spinnerArea');
        const successArea = document.getElementById('successArea');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingMessage = document.getElementById('loadingMessage');
        
        function showLoading(text, subtext) {
            loadingOverlay.classList.add('active');
            if (text) loadingTitle.textContent = text;
            if (subtext) loadingMessage.textContent = subtext;
        }
        
        function hideLoading() {
            loadingOverlay.classList.remove('active');
        }
        
        function showLoadingSuccess() {
            spinnerArea.style.display = 'none';
            successArea.style.display = 'block';
            loadingTitle.textContent = 'Berhasil! 🎉';
            loadingMessage.textContent = 'Passwordmu sudah direset!';
        }
        
        // Step 1: Submit email dengan AJAX
        const step1Form = document.getElementById('step1Form');
        if (step1Form) {
            step1Form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const email = document.getElementById('emailInput').value;
                
                if (!email) {
                    showError('Yuk, isi dulu emailnya!');
                    return;
                }
                
                showLoading('Mencari akunmu...', 'Kami cek dulu ya');
                
                try {
                    const formData = new FormData();
                    formData.append('check_email', '1');
                    formData.append('email', email);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Simpan data user
                        resetUserName = result.name;
                        resetQuestion = result.question;
                        setUserData(result.name, result.question);
                        currentStep = 2;
                        showStep(2);
                        hideLoading();
                    } else {
                        hideLoading();
                        showError(result.error);
                    }
                } catch (error) {
                    hideLoading();
                    showError('Ada kendala teknis. Coba lagi ya!');
                }
            });
        }
        
        // Step 2: Submit jawaban dengan AJAX
        const step2Form = document.getElementById('step2Form');
        if (step2Form) {
            step2Form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const answer = document.getElementById('answerInput').value;
                
                if (!answer) {
                    showError('Yuk, isi dulu jawabannya!');
                    return;
                }
                
                showLoading('Memeriksa jawaban...', 'Kami verifikasi dulu ya');
                
                try {
                    const formData = new FormData();
                    formData.append('verify_answer', '1');
                    formData.append('answer', answer);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        currentStep = 3;
                        showStep(3);
                        hideLoading();
                        // Inisialisasi password strength setelah step 3 muncul
                        initPasswordStrength();
                    } else {
                        hideLoading();
                        showError(result.error);
                    }
                } catch (error) {
                    hideLoading();
                    showError('Ada kendala teknis. Coba lagi ya!');
                }
            });
        }
        
        // Password Strength Checker
        function initPasswordStrength() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirmPassword');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const matchIndicator = document.getElementById('matchIndicator');
            
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.type === 'password' ? 'text' : 'password';
                    passwordInput.type = type;
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
            
            if (toggleConfirmPassword && confirmInput) {
                toggleConfirmPassword.addEventListener('click', function() {
                    const type = confirmInput.type === 'password' ? 'text' : 'password';
                    confirmInput.type = type;
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
            
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
                if (strengthFill) strengthFill.style.width = level.width;
                if (strengthFill) strengthFill.style.backgroundColor = level.color;
                if (strengthText) strengthText.textContent = level.text;
                if (strengthText) strengthText.style.color = level.color;
                
                return index >= 3;
            }
            
            function checkMatch() {
                if (!passwordInput || !confirmInput) return;
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                
                if (confirm.length === 0) {
                    if (matchIndicator) matchIndicator.innerHTML = '';
                    return;
                }
                
                if (password === confirm) {
                    if (matchIndicator) matchIndicator.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> <span style="color: #10b981;">Yess, cocok!</span>';
                } else {
                    if (matchIndicator) matchIndicator.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i> <span style="color: #ef4444;">Waduh, nggak sama</span>';
                }
            }
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkStrength(this.value);
                    checkMatch();
                });
            }
            
            if (confirmInput) {
                confirmInput.addEventListener('input', checkMatch);
            }
        }
        
        // Step 3: Submit reset password dengan AJAX
        const step3Form = document.getElementById('step3Form');
        if (step3Form) {
            step3Form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const password = document.getElementById('password').value;
                const confirm = document.getElementById('confirmPassword').value;
                
                if (password !== confirm) {
                    showError('Waduh, password dan konfirmasinya nggak sama nih!');
                    document.getElementById('confirmPassword').classList.add('shake');
                    setTimeout(() => document.getElementById('confirmPassword').classList.remove('shake'), 300);
                    return;
                }
                
                if (password.length < 6) {
                    showError('Password minimal 6 karakter ya, biar lebih aman!');
                    document.getElementById('password').classList.add('shake');
                    setTimeout(() => document.getElementById('password').classList.remove('shake'), 300);
                    return;
                }
                
                showLoading('Menyimpan password baru...', 'Kami simpan perubahanmu');
                
                setTimeout(() => {
                    showLoadingSuccess();
                }, 800);
                
                try {
                    const formData = new FormData();
                    formData.append('reset_password', '1');
                    formData.append('password', password);
                    formData.append('confirm_password', confirm);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        setTimeout(() => {
                            hideLoading();
                            showStep(4);
                            showSuccess('Yeay! Password berhasil direset. Sekarang kamu bisa login pakai password baru ya! 🎉');
                        }, 1500);
                    } else {
                        hideLoading();
                        showError(result.error);
                    }
                } catch (error) {
                    hideLoading();
                    showError('Ada kendala teknis. Coba lagi ya!');
                }
            });
        }
        
        // Inisialisasi berdasarkan step dari server
        if (currentStep === 2 && resetUserName) {
            setUserData(resetUserName, resetQuestion);
            showStep(2);
        } else if (currentStep === 3 && resetUserName) {
            setUserData(resetUserName, resetQuestion);
            showStep(3);
            initPasswordStrength();
        } else if (currentStep === 4) {
            showStep(4);
        } else {
            showStep(1);
        }
    </script>
</body>
</html>