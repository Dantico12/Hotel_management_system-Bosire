<?php
// Include the database connection file
require 'db_connection.php';

// Fetch orders along with client names and product details
$sql_orders = "
    SELECT o.sale_id, id, o.total_amount, o.sale_date, u.username, u.email, u.phone, p.product_id, p.name, p.image 
    FROM orders o
    JOIN users u ON id = u.id
    JOIN orders op ON o.sale_id = op.sale_id
    JOIN products p ON id = id
";
$result_orders = $conn->query($sql_orders);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Order</title>
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

        /* Icons for sidebar navigation */
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

        /* Specific icon styles for each menu item */
        .sidebar nav ul li a[href*="dashboard"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 24 24'%3E%3Cpath d='M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li a[href*="add_product"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 24 24'%3E%3Cpath d='M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li a[href*="users"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 24 24'%3E%3Cpath d='M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li a[href*="orders"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 24 24'%3E%3Cpath d='M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li a[href*="inventory"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 24 24'%3E%3Cpath d='M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.69V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.7c.57-.35 1-.97 1-1.69V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm5-7H4V4h16v3z'/%3E%3C/svg%3E");
        }

        .sidebar nav ul li a[href*="login"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 24 24'%3E%3Cpath d='M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z'/%3E%3C/svg%3E");
        }


        .content {
            margin-left: 220px; /* Space for the sidebar */
            padding: 20px;
            flex: 1; /* Take up remaining space */
        }

        h1 {
            text-align: center;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .add-order-btn {
            display: block;
            width: 200px;
            padding: 10px;
            margin: 20px auto;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
        }

        .add-order-btn:hover {
            background-color: #45a049;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #2196F3; /* Blue */
        }

        .delete-btn {
            background-color: #f44336; /* Red */
        }

        .contact-btn {
            background-color: #25D366; /* WhatsApp Green */
        }
    </style>
</head>
<body>
<header>
    <h2>Admin Panel</h2>
</header>
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

<div class="content">
    <h1>Add Order</h1>
    
    <h2>Orders and Products</h2>
    <table>
        <tr>
            <th>Order ID</th>
            <th>Client Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Total Amount</th>
            <th>Order Date</th>
            <th>Product Name</th>
            <th>Product Image</th>
            <th>Actions</th>
        </tr>
        <?php while ($order = $result_orders->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                <td><?php echo htmlspecialchars($order['username']); ?></td>
                <td><?php echo htmlspecialchars($order['email']); ?></td>
                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td><img src="<?php echo htmlspecialchars($order['product_image']); ?>" alt="Product Image" class="product-image"></td>
                <td class="action-btns">
                    <button class="action-btn edit-btn" onclick="window.location.href='edit_order.php?id=<?php echo $order['order_id']; ?>'">Edit</button>
                    <button class="action-btn delete-btn" onclick="confirmDelete('<?php echo $order['order_id']; ?>')">Delete</button>
                    <button class="action-btn contact-btn" onclick="window.open('https://wa.me/<?php echo htmlspecialchars($order['phone']); ?>', '_blank')">Contact</button>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <button class="add-order-btn" onclick="window.location.href='create_order.php'">Create New Order</button>
</div>

<script>
function confirmDelete(orderId) {
    if (confirm('Are you sure you want to delete this order?')) {
        window.location.href = 'delete_order.php?id=' + orderId;
    }
}
</script>
</body>
</html>
