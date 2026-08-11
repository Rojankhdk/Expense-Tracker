<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$popup_message = "";
$popup_title = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $pdo->prepare("
        INSERT INTO budgets (user_id, category, amount, month_year) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE amount = VALUES(amount)
    ");
    
    $stmt->execute([
        $_SESSION['user_id'], 
        $_POST['category'], 
        $_POST['amount'], 
        $_POST['month_year']
    ]);
    
    $popup_title = "Success!";
    $popup_message = "Budget saved successfully!";
}

// Fetch Budgets
$budgets = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ? ORDER BY month_year DESC, category ASC");
$budgets->execute([$_SESSION['user_id']]);

// Get current year-month string for default input value (e.g., '2026-08')
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
                <h3>Set / Update Budget</h3>
                <form method="POST">
                    <div class="input-group">
                        <label style="display:block; margin-bottom:5px; font-size:0.9rem; color:#8D6E63; font-weight:600;">Select Month</label>
                        <input type="month" name="month_year" value="<?php echo $default_month; ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <select name="category" required>
                            <option value="" disabled selected>Select Category</option>
                            <option value="Food">Food & Dining</option>
                            <option value="Rent">Rent / Housing</option>
                            <option value="Transport">Transport / Fuel</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Health">Health / Medical</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Savings">Savings</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <input type="number" name="amount" step="0.01" placeholder="Budget Amount" required>
                    </div>

                    <button type="submit" class="cta-button expense-btn">Save / Update Budget</button>
                </form>
            </div>

            <div class="card">
                <h3>Your Set Budgets</h3>
                <table>
                    <thead>
                        <tr><th>Month</th><th>Category</th><th>Limit</th></tr>
                    </thead>
                    <tbody>
                        <?php $budget_list = $budgets->fetchAll(PDO::FETCH_ASSOC); ?>
                        <?php if (count($budget_list) > 0): ?>
                            <?php foreach ($budget_list as $b): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['month_year']); ?></td>
                                <td><?php echo htmlspecialchars($b['category']); ?></td>
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

    <!-- Custom Popup Modal -->
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