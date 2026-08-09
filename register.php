<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SpendWise</title>
    <link rel="stylesheet" href="register.css?v=3">
</head>
<body class="auth-page">

    <nav class="navbar">
        <a href="index.html" class="logo">SpendWise</a>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-container">
            <h2>Create Account</h2>
            <form action="register.php" method="POST">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="cta-button">Register</button>
            </form>
            <p class="auth-footer">Already have an account? <a href="login.php">Login here</a></p>
            <p class="auth-footer"><a href="index.html">Back to Home</a></p>
        </div>
    </div>

    <div id="popupModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 id="modalTitle">Notice</h3>
            <p id="modalMessage">This is a message.</p>
            <button id="modalBtn" class="cta-button">OK</button>
        </div>
    </div>

    <?php
    require 'db.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO user (username, email, password) VALUES (:username, :email, :password)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $password
            ]);

            echo "<script>
                    window.addEventListener('DOMContentLoaded', (event) => {
                        showModal('Success!', 'Registration successful!', true);
                    });
                  </script>";
        } catch (PDOException $e) {
            echo "<script>
                    window.addEventListener('DOMContentLoaded', (event) => {
                        showModal('Error', 'Could not register. Email might already exist.', false);
                    });
                  </script>";
        }
    }
    ?>

    <script>
        function showModal(title, message, isSuccess) {
            const modal = document.getElementById('popupModal');
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalMessage').innerText = message;
            modal.style.display = 'flex';

            const btn = document.getElementById('modalBtn');
            btn.onclick = function() {
                modal.style.display = 'none';
                if (isSuccess) {
                    window.location.href = 'login.php';
                }
            }
        }
    </script>
</body>
</html>
