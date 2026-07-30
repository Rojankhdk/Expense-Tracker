<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SpendWise</title>
    <link rel="stylesheet" href="register.css">
</head>
<body class="auth-page">

    <div class="auth-container">
        <h2>Create Account</h2>
        <form action="register.php" method="POST">
            <div class="input-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
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

    <?php
    // We include the db connection here
    if (file_exists('db.php')) {
        require 'db.php';
    } else {
        echo "<script>console.error('db.php not found');</script>";
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $full_name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO users (full_name, email, password) VALUES (:name, :email, :password)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['name' => $full_name, 'email' => $email, 'password' => $password]);
            
            // This JS will run after the page loads
            echo "<script>
                    alert('Registration successful!'); 
                    window.location.href='login.php';
                  </script>";
        } catch (PDOException $e) {
            // Using error_log for security so you don't show the user database structure
            echo "<script>alert('Error: Could not register. Email might already exist.');</script>";
        }
    }
    ?>
</body>
</html>