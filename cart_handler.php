<?php
// cart_handler.php
session_start();
header('Content-Type: application/json');

// Include database connection
try {
    require 'db_connection.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please log in to add items to cart',
        'redirect' => 'login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'])
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// Validate user exists
try {
    $user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $user_check->bind_param("i", $userId);
    $user_check->execute();
    $user_result = $user_check->get_result();
    
    if ($user_result->num_rows === 0) {
        session_destroy();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid session. Please log in again.',
            'redirect' => 'login.php'
        ]);
        exit;
    }
    $user_check->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error validating user']);
    exit;
}

// Handle add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate inputs
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    if ($quantity <= 0 || $quantity > 99) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity. Please enter 1-99']);
        exit;
    }
    
    try {
        // Check if product exists and get price
        $product_stmt = $conn->prepare("SELECT product_id, name, price FROM products WHERE product_id = ?");
        $product_stmt->bind_param("i", $productId);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        
        if ($product_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        $product = $product_result->fetch_assoc();
        $product_stmt->close();
        
        // Calculate total price
        $totalPrice = $quantity * $product['price'];
        
        // Check if product already exists in cart
        $cart_check = $conn->prepare("SELECT cart_id, quantity, total_price FROM cart WHERE user_id = ? AND product_id = ?");
        $cart_check->bind_param("ii", $userId, $productId);
        $cart_check->execute();
        $cart_result = $cart_check->get_result();
        
        if ($cart_result->num_rows > 0) {
            // Update existing cart item
            $existing_cart = $cart_result->fetch_assoc();
            $new_quantity = $existing_cart['quantity'] + $quantity;
            $new_total = $existing_cart['total_price'] + $totalPrice;
            
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, total_price = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $update_stmt->bind_param("idii", $new_quantity, $new_total, $userId, $productId);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new cart item
            $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, total_price, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $insert_stmt->bind_param("iiid", $userId, $productId, $quantity, $totalPrice);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        $cart_check->close();
        
        // Get updated cart count
        $count_stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?");
        $count_stmt->bind_param("i", $userId);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $cart_count = $count_result->fetch_assoc()['total_items'] ?? 0;
        $count_stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_count' => (int)$cart_count,
            'product_name' => $product['name']
        ]);
        
    } catch (Exception $e) {
        error_log("Cart error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>