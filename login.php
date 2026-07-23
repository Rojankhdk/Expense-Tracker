<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BudgetApp</title>
    <link rel="stylesheet" href="logincss.css">
</head>
<body>

    <div class="login-container">
        <!-- Linked back to index.php -->
        <a href="index.php" style="text-decoration: none;">
            <h2>Welcome Back</h2>
        </a>
        
        <form action="process_login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">LOG IN</button>
            <p class="signup-link">Don't have an account? <a href="#">Sign up here</a></p>
        </form>
    </div>

</body>
</html>