<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../classes/Database.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// Hanya admin atau untuk debugging
$user_id = $_SESSION['user_id'];

$db = Database::getInstance()->getConnection();

// Lihat data sebelum direset
echo "<h2>Data Sebelum Reset</h2>";
$stmt = $db->prepare("
    SELECT at.*, a.name 
    FROM asset_transactions at
    JOIN assets a ON at.asset_id = a.id
    WHERE at.user_id = ?
    ORDER BY at.id
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($transactions);
echo "</pre>";

if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Hapus semua transaksi
    $stmt = $db->prepare("DELETE FROM asset_transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    echo "<h3 style='color: green'>✅ Semua transaksi telah direset!</h3>";
    echo "<a href='index.php'>Kembali ke halaman aset</a>";
} else {
    echo "<h3 style='color: red'>⚠️ Peringatan: Ini akan menghapus SEMUA transaksi Anda!</h3>";
    echo "<a href='?confirm=yes' style='color: red; font-weight: bold'>Klik di sini untuk konfirmasi reset</a><br>";
    echo "<a href='index.php'>Batal</a>";
}
?>