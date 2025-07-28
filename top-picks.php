<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Top Picks - Grocery Shopping</title>
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
    .products-container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 0 20px;
    }
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 25px;
    }
    .product-card {
      background-color: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s;
      display: flex;
      flex-direction: column;
    }
    .product-card:hover {
      transform: translateY(-5px);
    }
    .product-image {
      height: 180px;
      width: 100%;
      overflow: hidden;
      position: relative;
    }
    .product-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s;
    }
    .product-card:hover .product-image img {
      transform: scale(1.05);
    }
    .top-pick-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: #ff6b6b;
      color: white;
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 12px;
      font-weight: bold;
    }
    .product-details {
      padding: 15px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    .product-name {
      font-size: 18px;
      font-weight: bold;
      margin: 0 0 5px 0;
    }
    .product-price {
      font-size: 16px;
      color: #06c1d2;
      margin: 5px 0 15px 0;
    }
    .product-rating {
      color: #ffa41c;
      margin-bottom: 10px;
    }
    .add-to-cart-button {
      background-color: #06c1d2;
      color: white;
      border: none;
      padding: 10px 0;
      border-radius: 20px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.2s;
      margin-top: auto;
    }
    .add-to-cart-button:hover {
      background-color: #057f8b;
    }
    .section-title {
      text-align: center;
      color: white;
      margin: 30px 0;
      font-size: 24px;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
    }
  </style>
</head>
<body>
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html?error=login_required");
    exit();
}
?>

<header>
  <h1>Top Picks</h1>
  <div style="display: flex; gap: 15px; align-items: center;">
    <a href="products.php" style="color: white; text-decoration: none; display: flex; align-items: center;">
      <span style="margin-right: 5px;">Home</span>
    </a>
    <a href="cart.php" style="color: white; text-decoration: none; position: relative;">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
      </svg>
      <span id="cart-count" style="position: absolute; top: -8px; right: -8px; background-color: #ff6b6b; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 12px; display: flex; align-items: center; justify-content: center;"></span>
    </a>
    <button onclick="logout()" style="background:#fff; color:#0a74da; padding:8px 16px; border-radius:20px; border:none; cursor:pointer;">Logout</button>
  </div>
</header>

<div class="breadcrumb">
  <a href="products.php">Home</a> &gt; <span>Top Picks</span>
</div>

<div class="products-container">
  <h2 class="section-title">🔥 Our Most Popular Items 🔥</h2>
  <div class="products-grid" id="top-picks-grid">
    <!-- Top picks will be populated here by JavaScript -->
  </div>
</div>

<div id="cart-message" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background-color: #4CAF50; color: white; padding: 15px 20px; border-radius: 8px; z-index: 1000; display: none; box-shadow: 0 2px 10px rgba(0,0,0,0.2);"></div>

<script>
  // Get product data from localStorage
  const productData = JSON.parse(localStorage.getItem('productData')) || {};
  
  // Top picks data - we'll select some products from each category
  const topPicks = [
    { category: 'Fruits & Vegetables', product: 'Apple', reason: 'Customer Favorite' },
    { category: 'Personal Care', product: 'Shampoo', reason: 'Best Seller' },
    { category: 'Snacks & Drinks', product: 'Chips', reason: 'Most Popular' },
    { category: 'Cooking Needs', product: 'Oil', reason: 'Highly Rated' },
    { category: 'Packed Foods', product: 'Chocolate', reason: 'Best Value' },
    { category: 'Household Essentials', product: 'Detergent', reason: 'Top Quality' },
    { category: 'Baby Care', product: 'Baby Lotion', reason: 'Must-Have' },
    { category: 'Baking Essentials', product: 'Flour', reason: 'Seasonal Pick' }
  ];
  
  // Display top picks
  function displayTopPicks() {
    const topPicksGrid = document.getElementById('top-picks-grid');
    topPicksGrid.innerHTML = '';
    
    topPicks.forEach(pick => {
      const category = productData[pick.category];
      if (!category) return;
      
      const product = category.find(p => p.name === pick.product);
      if (!product) return;
      
      const productCard = document.createElement('div');
      productCard.className = 'product-card';
      
      productCard.innerHTML = `
        <div class="product-image">
          <img src="${product.img}" alt="${product.name}">
          <div class="top-pick-badge">${pick.reason}</div>
        </div>
        <div class="product-details">
          <h3 class="product-name">${product.name}</h3>
          <div class="product-rating">★★★★★</div>
          <p class="product-price">₹ ${product.price} / ${product.unit}</p>
          <button class="add-to-cart-button" onclick="addToCart(${product.id}, '${product.name}', ${product.price}, '${product.unit}', '${product.img}')">Add to Cart</button>
        </div>
      `;
      
      topPicksGrid.appendChild(productCard);
    });
  }
  
  // Add to cart function
  function addToCart(productId, productName, price, unit, image) {
    const formData = new FormData();
    formData.append('action', 'add');
    // Remove database-specific parameters and lookup
    // let productId = null;
    // for (const category in productData) {
    //     const product = productData[category].find(p => p.name === productName);
    //     if (product) {
    //         productId = product.id;
    //         break;
    //     }
    // }

    // if (productId === null) {
    //     console.error('Product ID not found for:', productName);
    //     alert('Could not find product details to add to cart.');
    //     return;
    // }
    // formData.append('product_id', productId);
    // formData.append('user_id', <?php echo $_SESSION['user_id']; ?>);
    // formData.append('quantity', 1);

    // Add parameters for session-based cart
    formData.append('name', productName);
    formData.append('price', price);
    formData.append('unit', unit);
    formData.append('image', image);
    formData.append('quantity', 1); // Add quantity for session cart
    formData.append('is_ajax', 'true');

    fetch('cart.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Display confirmation message
        const cartMessage = document.getElementById('cart-message');
        cartMessage.textContent = `${productName} has been added to cart!`;
        cartMessage.style.display = 'block';
        
        // Hide the message after a few seconds
        setTimeout(() => {
          cartMessage.style.display = 'none';
        }, 3000); // Hide after 3 seconds
        
        // Optionally update cart count display
        updateCartCount();
        
      } else {
        alert('Failed to add product to cart.' + (data.message ? ': ' + data.message : ''));
      }
    })
    .catch(error => {
      console.error('Error adding to cart:', error);
      alert('An error occurred while adding the product to cart.');
    });

    // Remove the form submission and reload
    // alert(`${productName} has been added to your cart!`);
    // Here you would implement actual cart functionality
  }
  
  // Logout function
  function logout() {
    document.cookie = "loggedIn=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    window.location.href = "index.html";
  }
  
  // Initialize the page
  window.onload = displayTopPicks;

// Function to update cart count in header (optional feature)
function updateCartCount() {
  fetch('cart_count.php')
    .then(response => response.json())
    .then(data => {
      const cartCountElement = document.getElementById('cart-count');
      if (cartCountElement) {
        cartCountElement.textContent = data.count;
        if (data.count > 0) {
          cartCountElement.style.display = 'inline-block';
        } else {
          cartCountElement.style.display = 'none';
        }
      }
    })
    .catch(error => console.error('Error:', error));
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
  // Only call this if the cart count element exists
  const cartCountElement = document.getElementById('cart-count');
  if (cartCountElement) {
    updateCartCount();
  }
});
</script>
</body>
</html>