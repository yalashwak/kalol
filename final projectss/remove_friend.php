<?php
session_start();
require('database.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $friend_username = trim($_POST['friend_username'] ?? '');
    $current_username = $_SESSION['username'];

    if (empty($friend_username)) {
        header("Location: friend.php");
        exit;
    }

    // Get user IDs
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $current_username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $friend_username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $friend_id);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($found) {
        // Remove friendship both ways
        mysqli_query($conn, "DELETE FROM user_friends 
                           WHERE (user_id = $user_id AND friend_id = $friend_id) 
                           OR (user_id = $friend_id AND friend_id = $user_id)");
        
        // Clear any pending friend requests
        mysqli_query($conn, "DELETE FROM friend_requests 
                           WHERE (from_user_id = $user_id AND to_user_id = $friend_id)
                           OR (from_user_id = $friend_id AND to_user_id = $user_id)");
    }

    header("Location: friend.php");
    exit;
}

// If not POST request, redirect to friends page
header("Location: friend.php");
exit;
?>
