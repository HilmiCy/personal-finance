<?php
ob_clean();
ob_start();

require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php'; // Tambahkan ini!
require_once '../../classes/Budget.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $budget = new Budget();
        
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $amount_raw = isset($_POST['amount']) ? $_POST['amount'] : '0';
        $month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date('n');
        $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
        
        // Clean amount (remove dots and commas)
        $amount = (int) preg_replace('/[^0-9]/', '', $amount_raw);
        
        if ($category_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak valid!']);
            exit;
        }
        
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Jumlah anggaran harus lebih dari 0!']);
            exit;
        }
        
        // Cek apakah budget sudah ada
        if ($budget->exists($_SESSION['user_id'], $category_id, $month, $year)) {
            echo json_encode(['success' => false, 'message' => 'Anggaran untuk kategori ini sudah ada di bulan ini!']);
            exit;
        }
        
        $data = [
            'user_id' => $_SESSION['user_id'],
            'category_id' => $category_id,
            'amount' => $amount,
            'month' => $month,
            'year' => $year
        ];
        
        if ($budget->create($data)) {
            echo json_encode(['success' => true, 'message' => 'Anggaran berhasil ditambahkan!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan anggaran! Mungkin anggaran untuk kategori ini sudah ada.']);
        }
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak valid!']);
exit;
?>