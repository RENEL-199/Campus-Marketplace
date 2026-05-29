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

$stock = (int)$rental->prod_stock;
?>

<div class="rental-modal-layout">

    <div class="rental-viewer" id="rentalViewer">

        <div class="rental-image-box">
            <img 
                src="<?= htmlspecialchars($rental->prod_image) ?>"
                alt="<?= htmlspecialchars($rental->prod_name) ?>"
            >
        </div>

        <div class="rental-info">

            <div class="rental-title-row">
                <h2><?= htmlspecialchars($rental->prod_name) ?></h2>

                <div>
                    <span class="rental-tag">Rentals</span>
                </div>
            </div>

            <div class="rental-price">
                ₱ <?= number_format($rental->prod_price, 0) ?><span>/day</span>
            </div>

            <p class="rental-owner">
                <strong>Owner:</strong>
            </p>

            <p class="rental-stock">
                <strong>Available:</strong> <?= $stock ?>
            </p>

            <div class="product-desc-box">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($rental->prod_desc)) ?></p>
            </div>

            <button type="button" class="rent-btn" id="rentThisItemBtn">
                Rent this Item
            </button>

        </div>

    </div>

    <div class="borrow-form-box borrow-form-centered" id="borrowFormBox">

        <h3>Borrow Form</h3>
        <form method="POST" action="cart.php">
    <input type="hidden" name="product_id" value="<?= htmlspecialchars($rental->prod_id) ?>">

    <input type="hidden" name="is_rental" value="1">

    <div class="borrow-quantity-row">
        <label>Quantity:</label>

        <button type="button" id="borrowMinusBtn">−</button>

        <input 
            type="number" 
            name="quantity" 
            id="borrowQty" 
            value="1" 
            min="1" 
            max="<?= $stock ?>"
        >

        <button type="button" id="borrowPlusBtn">+</button>
    </div>

    <div class="date-row">
        <label>From:</label>
        <input type="date" name="date_from" required>

        <label>To</label>
        <input type="date" name="date_to" required>
    </div>

    <h4>Borrower Information</h4>

    <input type="text" name="full_name" placeholder="Full Name" required>
    <input type="text" name="student_no" placeholder="Student No." required>

    <div class="borrow-two-inputs">
        <input type="number" name="age" placeholder="Age" required>
        <input type="text" name="gender" placeholder="Gender" required>
    </div>

    <button type="submit" class="confirm-rent-btn">
        Confirm Request
    </button>
</form>

    </div>

</div>