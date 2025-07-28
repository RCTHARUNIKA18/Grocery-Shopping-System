<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate total items in cart
$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartCount += $item['quantity'];
}

// Return as JSON for AJAX requests
if (isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true' &&
    !isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['count' => $cartCount]);
    exit;
}

// Handle add to cart action
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $productName = $_POST['name'];
    $productPrice = $_POST['price'];
    $productUnit = $_POST['unit'];
    $productImage = $_POST['image'];
    $productQuantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Check if product already exists in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['name'] === $productName) {
            $item['quantity'] += $productQuantity;
            $found = true;
            break;
        }
    }
    
    // If product is not in cart, add it
    if (!$found) {
        $_SESSION['cart'][] = [
            'name' => $productName,
            'price' => $productPrice,
            'unit' => $productUnit,
            'image' => $productImage,
            'quantity' => $productQuantity
        ];
    }
    
    // Redirect back or to success message
    // Check if the request is AJAX using the new parameter
    if (isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Product added to cart!', 'product_name' => $productName]);
        exit;
    } else {
        // Redirect for normal form submissions (fallback)
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Handle update cart action
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $productName = $_POST['name'];
    $newQuantity = (int)$_POST['quantity'];
    
    $cartTotal = 0;
    $cartItems = 0;
    $itemTotal = 0;
    $success = false;
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['name'] === $productName) {
            if ($newQuantity > 0) {
                $item['quantity'] = $newQuantity;
                $itemTotal = $item['price'] * $newQuantity;
                $success = true;
            }
        }
        $cartTotal += $item['price'] * $item['quantity'];
        $cartItems += $item['quantity'];
    }
    
    // Calculate final total with delivery
    $deliveryCharge = ($cartTotal >= 500) ? 0 : 40;
    $finalTotal = $cartTotal + $deliveryCharge;
    
    // Return JSON response for AJAX requests
    if (isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'itemTotal' => number_format($itemTotal, 2),
            'cartTotal' => number_format($cartTotal, 2),
            'cartItems' => $cartItems,
            'deliveryCharge' => number_format($deliveryCharge, 2),
            'finalTotal' => number_format($finalTotal, 2)
        ]);
        exit;
    }
    
    header('Location: cart.php');
    exit;
}

// Handle remove from cart action
if (isset($_POST['action']) && $_POST['action'] === 'remove') {
    $productName = $_POST['name'];
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['name'] === $productName) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
            break;
        }
    }
    
    // Return JSON response for AJAX requests
    if (isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true') {
        
        // Calculate new totals after removal
        $cartTotal = 0;
        $cartItems = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
            $cartItems += $item['quantity'];
        }
        
        $deliveryCharge = ($cartTotal >= 500) ? 0 : 40;
        $finalTotal = $cartTotal + $deliveryCharge;
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cartTotal' => number_format($cartTotal, 2),
            'cartItems' => $cartItems,
            'deliveryCharge' => number_format($deliveryCharge, 2),
            'finalTotal' => number_format($finalTotal, 2),
            'isEmpty' => empty($_SESSION['cart'])
        ]);
        exit;
    }
    
    header('Location: cart.php');
    exit;
}

// Handle clear cart action
if (isset($_POST['action']) && $_POST['action'] === 'clear') {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

// Calculate cart totals
$cartTotal = 0;
$cartItems = 0;

foreach ($_SESSION['cart'] as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
    $cartItems += $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shopping Cart - Grocery Shopping</title>
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
    .cart-container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .cart-title {
      text-align: center;
      margin-bottom: 30px;
      color: #333;
    }
    .cart-empty {
      text-align: center;
      padding: 50px 0;
      color: #666;
    }
    .cart-table {
      width: 100%;
      border-collapse: collapse;
    }
    .cart-table th {
      background-color: #f5f5f5;
      padding: 12px;
      text-align: left;
      border-bottom: 2px solid #ddd;
    }
    .cart-table td {
      padding: 15px 12px;
      border-bottom: 1px solid #ddd;
      vertical-align: middle;
    }
    .cart-product-image {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
    }
    .cart-product-name {
      font-weight: bold;
      color: #333;
    }
    .cart-product-price {
      color: #06c1d2;
      font-weight: bold;
    }
    .quantity-input {
      width: 60px;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 5px;
      text-align: center;
    }
    .update-btn, .remove-btn {
      background: none;
      border: none;
      cursor: pointer;
      padding: 5px 8px;
      border-radius: 4px;
      transition: background-color 0.2s;
    }
    .update-btn {
      color: #06c1d2;
      display: none; /* Hide update button by default as we'll update automatically */
    }
    .remove-btn {
      color: #ff6b6b;
    }
    .update-btn:hover {
      background-color: #eaf8f9;
    }
    .remove-btn:hover {
      background-color: #ffeeee;
    }
    .cart-summary {
      margin-top: 30px;
      display: flex;
      justify-content: flex-end;
    }
    .cart-totals {
      width: 400px;
      padding: 20px;
      background-color: #f9f9f9;
      border-radius: 10px;
    }
    .cart-totals-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid #eee;
    }
    .cart-totals-row:last-child {
      border-bottom: none;
      font-weight: bold;
      font-size: 1.2em;
      color: #06c1d2;
    }
    .action-buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
    }
    .continue-shopping-btn, .checkout-btn, .clear-cart-btn {
      padding: 12px 24px;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.2s;
      border: none;
    }
    .continue-shopping-btn {
      background-color: yellow;
      color: #333;
    }
    .checkout-btn {
      background-color: #06c1d2;
      color: white;
    }
    .clear-cart-btn {
      background-color: #ff6b6b;
      color: white;
    }
    .continue-shopping-btn:hover {
      background-color: #e4e4e4;
    }
    .checkout-btn:hover {
      background-color: #057f8b;
    }
    .clear-cart-btn:hover {
      background-color: #ff5252;
    }
    /* Animation for changes */
    .highlight {
      animation: highlight 1s ease-in-out;
    }
    @keyframes highlight {
      0% { background-color: rgba(6, 193, 210, 0.2); }
      100% { background-color: transparent; }
    }
    /* Animation for removal */
    .fade-out {
      opacity: 0;
      transition: opacity 0.3s ease-out;
    }
  </style>
</head>
<body>

<header>
  <h1>Shopping Cart</h1>
  <div style="display: flex; gap: 15px;">
    <a href="products.php" style="color: white; text-decoration: none; display: flex; align-items: center;">
      <span style="margin-right: 5px;">Home</span>
    </a>
    <button onclick="logout()" style="background:#fff; color:#0a74da; padding:8px 16px; border-radius:20px; border:none; cursor:pointer;">Logout</button>
  </div>
</header>

<div class="breadcrumb">
  <a href="products.php">Home</a> &gt; <span>Shopping Cart</span>
</div>

<div class="cart-container">
  <h2 class="cart-title">Your Shopping Cart</h2>
  
  <div id="cart-content">
    <?php if (empty($_SESSION['cart'])): ?>
      <div class="cart-empty">
        <h3>Your cart is empty</h3>
        <p>Looks like you haven't added anything to your cart yet.</p>
        <a href="products.php" class="continue-shopping-btn" style="display: inline-block; margin-top: 20px; text-decoration: none;">Continue Shopping</a>
      </div>
    <?php else: ?>
      <table class="cart-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($_SESSION['cart'] as $item): ?>
            <tr data-product="<?php echo htmlspecialchars($item['name']); ?>">
              <td>
                <div style="display: flex; align-items: center;">
                  <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-image">
                  <span class="cart-product-name" style="margin-left: 15px;"><?php echo htmlspecialchars($item['name']); ?></span>
                </div>
              </td>
              <td class="cart-product-price">₹ <?php echo number_format($item['price'], 2); ?> / <?php echo htmlspecialchars($item['unit']); ?></td>
              <td>
                <form class="quantity-form" method="post" action="cart.php">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="name" value="<?php echo htmlspecialchars($item['name']); ?>">
                  <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                  <button type="submit" class="update-btn">Update</button>
                </form>
              </td>
              <td class="cart-product-price item-total">₹ <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
              <td>
                <form method="post" action="cart.php" class="remove-form">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="name" value="<?php echo htmlspecialchars($item['name']); ?>">
                  <button type="submit" class="remove-btn">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <div class="cart-summary">
        <div class="cart-totals">
          <div class="cart-totals-row">
            <span>Subtotal (<span id="cart-items"><?php echo $cartItems; ?></span> items)</span>
            <span id="subtotal">₹ <?php echo number_format($cartTotal, 2); ?></span>
          </div>
          <div class="cart-totals-row">
            <span>Delivery Charges</span>
            <span id="delivery-charge">₹ <?php echo ($cartTotal >= 500) ? '0.00' : '40.00'; ?></span>
          </div>
          <div class="cart-totals-row">
            <span>Total</span>
            <span id="total-amount">₹ <?php echo number_format(($cartTotal >= 500) ? $cartTotal : $cartTotal + 40, 2); ?></span>
          </div>
        </div>
      </div>
      
      <div class="action-buttons">
        <div>
          <a href="products.php" class="continue-shopping-btn" style="text-decoration: none;">Continue Shopping</a>
        </div>
        <div>
          <form method="post" action="cart.php" style="display: inline-block; margin-right: 10px;" id="clear-cart-form">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="clear-cart-btn">Clear Cart</button>
          </form>
          <button class="checkout-btn" onclick="checkout()">Proceed to Checkout</button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  function logout() {
    document.cookie = "loggedIn=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    window.location.href = "index.html";
  }
  
  function checkout() {
    window.location.href = "payment.php";
    // You would redirect to a checkout page or process the order
  }
  
  // AJAX for dynamic cart updating
  document.addEventListener('DOMContentLoaded', function() {
    // Real-time quantity updates
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
      input.addEventListener('change', function() {
        const form = this.closest('form');
        const formData = new FormData(form);
        const row = this.closest('tr');
        const itemTotalCell = row.querySelector('.item-total');
        formData.append('is_ajax', 'true'); // Indicate this is an AJAX request
        
        // Highlight the row to show it's updating
        row.classList.add('highlight');
        
        fetch('cart.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Update item total
            itemTotalCell.textContent = '₹ ' + data.itemTotal;
            
            // Update cart summary
            document.getElementById('cart-items').textContent = data.cartItems;
            document.getElementById('subtotal').textContent = '₹ ' + data.cartTotal;
            document.getElementById('delivery-charge').textContent = '₹ ' + data.deliveryCharge;
            document.getElementById('total-amount').textContent = '₹ ' + data.finalTotal;
          }
        })
        .catch(error => console.error('Error:', error));
      });
    });
    
    // Enhanced Remove functionality
    const removeForms = document.querySelectorAll('.remove-form');
    removeForms.forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const productRow = form.closest('tr');
        
        // Add fade out class before removal
        productRow.classList.add('fade-out');
        
        fetch('cart.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            setTimeout(() => {
              // Remove the row from DOM
              productRow.remove();
              
              // Update cart summary
              document.getElementById('cart-items').textContent = data.cartItems;
              document.getElementById('subtotal').textContent = '₹ ' + data.cartTotal;
              document.getElementById('delivery-charge').textContent = '₹ ' + data.deliveryCharge;
              document.getElementById('total-amount').textContent = '₹ ' + data.finalTotal;
              
              // If cart is empty, reload to show empty cart message
              if (data.isEmpty) {
                window.location.reload();
              }
            }, 300);
          }
        })
        .catch(error => console.error('Error:', error));
      });
    });
    
    // Clear cart form
    const clearCartForm = document.getElementById('clear-cart-form');
    if (clearCartForm) {
      clearCartForm.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to clear your cart?')) {
          e.preventDefault();
        }
      });
    }
  });
</script>
</body>
</html>