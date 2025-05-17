<?php
session_start();
require('database.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $group_name = trim($_POST['group_name'] ?? '');

    if (empty($group_name)) {
        echo "Group name is required.";
        exit;
    }

    // Get user id
    $username = $_SESSION['username'];
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Create a wallet for the group
    $wallet_stmt = mysqli_prepare($conn, "INSERT INTO wallets (balance) VALUES (0.00)");
    mysqli_stmt_execute($wallet_stmt);
    $wallet_id = mysqli_insert_id($conn);
    mysqli_stmt_close($wallet_stmt);

    // Create the group
    $group_stmt = mysqli_prepare($conn, "INSERT INTO groups (name, wallet_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($group_stmt, "si", $group_name, $wallet_id);
    if (mysqli_stmt_execute($group_stmt)) {
        $group_id = mysqli_insert_id($conn);
        mysqli_stmt_close($group_stmt);

        // Add creator as a member
        $member_stmt = mysqli_prepare($conn, "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($member_stmt, "ii", $group_id, $user_id);
        mysqli_stmt_execute($member_stmt);
        mysqli_stmt_close($member_stmt);

        header("Location: groups.php");
        exit;
    } else {
        echo "Failed to create group. It may already exist.";
        mysqli_stmt_close($group_stmt);
    }
}
?>
