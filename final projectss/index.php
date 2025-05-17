<?php
session_start();
if (isset($_SESSION['username'])) {
    // Redirect to the actual home/dashboard page
    header("Location: wallet.php");
    exit;
} else {
    // Redirect to the login page
    header("Location: login.php");
    exit;
}
?>