<?php
date_default_timezone_set('Asia/Kolkata');
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL); ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: index.html?error=login_required");
    exit();
}

header('Content-Type: text/html; charset=UTF-8');

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your actual database username
$password = ""; // Replace with your actual database password
$dbname = "grocery_shopping_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect to products if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: products.php');
    exit;
}

// Initialize variables
$paymentSuccess = false;
$orderId = null;
$errorMessage = '';
$cartTotal = 0;
$deliveryCharge = 0;
$totalAmount = 0;
$userId = isset($_COOKIE['userId']) ? $_COOKIE['userId'] : 1; // Default to 1 if not set
$userName = isset($_COOKIE['userName']) ? $_COOKIE['userName'] : 'Guest';

// Calculate cart totals
foreach ($_SESSION['cart'] as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}
$deliveryCharge = ($cartTotal >= 500) ? 0 : 40;
$totalAmount = $cartTotal + $deliveryCharge;

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_submit'])) {
    // Validate payment information
    $cardNumber = isset($_POST['card_number']) ? $_POST['card_number'] : '';
    $cardName = isset($_POST['card_name']) ? $_POST['card_name'] : '';
    $expiryDate = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';
    $address = $_POST['address'];
    $city = $_POST['city'];
    $postalCode = $_POST['postal_code'];
    $paymentMethod = $_POST['payment_method'];
    
    // Simple validation (in real-world scenario, use more robust validation)
    $isValid = true;
    
    if ($paymentMethod === 'card') {
        if (strlen(preg_replace('/\D/', '', $cardNumber)) !== 16) {
            $errorMessage = "Invalid card number. Please enter a 16-digit number.";
            $isValid = false;
        } elseif (strlen($cvv) !== 3) {
            $errorMessage = "Invalid CVV. Please enter a 3-digit number.";
            $isValid = false;
        }
    }
    
    if (empty($address) || empty($city) || empty($postalCode)) {
        $errorMessage = "Please fill in all address fields.";
        $isValid = false;
    }
    
    // Process valid payment
    if ($isValid) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Create order in the database
            $orderDate = date('Y-m-d H:i:s');
            
            // Insert into orders table - using your specified structure
            $sql = "INSERT INTO orders (user_id, order_date, total_amount) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isd", $userId, $orderDate, $totalAmount);
            $stmt->execute();
            
            $orderId = $conn->insert_id;
            
            // Insert payment details - using your specified structure
            $sql = "INSERT INTO payment (order_id, payment_method, payment_status) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $paymentStatus = 'Completed';
            $stmt->bind_param("iss", $orderId, $paymentMethod, $paymentStatus);
            $stmt->execute();
            
            // Loop through cart items and update inventory accordingly
            foreach ($_SESSION['cart'] as $item) {
                // Log the product name from the session
                error_log("Processing item from cart: '" . $item['name'] . "'");

                // Get product_id based on product name
                $productQuery = "SELECT product_id FROM products WHERE product_name = ?";
                $stmtProduct = $conn->prepare($productQuery);
                $stmtProduct->bind_param("s", $item['name']);
                
                // Execute product query and check for errors
                if (!$stmtProduct->execute()) {
                    error_log("Error fetching product ID for '" . $item['name'] . "': " . $stmtProduct->error);
                    // Optionally add to a list of errors to display later
                    continue; // Skip this item and go to the next in the loop
                }
                
                $result = $stmtProduct->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $productId = $row['product_id'];
                    $quantity = $item['quantity'];
                    
                    // Log details before updating inventory
                    error_log("Attempting to update inventory for Product ID: " . $productId . ", Quantity: " . $quantity);

                    // First check current stock
                    $checkStockSql = "SELECT stock FROM inventory WHERE product_id = ?";
                    $checkStockStmt = $conn->prepare($checkStockSql);
                    $checkStockStmt->bind_param("i", $productId);
                    if ($checkStockStmt->execute()) {
                        $stockResult = $checkStockStmt->get_result();
                        if ($stockRow = $stockResult->fetch_assoc()) {
                            error_log("Current stock for Product ID " . $productId . " is: " . $stockRow['stock']);
                        } else {
                            error_log("No inventory record found for Product ID: " . $productId);
                        }
                    } else {
                        error_log("Error checking current stock: " . $checkStockStmt->error);
                    }
                    $checkStockStmt->close();

                    // Decrease stock in the inventory table
                    $updateInventorySql = "UPDATE inventory SET stock = stock - ? WHERE product_id = ?";
                    $updateInventoryStmt = $conn->prepare($updateInventorySql);
                    $updateInventoryStmt->bind_param("ii", $quantity, $productId);
                    
                    // Execute inventory update and check for errors
                    if (!$updateInventoryStmt->execute()) {
                        error_log("Error updating inventory for Product ID '" . $productId . "': " . $updateInventoryStmt->error);
                        // Optionally add to a list of errors to display later
                    } else {
                        // Check if any rows were actually updated
                        if ($updateInventoryStmt->affected_rows > 0) {
                            error_log("Successfully updated inventory for Product ID " . $productId . ". Rows affected: " . $updateInventoryStmt->affected_rows);
                            
                            // Verify the new stock level
                            $verifyStockSql = "SELECT stock FROM inventory WHERE product_id = ?";
                            $verifyStockStmt = $conn->prepare($verifyStockSql);
                            $verifyStockStmt->bind_param("i", $productId);
                            if ($verifyStockStmt->execute()) {
                                $verifyResult = $verifyStockStmt->get_result();
                                if ($verifyRow = $verifyResult->fetch_assoc()) {
                                    $newStock = $verifyRow['stock'];
                                    error_log("New stock level for Product ID " . $productId . " is: " . $newStock);

                                    // Check if stock is zero or less and add to alert session
                                    if ($newStock <= 0) {
                                        // Fetch product name to include in the alert
                                        $productNameQuery = "SELECT product_name FROM products WHERE product_id = ?";
                                        $stmtProductName = $conn->prepare($productNameQuery);
                                        $stmtProductName->bind_param("i", $productId);
                                        if ($stmtProductName->execute()) {
                                            $productNameResult = $stmtProductName->get_result();
                                            if ($productNameRow = $productNameResult->fetch_assoc()) {
                                                $productName = $productNameRow['product_name'];
                                                // Initialize session array if it doesn't exist
                                                if (!isset($_SESSION['admin_stock_alert'])) {
                                                    $_SESSION['admin_stock_alert'] = [];
                                                }
                                                // Add product to the alert session, ensuring no duplicates
                                                $_SESSION['admin_stock_alert'][$productId] = ['id' => $productId, 'name' => $productName];
                                                error_log("Added product ID " . $productId . " to admin_stock_alert session.");
                                            }
                                        }
                                        $stmtProductName->close();
                                    }
                                }
                            }
                            $verifyStockStmt->close();
                        } else {
                            error_log("No rows were updated for Product ID " . $productId . ". This might indicate a problem with the update query.");
                        }
                    }
                    $updateInventoryStmt->close();
                    
                    // Check if the product already exists in the user's cart
                    $checkSql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
                    $checkStmt = $conn->prepare($checkSql);
                    $checkStmt->bind_param("ii", $userId, $productId);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    // If product exists in cart table, update the row
                    if ($checkResult->num_rows > 0) {
                        $cartSql = "UPDATE cart SET quantity = 0, last_updated = NOW() 
                                    WHERE user_id = ? AND product_id = ?";
                        $cartStmt = $conn->prepare($cartSql);
                        $cartStmt->bind_param("ii", $userId, $productId);
                        $cartStmt->execute();
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Set payment success flag
            $paymentSuccess = true;
            
            // Clear the cart after successful payment
            // Do not clear immediately to show the order confirmation
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errorMessage = "Error processing your order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $paymentSuccess ? 'Order Confirmation' : 'Payment'; ?> - Grocery Shopping</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Nunito', sans-serif;
      background-image: url(images/GroceryProductBG.jpg);
      margin: 0;
      padding: 0;
    }
    header {
      background: #06c1d2;
      color: white;
      padding: 1em 2em;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .breadcrumb {
      padding: 10px 20px;
      background-color: rgba(255, 255, 255, 0.7);
      border-radius: 5px;
      margin: 10px 20px;
    }
    .breadcrumb a {
      color: #06c1d2;
      text-decoration: none;
    }
    .container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .page-title {
      text-align: center;
      margin-bottom: 30px;
      color: #333;
    }
    .payment-methods {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }
    .payment-method {
      padding: 15px;
      border: 2px solid #ddd;
      border-radius: 10px;
      flex: 1;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .payment-method.active {
      border-color: #06c1d2;
      background-color: #eaf8f9;
    }
    .payment-form {
      margin-top: 30px;
    }
    .form-row {
      margin-bottom: 20px;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #333;
    }
    .form-group input, .form-group select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
    }
    .form-columns {
      display: flex;
      gap: 20px;
    }
    .form-columns .form-group {
      flex: 1;
    }
    .order-summary {
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 30px;
    }
    .order-summary-title {
      font-size: 1.2em;
      margin-bottom: 15px;
      color: #333;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
    }
    .summary-row.total {
      font-weight: bold;
      font-size: 1.1em;
      border-top: 1px solid #ddd;
      padding-top: 10px;
      margin-top: 10px;
      color: #06c1d2;
    }
    .btn {
      display: inline-block;
      padding: 12px 24px;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.2s;
      border: none;
      text-decoration: none;
      text-align: center;
    }
    .btn-primary {
      background-color: #06c1d2;
      color: white;
    }
    .btn-secondary {
      background-color: yellow;
      color: #333;
    }
    .btn-primary:hover {
      background-color: #057f8b;
    }
    .btn-secondary:hover {
      background-color: #e4e4e4;
    }
    .button-row {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
    }
    .error-message {
      background-color: #ffe6e6;
      color: #ff5252;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
    }
    /* Confirmation specific styles */
    .confirmation-message {
      text-align: center;
      padding: 30px 0;
    }
    .confirmation-message h3 {
      color: #06c1d2;
      font-size: 1.8em;
      margin-bottom: 10px;
    }
    .confirmation-message p {
      font-size: 1.1em;
      color: #666;
    }
    .order-details {
      margin-top: 30px;
    }
    .order-id {
      font-weight: bold;
      color: #06c1d2;
    }
    .order-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .order-table th {
      background-color: #f5f5f5;
      padding: 12px;
      text-align: left;
      border-bottom: 2px solid #ddd;
    }
    .order-table td {
      padding: 15px 12px;
      border-bottom: 1px solid #ddd;
    }
    .print-button {
      margin-top: 20px;
      display: inline-block;
    }
    @media print {
      .no-print {
        display: none;
      }
      .container {
        box-shadow: none;
        margin: 0;
        padding: 15px;
      }
      body {
        background: none;
      }
    }
  </style>
</head>
<body>

<header class="no-print">
  <h1><?php echo $paymentSuccess ? 'Order Confirmation' : 'Payment'; ?></h1>
  <div style="display: flex; gap: 15px;">
    <a href="products.php" style="color: white; text-decoration: none; display: flex; align-items: center;">
      <span style="margin-right: 5px;">Home</span>
    </a>
    <button onclick="logout()" style="background:#fff; color:#0a74da; padding:8px 16px; border-radius:20px; border:none; cursor:pointer;">Logout</button>
  </div>
</header>

<div class="breadcrumb no-print">
  <a href="products.php">Home</a> &gt; 
  <a href="cart.php">Cart</a> &gt; 
  <span><?php echo $paymentSuccess ? 'Order Confirmation' : 'Payment'; ?></span>
</div>

<div class="container">
  <?php if ($paymentSuccess): ?>
    <!-- Order Confirmation Section -->
    <div class="confirmation-message">
      <h3>Thank You for Your Order!</h3>
      <p>Your order has been successfully placed and is being processed.</p>
      <p>Order #<span class="order-id"><?php echo $orderId; ?></span> was placed on <?php echo date('F j, Y, g:i a'); ?></p>
    </div>

    <div class="order-details">
      <h3>Order Details</h3>
      <table class="order-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($_SESSION['cart'] as $item): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['name']); ?></td>
              <td>₹ <?php echo number_format($item['price'], 2); ?> / <?php echo htmlspecialchars($item['unit']); ?></td>
              <td><?php echo $item['quantity']; ?></td>
              <td>₹ <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="order-summary">
        <h3 class="order-summary-title">Order Summary</h3>
        <div class="summary-row">
          <span>Subtotal</span>
          <span>₹ <?php echo number_format($cartTotal, 2); ?></span>
        </div>
        <div class="summary-row">
          <span>Delivery Charges</span>
          <span>₹ <?php echo number_format($deliveryCharge, 2); ?></span>
        </div>
        <div class="summary-row total">
          <span>Total</span>
          <span>₹ <?php echo number_format($totalAmount, 2); ?></span>
        </div>
      </div>

      <div class="confirmation-message">
        <p>A confirmation email will be sent to your registered email address.</p>
        <p>Thank you for shopping with us!</p>
      </div>

      <div class="button-row no-print">
        <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
        <button onclick="window.print()" class="btn btn-primary print-button">Print Receipt</button>
      </div>

      <?php
      // Clear the cart after showing the confirmation
      $_SESSION['cart'] = [];
      ?>

    </div>
  <?php else: ?>
    <!-- Payment Form Section -->
    <h2 class="page-title">Payment Information</h2>
    
    <?php if (!empty($errorMessage)): ?>
      <div class="error-message">
        <?php echo $errorMessage; ?>
      </div>
    <?php endif; ?>

    <div class="order-summary">
      <h3 class="order-summary-title">Order Summary</h3>
      <div class="summary-row">
        <span>Subtotal</span>
        <span>₹ <?php echo number_format($cartTotal, 2); ?></span>
      </div>
      <div class="summary-row">
        <span>Delivery Charges</span>
        <span>₹ <?php echo number_format($deliveryCharge, 2); ?></span>
      </div>
      <div class="summary-row total">
        <span>Total</span>
        <span>₹ <?php echo number_format($totalAmount, 2); ?></span>
      </div>
    </div>

    <form action="payment.php" method="post" class="payment-form">
      <div class="payment-methods">
        <div class="payment-method active" data-method="card" onclick="selectMethod('card')">
          <h3>Credit/Debit Card</h3>
          <p>Pay securely with your card</p>
        </div>
        <div class="payment-method" data-method="upi" onclick="selectMethod('upi')">
          <h3>UPI</h3>
          <p>Pay using UPI payment apps</p>
        </div>
        <div class="payment-method" data-method="cod" onclick="selectMethod('cod')">
          <h3>Cash on Delivery</h3>
          <p>Pay when you receive your order</p>
        </div>
      </div>
      
      <input type="hidden" name="payment_method" id="payment_method" value="card">
      
      <div id="card-payment" class="payment-method-form">
        <div class="form-row">
          <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
          </div>
        </div>
        
        <div class="form-row form-columns">
          <div class="form-group">
            <label for="card_name">Cardholder Name</label>
            <input type="text" id="card_name" name="card_name" placeholder="Name on card">
          </div>
          <div class="form-group">
            <label for="expiry_date">Expiry Date</label>
            <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5">
          </div>
          <div class="form-group">
            <label for="cvv">CVV</label>
            <input type="password" id="cvv" name="cvv" placeholder="123" maxlength="3">
          </div>
        </div>
      </div>
      
      <div id="upi-payment" class="payment-method-form" style="display:none;">
        <div class="form-group">
          <label for="upi_id">UPI ID</label>
          <input type="text" id="upi_id" name="upi_id" placeholder="yourname@upi">
        </div>
      </div>
      
      <div id="cod-payment" class="payment-method-form" style="display:none;">
        <p>You will pay the full amount when your order is delivered.</p>
      </div>
      
      <h3>Shipping Address</h3>
      <div class="form-row">
        <div class="form-group">
          <label for="address">Address</label>
          <input type="text" id="address" name="address" placeholder="Street address" required>
        </div>
      </div>
      
      <div class="form-row form-columns">
        <div class="form-group">
          <label for="city">City</label>
          <input type="text" id="city" name="city" placeholder="City" required>
        </div>
        <div class="form-group">
          <label for="postal_code">Postal Code</label>
          <input type="text" id="postal_code" name="postal_code" placeholder="Pin code" required>
        </div>
      </div>
      
      <div class="button-row">
        <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
        <button type="submit" name="payment_submit" class="btn btn-primary">Complete Payment</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
  function logout() {
    document.cookie = "loggedIn=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    window.location.href = "index.html";
  }
  
  function selectMethod(method) {
    // Update hidden field
    document.getElementById('payment_method').value = method;
    
    // Update active class
    const methods = document.querySelectorAll('.payment-method');
    methods.forEach(el => {
      el.classList.remove('active');
      if (el.getAttribute('data-method') === method) {
        el.classList.add('active');
      }
    });
    
    // Show/hide relevant form sections
    document.getElementById('card-payment').style.display = method === 'card' ? 'block' : 'none';
    document.getElementById('upi-payment').style.display = method === 'upi' ? 'block' : 'none';
    document.getElementById('cod-payment').style.display = method === 'cod' ? 'block' : 'none';
  }
  
  // Format card number with spaces
  const cardNumberInput = document.getElementById('card_number');
  if (cardNumberInput) {
    cardNumberInput.addEventListener('input', function (e) {
      let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      let formattedValue = '';
      for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) {
          formattedValue += ' ';
        }
        formattedValue += value[i];
      }
      e.target.value = formattedValue;
    });
  }
  
  // Format expiry date
  const expiryInput = document.getElementById('expiry_date');
  if (expiryInput) {
    expiryInput.addEventListener('input', function (e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
      }
      e.target.value = value;
    });
  }
</script>
</body>
</html>
