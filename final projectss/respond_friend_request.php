<?php
session_start();
require('database.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $from_username = $_POST['from_user'] ?? '';
    $action = $_POST['action'] ?? '';
    $to_username = $_SESSION['username'];

    // Get user IDs
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $to_username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $to_user_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $from_username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $from_user_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($action === "accept") {
        // Update request status
        $stmt = mysqli_prepare($conn, "UPDATE friend_requests SET status = 'accepted' WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, "ii", $from_user_id, $to_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Add to user_friends both ways
        mysqli_query($conn, "INSERT IGNORE INTO user_friends (user_id, friend_id) VALUES ($from_user_id, $to_user_id), ($to_user_id, $from_user_id)");
    } elseif ($action === "deny") {
        $stmt = mysqli_prepare($conn, "UPDATE friend_requests SET status = 'denied' WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, "ii", $from_user_id, $to_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: friend.php");
    exit;
}
?>
