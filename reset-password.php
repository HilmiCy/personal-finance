<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'classes/Database.php';

// Cek apakah sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Validasi token
$stmt = $db->prepare("SELECT id, name, email FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "Token tidak valid atau sudah kadaluarsa. Silakan request ulang reset password.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user['id']])) {
            $success = "Password berhasil direset! Silakan login dengan password baru Anda.";
            // Hapus token agar tidak bisa digunakan lagi
            $user = null; // Prevent form from showing again
        } else {
            $error = "Gagal mereset password. Silakan coba lagi.";
        }
    }
}

$page_title = 'Reset Password';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Reset Password</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .glass-container {
            width: 100%;
            max-width: 450px;
        }

        .reset-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo-icon i {
            font-size: 32px;
            color: white;
        }

        .reset-card h2 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .reset-subtitle {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: #9ca3af;
            font-size: 18px;
        }

        .form-control-custom {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control-custom:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            cursor: pointer;
            color: #9ca3af;
            background: none;
            border: none;
            font-size: 18px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
        }

        .back-link {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
        }

        .alert-custom {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
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
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #dc2626;
        }

        .user-info {
            background: #f3f4f6;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
        }

        .user-info i {
            color: #667eea;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="glass-container">
        <div class="reset-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-lock-open"></i>
                </div>
                <h2>Reset Password</h2>
                <p class="reset-subtitle">Buat password baru Anda</p>
            </div>

            <?php if($success): ?>
                <div class="alert-custom alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $success ?></span>
                </div>
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Login Sekarang
                    </a>
                </div>
            <?php elseif($error): ?>
                <div class="alert-custom alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
                <div class="back-link">
                    <a href="forgot-password.php">
                        <i class="fas fa-key"></i> Request Reset Password Baru
                    </a>
                </div>
            <?php elseif($user): ?>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <strong><?= htmlspecialchars($user['name']) ?></strong><br>
                    <small><?= htmlspecialchars($user['email']) ?></small>
                </div>

                <form method="POST" id="resetForm">
                    <div class="form-group">
                        <div class="input-group-custom">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control-custom" 
                                   placeholder="Password Baru"
                                   required 
                                   minlength="6">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group-custom">
                            <i class="fas fa-check-circle input-icon"></i>
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password"
                                   class="form-control-custom" 
                                   placeholder="Konfirmasi Password Baru"
                                   required>
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span>Reset Password</span>
                    </button>

                    <div class="back-link">
                        <a href="login.php">
                            <i class="fas fa-arrow-left"></i> Kembali ke Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        <?php if($user && !$success): ?>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        const resetForm = document.getElementById('resetForm');
        const submitBtn = document.getElementById('submitBtn');

        resetForm.addEventListener('submit', function(e) {
            submitBtn.classList.add('loading');
            submitBtn.querySelector('span').style.opacity = '0';
        });
        <?php endif; ?>
    </script>
</body>
</html>