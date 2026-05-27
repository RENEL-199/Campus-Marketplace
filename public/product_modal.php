<?php

require_once __DIR__ . '/../app/ProductRepository.php';

$repo = new ProductRepository();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "Invalid product ID";
    exit;
}

/* BETTER: fetch only needed product */
$product = null;

foreach ($repo->getAll() as $p) {
    if ($p->prod_id == $id) {
        $product = $p;
        break;
    }
}

if (!$product) {
    echo "Product not found";
    exit;
}
?>

<div class="product-page">

    <div class="product-image">
        <img src="<?= htmlspecialchars($product->prod_image) ?>">
    </div>

    <div class="product-info">

        <div class="container-1">
            <div class="product-title">
                <?= htmlspecialchars($product->prod_name) ?>
            </div>

            <div class="category-tag">
                Category ID: <?= htmlspecialchars($product->category_id) ?>
            </div>
        </div>

        <div class="product-price">
            ₱<?= number_format($product->prod_price, 2) ?>
        </div>

        <div class="stock">
            Stock: <?= $product->prod_stock ?>
        </div>

        <div class="product-desc-box">
            <h3>Description</h3>
            <p><?= nl2br(htmlspecialchars($product->prod_desc)) ?></p>
        </div>

        <div class="product-actions">

            <?php if ($product->prod_stock > 0): ?>

            <form method="POST" action="cart.php">

                <input type="hidden" name="product_id" value="<?= $product->prod_id ?>">

                <div class="cart-controls">

                    <select name="quantity">
                        <?php for ($i = 1; $i <= $product->prod_stock; $i++): ?>
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

        </div>

    </div>

</div>