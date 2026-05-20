<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Account.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account = new Account();
    
    $data = [
        'name' => $_POST['name'],
        'balance' => str_replace(',', '', $_POST['balance']),
        'currency' => $_POST['currency'] ?? 'IDR'
    ];
    
    if ($account->update($_POST['id'], $_SESSION['user_id'], $data)) {
        $_SESSION['success'] = 'Akun berhasil diupdate!';
    } else {
        $_SESSION['error'] = 'Gagal mengupdate akun!';
    }
}

header('Location: index.php');
exit;
?>