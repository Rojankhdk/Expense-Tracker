<?php
session_start();
require 'db.php';

// Security: If not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category_source, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'], 
        $_POST['type'], 
        $_POST['amount'], 
        $_POST['category_source'], 
        $_POST['description']
    ]);
    header("Location: dashboard.php");
    exit();
}

// Fetch Data
$transactions = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$transactions->execute([$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SpendWise | Dashboard</title>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <div class="sidebar">
        <!-- Top Section -->
        <div class="sidebar-top">
            <h2>SpendWise</h2>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="budgets.php"><i class="fas fa-wallet"></i> Budgets</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>

        <!-- Bottom Section -->
        <div class="sidebar-bottom">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        </header>

        <div class="dashboard-grid">
            <!-- Add Transaction Form -->
            <div class="card">
                <h3>Add New Record</h3>
                <form method="POST">
                    <select name="type" required>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                    <input type="number" name="amount" placeholder="Amount" required>
                    <input type="text" name="category_source" placeholder="Category or Source" required>
                    <input type="text" name="description" placeholder="Description">
                    <button type="submit" class="cta-button">Save Record</button>
                </form>
            </div>

            <!-- Transaction History -->
            <div class="card full-width">
                <h3>Recent History</h3>
                <table>
                    <thead>
                        <tr><th>Date</th><th>Type</th><th>Source/Cat</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                            <td><span class="badge <?php echo $t['type']; ?>"><?php echo ucfirst($t['type']); ?></span></td>
                            <td><?php echo htmlspecialchars($t['category_source']); ?></td>
                            <td>$<?php echo number_format($t['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>