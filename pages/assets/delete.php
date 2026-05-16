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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $asset = new Asset();
    
    if ($asset->delete($id, $_SESSION['user_id'])) {
        $_SESSION['success'] = 'Aset berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus aset! Aset masih memiliki transaksi.';
    }
}

header('Location: index.php');
exit;
?>