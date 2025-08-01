<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // If there's a redirect URL, go there, otherwise go to index
    $redirectUrl = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
    header("Location: " . $redirectUrl);
    exit();
}

require 'db_connection.php'; // Include your database connection file

$error_message = '';
$success_message = '';

// Handle signup
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $email = trim($_POST['email']); // Get the email address
    $phone = trim($_POST['phone']); // Get the phone number

    // Validate the email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check if username or email already exists in both tables
        $checkAdminQuery = "SELECT * FROM admins WHERE username = ? OR email = ?";
        $checkUserQuery = "SELECT * FROM users WHERE username = ? OR email = ?";

        // Check in admins table
        $stmt = $conn->prepare($checkAdminQuery);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $adminResult = $stmt->get_result();

        // Check in users table
        $stmt = $conn->prepare($checkUserQuery);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $userResult = $stmt->get_result();

        if ($adminResult->num_rows > 0 || $userResult->num_rows > 0) {
            $error_message = "Username or email already exists.";
        } else {
            // Determine which table to insert into
            if ($username === 'admin') {
                // Insert into admins table
                $insertQuery = "INSERT INTO admins (username, password, email) VALUES (?, ?, ?)";
            } else {
                // Insert into users table
                $insertQuery = "INSERT INTO users (username, password, email, phone) VALUES (?, ?, ?, ?)";
            }

            // Prepare and execute the insert statement
            $stmt = $conn->prepare($insertQuery);
            if ($username === 'admin') {
                $stmt->bind_param("sss", $username, $password, $email);
            } else {
                $stmt->bind_param("ssss", $username, $password, $email, $phone);
            }

            if ($stmt->execute()) {
                // Get the new user's ID
                $userId = $conn->insert_id;
                
                // Auto-login after successful registration (for regular users)
                if ($username !== 'admin') {
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    
                    // Handle redirect after successful registration
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
                } else {
                    // For admin registration, redirect to login page
                    $success_message = "Admin account created successfully! Please login.";
                }
            } else {
                $error_message = "Error during signup. Please try again.";
            }
        }
    }
}

// Get redirect URL for the form
$redirectUrl = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login">
    <div class="box">
        <span class="borderLine"></span>
        <form method="POST" action="">
            <h2>Signup</h2>
            
            <?php if (!empty($redirectUrl)): ?>
                <div style="background-color: #e3f2fd; color: #1976d2; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px;">
                    Create an account to continue with your action.
                </div>
                <input type="hidden" name="redirect" value="<?php echo $redirectUrl; ?>">
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <p style="color: red;"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <p style="color: green;"><?= htmlspecialchars($success_message) ?></p>
            <?php endif; ?>
            
            <div class="inputbox">
                <input type="text" name="username" required="required" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <span>Username</span>
                <i></i>
            </div>
            <div class="inputbox">
                <input type="password" name="password" required="required">
                <span>Password</span>
                <i></i>
            </div>
            <div class="inputbox">
                <input type="email" name="email" required="required"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <span>Email</span>
                <i></i>
            </div>
            <div class="inputbox">
                <input type="tel" name="phone" required="required"
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                <span>Phone Number</span>
                <i></i>
            </div>
            <div class="links">
                <a href="#">Forgot password?</a>
                <a href="login.php<?php echo !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : ''; ?>">Login</a>
            </div>
            <input type="submit" value="Signup">
        </form>
    </div>
</body>
</html>