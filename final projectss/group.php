<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require('database.php');
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Get user id
$username = $_SESSION['username'];
$user_query = mysqli_query($conn, "SELECT id FROM users WHERE username = '".mysqli_real_escape_string($conn, $username)."'");
$user_id = null;
if ($user_row = mysqli_fetch_assoc($user_query)) {
    $user_id = $user_row['id'];
}

// Handle group creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_group'])) {
    $group_name = mysqli_real_escape_string($conn, $_POST['group_name']);
    if (!empty($group_name)) {
        // Create group wallet first
        mysqli_query($conn, "INSERT INTO wallets (balance) VALUES (0.00)");
        $wallet_id = mysqli_insert_id($conn);

        // Generate a random join code (6 characters)
        $join_code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);

        // Create group with join_code
        $create_query = mysqli_query($conn, "INSERT INTO groups (name, wallet_id, join_code) VALUES ('$group_name', $wallet_id, '$join_code')");
        if ($create_query) {
            $group_id = mysqli_insert_id($conn);
            // Add creator as member
            mysqli_query($conn, "INSERT INTO group_members (group_id, user_id) VALUES ($group_id, $user_id)");
            $_SESSION['success_message'] = "Group created successfully!";
            header("Location: group.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Failed to create group.";
            header("Location: group.php");
            exit;
        }
    }
}

// Handle group joining
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['join_group'])) {
    $group_code = mysqli_real_escape_string($conn, $_POST['group_code']);
    if (!empty($group_code)) {
        // Get group id by join_code
        $group_result = mysqli_query($conn, "SELECT id FROM groups WHERE join_code = '$group_code'");
        if ($group = mysqli_fetch_assoc($group_result)) {
            $group_id = $group['id'];
            // Check if user is already a member
            $member_check = mysqli_query($conn, "SELECT * FROM group_members WHERE group_id = $group_id AND user_id = $user_id");
            if (mysqli_num_rows($member_check) == 0) {
                mysqli_query($conn, "INSERT INTO group_members (group_id, user_id) VALUES ($group_id, $user_id)");
                $_SESSION['success_message'] = "Successfully joined the group!";
                header("Location: group.php");
                exit;
            } else {
                $_SESSION['error_message'] = "You are already a member of this group.";
                header("Location: group.php");
                exit;
            }
        } else {
            $_SESSION['error_message'] = "Invalid group code.";
            header("Location: group.php");
            exit;
        }
    }
}

// Handle leaving group
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['leave_group'])) {
    $group_id = intval($_POST['group_id']);
    // Check if user is not the last member
    $member_count = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as count FROM group_members WHERE group_id = $group_id
    "))['count'];
    
    if ($member_count > 1) {
        mysqli_query($conn, "DELETE FROM group_members WHERE group_id = $group_id AND user_id = $user_id");
        $_SESSION['success_message'] = "Successfully left the group.";
        header("Location: group.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Cannot leave group: you are the last member.";
        header("Location: group.php");
        exit;
    }
}

// Fetch groups for the logged-in user
$groups = [];
if (isset($user_id)) {
    $group_query = mysqli_query($conn, "
        SELECT g.id, g.name, g.join_code, w.balance 
        FROM groups g 
        JOIN group_members gm ON g.id = gm.group_id 
        JOIN wallets w ON g.wallet_id = w.id
        WHERE gm.user_id = $user_id
    ");
    while ($row = mysqli_fetch_assoc($group_query)) {
        $groups[] = $row;
    }
}

// Fetch pending group invitations
$group_invitations = [];
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    // Get user id
    $user_query = mysqli_query($conn, "SELECT id FROM users WHERE username = '".mysqli_real_escape_string($conn, $username)."'");
    if ($user_row = mysqli_fetch_assoc($user_query)) {
        $user_id = $user_row['id'];
        // Get pending group invitations
        $inv_query = mysqli_query($conn, "SELECT gi.id, g.name as group_name, u.username as invited_by FROM group_invitations gi JOIN groups g ON gi.group_id = g.id JOIN users u ON gi.invited_by_user_id = u.id WHERE gi.invited_user_id = $user_id AND gi.status = 'pending'");
        while ($row = mysqli_fetch_assoc($inv_query)) {
            $group_invitations[] = $row;
        }
    }
}
?>
</head>
<link rel="stylesheet" href="group.css">
<link rel="stylesheet" href="color.css">
<link rel="stylesheet" href="notifications.css">
<body>
    <nav>
        <ul>
            <li class="sidebar-username"><?= htmlspecialchars($_SESSION['username']) ?></li>
            <li><a href="wallet.php">Wallet</a></li>
            <li><a href="friend.php">Friends</a></li>
            <li class="active"><a href="group.php">Groups</a></li>
            <li class="right"><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="notification error">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="notification success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <div class="groupsPage">
        <div class="groupSection">
            <h2>Your Groups</h2>
            <ul>
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $group): ?>
                        <li>
                            <span><?= htmlspecialchars($group['name']) ?> 
                                (Balance: $<?= number_format($group['balance'], 2) ?>)
                                <?php if ($group['join_code']): ?>
                                    - Code: <?= htmlspecialchars($group['join_code']) ?>
                                <?php endif; ?>
                            </span>
                            <form method="post">
                                <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                <button type="submit" name="leave_group" class="leave">Leave</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No groups joined yet.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="groupSection">
            <h2>Create a Group</h2>
            <form method="post">
                <input type="text" name="group_name" placeholder="Group name" required>
                <input type="submit" name="create_group" value="Create Group">
            </form>
        </div>

        <div class="groupSection">
            <h2>Join a Group</h2>
            <form method="post">
                <input type="text" name="group_code" placeholder="Enter group code" required>
                <input type="submit" name="join_group" value="Join Group">
            </form>
        </div>
    </div>
</body>
</html>