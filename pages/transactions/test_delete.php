<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h3>Debug Delete Transaction</h3>";

// Tampilkan session
echo "<pre>";
echo "SESSION: ";
print_r($_SESSION);
echo "</pre>";

// Cek user_id dari session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
echo "User ID dari session: $user_id<br>";

// Cek semua transaksi di database
try {
    $db = Database::getInstance()->getConnection();
    
    // Tampilkan semua transaksi
    $stmt = $db->prepare("SELECT id, user_id, description, amount FROM transactions LIMIT 10");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Daftar Transaksi di Database:</h4>";
    echo "<pre>";
    print_r($transactions);
    echo "</pre>";
    
    // Cek user yang login
    $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>User Login:</h4>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>