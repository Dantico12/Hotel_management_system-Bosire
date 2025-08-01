<?php
session_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Database connection with error handling
try {
    require 'db_connection.php';

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize variables
$cartItems = [];
$totalPrice = 0.00;
$error = null;

// Modified Cart logic to fetch from cart_items table
if (isset($_SESSION['user_id'])) {
    fetchCartItems($conn, $cartItems, $totalPrice);
}

// Function to fetch cart items
function fetchCartItems($conn, &$cartItems, &$totalPrice) {
    try {
        // Join cart_items with products table to get product details
        $stmt = $conn->prepare("
            SELECT c.id as cart_item_id, 
                   c.quantity, 
                   p.product_id, 
                   p.name, 
                   p.price, 
                   p.image,
                   (p.price * c.quantity) as subtotal
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.user_id = ?
        ");
        
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = [
                'product_id' => $row['product_id'],
                'cart_item_id' => $row['cart_item_id'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'image' => $row['image'],
                'quantity' => (int)$row['quantity'],
                'subtotal' => (float)$row['subtotal']
            ];
            $totalPrice += $row['subtotal'];
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "Error processing cart: " . $e->getMessage();
    }
}

// Handle AJAX remove item request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    try {
        $productId = (int)$_POST['product_id'];
        // Delete from cart_items table instead of session
        $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $productId, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Fetch updated cart items after deletion
            $cartItems = [];
            $totalPrice = 0.00;
            fetchCartItems($conn, $cartItems, $totalPrice);

            // Respond with updated cart information
            echo json_encode(['success' => true, 'totalPrice' => $totalPrice, 'cartItems' => $cartItems]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Handle adding a product to the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    try {
        $productId = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity > 0) {
            // Check if item already exists in cart
            $stmt = $conn->prepare("
                SELECT id, quantity FROM cart
                WHERE user_id = ? AND product_id = ?
            ");
            $stmt->bind_param("ii", $_SESSION['user_id'], $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing cart item
                $cartItem = $result->fetch_assoc();
                $newQuantity = $cartItem['quantity'] + $quantity;
                $updateStmt = $conn->prepare("
                    UPDATE cart 
                    SET quantity = ? 
                    WHERE id = ?
                ");
                $updateStmt->bind_param("ii", $newQuantity, $cartItem['id']);
                $updateStmt->execute();
            } else {
                // Insert new cart item
                $insertStmt = $conn->prepare("
                    INSERT INTO cart(user_id, product_id, quantity) 
                    VALUES (?, ?, ?)
                ");
                $insertStmt->bind_param("iii", $_SESSION['user_id'], $productId, $quantity);
                $insertStmt->execute();
            }
            
            header("Location: cart.php");
            exit();
        }
    } catch (Exception $e) {
        $error = "Error adding to cart: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Thika Baby World</title>
    <link rel="stylesheet" href="style.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(session_id()); ?>">
</head>
<body>
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
            </ul>
        </div>
    </div>

    <div class="container cart">
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cartItems)): ?>
                    <tr>
                        <td colspan="4" class="empty-cart-message">
                            Your cart is empty. <a href="products.php">Continue shopping</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): ?>
                        <tr data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">
                            <td>
                                <div class="cart-info">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         loading="lazy">
                                    <div>
                                        <p><?php echo htmlspecialchars($item['name']); ?></p>
                                        <span>Price: $<?php echo number_format($item['price'], 2); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="number" 
                                       value="<?php echo htmlspecialchars($item['quantity']); ?>" 
                                       min="1" 
                                       readonly>
                            </td>
                            <td>
                                $<?php echo number_format($item['subtotal'], 2); ?>
                            </td>
                            <td>
                                <button class="remove-btn" 
                                        data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="total-price">
            <table>
                <tr>
                    <td>Total</td>
                    <td id="cart-total">$<?php echo number_format($totalPrice, 2); ?></td>
                </tr>
            </table>
            <?php if (!empty($cartItems)): ?>
                <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="row">
            <div class="col d-flex">
                <h4>INFORMATION</h4>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                </ul>
            </div>
            <div class="col d-flex">
                <h4>MY ACCOUNT</h4>
                <ul>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="cart.php">Cart</a></li>
                </ul>
            </div>
            <div class="col d-flex">
                <h4>NEWSLETTER</h4>
                <form action="">
                    <input type="email" placeholder="Your Email" required>
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div>
    </footer>

    <script>
        const removeButtons = document.querySelectorAll('.remove-btn');
        const cartTotal = document.getElementById('cart-total');

        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = button.getAttribute('data-product-id');
                const productRow = button.closest('tr');

                fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartDisplay(data.cartItems, data.totalPrice);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the item');
                });
            });
        });

        function updateCartDisplay(cartItems, newTotal) {
            // Clear the existing rows
            const cartBody = document.querySelector('tbody');
            cartBody.innerHTML = '';

            // Populate with new cart items
            cartItems.forEach(item => {
                const row = document.createElement('tr');
                row.setAttribute('data-product-id', item.id);
                row.innerHTML = `
                    <td>
                        <div class="cart-info">
                            <img src="${item.image}" alt="${item.name}" loading="lazy">
                            <div>
                                <p>${item.name}</p>
                                <span>Price: $${item.price.toFixed(2)}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <input type="number" value="${item.quantity}" min="1" readonly>
                    </td>
                    <td>
                        $${item.subtotal.toFixed(2)}
                    </td>
                    <td>
                        <button class="remove-btn" data-product-id="${item.id}">Remove</button>
                    </td>
                `;
                cartBody.appendChild(row);
            });

            // Update the cart total display
            cartTotal.textContent = `$${newTotal.toFixed(2)}`;
        }
    </script>
</body>
</html>
