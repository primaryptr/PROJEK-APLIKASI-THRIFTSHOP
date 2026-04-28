<?php
session_start();

// Hancurkan semua session
$_SESSION = [];
session_unset();
session_destroy();

// Redirect kembali ke halaman login
header("Location: login.php");
exit;
?>
