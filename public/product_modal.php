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

$category_name = "Category " . $product->category_id;

if ($product->category_id == 1) {
    $category_name = "Electronics";
} elseif ($product->category_id == 2) {
    $category_name = "School Supplies";
} elseif ($product->category_id == 3) {
    $category_name = "Services";
} elseif ($product->category_id == 4) {
    $category_name = "Preloved";
} elseif ($product->category_id == 5) {
    $category_name = "Rentals";
}

$stock = (int)$product->prod_stock;
?>

<div class="product-page">

    <div class="product-image">
<<<<<<< HEAD
        <img src="<?= htmlspecialchars($product->prod_image) ?>">
=======
        <img src="<?= htmlspecialchars($product->prod_image) ?>" alt="<?= htmlspecialchars($product->prod_name) ?>">
>>>>>>> origin/polin
    </div>

    <div class="product-info">

        <div class="container-1">
            <div class="product-title">
                <?= htmlspecialchars($product->prod_name) ?>
            </div>

            <div class="category-tag">
<<<<<<< HEAD
                Category ID: <?= htmlspecialchars($product->category_id) ?>
=======
                <?= htmlspecialchars($category_name) ?>
>>>>>>> origin/polin
            </div>
        </div>

        <div class="product-price">
<<<<<<< HEAD
            ₱<?= number_format($product->prod_price, 2) ?>
        </div>

        <div class="stock">
            Stock: <?= $product->prod_stock ?>
=======
            ₱ <?= number_format($product->prod_price, 0) ?>
        </div>

        <div class="seller">
            <strong>Seller:</strong>
        </div>

        <div class="stock">
            <strong>Stock:</strong> <?= $stock ?>
>>>>>>> origin/polin
        </div>

        <div class="product-desc-box">
            <h3>Description</h3>
            <p><?= nl2br(htmlspecialchars($product->prod_desc)) ?></p>
        </div>

        <div class="product-actions">

<<<<<<< HEAD
            <?php if ($product->prod_stock > 0): ?>

            <form method="POST" action="cart.php">

                <input type="hidden" name="product_id" value="<?= $product->prod_id ?>">

                <div class="cart-controls">

                    <select name="quantity">
                        <?php for ($i = 1; $i <= $product->prod_stock; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
=======
            <?php if ($stock > 0): ?>

            <form method="POST" action="cart.php">

                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product->prod_id) ?>">

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
>>>>>>> origin/polin

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