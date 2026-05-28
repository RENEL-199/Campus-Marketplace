<?php

require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/ProductRepository.php';
require_once __DIR__ . '/../app/View.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$user_id = current_user_id();

$repo = new ProductRepository();
$view = new View();

$search = $_GET['q'] ?? null;
$category = $_GET['category'] ?? null;

/* GET ALL PRODUCTS */
$products = $repo->getAll();

/* FILTER: IN STOCK ONLY */
$products = array_filter($products, function ($product) {
    return $product->prod_stock > 0;
});

/* SEARCH FILTER */
if ($search) {
    $products = array_filter($products, function ($product) use ($search) {
        return stripos($product->prod_name, $search) !== false ||
               stripos($product->prod_desc, $search) !== false;
    });
}

/* FEATURED ITEMS */
$featured_items = array_filter($products, function ($product) use ($category) {

    if ($category) {
        return $product->category_id == $category &&
              ($product->category_id == 1 ||
               $product->category_id == 2 ||
               $product->category_id == 4);
    }

    return $product->category_id == 1 ||
           $product->category_id == 2 ||
           $product->category_id == 4;
});

/* FEATURED RENTALS */
$featured_rentals = array_filter($products, function ($product) use ($category) {

    if ($category) {
        return $product->category_id == $category &&
               $product->category_id == 5;
    }

    return $product->category_id == 5;
});

/* FEATURED SERVICES */
$featured_services = array_filter($products, function ($product) use ($category) {

    if ($category) {
        return $product->category_id == $category &&
               $product->category_id == 3;
    }

    return $product->category_id == 3;
});

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Campus Market</title>

<link rel="stylesheet" href="../assets/index-style.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

<!-- NAVBAR -->
<nav>

    <h1>Campus Market</h1>

    <div>

        <a href="index.php">
            <i class="fa-solid fa-house"></i> Home
        </a>

        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
        </a>

        <a href="orders.php">
            <i class="fa-solid fa-box"></i> Order History
        </a>

        <a href="seller_dashboard.php">
            <i class="fa-solid fa-dollar-sign"></i> Sell
        </a>

        <a href="account.php">
            <i class="fa-solid fa-user"></i>
        </a>

    </div>

</nav>

<!-- HERO -->
<section class="hero">

    <h2>Buy & Sell Campus Essentials</h2>

    <form method="GET" class="search-box">

        <input
            type="text"
            name="q"
            placeholder="Search items..."
            value="<?= htmlspecialchars($search ?? '') ?>"
        >

        <button type="submit">
            Search
        </button>

    </form>

</section>

<!-- MAIN -->
<div class="container">

    <!-- CATEGORIES -->
    <h2 class="section-title">
        Categories
    </h2>

    <div class="categories">

        <a class="category <?= !$category ? 'active' : '' ?>" href="index.php">
            All
        </a>

        <a class="category <?= $category==1?'active':'' ?>" href="index.php?category=1">
            Electronics
        </a>

        <a class="category <?= $category==2?'active':'' ?>" href="index.php?category=2">
            School Supplies
        </a>

        <a class="category <?= $category==3?'active':'' ?>" href="index.php?category=3">
            Services
        </a>

        <a class="category <?= $category==4?'active':'' ?>" href="index.php?category=4">
            Preloved
        </a>

        <a class="category <?= $category==5?'active':'' ?>" href="index.php?category=5">
            Rentals
        </a>

    </div>

    <?php if (!$category || $category == 1 || $category == 2 || $category == 4): ?>

    <!-- FEATURED ITEMS HEADER -->
    <div style="
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    ">

        <h2 class="section-title">
            Featured Items
        </h2>

        <div style="
        display:flex;
        align-items:center;
        gap:12px;
        ">

            <div style="
            text-align:right;
            font-size:12px;
            ">

                <p>Did you lose something?</p>
                <p>Check it here!</p>

            </div>

            <a href="lost_found.php">
                <button style="
                background:#8b0d04;
                color:white;
                border:none;
                padding:10px 16px;
                border-radius:8px;
                font-weight:600;
                cursor:pointer;
                ">

                    Lost & Found

                </button>
            </a>

        </div>

    </div>

    <!-- FEATURED ITEMS PRODUCTS -->
    <div class="grid">

        <?php if (empty($featured_items)): ?>

            <p>No products found in this category.</p>

        <?php else: ?>

            <?= $view->renderProducts($featured_items); ?>

        <?php endif; ?>

    </div>

    <?php endif; ?>

    <?php if (!$category || $category == 5): ?>

    <!-- FEATURED RENTALS -->
    <h2 class="section-title">
        Featured Rentals
    </h2>

    <div class="grid">

        <?php if (empty($featured_rentals)): ?>

            <p>No rentals found. Add rental products with category_id = 5.</p>

        <?php else: ?>

            <?php foreach ($featured_rentals as $rental): ?>

                <?php
                    $rental_desc = $rental->prod_desc ?? '';

                    $rental_short_desc = strlen($rental_desc) > 18
                        ? substr($rental_desc, 0, 18) . "......"
                        : $rental_desc . "......";

                    $rental_price = number_format((float)$rental->prod_price, 0);
                ?>

                <div class="card">

                    <img src="<?= htmlspecialchars($rental->prod_image) ?>" alt="rental">

                    <div class="card-content">

                        <div class="card-top-row">

                            <h3>
                                <?= htmlspecialchars($rental->prod_name) ?>
                            </h3>

                            <span class="category-tag">
                                <?= htmlspecialchars($rental->category_name) ?>
                            </span>

                        </div>

                        <p class="card-description">
                            <?= htmlspecialchars($rental_short_desc) ?>
                            <a href="#" onclick="openRental('<?= htmlspecialchars($rental->prod_id) ?>'); return false;">
                                read more
                            </a>
                        </p>

                        <div class="card-price-stock-row">
                            <div class="price">
                                ₱<?= $rental_price ?>
                            </div>

                            <div class="stock">
                                Stock: <?= htmlspecialchars($rental->prod_stock) ?>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rental-view-btn"
                            data-rental-id="<?= htmlspecialchars($rental->prod_id) ?>"
                        >
                            View Item
                        </button>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <?php endif; ?>

    <?php if (!$category || $category == 3): ?>

    <!-- FEATURED SERVICES -->
    <h2 class="section-title">
        Featured Services
    </h2>

    <div class="grid">

        <?php if (empty($featured_services)): ?>

            <p>No services found. Add service products with category_id = 3.</p>

        <?php else: ?>

            <?php foreach ($featured_services as $service): ?>

                <?php
                    $service_desc = $service->prod_desc ?? '';

                    $service_short_desc = strlen($service_desc) > 18
                        ? substr($service_desc, 0, 18) . "......"
                        : $service_desc . "......";

                    $service_price = number_format((float)$service->prod_price, 0);
                ?>

                <div class="card">

                    <img src="<?= htmlspecialchars($service->prod_image) ?>" alt="service">

                    <div class="card-content">

                        <div class="card-top-row">

                            <h3>
                                <?= htmlspecialchars($service->prod_name) ?>
                            </h3>

                            <span class="category-tag">
                                <?= htmlspecialchars($service->category_name) ?>
                            </span>

                        </div>

                        <p class="card-description">
                            <?= htmlspecialchars($service_short_desc) ?>
                            <a href="#" onclick="openService('<?= htmlspecialchars($service->prod_id) ?>'); return false;">
                                read more
                            </a>
                        </p>

                        <div class="card-price-stock-row">
                            <div class="price">
                                ₱<?= $service_price ?>
                            </div>

                            <div class="stock">
                                Stock: <?= htmlspecialchars($service->prod_stock) ?>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="service-view-btn"
                            data-service-id="<?= htmlspecialchars($service->prod_id) ?>"
                        >
                            View Item
                        </button>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <?php endif; ?>

    <!-- SELL SECTION -->
    <div style="
    margin-top:50px;
    background:#ead4d1;
    padding:20px;
    border-radius:10px;

    display:flex;
    justify-content:space-between;
    align-items:center;
    ">

        <div style="
        display:flex;
        align-items:center;
        gap:15px;
        ">

            <div style="
            width:45px;
            height:45px;

            background:#d6b0ab;

            border-radius:50%;

            display:flex;
            justify-content:center;
            align-items:center;

            color:#8b0d04;
            ">

                <i class="fa-solid fa-store"></i>

            </div>

            <div>

                <h3 style="font-size:14px;">
                    Got something to sell or offer?
                </h3>

                <p style="
                font-size:12px;
                color:#555;
                ">

                    List your items, rentals, or services and reach more students today.

                </p>

            </div>

        </div>

        <a href="seller_dashboard.php">

            <button style="
            background:#8b0d04;
            color:white;

            border:none;

            padding:12px 18px;

            border-radius:8px;

            font-weight:600;
            cursor:pointer;
            ">

                Sell Product
                <i class="fa-solid fa-arrow-right"></i>

            </button>

        </a>

    </div>

    <!-- MODAL -->
    <div id="productModal" class="modal">

        <div class="modal-content">

            <span class="close-btn" onclick="closeModal()">
                &times;
            </span>

            <div id="modal-body"></div>

        </div>

    </div>

</div>

<!-- FOOTER -->
<footer>
    © 2026 Campus Market — Built for students
</footer>

<!-- SCRIPT -->
<script>

function openProduct(id) {

    fetch("product_modal.php?id=" + encodeURIComponent(id))

        .then(res => res.text())

        .then(data => {

            document.getElementById("modal-body").innerHTML = data;

            document.getElementById("productModal").style.display = "block";

        });

}

function openRental(id) {

    fetch("rental_modal.php?id=" + encodeURIComponent(id))

        .then(res => res.text())

        .then(data => {

            document.getElementById("modal-body").innerHTML = data;

            document.getElementById("productModal").style.display = "block";

        });

}

function openService(id) {

    fetch("service_modal.php?id=" + encodeURIComponent(id))

        .then(res => res.text())

        .then(data => {

            document.getElementById("modal-body").innerHTML = data;

            document.getElementById("productModal").style.display = "block";

        });

}

function closeModal() {

    document.getElementById("productModal").style.display = "none";

    document.getElementById("modal-body").innerHTML = "";

}

window.onclick = function(event) {

    const modal = document.getElementById("productModal");

    if (event.target == modal) {

        closeModal();

    }

}

document.addEventListener("click", function(event) {

    const rentalButton = event.target.closest(".rental-view-btn");

    if (rentalButton) {

        event.preventDefault();

        const rentalId = rentalButton.getAttribute("data-rental-id");

        openRental(rentalId);

    }

    const rentBtn = event.target.closest("#rentThisItemBtn");

    if (rentBtn) {

        const viewer = document.getElementById("rentalViewer");
        const form = document.getElementById("borrowFormBox");
        const layout = document.querySelector(".rental-modal-layout");

        if (viewer && form && layout) {
            viewer.style.display = "none";
            form.style.display = "block";
            layout.style.display = "block";
        }

    }

    const minusBtn = event.target.closest("#borrowMinusBtn");

    if (minusBtn) {

        const qty = document.getElementById("borrowQty");
        let value = parseInt(qty.value);

        if (value > 1) {
            qty.value = value - 1;
        }

    }

    const plusBtn = event.target.closest("#borrowPlusBtn");

    if (plusBtn) {

        const qty = document.getElementById("borrowQty");
        const maxStock = parseInt(plusBtn.getAttribute("data-max-stock"));
        let value = parseInt(qty.value);

        if (value < maxStock) {
            qty.value = value + 1;
        }

    }

    const serviceButton = event.target.closest(".service-view-btn");

    if (serviceButton) {

        event.preventDefault();

        const serviceId = serviceButton.getAttribute("data-service-id");

        openService(serviceId);

    }

    const availServiceBtn = event.target.closest("#availServiceBtn");

    if (availServiceBtn) {

        const viewer = document.getElementById("serviceViewer");
        const form = document.getElementById("serviceFormBox");
        const layout = document.querySelector(".service-modal-layout");

        if (viewer && form && layout) {
            viewer.style.display = "none";
            form.style.display = "block";
            layout.style.display = "block";
        }

    }

});

document.addEventListener("click", function(event) {

    const productMinusBtn = event.target.closest(".product-minus-btn");
    const productPlusBtn = event.target.closest(".product-plus-btn");

    if (productMinusBtn) {

        const qtyInput = document.getElementById("modalQuantity");

        if (qtyInput) {
            let value = parseInt(qtyInput.value) || 1;

            if (value > 1) {
                qtyInput.value = value - 1;
            }
        }

    }

    if (productPlusBtn) {

        const qtyInput = document.getElementById("modalQuantity");
        const maxStock = parseInt(productPlusBtn.getAttribute("data-max-stock"));

        if (qtyInput) {
            let value = parseInt(qtyInput.value) || 1;

            if (value < maxStock) {
                qtyInput.value = value + 1;
            } else {
                qtyInput.value = maxStock;
            }
        }

    }

});

document.addEventListener("input", function(event) {

    if (event.target.classList.contains("product-qty-input")) {

        const qtyInput = event.target;
        const maxStock = parseInt(qtyInput.getAttribute("max"));
        let value = parseInt(qtyInput.value) || 1;

        if (value < 1) {
            qtyInput.value = 1;
        }

        if (value > maxStock) {
            qtyInput.value = maxStock;
        }

    }

});

document.addEventListener("click", function(event) {

    const serviceMinusBtn = event.target.closest(".service-minus-btn");
    const servicePlusBtn = event.target.closest(".service-plus-btn");

    if (serviceMinusBtn) {

        const qtyInput = document.getElementById("serviceQty");

        if (qtyInput) {
            let value = parseInt(qtyInput.value) || 1;

            if (value > 1) {
                qtyInput.value = value - 1;
            }
        }

    }

    if (servicePlusBtn) {

        const qtyInput = document.getElementById("serviceQty");

        if (qtyInput) {
            let value = parseInt(qtyInput.value) || 1;

            qtyInput.value = value + 1;
        }

    }

});

document.addEventListener("input", function(event) {

    if (event.target.classList.contains("service-qty-input")) {

        const qtyInput = event.target;
        let value = parseInt(qtyInput.value) || 1;

        if (value < 1) {
            qtyInput.value = 1;
        }

    }

});

document.addEventListener("change", function(event) {

    const fileInput = event.target.closest("#serviceFileInput");

    if (fileInput && fileInput.files.length > 0) {

        const file = fileInput.files[0];

        const previewImage = document.getElementById("previewImage");
        const uploadText = document.getElementById("uploadText");

        if (!previewImage || !uploadText) {
            return;
        }

        const reader = new FileReader();

        reader.onload = function(e) {

            previewImage.src = e.target.result;
            previewImage.style.display = "block";

            uploadText.style.display = "none";
        };

        reader.readAsDataURL(file);
    }

});

</script>

</body>
</html>
