<?php

require_once __DIR__ . '/../app/ProductRepository.php';
require_once __DIR__ . '/../app/csrf.php';

$repo = new ProductRepository();
$csrf = csrf_token();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<p>Invalid rental ID</p>";
    exit;
}

$rental = null;

$rental = $repo->getById((int)$id);

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
                ₱ <?= number_format($rental->prod_price, 0) ?>
                <span>/<?= htmlspecialchars($rental->prod_rate_type ?: 'Day') ?></span>
            </div>

            <p class="rental-owner">
                <strong>Owner:</strong> <?= htmlspecialchars($rental->seller_name ?? 'Unknown Seller') ?>
            </p>

            <p class="rental-stock">
                <strong>Available:</strong> <?= $stock ?>
            </p>

            <div class="product-desc-box">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($rental->prod_desc)) ?></p>
            </div>

            <?php if (!empty($rental->rental_terms)): ?>
            <div class="product-desc-box" style="margin-top:12px;">
                <h3>Rental Terms &amp; Conditions</h3>
                <p style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($rental->rental_terms)) ?></p>
            </div>
            <?php endif; ?>

            <button type="button" class="rent-btn" id="rentThisItemBtn">
                Rent this Item
            </button>

        </div>

    </div>

    <div class="borrow-form-box borrow-form-centered" id="borrowFormBox">

        <h3>Borrow Form</h3>
        <form method="POST" action="cart.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="product_id" value="<?= htmlspecialchars($rental->prod_id) ?>">

    <input type="hidden" name="is_rental" value="1">
    <input type="hidden" name="rate_type" id="rateTypeInput" value="<?= htmlspecialchars($rental->prod_rate_type ?: 'Per Day', ENT_QUOTES) ?>">

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

        <button type="button" id="borrowPlusBtn" data-max-stock="<?= $stock ?>">+</button>
    </div>

    <div class="date-row">
        <label>From:</label>
        <input type="date" name="date_from" id="dateFrom" required>

        <label>To</label>
        <input type="date" name="date_to" id="dateTo" required>
    </div>

    <div class="rental-total-row">
        <strong>Total:</strong>
        <span id="rentalTotal">₱<?= number_format($rental->prod_price, 2) ?></span>
    </div>

    <h4>Borrower Information</h4>

    <input type="text" name="full_name" placeholder="Full Name" required>
    <input type="text" name="student_no" placeholder="Student No." required>

    <div class="borrow-two-inputs">
        <input type="number" name="age" placeholder="Age" required>
        <input type="text" name="gender" placeholder="Gender" required>
    </div>

    <?php if (!empty($rental->rental_terms)): ?>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;margin-top:8px;">
        <input type="checkbox" name="rental_terms_accepted" value="1" required>
        I have reviewed and accept the rental terms and conditions above.
    </label>
    <?php endif; ?>

    <button type="submit" class="confirm-rent-btn">
        Confirm Request
    </button>
</form>

    </div>

</div>

<script>
(function() {
    const price = <?= json_encode((float)$rental->prod_price) ?>;
    const rateType = <?= json_encode($rental->prod_rate_type ?? 'Per Day') ?>;
    const qtyInput = document.getElementById('borrowQty');
    const dateFromInput = document.getElementById('dateFrom');
    const dateToInput = document.getElementById('dateTo');
    const totalLabel = document.getElementById('rentalTotal');
    const rateTypeInput = document.getElementById('rateTypeInput');

    function parseDateIso(value) {
        if (!value) {
            return null;
        }

        const parts = value.split('-').map(Number);
        if (parts.length !== 3) {
            return null;
        }

        return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
    }

    function getDurationDays(from, to) {
        const start = parseDateIso(from);
        const end = parseDateIso(to);

        if (!start || !end || Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
            return 1;
        }

        const diffMs = end.getTime() - start.getTime();
        const days = Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
        return Math.max(1, days);
    }

    function updateTotal() {
        const qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);
        const days = getDurationDays(dateFromInput.value, dateToInput.value);
        let subtotal = price * qty;

        if (rateType.toLowerCase() === 'per day') {
            subtotal = subtotal * days;
        } else if (rateType.toLowerCase() === 'per hour') {
            subtotal = subtotal * Math.max(1, days * 24);
        }

        totalLabel.textContent = '₱' + subtotal.toFixed(2);
        rateTypeInput.value = rateType;
    }

    qtyInput.addEventListener('input', updateTotal);
    dateFromInput.addEventListener('input', updateTotal);
    dateToInput.addEventListener('input', updateTotal);
    qtyInput.addEventListener('change', updateTotal);
    dateFromInput.addEventListener('change', updateTotal);
    dateToInput.addEventListener('change', updateTotal);
    updateTotal();
})();
</script>