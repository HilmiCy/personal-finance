<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../classes/Database.php';

if (!isLoggedIn()) {
    die("Silakan login");
}

$db = Database::getInstance()->getConnection();

// Cek data di tabel transfers
$stmt = $db->prepare("SELECT * FROM transfers WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$transfers = $stmt->fetchAll();

echo "<h2>Data Transfers</h2>";
echo "<pre>";
print_r($transfers);
echo "</pre>";

// Cek struktur tabel
$stmt = $db->query("DESCRIBE transfers");
$structure = $stmt->fetchAll();
echo "<h3>Struktur Tabel:</h3>";
echo "<pre>";
print_r($structure);
echo "</pre>";
?>