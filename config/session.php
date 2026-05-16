<?php
// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['last_activity'] = time();
?>