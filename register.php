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
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="input-group">
                    <label for="middle_name">Middle Name (Optional)</label>
                    <input type="text" id="middle_name" name="middle_name">
                </div>
                <div class="input-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="input-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
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

    <!-- Custom Popup Modal HTML -->
    <div id="popupModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 id="modalTitle">Notice</h3>
            <p id="modalMessage">This is a message.</p>
            <button id="modalBtn" class="cta-button">OK</button>
        </div>
    </div>

    <?php
    if (file_exists('db.php')) {
        require 'db.php';
    } else {
        echo "<script>console.error('db.php not found');</script>";
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $last_name = $_POST['last_name'];
        $gender = $_POST['gender'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO users (first_name, middle_name, last_name, gender, phone, email, password) 
                    VALUES (:first_name, :middle_name, :last_name, :gender, :phone, :email, :password)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'gender' => $gender,
                'phone' => $phone,
                'email' => $email,
                'password' => $password
            ]);
            
            // Trigger custom success popup and redirect to login on click
            echo "<script>
                    window.addEventListener('DOMContentLoaded', (event) => {
                        showModal('Success!', 'Registration successful!', true);
                    });
                  </script>";
        } catch (PDOException $e) {
            // Trigger custom error popup
            echo "<script>
                    window.addEventListener('DOMContentLoaded', (event) => {
                        showModal('Error', 'Could not register. Email might already exist.', false);
                    });
                  </script>";
        }
    }
    ?>

    <!-- JavaScript to handle modal behavior -->
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