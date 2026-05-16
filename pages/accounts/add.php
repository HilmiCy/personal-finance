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
        'user_id' => $_SESSION['user_id'],
        'name' => $_POST['name'],
        'balance' => str_replace(',', '', $_POST['balance'])
    ];
    
    if ($account->create($data)) {
        $_SESSION['success'] = 'Akun berhasil ditambahkan!';
    } else {
        $_SESSION['error'] = 'Gagal menambahkan akun!';
    }
}

header('Location: index.php');
exit;
?>