<?php
session_start(); // Start the session to access session variables
include 'db_connection.php'; // Include your database connection file

class MpesaPayment {
    private $consumerKey;
    private $consumerSecret;
    private $shortCode;
    private $baseUrl;
    private $callbackUrl;

    public function __construct() {
        $this->consumerKey = '4CXELNo5HnT5uW2rNR7Rls6JUQX6DscFYIrsunDpAQIgi99p';
        $this->consumerSecret = '5mLUeJ480thfZGJ6fkKENY8jtMXvdulXvzYYObYUtrrPsoEanGEZ3zJTbZqT8RIe';
        $this->shortCode = '174379';
        $this->baseUrl = 'https://sandbox.safaricom.co.ke';
        $this->callbackUrl = 'https://example.com/callback';
    }

    public function getAccessToken() {
        try {
            $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
            $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
            
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($curl);
            
            if (curl_errno($curl)) {
                throw new Exception('cURL error: ' . curl_error($curl));
            }
            
            curl_close($curl);

            $result = json_decode($response, true);
            if (!isset($result['access_token'])) {
                throw new Exception('Access token not found in response');
            }

            return $result['access_token'];
        } catch (Exception $e) {
            error_log("Access Token Error: " . $e->getMessage());
            throw new Exception('Failed to obtain access token');
        }
    }

    public function initiatePayment($amount, $phone) {
        try {
            // Validate amount and phone
            if ($amount <= 0) {
                throw new Exception('Invalid amount');
            }

            if (!preg_match('/^254[0-9]{9}$/', $phone)) {
                throw new Exception('Invalid phone number format');
            }

            $accessToken = $this->getAccessToken();
            $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
            
            $timestamp = date('YmdHis');
            $orderRef = 'ORDER' . $timestamp . rand(1000, 9999);

            $data = [
                'BusinessShortCode' => $this->shortCode,
                'Password' => $this->generatePassword($timestamp),
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => ceil($amount),
                'PartyA' => $phone,
                'PartyB' => $this->shortCode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $this->callbackUrl,
                'AccountReference' => $orderRef,
                'TransactionDesc' => "Payment for order {$orderRef}"
            ];

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($curl);
            
            if (curl_errno($curl)) {
                throw new Exception('cURL error: ' . curl_error($curl));
            }
            
            curl_close($curl);

            $result = json_decode($response, true);
            $this->logPaymentRequest($orderRef, $phone, $amount, $result);
            $this->insertTransaction($orderRef, $phone, $amount, json_encode($result));

            return [
                'status' => 'success',
                'message' => 'Payment initiated successfully',
                'data' => $result
            ];

        } catch (Exception $e) {
            error_log("Payment Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function generatePassword($timestamp) {
        $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; // Your passkey
        return base64_encode($this->shortCode . $passkey . $timestamp);
    }

    private function logPaymentRequest($orderRef, $phone, $amount, $response) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'order_ref' => $orderRef,
            'phone' => $phone,
            'amount' => $amount,
            'response' => $response
        ];
        error_log("M-Pesa Payment Request: " . json_encode($logData));
    }

    private function insertTransaction($orderRef, $phone, $amount, $mpesaResponse) {
        global $conn; // Use the global database connection variable
        $status = 'pending'; // Initial status
        $stmt = $conn->prepare("INSERT INTO transactions (order_ref, phone, amount, mpesa_response, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssdsd", $orderRef, $phone, $amount, $mpesaResponse, $status);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception('Database insert failed: ' . $conn->error);
        }
    }
}

function sanitizePhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 9) {
        return '254' . $phone;
    } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
        return '254' . substr($phone, 1);
    } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
        return $phone;
    }
    throw new Exception('Invalid phone number format');
}

// Initialize variables
$totalPrice = 0;
$error = "";
$paymentInitiated = false;
$paymentResponse = [];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout');
    exit();
}

try {
    // Handle POST request for payment
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isset($_POST['total_amount'])) {
            throw new Exception('Total amount is not set.');
        }
        
        $totalPrice = (float)$_POST['total_amount'];
        if ($totalPrice <= 0) {
            throw new Exception('Invalid total amount');
        }
        
        $phoneNumber = sanitizePhoneNumber($_POST['phone_number']);
        
        $mpesa = new MpesaPayment();
        $paymentResponse = $mpesa->initiatePayment($totalPrice, $phoneNumber);
        $paymentInitiated = true;

        // Handle AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($paymentResponse);
            exit;
        }
    } else {
        // Fetch total price from cart
        $userId = $_SESSION['user_id'];
        $query = "SELECT SUM(total_price) AS total_price FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $totalPrice = $row['total_price'] ? (float)$row['total_price'] : 0;
            }
            $stmt->close();
        } else {
            throw new Exception('Database query failed.');
        }

        if ($totalPrice <= 0) {
            throw new Exception('Your cart is empty');
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Thika Baby World</title>
    <link rel="stylesheet" href="style.css">
    <style>
                 .mpesa-form-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .amount-display {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .amount-display .amount-label {
            color: #495057;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .amount-display .price {
            font-size: 2.2rem;
            color: #2c3e50;
            font-weight: bold;
        }

        .mpesa-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-size: 0.95rem;
            color: #495057;
            font-weight: 500;
        }

        .form-group input {
            padding: 1rem;
            border: 2px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #00AB55;
            box-shadow: 0 0 0 3px rgba(0, 171, 85, 0.1);
        }

        .form-group input[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        .checkout-btn {
            background: #00AB55;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .checkout-btn:hover {
            background: #009648;
            transform: translateY(-1px);
        }

        .checkout-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 0;
            right: 1rem;
            bottom: 0;
            margin: auto;
            border: 3px solid transparent;
            border-top-color: white;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        }

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }
            to {
                transform: rotate(1turn);
            }
        }
        .loading {
            position: relative;
            pointer-events: none;
        }
        .loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 0;
            right: 1rem;
            bottom: 0;
            margin: auto;
            border: 3px solid transparent;
            border-top-color: white;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        }
        @keyframes button-loading-spinner {
            from { transform: rotate(0turn); }
            to { transform: rotate(1turn); }
        }
    </style>
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
            <a href="index.php" class="logo"><h2>Thika Baby World</h2></a>
            <ul class="nav-list d-flex">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="products.php" class="nav-link">Shop</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
            </ul>
        </div>
    </div>

    <div class="container cart">
        <h1>Checkout</h1>
        
        <div class="mpesa-form-container">
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($paymentInitiated && $paymentResponse['status'] === 'success'): ?>
                <div class="success-message">
                    Payment request sent successfully. Please check your phone for the STK push notification.
                </div>
            <?php endif; ?>

            <div class="amount-display">
                <div class="amount-label">Total Amount</div>
                <div class="price">Ksh <?php echo number_format($totalPrice, 2); ?></div>
            </div>

            <form method="POST" action="" class="mpesa-form" id="paymentForm">
                <div class="form-group">
                    <label for="amount">Amount (KES)</label>
                    <input type="text" id="amount" 
                           value="<?php echo number_format($totalPrice, 2); ?>" 
                           readonly>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" 
                           required placeholder="07XXXXXXXX" 
                           pattern="^(?:254|\+254|0)?([1-9]\d{8})$"
                           title="Please enter a valid Kenyan phone number">
                </div>

                <!-- ✅ New STK push message -->
                <div id="stkMessage" class="success-message" style="display:none;">
                    Please check your phone for the M-Pesa STK Push prompt and enter your PIN to complete the payment.
                </div>

                <input type="hidden" name="total_amount" value="<?php echo $totalPrice; ?>">
                <button type="submit" class="checkout-btn" id="submitBtn">Pay with M-Pesa</button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById("paymentForm").addEventListener("submit", function() {
        document.getElementById("stkMessage").style.display = "block";
        const submitBtn = document.getElementById("submitBtn");
        submitBtn.classList.add("loading");
        submitBtn.disabled = true;
    });
    </script>
</body>
</html>
