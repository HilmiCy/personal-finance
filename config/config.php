<?php
// Application Configuration
define('APP_NAME', 'Manajemen Keuangan');
define('APP_URL', 'http://localhost/keuangan/'); // Sesuaikan dengan URL Anda

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>