<?php

require_once __DIR__ . '/../app/ProductRepository.php';

$repo = new ProductRepository();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<p>Invalid rental ID</p>";
    exit;
}

$rental = null;

foreach ($repo->getAll() as $p) {
    if ((int)$p->prod_id === (int)$id) {
        $rental = $p;
        break;
    }
}

if (!$rental) {
    echo "<p>Rental not found</p>";
    exit;
}

?>

<div class="rental-modal-layout">

    <!-- LEFT VIEW -->
    <div class="rental-viewer" id="rentalViewer">

        <div class="rental-image-box">
            <img src="<?= htmlspecialchars($rental->prod_image) ?>"
                 alt="<?= htmlspecialchars($rental->prod_name) ?>">
        </div>

        <div class="rental-info">

            <h2><?= htmlspecialchars($rental->prod_name) ?></h2>

            <span class="rental-tag">Rental</span>

            <div class="rental-price">
                ₱<?= number_format($rental->prod_price, 0) ?>
            </div>

            <p class="rental-desc">
                <?= nl2br(htmlspecialchars($rental->prod_desc)) ?>
            </p>

        </div>
        

    </div>

    <!-- RIGHT FORM -->
    <div class="rental-form-box" id="borrowFormBox" style="display:none;">

        <h3>Borrow Form</h3>

        <form method="POST" action="rental_request.php">

            <input type="hidden" name="product_id"
                   value="<?= htmlspecialchars($rental->prod_id) ?>">

            <label>Full Name</label>
            <input type="text" name="full_name" required>

            <label>Student No.</label>
            <input type="text" name="student_no" required>

            <label>Quantity</label>

            <div class="qty-box">
                <button type="button" id="borrowMinusBtn">-</button>

                <input type="number"
                       id="borrowQty"
                       name="quantity"
                       value="1"
                       min="1"
                       max="<?= (int)$rental->prod_stock ?>">

                <button type="button"
                        id="borrowPlusBtn"
                        data-max-stock="<?= (int)$rental->prod_stock ?>">
                    +
                </button>
            </div>

            <button type="submit">Confirm Rent</button>

        </form>

    </div>

</div>