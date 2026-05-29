<?php

require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/ProductRepository.php';
require_once __DIR__ . '/../app/auth.php';

$user_id = current_user_id();

$repo = new ProductRepository();

$id = $_GET['id'] ?? null;
$product = null;

foreach ($repo->getAll() as $p) {
    if ($p->id == $id) {
        $product = $p;
        break;
    }
}

if (!$product) {
    echo "Product not found";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title><?= $product->name ?></title>

<link rel="stylesheet" href="../assets/index-style.css">

<style>

/* LAYOUT */
.product-page {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 30px;
    margin-top: 30px;
}

/* IMAGE CARD */
.product-image {
    background: white;
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-info img {
    width: 100%;
    max-height: 420px;
    object-fit: contain;
    border-radius: 12px;
}

/* INFO CARD */
.product-info {
    background: white;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    
}

/* TITLE */
.product-title {
    font-size: 1.6rem;
    font-weight: 600;
}

/* CATEGORY */
.category-tag {
    display: inline-block;
    background: var(--secondary);
    color: var(--primary-dark);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    margin-top: 10px;
}

/* PRICE */
.product-price {
    font-size: 2rem;
    color: var(--primary);
    font-weight: bold;
    margin: 15px 0;
}

/* STOCK */
.stock {
    font-size: 0.95rem;
    color: #555;
    margin-bottom: 20px;
}

/* DESCRIPTION BOX */
.product-desc-box {
    background: #f9fafb;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.product-desc-box h3 {
    margin-bottom: 8px;
}

/* ACTION AREA */
.product-actions {
    margin-top: auto;
    border-top: 1px solid #eee;
    padding-top: 15px;
}

/* CART CONTROLS */
.cart-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.cart-controls select {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

/* BUTTON */
.buy-btn {
    flex: 1;
    padding: 14px;
    border: none;
    background: var(--primary);
    color: white;
    border-radius: 10px;
    font-size: 1rem;
    cursor: pointer;
    transition: 0.2s;
}

.buy-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.buy-btn:disabled {
    background: gray;
    cursor: not-allowed;
}

/* BACK */
.back-link {
    display: inline-block;
    margin-top: 15px;
    color: var(--primary-dark);
    text-decoration: none;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .product-page {
        grid-template-columns: 1fr;
    }
}

.container-1 {
    display: flex;
    gap: 15px;
    align-items: center;
    justify-content: space-between;
}

</style>

</head>

<body>

<nav>
    <div>
        <a href="index.php">Home</a>
        <a href="cart.php">Cart</a>
        <a href="orders.php">Orders</a>
        <a href="seller_dashboard.php">Sell</a>
        <a href="account.php">Account</a>
        <a href="logout.php">Log Out</a>
    </div>
</nav>

<div class="container">

<div class="product-page">

    <!-- IMAGE -->
    <div class="product-info">
        <img src="<?= $product->image ?>" alt="product">
    

    <!-- DETAILS -->
 
<div class="container-1">
 

        <div class="product-title">
            <?= $product->name ?>
        </div>

               <div class="category-tag">
            <?= $product->category ?>
        </div>
        </div>

        <div class="product-price">
            ₱<?= $product->price ?>
        </div>

        <div class="stock">
            Stock available: <?= $product->stock ?>
        </div>

        <!-- DESCRIPTION -->
        <div class="product-desc-box">
            <h3>Description</h3>
            <p><?= $product->description ?></p>
        </div>

        <!-- ACTIONS -->
        <div class="product-actions">

            <?php if ($product->stock > 0): ?>

            <form method="POST" action="cart.php">

                <input type="hidden" name="product_id" value="<?= $product->id ?>">

                <div class="cart-controls">

                    <select name="quantity">
                        <?php for ($i = 1; $i <= $product->stock; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>

                    <button type="submit" class="buy-btn">
                        Add to Cart
                    </button>

                </div>

            </form>

            <?php else: ?>

                <button class="buy-btn" disabled>Out of Stock</button>

            <?php endif; ?>

            <a class="back-link" href="index.php">← Back to marketplace</a>

        </div>

    </div>

</div>

</div>

</body>
</html>