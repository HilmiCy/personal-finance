<?php
header('Content-Type: application/json');

// Pastikan path benar
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';  // <-- INI PENTING! Tambahkan ini
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login manual (tanpa isLoggedIn dulu untuk test)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Harus login terlebih dahulu']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid']);
    exit;
}

try {
    $transaction = new Transaction();
    
    // Cek apakah transaksi ada
    $cek = $transaction->getById($id, $user_id);
    
    if (!$cek) {
        echo json_encode(['success' => false, 'message' => "Transaksi dengan ID $id tidak ditemukan"]);
        exit;
    }
    
    $result = $transaction->delete($id, $user_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus transaksi']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>