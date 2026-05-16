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
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $amount_raw = isset($_POST['amount']) ? $_POST['amount'] : '0';
        
        // Clean amount (remove dots and commas)
        $amount = (int) preg_replace('/[^0-9]/', '', $amount_raw);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID anggaran tidak valid!']);
            exit;
        }
        
        if ($category_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak valid!']);
            exit;
        }
        
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Jumlah anggaran harus lebih dari 0!']);
            exit;
        }
        
        // Cek apakah budget milik user
        $budgetData = $budget->getById($id, $_SESSION['user_id']);
        if (!$budgetData) {
            echo json_encode(['success' => false, 'message' => 'Anggaran tidak ditemukan!']);
            exit;
        }
        
        $data = [
            'id' => $id,
            'user_id' => $_SESSION['user_id'],
            'category_id' => $category_id,
            'amount' => $amount
        ];
        
        if ($budget->update($data)) {
            echo json_encode(['success' => true, 'message' => 'Anggaran berhasil diupdate!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate anggaran!']);
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