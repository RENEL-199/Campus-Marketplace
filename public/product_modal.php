<?php

require_once __DIR__ . '/../app/ProductRepository.php';

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

<div class="product-page">

    <div class="product-image">
        <img src="<?= $product->image ?>">
    </div>

    <div class="product-info">

        <div class="container-1">
            <div class="product-title"><?= $product->name ?></div>
            <div class="category-tag"><?= $product->category ?></div>
        </div>

        <div class="product-price">₱<?= $product->price ?></div>

        <div class="stock">Stock: <?= $product->stock ?></div>

        <div class="product-desc-box">
            <h3>Description</h3>
            <p><?= $product->description ?></p>
        </div>

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

        </div>

    </div>

</div>