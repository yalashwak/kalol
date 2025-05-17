<?php
// wallet.php - Handles wallet operations for the logged-in user, including viewing balance, adding money, sending/receiving money to/from friends, sharing with groups, and spending group money.

session_start();

// Prevent back button from accessing cached pages after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require('database.php');

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Fetch user's wallet and balance
$balance = 0.00;
$user_id = null; // <-- Add this line
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $user_query = mysqli_query($conn, "SELECT id, wallet_id FROM users WHERE username = '".mysqli_real_escape_string($conn, $username)."'");
    if ($user_row = mysqli_fetch_assoc($user_query)) {
        $user_id = $user_row['id']; // <-- Set user_id here
        $wallet_id = $user_row['wallet_id'];
        if ($wallet_id) {
            $wallet_query = mysqli_query($conn, "SELECT balance FROM wallets WHERE id = $wallet_id");
            if ($wallet_row = mysqli_fetch_assoc($wallet_query)) {
                $balance = $wallet_row['balance'];
            }
        } else {
            // Create wallet if not exists
            mysqli_query($conn, "INSERT INTO wallets (balance) VALUES (0.00)");
            $wallet_id = mysqli_insert_id($conn);
            mysqli_query($conn, "UPDATE users SET wallet_id = $wallet_id WHERE username = '".mysqli_real_escape_string($conn, $username)."'");
        }
    }
}

// Handle add money form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_money'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    if ($amount > 0 && isset($wallet_id)) {
        mysqli_query($conn, "UPDATE wallets SET balance = balance + $amount WHERE id = $wallet_id");
        header("Location: wallet.php"); // Redirect to wallet.php instead of wallet.php
        exit;
    }
    // Optionally, handle invalid input
}

// Fetch user's groups for sharing money
$user_groups = [];
if (isset($user_id)) {
    $group_query = mysqli_query($conn, "SELECT g.id, g.name, g.wallet_id FROM groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = $user_id");
    while ($row = mysqli_fetch_assoc($group_query)) {
        // Fetch group wallet balance
        $group_wallet_id = $row['wallet_id'];
        $group_balance = 0.00;
        if ($group_wallet_id) {
            $wallet_query = mysqli_query($conn, "SELECT balance FROM wallets WHERE id = $group_wallet_id");
            if ($wallet_row = mysqli_fetch_assoc($wallet_query)) {
                $group_balance = $wallet_row['balance'];
            }
        }
        $row['balance'] = $group_balance;
        $user_groups[] = $row;
    }
}

// Handle share money form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['share_money'])) {
    $group_id = intval($_POST['group_id'] ?? 0);
    $amount = floatval($_POST['share_amount'] ?? 0);
    if ($amount > 0 && isset($wallet_id) && $group_id > 0) {
        // Get group wallet id
        $group_wallet_query = mysqli_query($conn, "SELECT wallet_id FROM groups WHERE id = $group_id");
        if ($group_wallet_row = mysqli_fetch_assoc($group_wallet_query)) {
            $group_wallet_id = $group_wallet_row['wallet_id'];
            // Check user has enough balance
            $user_wallet_query = mysqli_query($conn, "SELECT balance FROM wallets WHERE id = $wallet_id");
            $user_wallet_row = mysqli_fetch_assoc($user_wallet_query);
            if ($user_wallet_row && $user_wallet_row['balance'] >= $amount) {
                // Deduct from user, add to group
                mysqli_query($conn, "UPDATE wallets SET balance = balance - $amount WHERE id = $wallet_id");
                mysqli_query($conn, "UPDATE wallets SET balance = balance + $amount WHERE id = $group_wallet_id");
                header("Location: wallet.php"); // Stay on wallet.php
                exit;
            } else {
                echo "<p style='color:red;text-align:center;'>Insufficient balance.</p>";
            }
        }
    }
}

// Fetch user's friends for sending money
$user_friends = [];
if (isset($user_id)) {
    $friend_query = mysqli_query($conn, "SELECT u.id, u.username, u.wallet_id FROM users u JOIN user_friends uf ON u.id = uf.friend_id WHERE uf.user_id = $user_id");
    while ($row = mysqli_fetch_assoc($friend_query)) {
        $user_friends[] = $row;
    }
}

// Handle accept/deny money from friend
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['respond_money_friend'])) {
    $transfer_id = intval($_POST['transfer_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    // Get transfer info
    $transfer_query = mysqli_query($conn, "SELECT * FROM friend_money_transfers WHERE id = $transfer_id AND to_user_id = $user_id AND status = 'pending'");
    if ($transfer = mysqli_fetch_assoc($transfer_query)) {
        if ($action === "accept") {
            // Add to recipient wallet
            mysqli_query($conn, "UPDATE wallets SET balance = balance + {$transfer['amount']} WHERE id = {$transfer['to_wallet_id']}");
            // Update status
            mysqli_query($conn, "UPDATE friend_money_transfers SET status = 'accepted' WHERE id = $transfer_id");
        } elseif ($action === "deny") {
            // Refund sender
            mysqli_query($conn, "UPDATE wallets SET balance = balance + {$transfer['amount']} WHERE id = {$transfer['from_wallet_id']}");
            // Update status
            mysqli_query($conn, "UPDATE friend_money_transfers SET status = 'denied' WHERE id = $transfer_id");
        }
        header("Location: wallet.php"); // Stay on wallet.php
        exit;
    }
}

// Handle send money to friend form (now creates a pending transfer)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['send_money_friend'])) {
    $friend_id = intval($_POST['friend_id'] ?? 0);
    $amount = floatval($_POST['friend_amount'] ?? 0);
    if ($amount > 0 && isset($wallet_id) && $friend_id > 0) {
        // Get friend's wallet id
        $friend_wallet_query = mysqli_query($conn, "SELECT wallet_id FROM users WHERE id = $friend_id");
        if ($friend_wallet_row = mysqli_fetch_assoc($friend_wallet_query)) {
            $friend_wallet_id = $friend_wallet_row['wallet_id'];
            // Check user has enough balance
            $user_wallet_query = mysqli_query($conn, "SELECT balance FROM wallets WHERE id = $wallet_id");
            $user_wallet_row = mysqli_fetch_assoc($user_wallet_query);
            if ($user_wallet_row && $user_wallet_row['balance'] >= $amount) {
                // Deduct from sender, create pending transfer
                mysqli_query($conn, "UPDATE wallets SET balance = balance - $amount WHERE id = $wallet_id");
                mysqli_query($conn, "INSERT INTO friend_money_transfers (from_user_id, to_user_id, from_wallet_id, to_wallet_id, amount, status) VALUES ($user_id, $friend_id, $wallet_id, $friend_wallet_id, $amount, 'pending')");
                header("Location: wallet.php"); // Stay on wallet.php
                exit;
            } else {
                echo "<p style='color:red;text-align:center;'>Insufficient balance.</p>";
            }
        }
    }
}

// Fetch pending money transfers for the logged-in user
$pending_transfers = [];
if (isset($user_id)) {
    $pending_query = mysqli_query($conn, "SELECT t.id, u.username as from_username, t.amount FROM friend_money_transfers t JOIN users u ON t.from_user_id = u.id WHERE t.to_user_id = $user_id AND t.status = 'pending'");
    while ($row = mysqli_fetch_assoc($pending_query)) {
        $pending_transfers[] = $row;
    }
}

// Handle spend group money form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['spend_group_money'])) {
    $spend_group_id = intval($_POST['spend_group_id'] ?? 0);
    $spend_amount = floatval($_POST['spend_amount'] ?? 0);
    if ($spend_amount > 0 && $spend_group_id > 0 && isset($wallet_id)) {
        // Get group wallet id and balance
        $group_wallet_query = mysqli_query($conn, "SELECT wallet_id FROM groups WHERE id = $spend_group_id");
        if ($group_wallet_row = mysqli_fetch_assoc($group_wallet_query)) {
            $group_wallet_id = $group_wallet_row['wallet_id'];
            $group_balance_query = mysqli_query($conn, "SELECT balance FROM wallets WHERE id = $group_wallet_id");
            $group_balance_row = mysqli_fetch_assoc($group_balance_query);
            if ($group_balance_row && $group_balance_row['balance'] >= $spend_amount) {
                // Deduct from group wallet and add to user's wallet
                mysqli_query($conn, "UPDATE wallets SET balance = balance - $spend_amount WHERE id = $group_wallet_id");
                mysqli_query($conn, "UPDATE wallets SET balance = balance + $spend_amount WHERE id = $wallet_id");
                header("Location: wallet.php"); // Stay on wallet.php
                exit;
            } else {
                echo "<p style='color:red;text-align:center;'>Group has insufficient balance.</p>";
            }
        }
    }
}
?>
</head>
  <link rel="stylesheet" href="wallet.css">
  <link rel="stylesheet" href="color.css">
<body>
    <nav>
        <ul>
            <li class="sidebar-username"><?= htmlspecialchars($_SESSION['username']) ?></li>
            <li class="active"><a href="wallet.php">Wallet</a></li>
            <li><a href="friend.php">Friends</a></li>
            <li><a href="group.php">Groups</a></li>
            <li class="right"><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="walletPage">
        <div class="walletSection">
            <h2>Wallet</h2>
            <div class="balance">
                <h3>Balance</h3>
                <p class="balanceAmount">$<?= number_format($balance, 2) ?></p>
            </div>
            <form method="post">
                <input type="number" name="amount" min="0.01" step="0.01" placeholder="Amount to add" required>
                <input type="submit" name="add_money" value="Add Money">
            </form>
        </div>
        <div class="walletSection">
            <h2>Send Money to a Friend</h2>
            <form method="post">
                <select name="friend_id" required>
                    <option value="" disabled selected>Select friend</option>
                    <?php foreach ($user_friends as $friend): ?>
                        <option value="<?= $friend['id'] ?>"><?= htmlspecialchars($friend['username']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="friend_amount" min="0.01" step="0.01" placeholder="Amount to send" required>
                <input type="submit" name="send_money_friend" value="Send Money">
            </form>
        </div>
        <div class="walletSection">
            <h2>Pending Money Transfers</h2>
            <ul>
                <?php if (!empty($pending_transfers)): ?>
                    <?php foreach ($pending_transfers as $transfer): ?>
                        <li>
                            <span>From: <?= htmlspecialchars($transfer['from_username']) ?> - $<?= number_format($transfer['amount'], 2) ?></span>
                            <form method="post">
                                <input type="hidden" name="transfer_id" value="<?= $transfer['id'] ?>">
                                <button type="submit" name="action" value="accept">Accept</button>
                                <button type="submit" name="action" value="deny">Deny</button>
                                <input type="hidden" name="respond_money_friend" value="1">
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No pending transfers</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="walletSection">
            <h2>Share Money with Group</h2>
            <form method="post" onsubmit="return confirm('Are you sure you want to send that money to the group?');">
                <select name="group_id" required>
                    <option value="" disabled selected>Select group</option>
                    <?php foreach ($user_groups as $group): ?>
                        <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="share_amount" min="0.01" step="0.01" placeholder="Amount to share" required>
                <input type="submit" name="share_money" value="Share Money">
            </form>
        </div>
        <div class="walletSection">
            <h2>Your Groups' Wallets</h2>
            <ul>
                <?php if (!empty($user_groups)): ?>
                    <?php foreach ($user_groups as $group): ?>
                        <li><span><?= htmlspecialchars($group['name']) ?>: $<?= number_format($group['balance'], 2) ?></span></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No groups found</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="walletSection">
            <h2>Spend Group Money</h2>
            <form method="post">
                <select name="spend_group_id" required>
                    <option value="" disabled selected>Select group</option>
                    <?php foreach ($user_groups as $group): ?>
                        <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="spend_amount" min="0.01" step="0.01" placeholder="Amount to spend" required>
                <input type="submit" name="spend_group_money" value="Spend Money">
            </form>
        </div>
    </div>
</body>
</html>