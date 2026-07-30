<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Logic: If the user saves a budget that already exists, update the amount instead of creating a new row
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
    
    header("Location: budget.php");
    exit();
}

// Fetch Budgets
$budgets = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ? ORDER BY month_year DESC, category ASC");
$budgets->execute([$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SpendWise | Budgets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-top">
            <h2>SpendWise</h2>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <!-- Corrected link to singular transaction.php -->
                <li><a href="transaction.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="budget.php" class="active"><i class="fas fa-wallet"></i> Budgets</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        <div class="sidebar-bottom">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Monthly Budget Planning</h1>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Set/Update Budget</h3>
                <form method="POST">
                    <input type="month" name="month_year" required>
                    
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

                    <input type="number" name="amount" step="0.01" placeholder="Budget Amount" required>
                    <button type="submit" class="cta-button expense-btn">Save/Update Budget</button>
                </form>
            </div>

            <div class="card">
                <h3>Your Set Budgets</h3>
                <table>
                    <thead>
                        <tr><th>Month</th><th>Category</th><th>Limit</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($budgets as $b): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['month_year']); ?></td>
                            <td><?php echo htmlspecialchars($b['category']); ?></td>
                            <td><?php echo number_format($b['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>