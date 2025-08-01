<?php
    // [Previous PHP code remains the same]
    require 'db_connection.php';
    
    $user_query = "SELECT COUNT(*) AS total_users FROM users";
    $user_result = mysqli_query($conn, $user_query);
    $user_data = mysqli_fetch_assoc($user_result);
    $total_users = $user_data['total_users'];

    $product_query = "SELECT COUNT(*) AS total_products FROM products";
    $product_result = mysqli_query($conn, $product_query);
    $product_data = mysqli_fetch_assoc($product_result);
    $total_products = $product_data['total_products'];

    $sales_query = "SELECT SUM(total_amount) AS total_amount FROM orders";
    $sales_result = mysqli_query($conn, $sales_query);
    $sales_data = mysqli_fetch_assoc($sales_result);
    $total_sales = $sales_data['total_amount'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thika Baby World Admin Dashboard</title>
    <style>
        /* Reset and basic styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background:brown;
            margin: 0;
            display: flex;
            min-height: 100vh;
        }
        .hamburger-menu {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1000;
            cursor: pointer;
            width: 30px;
            height: 20px;
            flex-direction: column;
            justify-content: space-between;
        }

        .hamburger-menu span {
            width: 100%;
            height: 3px;
            background-color: black;
            transition: all 0.3s ease;
        }

        .hamburger-menu.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger-menu.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-menu.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }


        /* [Previous sidebar styles remain exactly the same] */
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
            border-left-color: #007BFF;
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
            color: #007BFF;
        }

        /* Sidebar icons */
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
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li:nth-child(5) a::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li:nth-child(6) a::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z'/%3E%3C/svg%3E");
        }

        /* Updated Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 40px;
            flex: 1;
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
        }

        .welcome-header {
            text-align: center;
            margin-bottom: 50px;
            color: #2c3e50;
            font-size: 2.5em;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        /* New Circular Card Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px;
            justify-items: center;
        }

        /* Updated Circular Card Design */
        .dashboard-card {
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
            border-radius: 50%;
        }

        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .dashboard-card h2 {
            color: #2c3e50;
            font-size: 1.2em;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
            z-index: 1;
        }

        .dashboard-card p {
            color: #007BFF;
            font-size: 1.5em;
            font-weight: bold;
            margin: 10px 0;
            z-index: 1;
        }

        .dashboard-card a {
            color: #007BFF;
            text-decoration: none;
            font-weight: 500;
            margin-top: 10px;
            transition: color 0.3s ease;
            z-index: 1;
        }

        .dashboard-card a:hover {
            color: #0056b3;
        }

        /* Card Color Variants */
        .dashboard-card:nth-child(1) {
            background: linear-gradient(135deg, #fff 0%, #e3f2fd 100%);
        }

        .dashboard-card:nth-child(2) {
            background: linear-gradient(135deg, #fff 0%, #e8f5e9 100%);
        }

        .dashboard-card:nth-child(3) {
            background: linear-gradient(135deg, #fff 0%, #fff3e0 100%);
        }

        .dashboard-card:nth-child(4) {
            background: linear-gradient(135deg, #fff 0%, #f3e5f5 100%);
        }

        /* Footer Styles */
        footer {
            margin-left: 250px;
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 15px;
            position: fixed;
            bottom: 0;
            width: calc(100% - 250px);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

/* Responsive Adjustments */
@media (max-width: 768px) {
            .hamburger-menu {
                display: flex;
            }

            .main-content {
                width: 100%;
            }
        }

    </style>
   
</head>
<body>
     <!-- Hamburger Menu -->
     <div class="hamburger-menu">
        <span></span>
        <span></span>
        <span></span>
    </div>

    
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
    
    <main class="main-content">
        <h1 class="welcome-header">Welcome To Thika Baby World Admin Dashboard</h1>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h2>User Management</h2>
                <p><?php echo number_format($total_users); ?></p>
                <span>Total Users</span>
            </div>
            
            <div class="dashboard-card">
                <h2>Product Management</h2>
                <p><?php echo number_format($total_products); ?></p>
                <span>Total Products</span>
            </div>
            
            <div class="dashboard-card">
                <h2>Sales Overview</h2>
                <p>$<?php echo number_format($total_sales, 2); ?></p>
                <span>Total Revenue</span>
            </div>
            
            <div class="dashboard-card">
                <h2>Inventory</h2>
                <p><a href="inventory.php">Manage →</a></p>
                <span>Stock Control</span>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; 2024 Thika Baby World. All rights reserved.</p>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.querySelector('.hamburger-menu');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const menuItems = document.querySelectorAll('.menu-item');

            function toggleSidebar() {
                hamburgerMenu.classList.toggle('active');
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('blurred');
            }

            // Hamburger menu click event
            hamburgerMenu.addEventListener('click', toggleSidebar);

            // Close sidebar when a menu item is clicked
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        // If on the same page, close the sidebar
                        if (window.location.href.includes(this.getAttribute('href'))) {
                            toggleSidebar();
                        }
                    }
                });
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (
                    sidebar.classList.contains('active') && 
                    !sidebar.contains(event.target) && 
                    !hamburgerMenu.contains(event.target)
                ) {
                    toggleSidebar();
                }
            });
        });
    </script>
</body>
</html>