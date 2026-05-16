<?php
require_once '../../../config/config.php';
require_once '../../../config/session.php';
require_once '../../../includes/functions.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/AssetTransaction.php';

if (!isLoggedIn()) {
    header('Location: ../../../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $assetTransaction = new AssetTransaction();
    
    if ($assetTransaction->delete($id, $_SESSION['user_id'])) {
        $_SESSION['success'] = 'Transaksi aset berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus transaksi aset!';
    }
}

header('Location: ../../index.php');
exit;
?>