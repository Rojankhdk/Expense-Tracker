<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    if ($delete_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        header("Location: admin_dashboard.php?msg=deleted");
        exit();
    }
}

$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | User Management</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .delete-btn {
            background-color: #C62828;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
        }
        .delete-btn:hover { background-color: #B71C1C; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-top">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
            </ul>
        </div>
        <div class="sidebar-bottom">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>User Management</h1>
        
        <div class="card">
            <h3>Registered Users</h3>
            <?php if(isset($_GET['msg'])): ?>
                <p style="color: green;">User deleted successfully.</p>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="admin_dashboard.php?delete=<?php echo $user['id']; ?>" 
                                   class="delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')">
                                   Delete
                                </a>
                            <?php else: ?>
                                <span>(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>