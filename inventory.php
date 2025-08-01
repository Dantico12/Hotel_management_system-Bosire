<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require 'db_connection.php';

// Your existing PHP code for database queries and form processing remains the same
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM orders s WHERE s.product_id = p.product_id) as times_sold,
          (SELECT SUM(quantity) FROM orders s WHERE s.product_id = p.product_id) as total_sold
          FROM products p";
$result = $conn->query($query);

// Process sale if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_sale'])) {
    // Your existing sale processing code remains the same
}

$movements_query = "SELECT m.*, p.name, 
                   CASE WHEN m.movement_type = 'in' THEN 'Stock Added' ELSE 'Stock Removed' END as movement_description
                   FROM inventory_movements m 
                   JOIN products p ON m.product_id = p.product_id 
                   ORDER BY m.created_at DESC 
                   LIMIT 10";
// Fetch recent movements
$movements = $conn->query($movements_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
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
            background-color: #f4f4f4;
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

        /* Main content styles */
        .main-container {
            margin-left: 250px;
            padding: 40px;
            flex-grow: 1;
            width: calc(100% - 250px);
            background-color: #f4f4f4;
        }

        .table-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .inventory-list, .inventory-movements {
            flex: 1;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 24px;
        }

        h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background-color: #fff;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #34495e;
            color: white;
            font-weight: normal;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .sale-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .sale-form input[type="number"] {
            width: 60px;
            padding: 4px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        button:hover {
            background-color: #45a049;
        }

        @media (max-width: 1200px) {
            .table-container {
                flex-direction: column;
            }

            .inventory-list, .inventory-movements {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-container {
                margin-left: 0;
                width: 100%;
                padding: 20px;
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

    <div class="main-container">
        <h2>Inventory Management</h2>
        
        <div class="table-container">
            <div class="inventory-list">
                <h3>Current Stock</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Sold</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo $row['times_sold'] ?? 0; ?></td>
                            <td><?php echo $row['total_sold'] ?? 0; ?></td>
                            <td>
                                <form method="POST" action="" class="sale-form">
                                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                    <input type="number" name="quantity" min="1" max="<?php echo $row['quantity']; ?>" required>
                                    <button type="submit" name="make_sale">Sale</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="inventory-movements">
                <h3>Recent Movements</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($movement = $movements->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($movement['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($movement['name']); ?></td>
                            <td><?php echo $movement['movement_description']; ?></td>
                            <td><?php echo $movement['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($movement['reason']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>