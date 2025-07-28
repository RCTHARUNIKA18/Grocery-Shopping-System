<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html?error=login_required");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Categories - Grocery Shopping</title>
  <link rel="stylesheet" href="style.css">
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
    .categories-container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 0 20px;
    }
    .categories-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }
    .category-card {
      width: 350px;
      height: 150px;
      background-color: #fff;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .category-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }
    .category-card img {
      width: 180px;
      height: 150px;
      object-fit: cover;
    }
    .category-info {
      padding: 10px 15px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .category-info h3 {
      margin: 0 0 5px 0;
      font-size: 18px;
    }
    .category-info p {
      margin: 0;
      color: #666;
      font-size: 14px;
    }
    
    /* Products display style */
    .products-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
      display: none;
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
    .add-to-cart-button {
      background-color: #06c1d2;
      color: white;
      border: none;
      padding: 10px 0;
      border-radius: 20px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    .add-to-cart-button:hover {
      background-color: #057f8b;
    }
    .back-button {
      background-color: #f0f0f0;
      border: none;
      padding: 10px 20px;
      border-radius: 20px;
      cursor: pointer;
      margin-bottom: 15px;
      font-weight: bold;
      display: none;
    }
  </style>
</head>
<body>

<header>
  <h1>Grocery Categories</h1>
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
  <a href="products.php">Home</a> &gt; <span id="current-page">Categories</span>
</div>

<div class="categories-container" id="categories-view">
  <h2 style="text-align: center; color: white; margin-bottom: 30px;">Browse Categories</h2>
  <div class="categories-grid" id="categories-grid">
    <!-- Categories will be populated here by JavaScript -->
  </div>
</div>

<div class="products-container" id="products-view">
  <button class="back-button" id="back-to-categories">← Back to Categories</button>
  <h2 id="category-title" style="text-align: center; color: white; margin-bottom: 30px;">Products</h2>
  <div class="products-grid" id="products-grid">
    <!-- Products will be populated here by JavaScript -->
  </div>
</div>

<div id="cart-message" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background-color: #4CAF50; color: white; padding: 15px 20px; border-radius: 8px; z-index: 1000; display: none; box-shadow: 0 2px 10px rgba(0,0,0,0.2);"></div>

<script>
  // Get product data from localStorage
  const productData = JSON.parse(localStorage.getItem('productData')) || {};
  
  // Categories data
  const categories = [
    { name: 'Fruits & Vegetables', img: 'images/Fruit and Vegetables.webp', desc: 'Fresh produce for your daily needs' },
    { name: 'Household Essentials', img: 'images/Household.jpg', desc: 'Keep your home clean and organized' },
    { name: 'Personal Care', img: 'images/Personal-care.jpg', desc: 'Products for personal hygiene and care' },
    { name: 'Cooking Needs', img: 'images/Cooking essentials.jpg', desc: 'Essential ingredients for cooking' },
    { name: 'Baking Essentials', img: 'images/Baking products.jpg', desc: 'Everything you need for baking' },
    { name: 'Snacks & Drinks', img: 'images/Snacks and drinks.jpg', desc: 'Tasty treats and refreshing beverages' },
    { name: 'Baby Care', img: 'images/Baby products.webp', desc: 'Products for baby health and comfort' },
    { name: 'Packed Foods', img: 'images/Packed items.jpg', desc: 'Ready-to-eat and conveniently packaged foods' }
  ];
  
  // Display categories
  function displayCategories() {
    const categoriesGrid = document.getElementById('categories-grid');
    categoriesGrid.innerHTML = '';
    
    categories.forEach(category => {
      const categoryCard = document.createElement('div');
      categoryCard.className = 'category-card';
      categoryCard.onclick = () => showProducts(category.name);
      
      categoryCard.innerHTML = `
        <img src="${category.img}" alt="${category.name}">
        <div class="category-info">
          <h3>${category.name}</h3>
          <p>${category.desc}</p>
        </div>
      `;
      
      categoriesGrid.appendChild(categoryCard);
    });
    
    document.getElementById('categories-view').style.display = 'block';
    document.getElementById('products-view').style.display = 'none';
    document.getElementById('current-page').textContent = 'Categories';
  }
  
  // Display products for a selected category
  function showProducts(categoryName) {
    const products = productData[categoryName] || [];
    const productsGrid = document.getElementById('products-grid');
    productsGrid.innerHTML = '';
    
    products.forEach(product => {
  const productCard = document.createElement('div');
  productCard.className = 'product-card';
  
  productCard.innerHTML = `
    <div class="product-image">
      <img src="${product.img}" alt="${product.name}">
    </div>
    <div class="product-details">
      <h3 class="product-name">${product.name}</h3>
      <p class="product-price">₹ ${product.price} / ${product.unit}</p>
      <button class="add-to-cart-button" onclick="addToCart('${product.id}', '${product.name}', ${product.price}, '${product.unit}', '${product.img}')">Add to Cart</button>
    </div>
  `;
  
  productsGrid.appendChild(productCard);
});

    
    document.getElementById('category-title').textContent = categoryName;
    document.getElementById('categories-view').style.display = 'none';
    document.getElementById('products-view').style.display = 'block';
    document.getElementById('back-to-categories').style.display = 'inline-block';
    document.getElementById('current-page').textContent = categoryName;
  }
  
  // Back to categories
  document.getElementById('back-to-categories').addEventListener('click', displayCategories);
  
  // Add to cart function
  function addToCart(productId, productName, price, unit, image) {
    // Use fetch to send data asynchronously
    const formData = new FormData();
    formData.append('action', 'add');
    // Remove database-specific parameters
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
  window.onload = displayCategories;

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