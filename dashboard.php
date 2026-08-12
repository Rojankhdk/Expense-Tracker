<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$popup_message = "";
$popup_title = "";

// 1. Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($_POST['form_type'] == 'add_category') {
        $category_name = trim($_POST['category_name']);
        if ($category_name !== '') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO category (user_id, category_name) VALUES (?, ?)");
            $stmt->execute([$user_id, $category_name]);
        }
        $popup_title = "Success!";
        $popup_message = "Category added!";
    }

    if ($_POST['form_type'] == 'income') {
        $amount = $_POST['amount'];
        $source = $_POST['source'];

        $stmt = $pdo->prepare("INSERT INTO income (user_id, amount, source) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $amount, $source]);
        $popup_title = "Success!";
        $popup_message = "Income added successfully!";
    }

    if ($_POST['form_type'] == 'expense') {
        $amount = $_POST['amount'];
        $category_id = $_POST['category_id'];
        $description = $_POST['description'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category_id, amount, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $category_id, $amount, $description]);
        $popup_title = "Success!";
        $popup_message = "Expense added successfully!";
    }
}

$current_month = date('Y-m');

// 2. Fetch Totals for Balance
$inc_stmt = $pdo->prepare("SELECT SUM(amount) FROM income WHERE user_id = ? AND DATE_FORMAT(income_date, '%Y-%m') = ?");
$inc_stmt->execute([$user_id, $current_month]);
$total_income = $inc_stmt->fetchColumn() ?: 0;

$exp_stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?");
$exp_stmt->execute([$user_id, $current_month]);
$total_expenses = $exp_stmt->fetchColumn() ?: 0;

$remaining = $total_income - $total_expenses;

// 3. Fetch Budget Overview (joined through category_id, not category name)
$summary_stmt = $pdo->prepare("
    SELECT c.category_name, b.amount AS budget_limit, IFNULL(SUM(e.amount), 0) AS actual_spent
    FROM monthly_budget b
    JOIN category c ON c.category_id = b.category_id
    LEFT JOIN expenses e ON e.category_id = b.category_id
        AND e.user_id = b.user_id
        AND DATE_FORMAT(e.expense_date, '%Y-%m') = b.month_year
    WHERE b.user_id = ? AND b.month_year = ?
    GROUP BY b.budget_id, c.category_name, b.amount
");
$summary_stmt->execute([$user_id, $current_month]);
$summary = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch this user's categories (for the expense dropdown)
$cat_stmt = $pdo->prepare("SELECT category_id, category_name FROM category WHERE user_id = ? ORDER BY category_name");
$cat_stmt->execute([$user_id]);
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SpendWise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css?v=5">
</head>
<body>
    <div class="sidebar">
        <div>
            <h2>SpendWise</h2>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="transaction.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budgets</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Dashboard</h1>

        <div class="dashboard-grid">
            <div class="card balance-card">
                <h3>Remaining Balance</h3>
                <h2 style="color: <?php echo ($remaining >= 0) ? '#3E2723' : '#c0392b'; ?>;">
                    <?php echo number_format($remaining, 2); ?>
                </h2>
                <p class="balance-sub">Income: <strong><?php echo number_format($total_income, 2); ?></strong> | Expenses: <strong><?php echo number_format($total_expenses, 2); ?></strong></p>
            </div>
        </div>

        <div class="card full-width">
            <h3>Budget Overview (<?php echo $current_month; ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Limit</th>
                        <th>Spent</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($summary) > 0): ?>
                        <?php foreach ($summary as $row):
                            if ($row['actual_spent'] > $row['budget_limit']) {
                                $status = "Over Budget";
                                $status_color = "#c0392b";
                            } else {
                                $status = "Within Budget";
                                $status_color = "#27ae60";
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
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
                    <input type="hidden" name="form_type" value="income">
                    <div class="input-group">
                        <input type="number" name="amount" step="0.01" placeholder="Amount" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="source" placeholder="Source (e.g. Salary)" required>
                    </div>
                    <button type="submit" class="cta-button income-btn">Save Income</button>
                </form>
            </div>

            <div class="card">
                <h3>Add Expense</h3>
                <?php if (count($categories) === 0): ?>
                    <p>You don't have any categories yet. Add one first, then log an expense.</p>
                    <form method="POST">
                        <input type="hidden" name="form_type" value="add_category">
                        <div class="input-group">
                            <input type="text" name="category_name" placeholder="New category (e.g. Food)" required>
                        </div>
                        <button type="submit" class="cta-button expense-btn">Add Category</button>
                    </form>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="form_type" value="expense">
                        <div class="input-group">
                            <input type="number" name="amount" step="0.01" placeholder="Amount" required>
                        </div>
                        <div class="input-group">
                            <select name="category_id" required>
                                <option value="" disabled selected>Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <input type="text" name="description" placeholder="Description (optional)">
                        </div>
                        <button type="submit" class="cta-button expense-btn">Save Expense</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Custom Popup Modal -->
    <div id="popupModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 id="modalTitle">Success!</h3>
            <p id="modalMessage">Action completed successfully.</p>
            <button id="modalBtn" class="cta-button income-btn">OK</button>
        </div>
    </div>

    <script>
        function showModal(title, message) {
            const modal = document.getElementById('popupModal');
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalMessage').innerText = message;
            modal.style.display = 'flex';

            document.getElementById('modalBtn').onclick = function() {
                modal.style.display = 'none';
            }
        }

        <?php if (!empty($popup_message)): ?>
            window.addEventListener('DOMContentLoaded', () => {
                showModal("<?php echo $popup_title; ?>", "<?php echo $popup_message; ?>");
            });
        <?php endif; ?>
    </script>
</body>
</html>
