<?php
session_start();

// Check if the user is already logged in, redirect to index.php
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

require_once 'config.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password cannot be empty.";
    } elseif (strlen($username) < 3 || strlen($password) < 6) {
        $error = "Username must be at least 3 characters and password at least 6 characters.";
    } else {
        // Connect to the database
        $conn = getConnection();

        // Prepare and execute the query to fetch user data
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Compare the password directly
            if ($password === $user['password']) {
                // Set session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];

                // Redirect to index.php
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2 class="text-center">Login</h2>
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" class="toffee-form">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="text-center">
                <button type="submit" class="btn">Login</button>
            </div>
        </form>
    </div>
</body>
</html>
