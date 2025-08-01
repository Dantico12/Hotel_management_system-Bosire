<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
require 'db_connection.php';

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the selected category from the URL, or default to 'all'
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Get the selected sort option from the URL, or default to 'default'
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Query to select products based on the selected category
$query = "SELECT * FROM products";
if ($category !== 'all') {
    $query .= " WHERE category = ?";
}

// Add sorting to the query
if ($sort == 'price') {
    $query .= " ORDER BY price ASC";
}

// Prepare and execute the query
if ($stmt = $conn->prepare($query)) {
    if ($category !== 'all') {
        $stmt->bind_param('s', $category);
    }
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Box icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    <!-- Custom StyleSheet -->
    <link rel="stylesheet" href="style.css" />
    <title>Products</title>
</head>
<body>
    <div class="navigation">
        <div class="nav-center container d-flex">
            <a href="index.html" class="logo"><h1>Truce Food and Beaverages</h1></a>
            <ul class="nav-list d-flex">
                <li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="products.php?category=all" class="nav-link">All</a></li>
                <li class="nav-item"><a href="products.php?category=Girls" class="nav-link">Food</a></li>
                <li class="nav-item"><a href="products.php?category=Boys" class="nav-link">Beaverages</a></li>
                <li class="nav-item"><a href="products.php?category=Accessories" class="nav-link">Specials</a></li>
                <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
                <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
                <li class="icons d-flex">
                    <a href="login.html" class="icon"><i class="bx bx-user"></i></a>
                   
                    <a href="cart.php" class="icon"><i class="bx bx-cart"></i><span class="d-flex">0</span></a>
                </li>
            </ul>
        </div>
    </div>
    <!-- All Products -->
    <section class="section all-products" id="products">
        <div class="top container">
            <h1>All Products</h1>
            <!-- Sorting Form -->
            <form method="GET">
                <!-- Preserve selected category in the sort form -->
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <select name="sort">
                    <option value="default" <?php if ($sort == 'default') echo 'selected'; ?>>Default Sorting</option>
                    <option value="price" <?php if ($sort == 'price') echo 'selected'; ?>>Sort By Price</option>
                </select>
                <button type="submit">Sort</button>
            </form>
        </div>
        <div class="product-center container">
            <?php
            // Check if any products are found
            if ($result->num_rows > 0) {
                // Loop through each product and display
                while ($row = $result->fetch_assoc()) {
                    // Construct the correct image path
                    $image_path = $row['image'];
                    
                    // Ensure that the image path is properly formatted
                    echo '
                    <div class="product-item">
                        <div class="overlay">
                            <a href="productDetails.php?product_id=' . htmlspecialchars($row['product_id']) . '" class="product-thumb">
                                <img src="' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($row['name']) . '" />
                            </a>
                            <span class="discount">40%</span>
                        </div>
                        <div class="product-info">
                            <span>' . htmlspecialchars($row['category']) . '</span>
                            <a href="productDetails.php?product_id=' . htmlspecialchars($row['product_id']) . '">' . htmlspecialchars($row['name']) . '</a>
                            <h4>Ksh' . htmlspecialchars($row['price']) . '</h4>
                            <a href="productDetails.php?product_id=' . htmlspecialchars($row['product_id']) . '" class="details-button">Details</a>
                        </div>
                        <ul class="icons">
                            <li><a href="productDetails.php?product_id=' . htmlspecialchars($row['product_id']) . '" class="icon"><i class="bx bx-cart"></i></a></li>
                        </ul>
                    </div>';
                }
            } else {
                echo "<p>No products found.</p>";
            }
            // Close the database connection
            $conn->close();
            ?>
        </div>
    </section>
    <section class="pagination">
        <div class="container">
            <span>1</span> <span>2</span> <span>3</span> <span>4</span>
            <span><i class="bx bx-right-arrow-alt"></i></span>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer">
        <div class="row">
            <div class="col d-flex">
                <h4>INFORMATION</h4>
                <a href="">About us</a>
                <a href="">Contact Us</a>
                <a href="">Term & Conditions</a>
                <a href="">Shipping Guide</a>
            </div>
            <div class="col d-flex">
                <h4>USEFUL LINK</h4>
                <a href="">Online Store</a>
                <a href="">Customer Services</a>
                <a href="">Promotion</a>
                <a href="">Top Brands</a>
            </div>
            <div class="col d-flex">
                <span><i class="bx bxl-facebook-square"></i></span>
                <span><i class="bx bxl-instagram-alt"></i></span>
                <span><i class="bx bxl-github"></i></span>
                <span><i class="bx bxl-twitter"></i></span>
                <span><i class="bx bxl-pinterest"></i></span>
            </div>
        </div>
    </footer>
    <!-- Custom Script -->
    <script src="./js/index.js"></script>
</body>
</html>