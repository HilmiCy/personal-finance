<?php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Installment.php';
require_once '../../classes/Account.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Session expired. Silakan login kembali.'
    ]);
    exit;
}

// Set header untuk JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $installment = new Installment();
    $account = new Account();
    
    // Ambil ID cicilan
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Validasi ID
    if ($id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID cicilan tidak valid!'
        ]);
        exit;
    }
    
    // Ambil data cicilan
    $installmentData = $installment->getById($id, $_SESSION['user_id']);
    
    if (!$installmentData) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cicilan tidak ditemukan!'
        ]);
        exit;
    }
    
    // Cek apakah cicilan sudah memiliki pembayaran
    $paymentHistory = $installment->getPaymentHistory($id, $_SESSION['user_id']);
    
    if (!empty($paymentHistory)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cicilan sudah memiliki riwayat pembayaran, tidak dapat dihapus!'
        ]);
        exit;
    }
    
    // Hapus akun terkait
    $accountId = $installmentData['account_id'];
    $account->delete($accountId, $_SESSION['user_id']);
    
    // Hapus cicilan
    if ($installment->delete($id, $_SESSION['user_id'])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Cicilan berhasil dihapus!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menghapus cicilan. Silakan coba lagi!'
        ]);
    }
    exit;
}

// Jika bukan method POST
echo json_encode([
    'success' => false, 
    'message' => 'Metode request tidak valid!'
]);
exit;
?>