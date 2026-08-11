<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SpendWise</title>
    <link rel="stylesheet" href="login.css?v=3">
</head>
<body class="auth-page">

    <nav class="navbar">
        <a href="index.html" class="logo">SpendWise</a>
    </nav>

    <div class="auth-wrapper">
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
    </div>

    <!-- Custom Popup Modal HTML -->
    <div id="popupModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 id="modalTitle">Notice</h3>
            <p id="modalMessage">This is a message.</p>
            <button id="modalBtn" class="cta-button">OK</button>
        </div>
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
                    window.addEventListener('DOMContentLoaded', (event) => {
                        showModal('Success!', 'Admin login successful!', 'admin_dashboard.php');
                    });
                  </script>";
            exit();
        }

        try {
            $sql = "SELECT id, first_name, last_name, password FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['role'] = 'user';
                
                echo "<script>
                        window.addEventListener('DOMContentLoaded', (event) => {
                            showModal('Success!', 'Login successful!', 'dashboard.php');
                        });
                      </script>";
            } else {
                echo "<script>
                        window.addEventListener('DOMContentLoaded', (event) => {
                            showModal('Error', 'Invalid email or password.', null);
                        });
                      </script>";
            }
        } catch (PDOException $e) {
            echo "<script>
                    window.addEventListener('DOMContentLoaded', (event) => {
                        showModal('Error', 'Database error.', null);
                    });
                  </script>";
        }
    }
    ?>

    <!-- JavaScript to handle modal behavior -->
    <script>
        function showModal(title, message, redirectUrl) {
            const modal = document.getElementById('popupModal');
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalMessage').innerText = message;
            modal.style.display = 'flex';

            const btn = document.getElementById('modalBtn');
            btn.onclick = function() {
                modal.style.display = 'none';
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            }
        }
    </script>
</body>
</html>