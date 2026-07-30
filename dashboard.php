<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// 1. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'];
    $category = $_POST['category_source'];
    $description = $_POST['description'] ?? '';

    if ($_POST['type'] == 'income') {
        $stmt = $pdo->prepare("INSERT INTO incomes (user_id, amount, category_source, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $amount, $category, $description]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, category_source, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $amount, $category, $description]);
    }
    header("Location: dashboard.php"); 
    exit();
}

$current_month = date('Y-m');

// 2. Fetch Totals for Balance
$inc_stmt = $pdo->prepare("SELECT SUM(amount) FROM incomes WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$inc_stmt->execute([$_SESSION['user_id'], $current_month]);
$total_income = $inc_stmt->fetchColumn() ?: 0;

$exp_stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$exp_stmt->execute([$_SESSION['user_id'], $current_month]);
$total_expenses = $exp_stmt->fetchColumn() ?: 0;

$remaining = $total_income - $total_expenses;

// 3. Fetch Budget Overview 
$summary_stmt = $pdo->prepare("
    SELECT b.category, b.amount AS budget_limit, IFNULL(SUM(t.amount), 0) AS actual_spent
    FROM budgets b
    LEFT JOIN transactions t ON TRIM(b.category) = TRIM(t.category_source) 
        AND t.user_id = b.user_id 
        AND DATE_FORMAT(t.created_at, '%Y-%m') = LEFT(b.month_year, 7)
    WHERE b.user_id = ? AND LEFT(b.month_year, 7) = ?
    GROUP BY b.category
");
$summary_stmt->execute([$_SESSION['user_id'], $current_month]);
$summary = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch available categories
$cat_stmt = $pdo->prepare("SELECT DISTINCT category FROM budgets WHERE user_id = ?");
$cat_stmt->execute([$_SESSION['user_id']]);
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | SpendWise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="sidebar">
        <h2>SpendWise</h2>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="transaction.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
            <li><a href="budget.php"><i class="fas fa-wallet"></i> Budgets</a></li>
        </ul>
        <a href="logout.php" class="sidebar-bottom">Logout</a>
    </div>

    <div class="main-content">
        <h1>Dashboard</h1>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Remaining Balance</h3>
                <h2 style="color: <?php echo ($remaining >= 0) ? '#27ae60' : '#c0392b'; ?>">
                    <?php echo number_format($remaining, 2); ?>
                </h2>
                <p>Income: <?php echo number_format($total_income, 2); ?> | Expenses: <?php echo number_format($total_expenses, 2); ?></p>
            </div>
        </div>

        <div class="card full-width">
            <h3>Budget Overview (<?php echo $current_month; ?>)</h3>
            <table>
                <thead><tr><th>Category</th><th>Limit</th><th>Spent</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (count($summary) > 0): ?>
                        <?php foreach ($summary as $row): 
                            // Determine status
                            if ($row['actual_spent'] > $row['budget_limit']) {
                                $status = "Over Budget";
                                $status_color = "#c0392b"; // Red
                            } else {
                                $status = "Within Budget";
                                $status_color = "#27ae60"; // Green
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo number_format($row['budget_limit'], 2); ?></td>
                            <td><?php echo number_format($row['actual_spent'], 2); ?></td>
                            <td style="color: <?php echo $status_color; ?>; font-weight: bold;">
                                <?php echo $status; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">No budgets set for <?php echo $current_month; ?>. Please set them in the <a href="budget.php">Budgets page</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Add Income</h3>
                <form method="POST">
                    <input type="hidden" name="type" value="income">
                    <input type="number" name="amount" step="0.01" placeholder="Amount" required>
                    <input type="text" name="category_source" placeholder="Source (e.g. Salary)" required>
                    <button type="submit" class="cta-button income-btn">Save Income</button>
                </form>
            </div>

            <div class="card">
                <h3>Add Expense</h3>
                <form method="POST">
                    <input type="hidden" name="type" value="expense">
                    <input type="number" name="amount" step="0.01" placeholder="Amount" required>
                    <select name="category_source" required>
                        <option value="" disabled selected>Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="cta-button expense-btn">Save Expense</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>