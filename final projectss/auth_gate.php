<?php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
