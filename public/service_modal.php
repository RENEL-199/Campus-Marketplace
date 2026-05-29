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

    <!-- SERVICE VIEWER -->
    <div class="service-viewer" id="serviceViewer">

        <div class="service-image-box">
            <img 
                src="<?= htmlspecialchars($service->prod_image) ?>"
                alt="<?= htmlspecialchars($service->prod_name) ?>"
            >
        </div>

        <div class="service-info">

            <div class="service-title-row">

                <h2><?= htmlspecialchars($service->prod_name) ?></h2>

                <div>
                    <span class="service-tag">Services</span>
                </div>

            </div>

            <div class="service-price">
                ₱ <?= number_format($service->prod_price, 0) ?>
            </div>

            <p class="service-owner">
                <strong>Owner:</strong>
            </p>

            <p class="service-location">
                <strong>Location:</strong>
                <?= htmlspecialchars($service->location ?? 'Campus Area') ?>
            </p>

            <div class="product-desc-box">

                <h3>Description</h3>

                <p>
                    <?= nl2br(htmlspecialchars($service->prod_desc)) ?>
                </p>

            </div>

            <button 
                type="button" 
                class="avail-service-btn"
                id="availServiceBtn"
            >
                Avail Service
            </button>

        </div>

    </div>

    <!-- SERVICE FORM -->
    <div class="service-form-box service-form-centered" id="serviceFormBox">

        <h3>Service Form</h3>

        <form method="POST" action="cart.php" enctype="multipart/form-data">
            <input 
                type="hidden" 
                name="product_id" 
                value="<?= htmlspecialchars($service->prod_id) ?>"
            >

            <div class="upload-box" id="uploadPreviewBox">

                <img 
                    id="previewImage"
                    src=""
                    alt="Preview"
                    style="display:none;"
                >

                <h4 id="uploadText">
                    Upload file here
                </h4>

            </div>

            <div class="service-files-section">

                <h3>Files:</h3>

                <div class="service-file-row">

                    <input 
                        type="file" 
                        name="service_file"
                        id="serviceFileInput"
                        accept="image/*"
                        required
                    >

                    <button 
                        type="button" 
                        class="service-qty-btn service-minus-btn"
                    >
                        −
                    </button>

                    <input 
                        type="number" 
                        name="quantity" 
                        id="serviceQty"
                        class="service-qty-input"
                        value="1"
                        min="1"
                    >

                    <button 
                        type="button" 
                        class="service-qty-btn service-plus-btn"
                    >
                        +
                    </button>

                    <div class="print-options">

                        <label>
                            <input type="radio" name="print_type" value="B&W" checked>
                            B&W
                        </label>

                        <label>
                            <input type="radio" name="print_type" value="Colored">
                            colored
                        </label>

                    </div>

                </div>

            </div>

            <div class="service-info-section">

                <h3>Information</h3>

                <input 
                    type="text" 
                    name="full_name" 
                    placeholder="Full Name"
                    required
                >

                <input 
                    type="text" 
                    name="student_no" 
                    placeholder="Student No."
                    required
                >

            </div>

            <button type="submit" class="confirm-service-btn">
                Confirm Service
            </button>

        </form>

    </div>

</div>