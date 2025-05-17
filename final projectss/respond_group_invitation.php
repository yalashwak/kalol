<?php
session_start();
require('database.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $invitation_id = intval($_POST['invitation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Get invitation info
    $stmt = mysqli_prepare($conn, "SELECT group_id, invited_user_id FROM group_invitations WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $invitation_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $group_id, $invited_user_id);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$found) {
        header("Location: groups.php");
        exit;
    }

    // Check user is the invited user
    $username = $_SESSION['username'];
    $user_query = mysqli_query($conn, "SELECT id FROM users WHERE username = '".mysqli_real_escape_string($conn, $username)."'");
    $user_row = mysqli_fetch_assoc($user_query);
    if (!$user_row || $user_row['id'] != $invited_user_id) {
        header("Location: groups.php");
        exit;
    }

    if ($action === "accept") {
        // Update invitation status
        $stmt = mysqli_prepare($conn, "UPDATE group_invitations SET status = 'accepted' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $invitation_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Add user to group_members
        $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $group_id, $invited_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } elseif ($action === "reject") {
        $stmt = mysqli_prepare($conn, "UPDATE group_invitations SET status = 'rejected' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $invitation_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: groups.php");
    exit;
}
?>
