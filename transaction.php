<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

// Fetch All Transactions
$transactions = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$transactions->execute([$_SESSION['user_id']]);
$all_transactions = $transactions->fetchAll(PDO::FETCH_ASSOC);

// Fetch Current Month Budget Status
$current_month = date('Y-m');
$summary_stmt = $pdo->prepare("
    SELECT b.category, b.amount AS budget_limit, IFNULL(SUM(t.amount), 0) AS actual_spent
    FROM budgets b
    LEFT JOIN transactions t ON b.category = t.category_source AND t.user_id = b.user_id AND t.type = 'expense' AND DATE_FORMAT(t.created_at, '%Y-%m') = b.month_year
    WHERE b.user_id = ? AND b.month_year = ?
    GROUP BY b.category
");
$summary_stmt->execute([$_SESSION['user_id'], $current_month]);
$budget_status = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions | SpendWise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-top">
            <h2>SpendWise</h2>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budgets</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        <div class="sidebar-bottom"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </div>

    <div class="main-content">
        <h1>All Transactions</h1>
        
        <div class="dashboard-grid">
            <!-- Transaction History Log -->
            <div class="card full-width">
                <h3>Transaction History</h3>
                <table>
                    <thead>
                        <tr><th>Date</th><th>Description</th><th>Category/Source</th><th>Type</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_transactions as $t): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($t['description'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($t['category_source']); ?></td>
                            <td><span class="badge <?php echo $t['type']; ?>"><?php echo ucfirst($t['type']); ?></span></td>
                            <td style="font-weight:bold; color: <?php echo ($t['type'] == 'income') ? '#2E7D32' : '#C62828'; ?>">
                                <?php echo ($t['type'] == 'income' ? '+' : '-') . number_format($t['amount'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Current Month Budget Status -->
            <div class="card full-width">
                <h3>Budget Status (<?php echo $current_month; ?>)</h3>
                <table>
                    <thead><tr><th>Category</th><th>Budget Limit</th><th>Spent</th><th>Remaining</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($budget_status as $row): 
                            $remaining = $row['budget_limit'] - $row['actual_spent'];
                        ?>
                        <tr>
                            <td><?php echo $row['category']; ?></td>
                            <td><?php echo number_format($row['budget_limit'], 2); ?></td>
                            <td><?php echo number_format($row['actual_spent'], 2); ?></td>
                            <td style="font-weight:bold; color: <?php echo ($remaining < 0) ? 'red' : 'green'; ?>">
                                <?php echo number_format($remaining, 2); ?>
                            </td>
                            <td><?php echo ($row['actual_spent'] > $row['budget_limit']) ? '<span style="color:red">Over Budget</span>' : '<span style="color:green">Safe</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>