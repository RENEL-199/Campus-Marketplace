<?php

require_once __DIR__ . '/../app/ProductRepository.php';

$repo = new ProductRepository();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<p>Invalid product ID</p>";
    exit;
}

$product = null;

foreach ($repo->getAll() as $p) {
    if ((int)$p->prod_id === (int)$id) {
        $product = $p;
        break;
    }
}

if (!$product) {
    echo "<p>Product not found</p>";
    exit;
}

?>

<div class="product-modal-layout">

    <!-- IMAGE -->
    <div class="product-image-box">
        <img src="<?= htmlspecialchars($product->prod_image) ?>"
             alt="<?= htmlspecialchars($product->prod_name) ?>">
    </div>

    <!-- INFO -->
    <div class="product-info">

        <h2><?= htmlspecialchars($product->prod_name) ?></h2>

        <div class="product-price">
            ₱<?= number_format($product->prod_price, 0) ?>
        </div>

        <div class="product-stock">
            Stock: <?= (int)$product->prod_stock ?>
        </div>

        <p class="product-desc">
            <?= nl2br(htmlspecialchars($product->prod_desc)) ?>
        </p>

    </div>

</div>