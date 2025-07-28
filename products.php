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
  <title>Product Dashboard</title>
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
    .search-bar input {
      padding: 8px 14px;
      width: 450px;
      border-radius: 20px;
      border: none;
    }
    .cards-section {
      display: flex;
      justify-content: center;
      gap: 40px;
      margin: 30px auto;
    }
    .card {
      background-color: white;
      width: 500px;
      height: 410px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      text-align: center;
      padding: 20px;
      font-weight: bold;
      cursor: pointer;
      transition: transform 0.2s;
    }
    .card:hover {
      transform: scale(1.05);
    }
    .card img {
      width: 100%;
      height: 360px;
      object-fit: cover;
      border-radius: 25px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
<?php
  // Get user_id from session
  $user_id = $_SESSION['user_id'];
?>

<header>
  <div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search for products..." onkeyup="searchProducts()">
  </div>
  <div style="display: flex; gap: 15px; align-items: center;">
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

<section class="cards-section">
  <div class="card" onclick="window.location.href='categories.php'">
    <img src="images/Grocery Category.avif" alt="Categories Image">
    📦 Categories
  </div>
  <div class="card" onclick="window.location.href='top-picks.php'">
    <img src="images/Grocery Top pick.jpg" alt="Top Picks Image">
    🔥 Top Picks
  </div>
</section>

<div id="searchResults" style="display:none; margin-top: 30px;"></div>

<div id="cart-message" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background-color: #4CAF50; color: white; padding: 15px 20px; border-radius: 8px; z-index: 1000; display: none; box-shadow: 0 2px 10px rgba(0,0,0,0.2);"></div>

<script>
  // Store user ID from PHP session to use in JavaScript
  const user_id = <?php echo json_encode($user_id); ?>;

  // Shared product data with unique IDs
  const productData = {
    'Fruits & Vegetables': [
      { id: 1, name: 'Apple', img: 'images/apple.jpg', price: 250, unit: 'kg' },
      { id: 2, name: 'Orange', img: 'images/orange.jpg', price: 150, unit: 'kg' },
      { id: 3, name: 'Tomato', img: 'images/tomato.jpg', price: 20, unit: 'kg' },
      { id: 4, name: 'Carrot', img: 'images/carrot.jpg', price: 40, unit: 'kg' },
    ],
    'Household Essentials': [
      { id: 5, name: 'Broom', img: 'images/broom.webp', price: 300, unit: 'pcs' },
      { id: 6, name: 'Washing Soap', img: 'images/washing soap.jpg', price: 40, unit: 'pcs' },
      { id: 7, name: 'Detergent', img: 'images/detergent.jpg', price: 57, unit: 'ml' },
      { id: 8, name: 'Floor Cleaner', img: 'images/Floor_Cleaner.webp', price: 99, unit: 'l' },
    ],
    'Personal Care': [
      { id: 9, name: 'Shampoo', img: 'images/shampoo.webp', price: 174, unit: 'ml' },
      { id: 10, name: 'Body Lotion', img: 'images/body lotion.avif', price: 156, unit: 'ml' },
      { id: 11, name: 'Toothpaste', img: 'images/toothpaste.jpg', price: 75, unit: 'g' },
      { id: 12, name: 'Toothbrush', img: 'images/toothbrush.jpg', price: 25, unit: 'pcs' },
    ],
    'Cooking Needs': [
      { id: 13, name: 'Oil', img: 'images/oil.avif', price: 250, unit: 'l' },
      { id: 14, name: 'Atta', img: 'images/atta.jpg', price: 35, unit: 'kg' },
      { id: 15, name: 'Spices', img: 'images/spices.jpg', price: 750, unit: 'g' },
      { id: 16, name: 'Dhal', img: 'images/dhal.png', price: 48, unit: 'kg' },
    ],
    'Baking Essentials': [
      { id: 17, name: 'Flour', img: 'images/flour.jpg', price: 49, unit: 'kg' },
      { id: 18, name: 'Cocoa Powder', img: 'images/cocoa powder.png', price: 169, unit: 'g' },
      { id: 19, name: 'Baking Powder', img: 'images/baking powder.jpeg', price: 120, unit: 'g' },
      { id: 20, name: 'Baking Soda', img: 'images/baking soda.webp', price: 145, unit: 'g' },
    ],
    'Snacks & Drinks': [
      { id: 21, name: 'Chips', img: 'images/Chips.png', price: 70, unit: 'g' },
      { id: 22, name: 'Frech fries',img: 'images/french fries.webp', price: 55, unit: 'g' },
      { id: 23, name: 'Frooti', img: 'images/frooti.jpg', price: 30, unit: 'ml' },
      { id: 24, name: 'Coca Cola', img: 'images/coco cola.webp', price: 56, unit: 'ml' }
    ],
    'Baby Care': [
      { id: 25, name: 'Diapers', img: 'images/diapers.jpg', price: 15, unit: 'pcs'},
      { id: 26, name: 'Hair oil',img: 'images/hair oil.jpg', price: 80, unit: 'ml'},
      { id: 27, name: 'Baby Lotion', img: 'images/baby lotion.jpg', price: 75, unit: 'ml'},
      { id: 28, name: 'Baby Shampoo', img: 'images/baby shampoo.webp', price: 90, unit: 'l' },
    ],
    'Packed Foods': [
      { id: 29, name: 'Instant Noodles', img: 'images/noodles.webp', price: 45 , unit: 'g' },
      { id: 30, name: 'Chocolate', img: 'images/chocolate.avif', price: 35, unit: 'g'},
      { id: 31, name: 'Biscuits', img: 'images/biscuits.jpg', price: 27, unit: 'g' },
      { id: 32, name: 'Brownie', img: 'images/brownie.jpg', price: 47, unit: 'g' },
    ]
  };

  // Save product data to localStorage for use in other pages
  localStorage.setItem('productData', JSON.stringify(productData));

  // Function to find image URL from hardcoded data based on product name
  function findProductImage(productName) {
    for (const category in productData) {
      const product = productData[category].find(p => p.name.toLowerCase() === productName.toLowerCase());
      if (product) {
        return product.img;
      }
    }
    return 'images/default-product-image.jpg'; // Provide a default image path
  }

  function searchProducts() {
    const searchTerm = document.getElementById("searchInput").value.trim(); // Use trim()
    const searchResultsDiv = document.getElementById("searchResults");

    if (!searchTerm) {
      searchResultsDiv.style.display = 'none';
      searchResultsDiv.innerHTML = '';
      return;
    }

    // Show the search results div
    searchResultsDiv.style.display = 'block'; // Change from flex to block for structure
    searchResultsDiv.innerHTML = '<p style="color:white; width:100%; text-align:center; font-size:18px;">Searching...</p>'; // Show loading message

    // Make AJAX call to the backend search script
    fetch(`search_products.php?query=${encodeURIComponent(searchTerm)}`)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(results => {
        displayResults(results);
      })
      .catch(error => {
        console.error('Error fetching search results:', error);
        searchResultsDiv.innerHTML = '<p style="color:red; width:100%; text-align:center; font-size:18px;">Error fetching results.</p>';
      });
  }

  function displayResults(results) {
    const searchResults = document.getElementById("searchResults");
    searchResults.innerHTML = ''; // Clear previous results

    if (results.length === 0) {
      searchResults.innerHTML = '<p style="color:white; width:100%; text-align:center; font-size:18px;">No products found.</p>';
      return;
    }

    // Create a container for the product cards to maintain flex layout for results
    const resultsContainer = document.createElement('div');
    resultsContainer.style.display = 'flex';
    resultsContainer.style.flexDirection = 'row';
    resultsContainer.style.flexWrap = 'wrap';
    resultsContainer.style.justifyContent = 'center';
    resultsContainer.style.gap = '20px';

    results.forEach(prod => {
      // Access product details from the backend response
      const productId = prod.product_id;
      const productName = prod.product_name;
      const price = prod.price;
      const availability = prod.availability; // You might want to display this
      // Use the helper function to find the image URL
      const imgUrl = findProductImage(productName);

      const div = document.createElement('div');
      div.style.width = '220px';
      div.style.height = '300px';
      div.style.backgroundColor = '#fff';
      div.style.borderRadius = '12px';
      div.style.padding = '15px';
      div.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
      div.style.display = 'flex';
      div.style.flexDirection = 'column';
      div.style.alignItems = 'center';
      div.style.justifyContent = 'space-between';

      const img = document.createElement('img');
      img.src = imgUrl;
      img.alt = productName; // Add alt text for accessibility
      img.style.width = '100%';
      img.style.height = '150px';
      img.style.objectFit = 'cover';
      img.style.borderRadius = '8px';

      const name = document.createElement('h3');
      name.textContent = productName; // Use product name from backend
      name.style.margin = '10px 0 5px';

      const priceElem = document.createElement('p'); // Renamed from 'price'
      priceElem.textContent = `₹ ${price}`; // Use price from backend
      // You might want to add unit if available in your products table or derive it
      // For now, assuming price is just a number
      priceElem.style.margin = '5px 0 15px';

      const button = document.createElement('button');
      button.textContent = 'Add to Cart';
      button.style.backgroundColor = '#06c1d2';
      button.style.color = 'white';
      button.style.border = 'none';
      button.style.borderRadius = '20px';
      button.style.padding = '10px 20px';
      button.style.cursor = 'pointer';
      // Pass product_id and product_name to addToCart
      button.onclick = () => {
        // Assuming your addToCart function expects product ID and name
        // You might need to adjust addToCart based on its actual implementation
        addToCart(productId, productName, price, user_id, 1, imgUrl); // Add 1 quantity, passing price, name, and image URL
      };

      div.appendChild(img);
      div.appendChild(name);
      div.appendChild(priceElem);
      div.appendChild(button);

      resultsContainer.appendChild(div);
    });

    searchResults.appendChild(resultsContainer);
  }

  // Assuming an addToCart function exists and handles the logic
  // It should likely send an AJAX request to add the item to the server-side cart (database/session)
  function addToCart(productId, productName, price, userId, quantity, imageUrl) {
    console.log(`Adding product ${productName} (ID: ${productId}, Price: ${price}) to cart for user ${userId}, Quantity: ${quantity}`);
    
    const formData = new FormData();
    formData.append('action', 'add');
    // Remove database-specific parameters
    // formData.append('product_id', productId);
    // formData.append('user_id', userId);
    // formData.append('quantity', quantity);

    // Add parameters for session-based cart
    formData.append('name', productName);
    formData.append('price', price);
    formData.append('unit', ''); // Unit is not available in search results, might need to fetch or adjust
    formData.append('image', imageUrl);
    formData.append('quantity', 1); // Add quantity for session cart
    formData.append('is_ajax', 'true'); // Add this line to indicate an AJAX request

    fetch('cart.php', {
      method: 'POST',
      body: formData,
    })
    .then(response => response.json())
    .then(data => {
      console.log('Cart updated:', data);
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
      // updateCartCount();
        
      } else {
        alert('Failed to add product to cart.' + (data.message ? ': ' + data.message : ''));
      }
    })
    .catch((error) => {
      console.error('Error adding to cart:', error);
      alert('An error occurred while adding the product to cart.');
    });

    // For now, let's simulate adding to session cart (requires server-side handling)
    // You would typically make an AJAX call here.
    // alert(`Added ${productName} to cart! (Simulated)`);
    // You need a backend script to handle adding to cart session/database
  }

  // Placeholder for updating cart count (if you have this functionality)
  function updateCartCount() {
    // Fetch cart count from backend and update the #cart-count span
  }

  function logout() {
    // Create a form to submit the logout request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'admin_logout.php';
    // Add a hidden input to indicate the redirect target
    const redirectInput = document.createElement('input');
    redirectInput.type = 'hidden';
    redirectInput.name = 'redirect_to';
    redirectInput.value = 'index.html';
    form.appendChild(redirectInput);

    document.body.appendChild(form);
    form.submit();
  }

  // Initialize cart count on page load
  document.addEventListener('DOMContentLoaded', function() {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
      updateCartCount();
    }
  });
</script>
</body>
</html>