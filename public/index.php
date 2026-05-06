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

$products = array_filter($repo->getAll(), function ($product) {
    return $product->stock > 0;
});

if ($category) {
    $products = array_filter($products, function ($product) use ($category) {
        return $product->category === $category;
    });
}


if ($search) {
    $products = array_filter($products, function ($product) use ($search) {
        return stripos($product->name, $search) !== false ||
               stripos($product->description, $search) !== false ||
               stripos($product->category, $search) !== false;
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

<!-- HERO -->
<section class="hero">
    <h2>Buy & Sell Campus Essentials</h2>
    <p>Find affordable gadgets, school supplies, and services near you.</p>

    <form method="GET" class="search-box">
        <input type="text" name="q" placeholder="Search items..."
               value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit">Search</button>
    </form>
</section>

<div class="container">

    <!-- ACTIVE FILTER INFO -->
    <?php if ($search || $category): ?>
        <p style="margin-bottom: 15px;">
            <?php if ($search): ?>
                Search: <strong><?= htmlspecialchars($search) ?></strong>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <!-- CATEGORIES -->
    <h2 class="section-title">Categories</h2>

    <div class="categories">

        <a class="category <?= !$category ? 'active' : '' ?>" href="index.php">All</a>
        <a class="category <?= $category=='Electronics'?'active':'' ?>" href="index.php?category=Electronics">Electronics</a>
        <a class="category <?= $category=='School Supplies'?'active':'' ?>" href="index.php?category=School Supplies">School Supplies</a>
        <a class="category <?= $category=='Services'?'active':'' ?>" href="index.php?category=Services">Services</a>
        <a class="category <?= $category=='Preloved'?'active':'' ?>" href="index.php?category=Preloved">Preloved</a>

    </div>

    <!-- PRODUCTS -->
    <h2 class="section-title">Featured Items</h2>

    <?php
        if (empty($products)) {
            echo "<p>No products found.</p>";
        } else {
            echo $view->renderProducts($products);
        }
    ?>

    <!-- PRODUCT MODAL -->
    <div id="productModal" class="modal">

        <div class="modal-content">

            <span class="close-btn" onclick="closeModal()">&times;</span>

            <div id="modal-body">

            </div>

        </div>

    </div>

</div>

<footer>
    © 2026 Campus Market — Built for students
</footer>

</body>

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

</html>