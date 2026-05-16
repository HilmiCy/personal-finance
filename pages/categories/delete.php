<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Category.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// Set header untuk JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = new Category();
    
    $id = $_POST['id'] ?? 0;
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID kategori tidak valid!'
        ]);
        exit;
    }
    
    // Cek apakah kategori milik user yang sedang login
    $categoryData = $category->getCategoryByIdAndUser($id, $_SESSION['user_id']);
    if (!$categoryData) {
        echo json_encode([
            'success' => false, 
            'message' => 'Kategori tidak ditemukan atau tidak memiliki akses!'
        ]);
        exit;
    }
    
    // Cek apakah kategori memiliki transaksi
    if ($category->hasTransactions($id)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Kategori masih digunakan dalam transaksi, tidak dapat dihapus!'
        ]);
        exit;
    }
    
    if ($category->delete($id, $_SESSION['user_id'])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Kategori berhasil dihapus!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menghapus kategori!'
        ]);
    }
    exit;
}

echo json_encode([
    'success' => false, 
    'message' => 'Metode request tidak valid!'
]);
exit;
?>