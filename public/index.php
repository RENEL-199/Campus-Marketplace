<?php

require_once __DIR__. '/../app/Product.php';
require_once __DIR__. '/../app/ProductRepository.php';
require_once __DIR__. '/../app/View.php';
require_once __DIR__. '/../app/auth.php';

require_login();

$repo = new ProductRepository();
$view = new View();

$search = $_GET['q'] ?? null;
$category = $_GET['category'] ?? null;

/* =========================
   GET ALL PRODUCTS
========================= */
$products = $repo->getAll();

/* =========================
   SEARCH FILTER
========================= */
if (!empty($search)) {

    $products = array_filter($products, function ($product) use ($search) {

        return stripos($product->prod_name, $search) !== false
            || stripos($product->prod_desc, $search) !== false;
    });
}

/* =========================
   CATEGORY FILTER FUNCTION
========================= */
function filterByCategory($products, $allowedCategories, $selectedCategory = null) {

    return array_filter($products, function ($product) use ($allowedCategories, $selectedCategory) {

        if ($selectedCategory) {
            return in_array($product->category_id, $allowedCategories)
                && $product->category_id == $selectedCategory;
        }

        return in_array($product->category_id, $allowedCategories);
    });
}

/* =========================
   FEATURED ITEMS (1,2,4)
========================= */
$featured_items = filterByCategory($products, [1, 2, 4], $category);

/* =========================
   RENTALS (5)
========================= */
$featured_rentals = filterByCategory($products, [5], $category);

/* =========================
   SERVICES (3)
========================= */
$featured_services = filterByCategory($products, [3], $category);

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Campus Market</title>

<link rel="stylesheet" href="../assets/index-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<!-- NAV -->
<nav>
    <h1>Campus Market</h1>
    <div>
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
        <a href="orders.php"><i class="fa-solid fa-box"></i> Orders</a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> Sell</a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <h2>Buy & Sell Campus Essentials</h2>

    <form method="GET" class="search-box">
        <input type="text" name="q" placeholder="Search items..."
               value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit">Search</button>
    </form>
</section>

<div class="container">

<!-- CATEGORIES -->
<h2 class="section-title">Categories</h2>

<div class="categories">
    <a class="category <?= !$category ? 'active' : '' ?>" href="index.php">All</a>
    <a class="category <?= $category==1?'active':'' ?>" href="index.php?category=1">Electronics</a>
    <a class="category <?= $category==2?'active':'' ?>" href="index.php?category=2">School Supplies</a>
    <a class="category <?= $category==3?'active':'' ?>" href="index.php?category=3">Services</a>
    <a class="category <?= $category==4?'active':'' ?>" href="index.php?category=4">Preloved</a>
    <a class="category <?= $category==5?'active':'' ?>" href="index.php?category=5">Rentals</a>
</div>

<!-- FEATURED ITEMS -->
<?php if (!$category || in_array($category, [1,2,4])): ?>

<div class="section-title">Featured Items</div>

<div class="grid">

    <?php if (empty($featured_items)): ?>
        <p>No products found.</p>
    <?php else: ?>
        <?= $view->renderProducts($featured_items); ?>
    <?php endif; ?>

</div>

<?php endif; ?>

<!-- RENTALS -->
<?php if (!$category || $category == 5): ?>

<div class="section-title">Featured Rentals</div>

<div class="grid">

    <?php if (empty($featured_rentals)): ?>
        <p>No rentals found.</p>
    <?php else: ?>
        <?= $view->renderProducts($featured_rentals); ?>
    <?php endif; ?>

</div>

<?php endif; ?>

<!-- SERVICES -->
<?php if (!$category || $category == 3): ?>

<div class="section-title">Featured Services</div>

<div class="grid">

    <?php if (empty($featured_services)): ?>
        <p>No services found.</p>
    <?php else: ?>
        <?= $view->renderProducts($featured_services); ?>
    <?php endif; ?>

</div>

<?php endif; ?>

<!-- SELL BANNER -->
<div style="margin-top:50px;background:#ead4d1;padding:20px;border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h3>Got something to sell or offer?</h3>
        <p>List your items, rentals, or services.</p>
    </div>

    <a href="seller_dashboard.php">
        <button style="background:#8b0d04;color:white;border:none;padding:12px 18px;border-radius:8px;">
            Sell Product
        </button>
    </a>
</div>

</div>

<!-- MODAL -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <div id="modal-body"></div>
    </div>
</div>

<script>

function openModal(url) {

    fetch(url)
        .then(res => res.text())
        .then(data => {

            document.getElementById("modal-body").innerHTML = data;
            document.getElementById("productModal").style.display = "block";

        })
        .catch(err => {
            console.error("Modal load failed:", err);
        });

}

/* PRODUCT MODAL */
function openProduct(id) {
    openModal("product_modal.php?id=" + id);
}

/* RENTAL MODAL */
function openRental(id) {
    openModal("rental_modal.php?id=" + id);
}

/* SERVICE MODAL */
function openService(id) {
    openModal("service_modal.php?id=" + id);
}

/* CLOSE MODAL */
function closeModal() {
    document.getElementById("productModal").style.display = "none";
    document.getElementById("modal-body").innerHTML = "";
}

/* CLOSE ON OUTSIDE CLICK */
window.onclick = function(event) {
    const modal = document.getElementById("productModal");
    if (event.target === modal) {
        closeModal();
    }
};

</script>

</body>


</html>