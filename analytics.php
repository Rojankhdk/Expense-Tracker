<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Overall distribution of budget: total budgeted amount per category,
// summed across every month the user has set a budget for (not just one month).
$stmt = $pdo->prepare("
    SELECT c.category_name, SUM(b.amount) AS total_budget
    FROM monthly_budget b
    JOIN category c ON c.category_id = b.category_id
    WHERE b.user_id = ?
    GROUP BY c.category_id, c.category_name
    ORDER BY total_budget DESC
");
$stmt->execute([$user_id]);
$budget_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_labels = array_column($budget_by_category, 'category_name');
$chart_values = array_map('floatval', array_column($budget_by_category, 'total_budget'));
$grand_total  = array_sum($chart_values);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise | Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css?v=6">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
</head>
<body>

    <div class="sidebar">
        <div>
            <h2>SpendWise</h2>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="transaction.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budgets</a></li>
                <li><a href="analytics.php" class="active"><i class="fas fa-chart-pie"></i> Analytics</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Analytics</h1>

        <div class="card full-width">
            <h3>Overall Budget Distribution</h3>
            <?php if (count($budget_by_category) === 0): ?>
                <p>No budgets set yet. Set some on the <a href="budget.php">Budgets page</a> to see the distribution here.</p>
            <?php else: ?>
                <div class="chart-wrap">
                    <div class="chart-canvas-box">
                        <canvas id="budgetPieChart"></canvas>
                    </div>
                    <ul class="chart-legend">
                        <?php foreach ($budget_by_category as $row):
                            $pct = $grand_total > 0 ? ($row['total_budget'] / $grand_total) * 100 : 0;
                        ?>
                            <li>
                                <span class="legend-swatch" data-category="<?php echo htmlspecialchars($row['category_name']); ?>"></span>
                                <span class="legend-name"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                <span class="legend-value">
                                    <?php echo number_format($row['total_budget'], 2); ?>
                                    (<?php echo number_format($pct, 1); ?>%)
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        <?php if (count($budget_by_category) > 0): ?>
        const budgetLabels = <?php echo json_encode($chart_labels); ?>;
        const budgetValues = <?php echo json_encode($chart_values); ?>;

        // Same warm palette as the rest of the app, cycled if there are more
        // categories than colors.
        const palette = [
            '#8D6E63', '#C62828', '#2E7D32', '#E67E22', '#5D4037',
            '#3E2723', '#A1887F', '#D7CCC8', '#F4511E', '#6D4C41'
        ];
        const colors = budgetLabels.map((_, i) => palette[i % palette.length]);

        // Give each legend swatch the same color as its slice
        document.querySelectorAll('.legend-swatch').forEach((el, i) => {
            el.style.backgroundColor = colors[i];
        });

        new Chart(document.getElementById('budgetPieChart'), {
            type: 'pie',
            data: {
                labels: budgetLabels,
                datasets: [{
                    data: budgetValues,
                    backgroundColor: colors,
                    borderColor: '#FDFBF7',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.parsed;
                                const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
