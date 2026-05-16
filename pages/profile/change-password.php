<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/User.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = new User();
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate
    if (empty($current_password)) {
        $_SESSION['error'] = 'Password lama harus diisi!';
        header('Location: index.php');
        exit;
    }
    
    if (empty($new_password)) {
        $_SESSION['error'] = 'Password baru harus diisi!';
        header('Location: index.php');
        exit;
    }
    
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = 'Password baru minimal 6 karakter!';
        header('Location: index.php');
        exit;
    }
    
    if ($new_password != $confirm_password) {
        $_SESSION['error'] = 'Konfirmasi password baru tidak cocok!';
        header('Location: index.php');
        exit;
    }
    
    // Verify current password
    $stmt = Database::getInstance()->getConnection()->prepare("
        SELECT password FROM users WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data || !password_verify($current_password, $user_data['password'])) {
        $_SESSION['error'] = 'Password lama salah!';
        header('Location: index.php');
        exit;
    }
    
    // Update password
    if ($user->updatePassword($_SESSION['user_id'], $new_password)) {
        $_SESSION['success'] = 'Password berhasil diubah!';
    } else {
        $_SESSION['error'] = 'Gagal mengubah password!';
    }
}

header('Location: index.php');
exit;
?>