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

    if ($friend_username === $current_username) {
        $_SESSION['error_message'] = "You cannot add yourself as a friend.";
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

    if (!$found) {
        $_SESSION['error_message'] = "User not found.";
        header("Location: friend.php");
        exit;
    }

    // Check if already friends
    $check = mysqli_query($conn, "SELECT * FROM user_friends WHERE user_id = $user_id AND friend_id = $friend_id");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error_message'] = "You are already friends with this user.";
        header("Location: friend.php");
        exit;
    }

    // Check if request already sent
    $check_req = mysqli_query($conn, "SELECT * FROM friend_requests WHERE from_user_id = $user_id AND to_user_id = $friend_id AND status = 'pending'");
    if (mysqli_num_rows($check_req) > 0) {
        $_SESSION['error_message'] = "Friend request already sent.";
        header("Location: friend.php");
        exit;
    }

    // Insert friend request
    $stmt = mysqli_prepare($conn, "INSERT INTO friend_requests (from_user_id, to_user_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $friend_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Friend request sent successfully.";
        header("Location: friend.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Failed to send friend request.";
        header("Location: friend.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

header("Location: friend.php");
exit;
?>
