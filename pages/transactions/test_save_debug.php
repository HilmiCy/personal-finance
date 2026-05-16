<?php
// test_save_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html');

echo "<h2>Debugging Save.php</h2>";

// Test 1: Cek file config
echo "<h3>Test 1: Memuat file config</h3>";
try {
    require_once '../../config/config.php';
    echo "✓ config.php berhasil dimuat<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Cek session
echo "<h3>Test 2: Memuat session</h3>";
try {
    require_once '../../config/session.php';
    echo "✓ session.php berhasil dimuat<br>";
    echo "Status login: " . (isLoggedIn() ? "Logged in" : "Not logged in") . "<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Cek class Database
echo "<h3>Test 3: Memuat Database class</h3>";
try {
    require_once '../../classes/Database.php';
    echo "✓ Database.php berhasil dimuat<br>";
    $db = Database::getInstance();
    echo "✓ Koneksi database berhasil<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Cek class Transaction
echo "<h3>Test 4: Memuat Transaction class</h3>";
try {
    require_once '../../classes/Transaction.php';
    echo "✓ Transaction.php berhasil dimuat<br>";
    $transaction = new Transaction();
    echo "✓ Transaction object created<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Test 5: Testing POST request (simulate)</h3>";
echo "<form method='POST' action='save.php'>";
echo "<input type='hidden' name='type' value='expense'>";
echo "<input type='hidden' name='amount' value='10000'>";
echo "<input type='hidden' name='account_id' value='1'>";
echo "<input type='hidden' name='transaction_date' value='2026-05-06'>";
echo "<input type='hidden' name='category_id' value='1'>";
echo "<input type='hidden' name='description' value='Test'>";
echo "<button type='submit'>Test Save.php</button>";
echo "</form>";