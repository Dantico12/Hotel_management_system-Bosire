<?php
session_start();
require 'db_connection.php';

// Initialize variables
$productId = null;
$name = '';
$category = '';
$description = '';
$price = '';
$quantity = '';
$image = '';
$message = '';

// Check if an ID is provided for editing
if (isset($_GET['product_id'])) {
    $productId = $_GET['product_id'];

    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $name = $product['name'];
        $category = $product['category'];
        $description = $product['description'];
        $price = $product['price'];
        $quantity = $product['quantity'];
        $image = $product['image'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = 'uploads/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    } else {
        if (!$productId) {
            $image = '';
        } else {
            $image = $product['image'];
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        if ($productId) {
            // Update existing product
            $sql = "UPDATE products SET name=?, category=?, description=?, price=?, quantity=?, image=? WHERE product_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdssi", $name, $category, $description, $price, $quantity, $image, $productId);
            $stmt->execute();

            // Record inventory movement if quantity changed
            if ($quantity != $product['quantity']) {
                $quantity_difference = $quantity - $product['quantity'];
                $movement_type = $quantity_difference > 0 ? 'in' : 'out';
                $movement_quantity = abs($quantity_difference);
                $reason = 'Quantity adjustment';

                $stmt = $conn->prepare("INSERT INTO inventory_movements (product_id, movement_type, quantity, reason) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isis", $productId, $movement_type, $movement_quantity, $reason);
                $stmt->execute();
            }
        } else {
            // Add new product
            $sql = "INSERT INTO products (name, category, description, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdss", $name, $category, $description, $price, $quantity, $image);
            $stmt->execute();

            $product_id = $conn->insert_id;

            // Record initial inventory movement
            $movement_type = 'in';
            $reason = 'Initial stock';
            $stmt = $conn->prepare("INSERT INTO inventory_movements (product_id, movement_type, quantity, reason) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $product_id, $movement_type, $quantity, $reason);
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        // Redirect with success message
        header("Location: view_product.php?message=Product " . ($productId ? 'updated' : 'added') . " successfully!");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $productId ? 'Edit' : 'Add'; ?> Product</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            background-color: #1a1a1a;
            color: #fff;
        }

        /* Enhanced Sidebar styles */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            position: fixed;
            overflow-y: auto;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar nav {
            padding: 20px 0;
        }

        .sidebar nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .sidebar nav ul li {
            margin: 0;
            padding: 0;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar nav ul li:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #4CAF50;
        }

        .sidebar nav ul li a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .sidebar nav ul li a:hover {
            color: #4CAF50;
        }

        /* Add icons using pseudo-elements */
        .sidebar nav ul li a::before {
            content: '';
            width: 20px;
            height: 20px;
            margin-right: 10px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.7;
        }

        .sidebar nav ul li:nth-child(1) a::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li:nth-child(2) a::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li:nth-child(3) a::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li:nth-child(4) a::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li:nth-child(5) a::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li:nth-child(6) a::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z'/%3E%3C/svg%3E");
        }

        /* Main container adjustment for sidebar */
        .main-container {
            margin-left: 250px;
            padding: 40px;
            flex-grow: 1;
            width: calc(100% - 250px);
        }

        /* Rest of your existing add_product.php styles */
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 30px;
            color: #4CAF50;
            text-align: center;
            font-size: 2.5em;
        }

        /* Keep your existing form styles */
        form {
            background-color: #2c2c2c;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }
        .form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #4CAF50;
    font-size: 0.95rem;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #3a3a3a;
    border-radius: 6px;
    background-color: #333;
    color: #fff;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-group input[type="file"] {
    width: 100%;
    padding: 0.5rem;
    background-color: #333;
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.form-group select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 20px;
    padding-right: 2.5rem;
}

/* Button styling */
.button-group {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: flex-end;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

button.btn {
    background-color: #4CAF50;
    color: white;
}

button.btn:hover {
    background-color: #45a049;
    transform: translateY(-1px);
}

a.btn {
    background-color: #424242;
    color: white;
}

a.btn:hover {
    background-color: #505050;
    transform: translateY(-1px);
}

/* Message styling */
.message {
    margin-bottom: 2rem;
    padding: 1rem;
    border-radius: 6px;
    background-color: rgba(76, 175, 80, 0.1);
    border: 1px solid #4CAF50;
    color: #4CAF50;
    text-align: center;
}

/* Container styling */
.container {
    animation: fadeIn 0.3s ease-in-out;
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }

    .button-group {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}
    </style>
</head>
<body>
    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="add_product.php">Add Product</a></li>
                <li><a href="users.php">View Users</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="login.php">Logout</a></li>
            </ul>
        </nav>
    </aside>
        <div class="container">
            <h1><?php echo $productId ? 'Edit' : 'Add'; ?> Product</h1>
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="" disabled <?php echo $category == '' ? 'selected' : ''; ?>>Select a category</option>
                        <option value="Boys" <?php echo $category == 'Boys' ? 'selected' : ''; ?>>Food </option>
                        <option value="Girls" <?php echo $category == 'Girls' ? 'selected' : ''; ?>>Beaverages</option>
                        <option value="Accessories" <?php echo $category == 'Accessories' ? 'selected' : ''; ?>>Special offers</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" required  placeholder="Enter price">
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" required  placeholder="Enter quantity">
                </div>
                <div class="form-group">
                    <label for="image">Product Image:</label>
                    <input type="file" id="image" name="image">
                </div>
                <div class="button-group">
                    <a href="view_product.php" class="btn">Cancel</a>
                    <button type="submit" class="btn"><?php echo $productId ? 'Update' : 'Add'; ?> Product</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>