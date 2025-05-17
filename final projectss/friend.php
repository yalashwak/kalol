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

// Fetch friends and pending requests for the logged-in user
$friends = [];
$pending_requests = [];
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    // Get user id
    $user_query = mysqli_query($conn, "SELECT id FROM users WHERE username = '".mysqli_real_escape_string($conn, $username)."'");
    if ($user_row = mysqli_fetch_assoc($user_query)) {
        $user_id = $user_row['id'];
        // Get friends
        $friend_query = mysqli_query($conn, "SELECT u.username FROM users u JOIN user_friends uf ON u.id = uf.friend_id WHERE uf.user_id = $user_id");
        while ($row = mysqli_fetch_assoc($friend_query)) {
            $friends[] = $row['username'];
        }
        // Get pending requests (assuming a table 'friend_requests' with 'to_user_id' and 'from_user_id')
        $pending_query = mysqli_query($conn, "SELECT u.username FROM users u JOIN friend_requests fr ON u.id = fr.from_user_id WHERE fr.to_user_id = $user_id AND fr.status = 'pending'");
        while ($row = mysqli_fetch_assoc($pending_query)) {
            $pending_requests[] = $row['username'];
        }
    }
}
?>
</head>
<link rel="stylesheet" href="color.css">
<link rel="stylesheet" href="friend.css">
<link rel="stylesheet" href="notifications.css">
<body>
    <nav>
        <ul>
            <li class="sidebar-username"><?= htmlspecialchars($_SESSION['username']) ?></li>
            <li><a href="wallet.php">Wallet</a></li>
            <li class="active"><a href="friend.php">Friends</a></li>
            <li><a href="group.php">Groups</a></li>
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
    
    <div class="friendsPage">
        <div class="friendSection">
            <h2>Your Friends</h2>
            <ul>
                <?php if (!empty($friends)): ?>
                    <?php foreach ($friends as $friend): ?>
                        <li>
                            <span><?= htmlspecialchars($friend) ?></span>
                            <form method="post" action="remove_friend.php">
                                <input type="hidden" name="friend_username" value="<?= htmlspecialchars($friend) ?>">
                                <button type="submit" class="remove">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No friends added yet.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="friendSection">
            <h2>Pending Friend Requests</h2>
            <ul>
                <?php if (!empty($pending_requests)): ?>
                    <?php foreach ($pending_requests as $request): ?>
                        <li>
                            <span>From: <?= htmlspecialchars($request) ?></span>
                            <form method="post" action="respond_friend_request.php">
                                <input type="hidden" name="from_user" value="<?= htmlspecialchars($request) ?>">
                                <button type="submit" name="action" value="accept">Accept</button>
                                <button type="submit" name="action" value="deny" class="deny">Deny</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No pending friend requests.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="friendSection">
            <h2>Add a Friend</h2>
            <form method="post" action="send_friend_request.php">
                <input type="text" name="friend_username" placeholder="Friend's username" required>
                <input type="submit" value="Add Friend">
            </form>
        </div>
    </div>
</body>
</html>