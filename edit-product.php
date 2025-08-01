<?php
// [Previous PHP code remains the same until the HTML form]
// Include the database connection file
require 'db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    die("Error: User is not logged in");
}

// Initialize the product variable
$product = null;

// Fetch the product details if the ID is set
if (isset($_GET['product_id'])) {
    $id = $_GET['product_id'];
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product === null) {
        die("Product not found. ID received: " . htmlspecialchars($id));
    }
}

// Update the product if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image = $product['image']; // Keep existing image by default

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";

        // Create uploads directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Delete old image if it exists
            if (!empty($product['image']) && file_exists($product['image'])) {
                unlink($product['image']);
            }
            $image = $target_file;
        } else {
            echo "Failed to upload image. Error: " . $_FILES['image']['error'];
            exit();
        }
    }

    // Update product in database
    $sql = "UPDATE products SET name = ?, category = ?, description = ?, price = ?, image = ? WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $name, $category, $description, $price, $image, $id);

    if ($stmt->execute()) {
        header("Location: view_product.php?message=Product updated successfully");
        exit();
    } else {
        echo "Error updating product: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: grey;
            margin: 0;
            padding: 20px;
        }

        .container {
            min-height: 350px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 50px;
        }

        h1 {
            margin-bottom: 30px;
            color: #4CAF50;
            text-align: center;
            font-size: 2.5em;
        }

        .form-container {
            background-color: #2c2c2c;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #4CAF50;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #1a1a1a;
            color: #fff;
            font-size: 16px;
        }

        select option {
            background-color: #1a1a1a;
            color: #fff;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #1a1a1a;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.1s ease;
            text-decoration: none;
            min-width: 120px;
            text-align: center;
        }

        .btn:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-cancel {
            background-color: #dc3545;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }

        .current-image {
            margin-top: 10px;
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
            display: block;
        }

        .image-preview {
            margin-top: 10px;
            padding: 10px;
            background-color: #1a1a1a;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Edit Product</h1>
        <div class="form-container">
            <form method="post" action="edit-product.php?product_id=<?php echo htmlspecialchars($product['product_id']); ?>" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">

                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category" required>
                        <option value="boys" <?php echo ($product['category'] === 'boys') ? 'selected' : ''; ?>>Boys</option>
                        <option value="girls" <?php echo ($product['category'] === 'girls') ? 'selected' : ''; ?>>Girls</option>
                        <option value="accessories" <?php echo ($product['category'] === 'accessories') ? 'selected' : ''; ?>>Accessories</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" name="price" id="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="image">New Image:</label>
                    <input type="file" name="image" id="image" accept="image/*">
                    <div class="image-preview">
                        <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                            <p>Current Image:</p>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Current product image" class="current-image">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="button-group">
                    <a href="view_product.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>