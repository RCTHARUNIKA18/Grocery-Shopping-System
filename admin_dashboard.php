<?php
// Start session for login management
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: admin_login.html?error=not_authorized");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "grocery_shopping_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch users
$users = $conn->query("SELECT user_id, username, email_id, phone_no, address FROM user");
if ($users === FALSE) {
    echo "<p>Error fetching users: " . $conn->error . "</p>";
}

// Fetch products
$products = $conn->query("SELECT product_id, category, product_name, price, availability FROM products");
if ($products === FALSE) {
    echo "<p>Error fetching products: " . $conn->error . "</p>";
}

// Fetch distinct categories for the dropdown
$categories_result = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = htmlspecialchars($row['category']);
    }
} else {
    echo "<p>Error fetching categories: " . $conn->error . "</p>";
}

// Fetch payments
$payments = $conn->query("SELECT * FROM payment");
if ($payments === FALSE) {
    echo "<p>Error fetching payments: " . $conn->error . "</p>";
}

// Fetch orders
$orders = $conn->query("SELECT * FROM orders");
if ($orders === FALSE) {
    echo "<p>Error fetching orders: " . $conn->error . "</p>";
}

// Fetch inventory
$inventory = $conn->query("SELECT * FROM inventory");
if ($inventory === FALSE) {
    echo "<p>Error fetching inventory: " . $conn->error . "</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Verdana', sans-serif;
      background-image: url('images/Grocery BG.jpeg');
      background-size: cover;
      background-position: center;
      min-height: 100vh;
      color: #0a0101;
    }
    .dashboard-container {
      width: 600px;
      background-color: rgba(255, 255, 255, 0.90);
      border-radius: 15px;
      padding: 30px 25px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      backdrop-filter: blur(5px);
    }
    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: center;
    }
    th {
      background-color: #00bcd4;
      color: white;
    }
    tr:nth-child(even) {
      background-color: #f2f2f2;
    }
    .success { color: green; text-align: center; margin-bottom: 15px; }
    .sidebar {
      width: 250px; background: #1a237e; color: #fff; height: 100vh; position: fixed; top: 0; left: 0;
      display: flex; flex-direction: column; padding-top: 30px;
    }
    .sidebar h2 {
      text-align: center;
      margin-bottom: 30px;
      color: green;
    }
    .sidebar button {
      background: none; border: none; color: #fff; padding: 18px 30px; text-align: left;
      font-size: 1.1em; cursor: pointer; transition: background 0.2s;
      width: 100%;
    }
    .sidebar button:hover, .sidebar button.active { background: #00bcd4; color: #fff; }
    .main-content {
      margin-left: 250px; padding: 40px 30px;
    }
    .tab-content { display: none; }
    .tab-content.active {
      display: block !important;
    }
    h3 {
      color: navy;
    }
    .admin-info {
      padding: 10px 20px;
      text-align: center;
      border-top: 1px solid rgb(243, 229, 229);
      margin-top: auto;
      margin-bottom: 20px;
    }
    .admin-info p {
      margin: 5px 0;
      font-size: 0.9em;
    }
    .logout-btn {
      background: #f44336 !important;
      margin-top: 10px;
      text-align: center !important;
    }
    .data-actions {
      margin-bottom: 20px;
    }
    .data-actions button {
      background-color: #4CAF50;
      color: white;
      border: none;
      padding: 8px 15px;
      margin-right: 5px;
      border-radius: 4px;
      cursor: pointer;
    }
    .data-actions button:hover {
      opacity: 0.8;
    }
    .data-actions button.delete {
      background-color: #f44336;
    }
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.4);
    }
    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 50%;
      border-radius: 5px;
    }
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    .close:hover {
      color: black;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
    }
    .form-group input, .form-group select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    .submit-btn {
      background-color: #4CAF50;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .submit-btn:hover {
      opacity: 0.8;
    }
    .report-options {
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .report-option {
      margin-bottom: 15px;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      background-color: #f9f9f9;
    }
    .report-option h4 {
      margin: 0 0 10px 0;
      color: #333;
    }
    .report-option p {
      margin: 0 0 15px 0;
      color: #666;
    }
    .generate-report-btn {
      background-color: #2196F3;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    .generate-report-btn:hover {
      background-color: #1976D2;
    }
  </style>
</head>
<body>
  <?php if (isset($_GET['success']) && $_GET['success'] === 'logged_in'): ?>
    <div class="success">Successfully logged in as admin!</div>
  <?php endif; ?>

  <div class="sidebar">
    <h2>Admin Dashboard</h2>
    <button class="tab-btn" onclick="showTab('users')">User Details</button>
    <button class="tab-btn" onclick="showTab('products')">Product Details</button>
    <button class="tab-btn" onclick="showTab('payments')">Payment Details</button>
    <button class="tab-btn" onclick="showTab('orders')">Order Details</button>
    <button class="tab-btn" onclick="showTab('inventory')">Inventory Details</button>
    <button class="tab-btn" onclick="showTab('reports')">Generate Reports</button>
    
    <div class="admin-info">
      <p>Logged in as: <?= htmlspecialchars($_SESSION['admin_name']) ?></p>
      <form action="admin_logout.php" method="POST">
        <button type="submit" class="logout-btn">Logout</button>
      </form>
    </div>
  </div>
  
  <div class="main-content">
    <div id="users" class="tab-content active">
      <h3>Signed-up Users</h3>
      <?php if ($users && $users->num_rows > 0): ?>
      <div class="data-actions">
        <button class="delete" onclick="deleteSelectedUsers()">Delete Selected</button>
      </div>
      <table>
        <tr>
          <th></th>
          <th>ID</th>
          <th>Username</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Address</th>
          <th>Actions</th>
        </tr>
        <?php while($row = $users->fetch_assoc()): ?>
          <tr data-user-id="<?= htmlspecialchars($row['user_id']) ?>">
            <td><input type="checkbox" class="user-checkbox" value="<?= htmlspecialchars($row['user_id']) ?>"></td>
            <td><?= htmlspecialchars($row['user_id']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email_id']) ?></td>
            <td><?= htmlspecialchars($row['phone_no']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td>
              <button onclick="deleteUser(<?= htmlspecialchars($row['user_id']) ?>)">Delete</button>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
      <?php else: ?>
        <p>No user details found.</p>
      <?php endif; ?>
    </div>
    
    <div id="products" class="tab-content">
      <h3>Product Details</h3>
      <?php if ($products && $products->num_rows > 0): ?>
      <div class="data-actions">
        <button onclick="addNewProduct()">Add New Product</button>
        <button class="delete" onclick="deleteSelectedProducts()">Delete Selected</button>
      </div>
      <table>
        <tr>
          <th></th>
          <th>Product ID</th>
          <th>Category</th>
          <th>Product Name</th>
          <th>Price</th>
          <th>Availability</th>
          <th>Actions</th>
        </tr>
        <?php $products->data_seek(0); while($row = $products->fetch_assoc()): ?>
          <tr>
            <td><input type="checkbox" class="product-checkbox" value="<?= htmlspecialchars($row['product_id']) ?>"></td>
            <td><?= htmlspecialchars($row['product_id']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <td><?= htmlspecialchars($row['price']) ?></td>
            <td><?= htmlspecialchars($row['availability']) ?></td>
            <td>
              <button onclick="editProduct(<?= htmlspecialchars($row['product_id']) ?>)">Edit</button>
              <button onclick="deleteProduct(<?= htmlspecialchars($row['product_id']) ?>)">Delete</button>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
      <?php else: ?>
        <p>No product details found.</p>
      <?php endif; ?>
    </div>
    
    <div id="payments" class="tab-content">
      <h3>Payment Details</h3>
      <?php if ($payments && $payments->num_rows > 0): ?>
      <div class="data-actions">
        <button class="delete" onclick="deleteSelectedPayments()">Delete Selected</button>
      </div>
      <table>
        <tr>
          <th></th>
          <?php foreach($payments->fetch_fields() as $field): ?>
            <th><?= htmlspecialchars($field->name) ?></th>
          <?php endforeach; ?>
        </tr>
        <?php $payments->data_seek(0); while($row = $payments->fetch_assoc()): ?>
          <tr>
            <td><input type="checkbox" class="payment-checkbox" value="<?= htmlspecialchars($row['payment_id']) ?>"></td>
            <?php foreach($row as $key => $cell): ?>
              <td><?= htmlspecialchars($cell) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endwhile; ?>
      </table>
      <?php else: ?>
        <p>No payment details found.</p>
      <?php endif; ?>
    </div>
    
    <div id="orders" class="tab-content">
      <h3>Order Details</h3>
      <?php if ($orders && $orders->num_rows > 0): ?>
      <div class="data-actions">
        <button class="delete" onclick="deleteSelectedOrders()">Delete Selected</button>
      </div>
      <table>
        <tr>
          <th></th>
          <?php foreach($orders->fetch_fields() as $field): ?>
            <th><?= htmlspecialchars($field->name) ?></th>
          <?php endforeach; ?>
        </tr>
        <?php $orders->data_seek(0); while($row = $orders->fetch_assoc()): ?>
          <tr>
            <td><input type="checkbox" class="order-checkbox" value="<?= htmlspecialchars(array_values($row)[0]) ?>"></td>
            <?php foreach($row as $cell): ?>
              <td><?= htmlspecialchars($cell) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endwhile; ?>
      </table>
      <?php else: ?>
        <p>No order details found.</p>
      <?php endif; ?>
    </div>
    
    <div id="inventory" class="tab-content">
      <h3>Inventory Details</h3>
      <?php if ($inventory && $inventory->num_rows > 0): ?>
      <div class="data-actions">
        <button onclick="updateInventory()">Update Inventory</button>
      </div>
      <table>
        <tr>
          <th></th>
          <?php foreach($inventory->fetch_fields() as $field): ?>
            <th><?= htmlspecialchars($field->name) ?></th>
          <?php endforeach; ?>
          <th>Actions</th>
        </tr>
        <?php $inventory->data_seek(0); while($row = $inventory->fetch_assoc()): ?>
          <tr>
            <td><input type="checkbox" class="inventory-checkbox" value="<?= htmlspecialchars(array_values($row)[0]) ?>"></td>
            <?php foreach($row as $key => $cell): ?>
              <td><?= htmlspecialchars($cell) ?></td>
            <?php endforeach; ?>
            <td>
              <button onclick="editInventoryItem('<?= htmlspecialchars(array_values($row)[0]) ?>')">Edit</button>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
      <?php else: ?>
        <p>No inventory details found.</p>
      <?php endif; ?>
    </div>

    <div id="reports" class="tab-content">
      <h3>Generate Reports</h3>
      <div class="report-options">
        <div class="report-option">
          <h4>Sales Report</h4>
          <p>Generate a comprehensive report of all sales, including order details, payment information, and revenue analysis.</p>
          <button class="generate-report-btn" onclick="generateReport('sales')">Generate Sales Report</button>
        </div>
        
        <div class="report-option">
          <h4>Inventory Report</h4>
          <p>Get a detailed report of current inventory levels, stock movements, and product availability.</p>
          <button class="generate-report-btn" onclick="generateReport('inventory')">Generate Inventory Report</button>
        </div>
        
        <div class="report-option">
          <h4>User Activity Report</h4>
          <p>View user registration details, order history, and customer activity analysis.</p>
          <button class="generate-report-btn" onclick="generateReport('users')">Generate User Report</button>
        </div>
        
        <div class="report-option">
          <h4>Complete System Report</h4>
          <p>Generate a comprehensive report including all system data: sales, inventory, users, and orders.</p>
          <button class="generate-report-btn" onclick="generateReport('complete')">Generate Complete Report</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Product Modal -->
  <div id="addProductModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Add New Product</h2>
      <form id="addProductForm" action="add_product.php" method="POST">
        <div class="form-group">
          <label for="category">Category:</label>
          <select name="category" id="category" required>
            <option value="">Select Category</option>
            <?php foreach($categories as $category_name): ?>
                <option value="<?= $category_name ?>"><?= $category_name ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="product_name">Product Name:</label>
          <input type="text" name="product_name" id="product_name" required>
        </div>
        <div class="form-group">
          <label for="price">Price:</label>
          <input type="number" name="price" id="price" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label for="availability">Availability:</label>
          <select name="availability" id="availability" required>
            <option value="In Stock">In Stock</option>
            <option value="Out of Stock">Out of Stock</option>
          </select>
        </div>
        <button type="submit" class="submit-btn">Add Product</button>
      </form>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div id="editProductModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h2>Edit Product</h2>
      <form id="editProductForm" action="update_product.php" method="POST">
        <input type="hidden" name="product_id" id="edit_product_id">
        <div class="form-group">
          <label for="edit_category">Category:</label>
          <select name="category" id="edit_category" required>
            <option value="">Select Category</option>
            <?php foreach($categories as $category_name): ?>
                <option value="<?= $category_name ?>"><?= $category_name ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_product_name">Product Name:</label>
          <input type="text" name="product_name" id="edit_product_name" required>
        </div>
        <div class="form-group">
          <label for="edit_price">Price:</label>
          <input type="number" name="price" id="edit_price" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label for="edit_availability">Availability:</label>
          <select name="availability" id="edit_availability" required>
            <option value="In Stock">In Stock</option>
            <option value="Out of Stock">Out of Stock</option>
          </select>
        </div>
        <button type="submit" class="submit-btn">Update Product</button>
      </form>
    </div>
  </div>

  <script>
    console.log('Admin dashboard script is running!');

    // Function to switch between tabs
    function showTab(tabId) {
      const tabs = document.querySelectorAll('.tab-content');
      tabs.forEach(tab => tab.classList.remove('active'));
      document.getElementById(tabId).classList.add('active');
      
      const tabButtons = document.querySelectorAll('.tab-btn');
      tabButtons.forEach(btn => btn.classList.remove('active'));
      document.querySelector(`.tab-btn[onclick="showTab('${tabId}')"]`).classList.add('active');
      
      // Update URL hash to remember the active tab
      history.pushState(null, null, `#${tabId}`);
    }
    
    // Function to toggle all checkboxes in a tab
    function toggleAllCheckboxes(tabId) {
      const selectAllCheckbox = document.getElementById(`select-all-${tabId}`);
      const checkboxes = document.querySelectorAll(`#${tabId} .${tabId}-checkbox:not(:checked)`);
      checkboxes.forEach(checkbox => checkbox.checked = selectAllCheckbox.checked);
    }

    // --- User Actions ---

    // Function to handle Delete User button click
    function deleteUser(userId) {
      if (confirm('Are you sure you want to delete this user?')) {
        // Send an AJAX request to delete the user
        fetch('delete_user.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            // Remove the user row from the table
            const userRow = document.querySelector(`tr[data-user-id='${userId}']`);
            if (userRow) {
              userRow.remove();
            }
          } else {
            alert('Error deleting user: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting the user.');
        });
      }
    }
    
    // Function to handle Delete Selected Users button click
    function deleteSelectedUsers() {
      const selectedUserIds = [];
      const checkboxes = document.querySelectorAll('#users .user-checkbox:checked');
      checkboxes.forEach(checkbox => {
        selectedUserIds.push(checkbox.value);
      });

      if (selectedUserIds.length === 0) {
        alert('Please select at least one user to delete.');
        return;
      }

      if (confirm(`Are you sure you want to delete ${selectedUserIds.length} selected user(s)?`)) {
        // Send an AJAX request to delete selected users
        // You might need a separate backend script (e.g., delete_multiple_users.php)
        // or modify delete_user.php to handle multiple IDs.
        // For now, let's assume delete_user.php can handle multiple IDs in a comma-separated string
        // **Note:** This approach requires modifying delete_user.php to handle a list of IDs.
        // A better approach might be to send an array of IDs.

        // **Alternative (and often better) approach: Send as JSON array**
        fetch('delete_user.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ user_ids: selectedUserIds })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            // Remove the deleted user rows from the table
            selectedUserIds.forEach(userId => {
              const userRow = document.querySelector(`tr[data-user-id='${userId}']`);
              if (userRow) {
                userRow.remove();
              }
            });
          } else {
            alert('Error deleting selected users: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting selected users.');
        });
      }
    }

    // --- Product Actions ---
    function addNewProduct() {
      const modal = document.getElementById('addProductModal');
      modal.style.display = "block";
    }

    // Close modal when clicking the X
    document.querySelector('.close').onclick = function() {
      document.getElementById('addProductModal').style.display = "none";
    }

    // Close edit modal when clicking the X
    function closeEditModal() {
      document.getElementById('editProductModal').style.display = "none";
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('addProductModal');
      const editModal = document.getElementById('editProductModal');
      if (event.target == modal) {
        modal.style.display = "none";
      }
      if (event.target == editModal) {
        editModal.style.display = "none";
      }
    }

    // Handle form submission
    document.getElementById('addProductForm').onsubmit = function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('add_product.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Product added successfully!');
          location.reload(); // Refresh the page to show the new product
        } else {
          alert('Error adding product: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the product.');
      });
    };

    // Handle edit form submission
    document.getElementById('editProductForm').onsubmit = function(e) {
      e.preventDefault(); // Prevent default form submission
      
      const formData = new FormData(this);
      
      fetch('update_product.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Product updated successfully!');
          closeEditModal(); // Close the modal
          location.reload(); // Refresh the page to show the updated product
        } else {
          alert('Error updating product: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the product.');
      });
    };

    // Function to handle Delete Selected Products button click
    function deleteSelectedProducts() {
      const selectedProductIds = [];
      const checkboxes = document.querySelectorAll('#products .product-checkbox:checked');
      checkboxes.forEach(checkbox => {
        selectedProductIds.push(checkbox.value);
      });

      if (selectedProductIds.length === 0) {
        alert('Please select at least one product to delete.');
        return;
      }

      if (confirm(`Are you sure you want to delete ${selectedProductIds.length} selected product(s)?`)) {
        fetch('delete_product.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ product_ids: selectedProductIds })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            selectedProductIds.forEach(productId => {
              const productRow = document.querySelector(`#products tr[data-product-id=\'${productId}\}']`);
              if (productRow) {
                productRow.remove();
              }
            });
          } else {
            alert('Error deleting selected products: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting selected products.');
        });
      }
    }

    // Function to handle Edit Product button click
    function editProduct(productId) {
      const editModal = document.getElementById('editProductModal');
      const editForm = document.getElementById('editProductForm');
      
      // Reset form fields and display loading or default state
      editForm.reset();
      document.getElementById('edit_product_id').value = '';
      // You might show a loading indicator here

      // Fetch product data
      fetch(`fetch_product.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Populate the form with fetched data
            document.getElementById('edit_product_id').value = data.product.product_id;
            document.getElementById('edit_category').value = data.product.category;
            document.getElementById('edit_product_name').value = data.product.product_name;
            document.getElementById('edit_price').value = data.product.price;
            document.getElementById('edit_availability').value = data.product.availability;
            
            // Display the modal
            editModal.style.display = "block";
          } else {
            alert('Error fetching product details: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while fetching product details.');
        });
    }

    // Function to handle Delete Product button click
    function deleteProduct(productId) {
      if (confirm('Are you sure you want to delete this product?')) {
        fetch('delete_product.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            const productRow = document.querySelector(`#products tr[data-product-id=\'${productId}\} ']`);
            if (productRow) {
              productRow.remove();
            }
          } else {
            alert('Error deleting product: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting the product.');
        });
      }
    }

    // --- Payment Actions ---
    // Placeholder functions
    function viewPaymentDetails(paymentId) { alert(`View Payment Details ${paymentId} button clicked`); }

    // Function to handle Delete Selected Payments button click
    function deleteSelectedPayments() {
      const selectedPaymentIds = [];
      const checkboxes = document.querySelectorAll('#payments .payment-checkbox:checked');
      checkboxes.forEach(checkbox => {
        selectedPaymentIds.push(checkbox.value);
      });

      if (selectedPaymentIds.length === 0) {
        alert('Please select at least one payment to delete.');
        return;
      }

      if (confirm(`Are you sure you want to delete ${selectedPaymentIds.length} selected payment(s)?`)) {
        fetch('delete_payment.php', { // Assuming delete_payment.php handles the deletion
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ payment_ids: selectedPaymentIds })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            // Remove the deleted payment rows from the table
            selectedPaymentIds.forEach(paymentId => {
              const paymentRow = document.querySelector(`#payments input[value=\'${paymentId}\']`).closest('tr');
              if (paymentRow) {
                paymentRow.remove();
              }
            });
          } else {
            alert('Error deleting selected payments: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting selected payments.');
        });
      }
    }

    // --- Order Actions ---
    // Placeholder functions
    function viewOrderDetails(orderId) { alert(`View Order Details ${orderId} button clicked`); }

    // Function to handle Delete Selected Orders button click
    function deleteSelectedOrders() {
      const selectedOrderIds = [];
      const checkboxes = document.querySelectorAll('#orders .order-checkbox:checked');
      checkboxes.forEach(checkbox => {
        selectedOrderIds.push(checkbox.value);
      });

      if (selectedOrderIds.length === 0) {
        alert('Please select at least one order to delete.');
        return;
      }

      if (confirm(`Are you sure you want to delete ${selectedOrderIds.length} selected order(s)?`)) {
        fetch('delete_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ order_ids: selectedOrderIds })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            selectedOrderIds.forEach(orderId => {
              const orderRow = document.querySelector(`#orders input[value=\'${orderId}\']`).closest('tr');
              if (orderRow) {
                orderRow.remove();
              }
            });
          } else {
            alert('Error deleting selected orders: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting selected orders.');
        });
      }
    }

    // --- Inventory Actions ---
    // Placeholder function for editing individual inventory items (can be implemented later)
    // function editInventoryItem(itemId) { alert(`Edit Inventory for Product ${itemId} button clicked`); }
    // Placeholder function for deleting individual inventory items (can be implemented later)
    // function deleteInventoryItem(itemId) { alert(`Delete Inventory for Product ${itemId} button clicked`); }

    // Function to handle the Update Inventory button click
    function updateInventory() {
      const rows = document.querySelectorAll('#inventory table tbody tr');
      const productsToUpdate = [];
      const stockIncreaseAmount = 50; // Default amount to increase stock

      rows.forEach(row => {
        // Assuming the product ID is in the second td (index 1) and stock is in the third td (index 2)
        // You might need to adjust indices based on your table structure.
        const cells = row.querySelectorAll('td');
        if (cells.length > 2) { // Ensure there are enough columns
          const productId = cells[1].textContent.trim(); // Assuming product ID is in the second column
          const stock = parseInt(cells[2].textContent.trim(), 10); // Assuming stock is in the third column

          if (!isNaN(stock) && stock <= 0) {
            productsToUpdate.push(productId);
          }
        }
      });

      if (productsToUpdate.length === 0) {
        alert('No inventory items with zero or less stock to update.');
        return;
      }

      if (confirm(`Are you sure you want to increase stock for ${productsToUpdate.length} item(s) by ${stockIncreaseAmount}?`)) {
        // Send AJAX request to update inventory
        fetch('update_inventory.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            product_ids: productsToUpdate,
            increase_amount: stockIncreaseAmount
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message || 'Inventory updated successfully!');
            location.reload(); // Refresh the page to show updated stock
          } else {
            alert('Error updating inventory: ' + (data.message || 'An unknown error occurred.'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while updating the inventory.');
        });
      }
    }

    // Set default tab on page load based on URL hash or show nothing
    document.addEventListener('DOMContentLoaded', function() {
      const initialTab = window.location.hash.substring(1); // Get tabId from hash
      const tabs = document.querySelectorAll('.tab-content');
      const tabButtons = document.querySelectorAll('.tab-btn');

      if (initialTab && document.getElementById(initialTab)) {
        showTab(initialTab); // Show the tab specified in the hash
      } else {
        // If no valid hash, default to showing the 'users' tab
        showTab('users');
      }
    });

    function generateReport(type) {
        // Show loading state
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Generating...';
        button.disabled = true;

        // Make AJAX call to generate PDF
        fetch('generate_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'report_type=' + type
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.blob();
        })
        .then(blob => {
            // Create a download link for the PDF
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = type + '_report_' + new Date().toISOString().split('T')[0] + '.pdf';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generating report. Please try again.');
        })
        .finally(() => {
            // Reset button state
            button.textContent = originalText;
            button.disabled = false;
        });
    }
  </script>
</body>
</html>
<?php $conn->close(); ?>