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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($_POST['form_type'] == 'add_category') {
        $category_name = trim($_POST['category_name']);
        if ($category_name !== '') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO category (user_id, category_name) VALUES (?, ?)");
            $stmt->execute([$user_id, $category_name]);
        }
        $popup_title = "Success!";
        $popup_message = "Category added successfully!";
    }

    if ($_POST['form_type'] == 'set_budget') {
        $stmt = $pdo->prepare("
            INSERT INTO monthly_budget (user_id, category_id, amount, month_year)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount)
        ");

        $stmt->execute([
            $user_id,
            $_POST['category_id'],
            $_POST['amount'],
            $_POST['month_year']
        ]);

        $popup_title = "Success!";
        $popup_message = "Budget saved successfully!";
    }
}


$cat_stmt = $pdo->prepare("SELECT category_id, category_name FROM category WHERE user_id = ? ORDER BY category_name");
$cat_stmt->execute([$user_id]);
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);


$budgets = $pdo->prepare("
    SELECT b.month_year, c.category_name, b.amount
    FROM monthly_budget b
    JOIN category c ON c.category_id = b.category_id
    WHERE b.user_id = ?
    ORDER BY b.month_year DESC, c.category_name ASC
");
$budgets->execute([$user_id]);
$budget_list = $budgets->fetchAll(PDO::FETCH_ASSOC);


$default_month = date('Y-m');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SpendWise | Budgets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css?v=6">
</head>
<body>

    <div class="sidebar">
        <div>
            <h2>SpendWise</h2>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="transaction.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="budget.php" class="active"><i class="fas fa-wallet"></i> Budgets</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Monthly Budget Planning</h1>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Your Categories</h3>
                <form method="POST" style="margin-bottom: 1rem;">
                    <input type="hidden" name="form_type" value="add_category">
                    <div class="input-group">
                        <input type="text" name="category_name" placeholder="New category (e.g. Food)" required>
                    </div>
                    <button type="submit" class="cta-button expense-btn">Add Category</button>
                </form>
                <ul>
                    <?php foreach ($categories as $cat): ?>
                        <li><?php echo htmlspecialchars($cat['category_name']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card">
                <h3>Set / Update Budget</h3>
                <?php if (count($categories) === 0): ?>
                    <p>Add a category first, then set a budget for it.</p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="form_type" value="set_budget">
                        <div class="input-group">
                            <label style="display:block; margin-bottom:5px; font-size:0.9rem; color:#8D6E63; font-weight:600;">Select Month</label>
                            <input type="month" name="month_year" value="<?php echo $default_month; ?>" required>
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
                            <input type="number" name="amount" step="0.01" placeholder="Budget Amount" required>
                        </div>

                        <button type="submit" class="cta-button expense-btn">Save / Update Budget</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Your Set Budgets</h3>
                <table>
                    <thead>
                        <tr><th>Month</th><th>Category</th><th>Limit</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($budget_list) > 0): ?>
                            <?php foreach ($budget_list as $b): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['month_year']); ?></td>
                                <td><?php echo htmlspecialchars($b['category_name']); ?></td>
                                <td><?php echo number_format($b['amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center;">No budgets configured yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div id="popupModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 id="modalTitle">Success!</h3>
            <p id="modalMessage">Action completed successfully.</p>
            <button id="modalBtn" class="cta-button expense-btn">OK</button>
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
