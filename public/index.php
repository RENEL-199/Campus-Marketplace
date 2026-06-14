<?php

require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/ProductRepository.php';
require_once __DIR__ . '/../app/View.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$repo = new ProductRepository();
$view = new View();

$search = $_GET['q'] ?? null;
$category = $_GET['category'] ?? null;

/* =========================
   GET ALL PRODUCTS
========================= */
$products = $repo->getAll();

/* =========================
   SEARCH FILTER
========================= */
if (!empty($search)) {

    $products = array_filter($products, function ($product) use ($search) {

        return stripos($product->prod_name, $search) !== false
            || stripos($product->prod_desc, $search) !== false;
    });
}

/* =========================
   CATEGORY FILTER FUNCTION
========================= */
function filterByCategory($products, $allowedCategories, $selectedCategory = null)
{

    return array_filter($products, function ($product) use ($allowedCategories, $selectedCategory) {

        if ($selectedCategory) {
            return in_array($product->category_id, $allowedCategories)
                && $product->category_id == $selectedCategory;
        }

        return in_array($product->category_id, $allowedCategories);
    });
}

/* =========================
    FEATURED ITEMS (1,2,4,7)
========================= */
$featured_items = filterByCategory($products, [1, 2, 4, 7], $category);

/* =========================
   RENTALS (5)
========================= */
$featured_rentals = filterByCategory($products, [5], $category);

/* =========================
   SERVICES (3)
========================= */
$featured_services = filterByCategory($products, [3], $category);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Campus Market</title>

    <link rel="stylesheet" href="../assets/index-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    
.logout-btn{
    background:#810C01;
    color:white;
    padding:8px 15px;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
    transition:.3s;
}

.logout-btn:hover{
    background:#5f0801;
}

     nav {
            height: 58px;
            background: #810C01;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 26px;
                   font-family: Arial, sans-serif;
        }

        nav h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        nav div {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 12px;
        }

        nav i {
            margin-right: 4px;
            font-size: 13px;
        }

        /* Lost & Found integration */
        .categories .category.active {
            background: #8b0d04 !important;
            color: #fff !important;
            border-color: #8b0d04 !important;
        }

        .lost-found-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .lost-found-card .lost-found-icon-box {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff7f6, #f1d6d3);
            border-radius: 14px 14px 0 0;
            overflow: hidden;
        }

        .lost-found-card .lost-found-icon-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

    </style>
</head>

<body>

    <!-- NAV -->
    <nav>
        <h1>IskoHub</h1>
        <div>
            <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
            <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
            <a href="orders.php"><i class="fa-solid fa-box"></i> Order History</a>
            <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> Sell</a>            
            <a href="lost_found_inbox.php"><i class="fa-solid fa-box-open">  Inbox</i></a>
            <a href="account.php"><i class="fa-solid fa-user"></i></a>
            <a href="logout.php" class="logout-btn">
Logout
</a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <h2>Buy & Sell Campus Essentials</h2>
        
        <form method="GET" class="search-box">
            <input type="text" name="q" placeholder="Search items..."
                value="<?= htmlspecialchars($search ?? '') ?>">
            <button type="submit">Search</button>
        </form>
    </section>

    <div class="container">
        

        <!-- CATEGORIES -->
        <h2 class="section-title">Categories</h2>

        <div class="categories">
            <a class="category <?= !$category ? 'active' : '' ?>" href="index.php">All</a>
            <a class="category <?= $category == 1 ? 'active' : '' ?>" href="index.php?category=1">Electronics</a>
            <a class="category <?= $category == 2 ? 'active' : '' ?>" href="index.php?category=2">School Supplies</a>
            <a class="category <?= $category == 3 ? 'active' : '' ?>" href="index.php?category=3">Services</a>
            <a class="category <?= $category == 4 ? 'active' : '' ?>" href="index.php?category=4">Preloved</a>
            <a class="category <?= $category == 5 ? 'active' : '' ?>" href="index.php?category=5">Rentals</a>

            <a class="category <?= $category == 7 ? 'active' : '' ?>" href="index.php?category=7">Others</a>
         
        </div>

        <!-- FEATURED ITEMS -->
        <?php if (!$category || in_array($category, [1, 2, 4, 7])): ?>

            <div class="section-title">Featured Items</div>

            <div class="grid">

                <?php if (empty($featured_items)): ?>
                    <p>No products found.</p>
                <?php else: ?>
                    <?= $view->renderProducts($featured_items); ?>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- RENTALS -->
        <?php if (!$category || $category == 5): ?>

            <div class="section-title">Featured Rentals</div>

            <div class="grid">

                <?php if (empty($featured_rentals)): ?>
                    <p>No rentals found.</p>
                <?php else: ?>
                    <?= $view->renderProducts($featured_rentals); ?>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- SERVICES -->
        <?php if (!$category || $category == 3): ?>

            <div class="section-title">Featured Services</div>

            <div class="grid">

                <a href="lost_found.php" class="lost-found-card-link">
                    <div class="card lost-found-card">
                        <div class="lost-found-icon-box">
                            <img src="uploads/lost_found-default.png" alt="Lost and Found">
                        </div>

                        <div class="card-content">
                            <div class="card-top-row">
                                <h3>Lost & Found</h3>
                                <span class="category-tag">Services</span>
                            </div>

                            <p class="card-description">
                                Report lost items or post found items on campus.
                            </p>

                            <div class="card-price-stock-row">
                                <div class="price">Free</div>
                                <div class="stock">Campus</div>
                            </div>

                            <button type="button">Open Lost & Found</button>
                        </div>
                    </div>
                </a>

                <?php if (empty($featured_services)): ?>
                    <p>No services found.</p>
                <?php else: ?>
                    <?= $view->renderProducts($featured_services); ?>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- SELL BANNER -->
        <div style="margin-top:50px;background:#ead4d1;padding:20px;border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h3>Got something to sell or offer?</h3>
                <p>List your items, rentals, or services.</p>
            </div>

            <a href="seller_dashboard.php">
                <button style="background:#8b0d04;color:white;border:none;padding:12px 18px;border-radius:8px;">
                    Sell Product
                </button>
            </a>
        </div>

    </div>

    <!-- MODAL -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        function openModal(url) {

            fetch(url)
                .then(res => res.text())
                .then(data => {

                    document.getElementById("modal-body").innerHTML = data;
                    document.getElementById("productModal").style.display = "block";

                })
                .catch(err => {
                    console.error("Modal load failed:", err);
                });

        }

        /* PRODUCT MODAL */
        function openProduct(id) {
            openModal("product_modal.php?id=" + id);
        }

        /* RENTAL MODAL */
        function openRental(id) {
            openModal("rental_modal.php?id=" + id);
        }

        /* SERVICE MODAL */
        function openService(id) {
            openModal("service_modal.php?id=" + id);
        }

        /* CLOSE MODAL */
        function closeModal() {
            document.getElementById("productModal").style.display = "none";
            document.getElementById("modal-body").innerHTML = "";
        }

        /* CLOSE ON OUTSIDE CLICK */
        window.onclick = function(event) {
            const modal = document.getElementById("productModal");
            if (event.target === modal) {
                closeModal();
            }
        };

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
                let value = parseInt(qty.value) || 1;

                if (value > 1) {
                    qty.value = value - 1;
                }
            }

            const plusBtn = event.target.closest("#borrowPlusBtn");

            if (plusBtn) {
                const qty = document.getElementById("borrowQty");
                const maxStock = parseInt(plusBtn.getAttribute("data-max-stock"));
                let value = parseInt(qty.value) || 1;

                if (value < maxStock) {
                    qty.value = value + 1;
                } else {
                    qty.value = maxStock;
                }
            }

        });

        document.addEventListener("click", function(event) {

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