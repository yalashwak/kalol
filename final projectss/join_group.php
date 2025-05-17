<?php
session_start();
require('database.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $group_name = trim($_POST['group_name'] ?? '');
    $friend_usernames = $_POST['friend_usernames'] ?? [];

    if (empty($group_name) || empty($friend_usernames)) {
        echo "Group and friends are required.";
        exit;
    }

    // Get group id
    $stmt = mysqli_prepare($conn, "SELECT id FROM groups WHERE name = ?");
    mysqli_stmt_bind_param($stmt, "s", $group_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $group_id);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$found) {
        echo "Group not found.";
        exit;
    }

    // Get inviter user id
    $inviter_username = $_SESSION['username'];
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $inviter_username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $inviter_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Invite each friend (insert invitation)
    foreach ($friend_usernames as $friend_username) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $friend_username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $friend_id);
        $user_found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($user_found) {
            // Check if already a member
            $check = mysqli_query($conn, "SELECT * FROM group_members WHERE group_id = $group_id AND user_id = $friend_id");
            if (mysqli_num_rows($check) == 0) {
                // Check if invitation already exists and is pending
                $inv_check = mysqli_query($conn, "SELECT * FROM group_invitations WHERE group_id = $group_id AND invited_user_id = $friend_id AND status = 'pending'");
                if (mysqli_num_rows($inv_check) == 0) {
                    $add_stmt = mysqli_prepare($conn, "INSERT INTO group_invitations (group_id, invited_user_id, invited_by_user_id) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($add_stmt, "iii", $group_id, $friend_id, $inviter_id);
                    mysqli_stmt_execute($add_stmt);
                    mysqli_stmt_close($add_stmt);
                }
            }
        }
    }

    header("Location: groups.php");
    exit;
}
?>
