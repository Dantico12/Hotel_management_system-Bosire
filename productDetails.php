<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Let's see what's in the session
echo "<!-- DEBUG SESSION DATA: ";
print_r($_SESSION);
echo " -->";

// Include database connection
try {
    require 'db_connection.php';
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize variables
$productId = null;
$product = null;
$error = null;
$success = null;

// Debug: Show what we're looking for
$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && !empty($_SESSION['user_id']);
echo "<!-- DEBUG: User logged in status: " . ($isLoggedIn ? 'YES' : 'NO') . " -->";
echo "<!-- DEBUG: user_id exists: " . (isset($_SESSION['user_id']) ? 'YES - ' . $_SESSION['user_id'] : 'NO') . " -->";
echo "<!-- DEBUG: user_logged_in exists: " . (isset($_SESSION['user_logged_in']) ? 'YES - ' . $_SESSION['user_logged_in'] : 'NO') . " -->";

// Get user ID from session (handle both possible field names)
$currentUserId = null;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $currentUserId = $_SESSION['user_id'];
} elseif (isset($_SESSION['username'])) {
    // If user_id is empty, let's try to get it from the database using username
    try {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $currentUserId = $user['user_id']; // Fixed: use user_id instead of id
            $_SESSION['user_id'] = $currentUserId; // Update session with correct user_id
        }
        $stmt->close();
    } catch (Exception $e) {
        echo "<!-- DEBUG: Error getting user ID: " . $e->getMessage() . " -->";
    }
}

$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && !empty($currentUserId);

// Validate and get product ID
if (isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    $productId = (int)$_GET['product_id'];

    // Fetch product details with better error handling
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $productId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Get result failed: " . $stmt->error);
        }

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
        } else {
            $error = "Product not found (ID: $productId)";
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "Error fetching product details: " . $e->getMessage();
    }
} else {
    $error = "Invalid product ID. Received: " . (isset($_GET['product_id']) ? $_GET['product_id'] : 'none');
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    echo "<!-- DEBUG: Add to cart form submitted -->";
    
    // Check if product data is available
    if (!$product) {
        $error = "Cannot add to cart: Product data not available";
    } elseif (!$isLoggedIn) {
        echo "<!-- DEBUG: User not logged in, redirecting -->";
        // Store the current page to redirect back after login
        $redirectUrl = $_SERVER['REQUEST_URI'];
        header("Location: login.php?redirect=" . urlencode($redirectUrl));
        exit();
    } else {
        echo "<!-- DEBUG: User is logged in, proceeding with cart addition -->";
        $userId = $currentUserId;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if ($quantity <= 0 || $quantity > 99) {
            $error = "Please enter a valid quantity (1-99)";
        } else {
            // Validate product price exists and is numeric
            if (!isset($product['price']) || !is_numeric($product['price'])) {
                $error = "Invalid product price";
            } else {
                $totalPrice = $quantity * $product['price'];

                try {
                    // Fixed: Use consistent column name - either user_id throughout or id throughout
                    // Verify user exists - FIXED THE COLUMN MISMATCH
                    $user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
                    $user_check->bind_param("i", $userId);
                    $user_check->execute();
                    $user_result = $user_check->get_result();
                    
                    if ($user_result->num_rows === 0) {
                        $error = "Invalid user session. Please log in again.";
                        session_destroy();
                        header("Location: login.php");
                        exit();
                    }
                    $user_check->close();

                    // Check if the product is already in the cart
                    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("ii", $userId, $productId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // Update existing cart item
                        $existing = $result->fetch_assoc();
                        $newQuantity = $existing['quantity'] + $quantity;
                        $newTotal = $existing['total_price'] + $totalPrice;
                        
                        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, total_price = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                        $update_stmt->bind_param("idii", $newQuantity, $newTotal, $userId, $productId);
                        
                        if ($update_stmt->execute()) {
                            $success = "Product quantity updated in cart successfully!";
                        } else {
                            $error = "Error updating cart: " . $update_stmt->error;
                        }
                        $update_stmt->close();
                    } else {
                        // Insert new cart item
                        $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, total_price, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                        $insert_stmt->bind_param("iiid", $userId, $productId, $quantity, $totalPrice);
                        
                        if ($insert_stmt->execute()) {
                            $success = "Product added to cart successfully!";
                        } else {
                            $error = "Error adding to cart: " . $insert_stmt->error;
                        }
                        $insert_stmt->close();
                    }
                    $stmt->close();

                    // If successful, redirect to cart page after a brief delay
                    if ($success) {
                        header("refresh:2;url=cart.php");
                    }
                    
                } catch (Exception $e) {
                    $error = "Error adding product to cart: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['name']) : 'Product Details'; ?> - Thika Baby World</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <style>
        /* Product Details Specific Styles */
        .product-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .product-details {
            display: flex;
            gap: 2rem;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            flex: 1;
            min-width: 300px;
            max-width: 500px;
            aspect-ratio: 1;
            position: relative;
            background-color: #f8f8f8;
            border-radius: 8px;
            overflow: hidden;
        }

        .product-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 1rem;
            background-color: white;
            transition: transform 0.3s ease;
        }

        .product-image img:hover {
            transform: scale(1.05);
        }

        .product-info {
            flex: 1;
            min-width: 300px;
        }

        @media (max-width: 768px) {
            .product-details {
                flex-direction: column;
                align-items: center;
            }

            .product-image {
                width: 100%;
                max-width: 400px;
            }

            .product-info {
                width: 100%;
            }
        }

        .product-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .product-category {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .product-description {
            color: #444;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .product-price {
            font-size: 1.5rem;
            color: #e44d26;
            font-weight: bold;
            margin-bottom: 2rem;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quantity-input input {
            width: 80px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .add-to-cart-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.3s;
        }

        .add-to-cart-btn:hover {
            background-color: #45a049;
        }

        .add-to-cart-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .error {
            background-color: #ffe6e6;
            color: #d63031;
            border: 1px solid #ff7675;
        }

        .success {
            background-color: #e6ffe6;
            color: #27ae60;
            border: 1px solid #2ecc71;
        }

        .breadcrumb {
            padding: 1rem 0;
            color: #666;
        }

        .breadcrumb a {
            color: #4CAF50;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .login-prompt {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .login-prompt a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        .debug-info {
            background-color: #f0f0f0;
            padding: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <div class="container d-flex">
            <p>Order online or call us: +254 719 415 624</p>
            <ul class="d-flex">
                <li><a href="about.php">About Us</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact Us</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Navigation -->
    <div class="navigation">
        <div class="nav-center container d-flex">
            <a href="index.php" class="logo">
                <h2>Thika Baby World</h2>
            </a>
            <ul class="nav-list d-flex">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="products.php" class="nav-link">Shop</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                <li class="nav-item">
                    <a href="cart.php" class="nav-link">
                        <i class='bx bx-cart'></i> Cart
                    </a>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="login.php" class="nav-link">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="breadcrumb container">
        <a href="index.php">Home</a> &gt; 
        <a href="products.php">Shop</a> &gt; 
        <span><?php echo $product ? htmlspecialchars($product['name']) : 'Product Details'; ?></span>
    </div>

    <div class="product-container">
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
                <?php if (!$product): ?>
                    <br><br>
                    <a href="products.php" class="add-to-cart-btn" style="display: inline-block; text-decoration: none;">
                        Browse All Products
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
                <br><small>Redirecting to cart in 2 seconds...</small>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
            <div class="product-details">
                <div class="product-image">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                    <?php else: ?>
                        <img src="placeholder.jpg" alt="Image not available" />
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h2 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                    <p class="product-price">KSh <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></p>

                    <?php if (!$isLoggedIn): ?>
                        <div class="login-prompt">
                            <i class='bx bx-info-circle'></i>
                            Please <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">login</a> to add items to your cart.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="quantity-input">
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" min="1" max="99" value="1" required>
                        </div>
                        <button type="submit" name="add_to_cart" class="add-to-cart-btn" 
                                <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
                            <?php echo $isLoggedIn ? 'Add to Cart' : 'Login Required'; ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container d-flex">
            <div class="footer-left">
                <p>&copy; 2024 Thika Baby World. All Rights Reserved.</p>
            </div>
            <div class="footer-right">
                <p>Follow us on:</p>
                <a href="#"><i class='bx bxl-facebook'></i></a>
                <a href="#"><i class='bx bxl-twitter'></i></a>
                <a href="#"><i class='bx bxl-instagram'></i></a>
            </div>
        </div>
    </footer>
</body>
</html>