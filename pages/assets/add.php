<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Asset.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $asset = new Asset();
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'name' => $_POST['name'],
        'type' => $_POST['type'],
        'symbol' => $_POST['symbol'] ?? '',
        'currency' => $_POST['currency'] ?? 'IDR'
    ];
    
    if ($asset->create($data)) {
        $_SESSION['success'] = 'Aset berhasil ditambahkan!';
    } else {
        $_SESSION['error'] = 'Gagal menambahkan aset!';
    }
}

header('Location: index.php');
exit;
?>