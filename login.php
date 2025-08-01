<?php
session_start();



require 'db_connection.php'; // Include the database connection

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']); // Trim whitespace
    $password = $_POST['password'];

    // Check for admin credentials
    $adminQuery = "SELECT * FROM admins WHERE username = ?";
    $stmt = $conn->prepare($adminQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        // Verify the hashed password
        if (password_verify($password, $admin['password'])) {
            // Set session variables for admin
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $admin['id']; // Set user_id for consistency
            header('Location: admin_dashboard.php');
            exit();
        }
    }

    // Check for regular user credentials
    $userQuery = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            // Set session variables for user
            $_SESSION['user_role'] = 'user';
            $_SESSION['user_logged_in'] = true;
            // Fix: Use the correct field name for user ID
            $_SESSION['user_id'] = isset($user['id']) ? $user['id'] : (isset($user['user_id']) ? $user['user_id'] : null);
            $_SESSION['username'] = $user['username']; // Store username
            $_SESSION['email'] = $user['email']; // Store email
            
            // Handle redirect after successful login
            $redirectUrl = 'index.php'; // default redirect
            if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                $redirectUrl = $_POST['redirect'];
            } elseif (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                $redirectUrl = $_GET['redirect'];
            }
            
            // Validate redirect URL to prevent open redirect attacks
            $parsedUrl = parse_url($redirectUrl);
            if ($parsedUrl && (!isset($parsedUrl['host']) || $parsedUrl['host'] === $_SERVER['HTTP_HOST'])) {
                header("Location: " . $redirectUrl);
            } else {
                header("Location: index.php");
            }
            exit();
        }
    }
    
    // Handle invalid login
    $error_message = "Invalid username or password.";
}

// Get redirect URL for the form
$redirectUrl = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login">
    <div class="box">
        <span class="borderLine"></span>
        <form method="POST" action="">
            <h2>Login</h2>
            
            <?php if (!empty($redirectUrl)): ?>
                <div style="background-color: #e3f2fd; color: #1976d2; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px;">
                    Please login to continue with your action.
                </div>
                <input type="hidden" name="redirect" value="<?php echo $redirectUrl; ?>">
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <p style="color: red;"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>
            
            <div class="inputbox">
                <input type="text" name="username" required="required" placeholder="Username">
                <span>Username</span>
                <i></i>
            </div>
            <div class="inputbox">
                <input type="password" name="password" required="required" placeholder="Password">
                <span>Password</span>
                <i></i>
            </div>
            <div class="links">
                <a href="#">Forgot password?</a>
                <a href="signup.php<?php echo !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : ''; ?>">Signup</a>
            </div>
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>