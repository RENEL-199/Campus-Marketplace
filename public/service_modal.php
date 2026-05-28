<?php

require_once __DIR__ . '/../app/ProductRepository.php';

$repo = new ProductRepository();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<p>Invalid service ID</p>";
    exit;
}

$service = null;

foreach ($repo->getAll() as $p) {
    if ((int)$p->prod_id === (int)$id) {
        $service = $p;
        break;
    }
}

if (!$service) {
    echo "<p>Service not found</p>";
    exit;
}

?>

<div class="service-modal-layout">

    <!-- VIEW -->
    <div class="service-viewer" id="serviceViewer">

        <div class="service-image-box">
            <img src="<?= htmlspecialchars($service->prod_image) ?>"
                 alt="<?= htmlspecialchars($service->prod_name) ?>">
        </div>

        <div class="service-info">

            <h2><?= htmlspecialchars($service->prod_name) ?></h2>

            <span class="service-tag">Service</span>

            <div class="service-price">
                ₱<?= number_format($service->prod_price, 0) ?>
            </div>

            <p>
                <strong>Location:</strong>
                <?= htmlspecialchars($service->location ?? 'Campus Area') ?>
            </p>

            <p>
                <?= nl2br(htmlspecialchars($service->prod_desc)) ?>
            </p>

            <!-- IMPORTANT: no duplicate ID issue -->
            <button type="button" class="open-service-form-btn">
                Avail Service
            </button>

        </div>

    </div>

    <!-- FORM -->
    <div class="service-form-box service-form-centered" id="serviceFormBox" style="display:none;">

        <h3>Service Form</h3>

        <form method="POST" action="service_request.php" enctype="multipart/form-data">

            <input type="hidden" name="product_id"
                   value="<?= htmlspecialchars($service->prod_id) ?>">

            <div>
                <input type="file" name="service_file" id="serviceFileInput" accept="image/*" required>
            </div>

            <div>
                <button type="button" class="service-minus-btn">-</button>

                <input type="number"
                       id="serviceQty"
                       name="copies"
                       value="1"
                       min="1">

                <button type="button" class="service-plus-btn">+</button>
            </div>

            <div>
                <label><input type="radio" name="print_type" value="B&W" checked> B&W</label>
                <label><input type="radio" name="print_type" value="Colored"> Colored</label>
            </div>

            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="text" name="student_no" placeholder="Student No." required>

            <button type="submit">Confirm Service</button>

        </form>

    </div>

</div>