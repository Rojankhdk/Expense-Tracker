<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SpendWise</title>
    <link rel="stylesheet" href="login.css">
</head>
<body class="auth-page">

    <div class="auth-container">
        <h2>Login</h2>
        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="cta-button">Login</button>
        </form>
        <p class="auth-footer">Don't have an account? <a href="register.php">Register here</a></p>
        <p class="auth-footer"><a href="index.html">Back to Home</a></p>
    </div>

    <?php
    session_start();
    if (file_exists('db.php')) {
        require 'db.php';
    }

    define('ADMIN_EMAIL', 'admin@spendwise.com');
    define('ADMIN_PASSWORD', 'Password123'); 

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
            $_SESSION['user_id'] = 0; 
            $_SESSION['user_name'] = 'System Admin';
            $_SESSION['role'] = 'admin';
            
            echo "<script>
                    alert('Admin login successful!'); 
                    window.location.href='admin_dashboard.php';
                  </script>";
            exit();
        }

        try {
            $sql = "SELECT id, full_name, password FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = 'user';
                
                echo "<script>
                        alert('Login successful!'); 
                        window.location.href='dashboard.php';
                      </script>";
            } else {
                echo "<script>alert('Invalid email or password.');</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Database error.');</script>";
        }
    }
    ?>
</body>
</html>