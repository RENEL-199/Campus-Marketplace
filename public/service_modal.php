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
            <input type="hidden" name="is_service" value="1">

            <input 
                type="hidden" 
                name="product_id" 
                value="<?= htmlspecialchars($service->prod_id) ?>"
            >

            
                <p style="font-size:13px; margin:6px 0 0; color:red;">
                    Note: For multiple Images. Please Upload a pdf file with all the images inside. Or you can also upload a zip file with all the images inside.
                </p>


            <div class="service-files-section">

                <h3>Files:</h3>

                <div class="service-file-row">

                    <input 
                        type="file" 
                        name="service_files[]"
                        id="serviceFileInput"
                        accept="image/*,.pdf,.doc,.docx,.ppt,.pptx"
                        multiple
                        required
                    >

                    <input 
                        type="hidden" 
                        name="quantity" 
                        id="serviceQty"
                        value="1"
                    >

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

              
                <div id="selectedFilesBox" style="margin-top:10px; display:none;">
                    <strong style="font-size:14px;">Selected file names:</strong>
                    <ul id="selectedFilesList" style="margin:8px 0 0 18px; padding:0; font-size:13px; line-height:1.5; color:#333;"></ul>
                </div>

                <p style="font-size:15px; margin-top:10px;">
                    Total: <strong id="serviceTotalText">₱<?= number_format((float)$service->prod_price, 2) ?></strong>
                </p>

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

<script>
(function () {
    const servicePrice = <?= json_encode((float)$service->prod_price) ?>;
    const fileInput = document.getElementById('serviceFileInput');
    const qtyInput = document.getElementById('serviceQty');
    const fileCountText = document.getElementById('fileCountText');
    const serviceTotalText = document.getElementById('serviceTotalText');
    const selectedFilesBox = document.getElementById('selectedFilesBox');
    const selectedFilesList = document.getElementById('selectedFilesList');
    const uploadText = document.getElementById('uploadText');

    const selectedFiles = new DataTransfer();

    function refreshNativeInput() {
        fileInput.files = selectedFiles.files;
    }

    function updateServiceTotal() {
        const files = Array.from(selectedFiles.files || []);
        const count = files.length;
        const safeCount = Math.max(1, count);

        qtyInput.value = safeCount;
        fileCountText.textContent = count;
        serviceTotalText.textContent = '₱' + (servicePrice * safeCount).toFixed(2);

        selectedFilesList.innerHTML = '';

        if (count > 0) {
            uploadText.textContent = count + ' file' + (count > 1 ? 's' : '') + ' selected';
            selectedFilesBox.style.display = 'block';

            files.forEach(function(file, index) {
                const li = document.createElement('li');
                li.style.display = 'flex';
                li.style.justifyContent = 'space-between';
                li.style.gap = '10px';
                li.style.alignItems = 'center';

                const name = document.createElement('span');
                name.textContent = file.name;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.textContent = 'Remove';
                removeBtn.style.fontSize = '11px';
                removeBtn.style.cursor = 'pointer';

                removeBtn.addEventListener('click', function () {
                    removeFile(index);
                });

                li.appendChild(name);
                li.appendChild(removeBtn);
                selectedFilesList.appendChild(li);
            });
        } else {
            uploadText.textContent = 'Upload one or more files here';
            selectedFilesBox.style.display = 'none';
        }
    }

    function removeFile(removeIndex) {
        const newFiles = new DataTransfer();

        Array.from(selectedFiles.files).forEach(function(file, index) {
            if (index !== removeIndex) {
                newFiles.items.add(file);
            }
        });

        selectedFiles.items.clear();

        Array.from(newFiles.files).forEach(function(file) {
            selectedFiles.items.add(file);
        });

        refreshNativeInput();
        updateServiceTotal();
    }

    fileInput.addEventListener('change', function () {
        Array.from(fileInput.files || []).forEach(function(file) {
            selectedFiles.items.add(file);
        });

        refreshNativeInput();
        updateServiceTotal();
    });

    updateServiceTotal();
})();
</script>
