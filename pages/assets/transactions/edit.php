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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assetTransaction = new AssetTransaction();
    
    $quantity = cleanNumber($_POST['quantity']);
    $price_per_unit = cleanNumber($_POST['price_per_unit']);
    $total_price = $quantity * $price_per_unit;
    
    $data = [
        'id' => $_POST['id'],
        'user_id' => $_SESSION['user_id'],
        'quantity' => $quantity,
        'price_per_unit' => $price_per_unit,
        'total_price' => $total_price,
        'transaction_date' => $_POST['transaction_date']
    ];
    
    if ($assetTransaction->update($data)) {
        $_SESSION['success'] = 'Transaksi aset berhasil diupdate!';
    } else {
        $_SESSION['error'] = 'Gagal mengupdate transaksi aset!';
    }
}

header('Location: ../../index.php');
exit;
?>