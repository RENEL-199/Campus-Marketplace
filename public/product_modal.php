<?php

require_once __DIR__ . '/../app/ProductRepository.php';

$repo = new ProductRepository();

function public_image_path(?string $path): string {
    $path = trim((string)$path);

    if ($path === '') {
        return 'uploads/default.png';
    }

    $path = str_replace('\\', '/', $path);

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    $pos = strpos($path, 'uploads/');
    if ($pos !== false) {
        return substr($path, $pos);
    }

    return 'uploads/' . basename($path);
}


$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<p>Invalid product ID</p>";
    exit;
}

$product = $repo->getById($id);

if (!$product) {
    echo "<p>Product not found</p>";
    exit;
}

$category_name = $product->category_name ?: ("Category " . $product->category_id);
$stock = (int)$product->prod_stock;
$imagePath = public_image_path($product->prod_image);
?><div class="product-page">

    <div class="product-image">
        <img 
            src="<?= htmlspecialchars($imagePath) ?>" 
            alt="<?= htmlspecialchars($product->prod_name) ?>"
        >
    </div>

    <div class="product-info">

        <div class="container-1">
            <div class="product-title">
                <?= htmlspecialchars($product->prod_name) ?>
            </div>

            <div class="category-tag">
                <?= htmlspecialchars($category_name) ?>
            </div>
        </div>

        <div class="product-price">
            ₱ <?= number_format($product->prod_price, 0) ?>
        </div>

        <div class="seller">
            <strong>Seller:</strong>
        </div>

        <div class="stock">
            <strong>Stock:</strong> <?= $stock ?>
        </div>

        <div class="product-desc-box">
            <h3>Description</h3>
            <p><?= nl2br(htmlspecialchars($product->prod_desc)) ?></p>
        </div>

        <div class="product-actions">

            <?php if ($stock > 0): ?>

                <form method="POST" action="cart.php">

                    <input 
                        type="hidden" 
                        name="product_id" 
                        value="<?= htmlspecialchars($product->prod_id) ?>"
                    >

                    <div class="cart-controls">

                        <button 
                            type="button" 
                            class="qty-btn product-minus-btn"
                        >
                            −
                        </button>

                        <input 
                            type="number" 
                            name="quantity" 
                            id="modalQuantity" 
                            class="qty-input product-qty-input" 
                            value="1" 
                            min="1" 
                            max="<?= $stock ?>"
                        >

                        <button 
                            type="button" 
                            class="qty-btn product-plus-btn"
                            data-max-stock="<?= $stock ?>"
                        >
                            +
                        </button>

                        <button type="submit" class="buy-btn">
                            Add to Cart
                        </button>

                    </div>

                </form>

            <?php else: ?>

                <button class="buy-btn" disabled>
                    Out of Stock
                </button>

            <?php endif; ?>

        </div>

    </div>

</div>