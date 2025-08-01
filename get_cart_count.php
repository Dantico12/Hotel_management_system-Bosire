<?php
// get_cart_count.php
session_start();
header('Content-Type: application/json');

// Include database connection
try {
    require 'db_connection.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'Database connection failed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get cart count for the user
    $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_count = 0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cart_count = (int)($row['total_items'] ?? 0);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'count' => $cart_count
    ]);
    
} catch (Exception $e) {
    error_log("Cart count error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Error fetching cart count'
    ]);
}
?>