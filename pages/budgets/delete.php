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
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID anggaran tidak valid!']);
            exit;
        }
        
        // Cek apakah budget milik user
        $budgetData = $budget->getById($id, $_SESSION['user_id']);
        if (!$budgetData) {
            echo json_encode(['success' => false, 'message' => 'Anggaran tidak ditemukan!']);
            exit;
        }
        
        if ($budget->delete($id, $_SESSION['user_id'])) {
            echo json_encode(['success' => true, 'message' => 'Anggaran berhasil dihapus!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus anggaran!']);
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