<?php

require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/ProductRepository.php';
require_once __DIR__ . '/../app/View.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$user_id = current_user_id();

$repo = new ProductRepository();
$view = new View();

$search = $_GET['q'] ?? null;
$category = $_GET['category'] ?? null;

/* GET ALL PRODUCTS */
$products = $repo->getAll();

/* FILTER: IN STOCK ONLY */
$products = array_filter($products, function ($product) {
    return $product->prod_stock > 0;
});

/* SEARCH FILTER */
if ($search) {
    $products = array_filter($products, function ($product) use ($search) {
        return stripos($product->prod_name, $search) !== false ||
               stripos($product->prod_desc, $search) !== false;
    });
}

/* CATEGORY FILTER (TEMP: using category_id) */
if ($category) {
    $products = array_filter($products, function ($product) use ($category) {
        return $product->category_id == $category;
    });
}

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

<nav>
    <h1>Campus Market</h1>
    <div>
        <a href="index.php"><i class="fa-solid fa-house"></i></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
        <a href="orders.php"><i class="fa-solid fa-box"></i></a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i></a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
    </div>
</nav>

<section class="hero">
    <h2>Buy & Sell Campus Essentials</h2>

    <form method="GET" class="search-box">
        <input type="text" name="q" placeholder="Search items..."
               value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit">Search</button>
    </form>
</section>

<div class="container">

    <?php if ($search || $category): ?>
        <p>
            <?php if ($search): ?>
                Search: <strong><?= htmlspecialchars($search) ?></strong>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <h2 class="section-title">Categories</h2>

    <div class="categories">

        <a class="category <?= !$category ? 'active' : '' ?>" href="index.php">All</a>
        <a class="category <?= $category==1?'active':'' ?>" href="index.php?category=1">Electronics</a>
        <a class="category <?= $category==2?'active':'' ?>" href="index.php?category=2">School Supplies</a>
        <a class="category <?= $category==3?'active':'' ?>" href="index.php?category=3">Services</a>
        <a class="category <?= $category==4?'active':'' ?>" href="index.php?category=4">Preloved</a>

    </div>

    <h2 class="section-title">Featured Items</h2>

    <?php
        if (empty($products)) {
            echo "<p>No products found.</p>";
        } else {
            echo $view->renderProducts($products);
        }
    ?>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>

</div>

<footer>
    © 2026 Campus Market — Built for students
</footer>

<script>
function openProduct(id) {
    fetch("product_modal.php?id=" + id)
        .then(res => res.text())
        .then(data => {
            document.getElementById("modal-body").innerHTML = data;
            document.getElementById("productModal").style.display = "block";
        });
}

function closeModal() {
    document.getElementById("productModal").style.display = "none";
}
</script>

</body>
</html>