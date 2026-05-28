<?php

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/ProductRepository.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

$repo = new ProductRepository();

/* =========================
   DELETE PRODUCT
========================= */
<<<<<<< HEAD
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete") {

    if (empty($_POST["selected_product_id"])) {
        echo "<script>alert('Select product first to Delete it.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $delete_id = (int) $_POST["selected_product_id"];

    $stmt = $pdo->prepare("
        DELETE FROM products
        WHERE prod_id = ? AND user_id = ?
    ");

    $stmt->execute([$delete_id, $user_id]);
=======
if (isset($_POST['delete_id'])) {

    $stmt = $pdo->prepare("
        DELETE FROM products 
        WHERE prod_id = ? AND user_id = ?
    ");

    $stmt->execute([$_POST['delete_id'], $user_id]);
>>>>>>> origin/polin

    header("Location: seller_dashboard.php");
    exit;
}

/* =========================
<<<<<<< HEAD
   UPDATE PRODUCT
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update") {

    if (empty($_POST["selected_product_id"])) {
        echo "<script>alert('Select product first to update it.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $update_id = (int) $_POST["selected_product_id"];
=======
   ADD PRODUCT
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["name"])) {

    $name = $_POST["name"];
    $desc = $_POST["description"];
    $price = $_POST["price"];
    $category_id = $_POST["category"];
    $stock = (int) $_POST["stock"];
>>>>>>> origin/polin

    $name = trim($_POST["name"]);
    $desc = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $category_id = trim($_POST["category"]);
    $stock = trim($_POST["stock"]);
    $location = trim($_POST["location"] ?? "");
    $rate_type = trim($_POST["rate_type"] ?? "");

    if ($name === "" || $desc === "" || $price === "" || $category_id === "" || $stock === "") {

        echo "<script>alert('Please fill out Product Name, Description, Price, Quantity, and Category.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $stock = (int) $stock;

    /* KEEP OLD IMAGE */
    $stmt = $pdo->prepare("
        SELECT prod_image
        FROM products
        WHERE prod_id = ? AND user_id = ?
    ");

    $stmt->execute([$update_id, $user_id]);

    $oldProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    $image = $oldProduct['prod_image'];

    /* NEW IMAGE IF UPLOADED */
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === 0) {

        $uploadDir = __DIR__ . "/uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);

        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {

            $image = "uploads/" . $fileName;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE products
        SET
            prod_name = ?,
            prod_desc = ?,
            prod_price = ?,
            prod_stock = ?,
            category_id = ?,
            prod_image = ?,
            prod_location = ?,
            prod_rate_type = ?
        WHERE prod_id = ? AND user_id = ?
    ");

    $stmt->execute([
        $name,
        $desc,
        $price,
        $stock,
        $category_id,
        $image,
        $location,
        $rate_type,
        $update_id,
        $user_id
    ]);

    header("Location: seller_dashboard.php");
    exit;
}

/* =========================
   ADD PRODUCT
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create") {
    $name = trim($_POST["name"]);
$desc = trim($_POST["description"]);
$price = trim($_POST["price"]);
$category_id = trim($_POST["category"]);
$stock = trim($_POST["stock"]);
$location = trim($_POST["location"] ?? "");
$rate_type = trim($_POST["rate_type"] ?? "");

if ($name === "" || $desc === "" || $price === "" || $category_id === "" || $stock === "") {
    echo "<script>alert('Please fill out Product Name, Description, Price, Quantity, and Category.'); window.location.href='seller_dashboard.php';</script>";
    exit;
}

$stock = (int) $stock;
    $image = "uploads/default.png";

    /* IMAGE UPLOAD */
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === 0) {

        $uploadDir = __DIR__ . "/uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $image = "uploads/" . $fileName;
        }
    }

    /* CREATE PRODUCT OBJECT */
    $product = new Product(
        0,
        $user_id,
        $name,
        $desc,
        $price,
        $image,
        $stock,
        null,
        null,
        $category_id
    );

    $repo->add($product);


    $last_id = $pdo->lastInsertId();

$stmt = $pdo->prepare("
    UPDATE products
    SET prod_location = ?, prod_rate_type = ?
    WHERE prod_id = ? AND user_id = ?
");

$stmt->execute([$location, $rate_type, $last_id, $user_id]);

    header("Location: seller_dashboard.php");
    exit;
}

/* =========================
   GET PRODUCTS
========================= */
<<<<<<< HEAD
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ?");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll(PDO::FETCH_OBJ);
=======
$products = $repo->getByUser($user_id);
>>>>>>> origin/polin

/* =========================
   STATS
========================= */
$total = count($products);
$active = 0;
$out = 0;

foreach ($products as $p) {
    if ($p->prod_stock > 0) $active++;
    else $out++;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seller Dashboard</title>

<link rel="stylesheet" href="../assets/index-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* PAGE */
<<<<<<< HEAD
body {
    margin: 0;
    background: #f3f5f2;
    font-family: Arial, sans-serif;
}

/* PAGE */
.dashboard {
    max-width: 1000px;
    margin: 12px auto 30px;
    padding: 0 20px;
=======
.dashboard {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 20px;
}

/* CARD */
.card {
    background: white;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
>>>>>>> origin/polin
}

/* STATS */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 8px;
   
}

.stat {
    background: white;
    padding: 7px 10px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 3px 4px rgba(0,0,0,0.22);
     margin-top: 10px;
}

.stat h2 {
<<<<<<< HEAD
    margin: 0;
    color: #000;
    font-size: 24px;
    font-weight: 800;
}

.stat p {
    display: inline;
=======
    color: #3A7D5D;
}

/* PRODUCT LIST */
.product {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #eee;
>>>>>>> origin/polin
}

.stat h2 {
    margin: 0;
    color: #000;
    font-size: 24px;
    font-weight: 800;
    display: inline;
}

/* MAIN LAYOUT */
.grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr);
    gap: 24px;
    align-items: start;
    margin-top: 20px; /* increase if you want lower */
}

/* CARD */
.card {
    background: white;
    padding: 18px 18px 18px;
    border-radius: 14px; /* instead of 0 0 16px 16px */
    box-shadow: 0 2px 4px rgba(0,0,0,0.28);
    min-height: 340px;
    height: fit-content;
}

<<<<<<< HEAD
.card h2 {
    margin: 0 0 8px;
    font-size: 21px;
    font-weight: 800;
}

/* IMAGE UPLOAD */
.image-upload {
    width: 100%;
    height: 185px;
    border: 1px dashed #b56b62;
    border-radius: 24px;
=======
button {
    width: 100%;
    padding: 12px;
    background: #3A7D5D;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}

/* NAV */
nav {
    background: #3A7D5D;
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
}

nav a {
    color: white;
    margin-left: 15px;
    text-decoration: none;
}

.image-upload {
    width: 100%;
    height: 220px;
    border: 2px dashed #A7D7C5;
    border-radius: 12px;
>>>>>>> origin/polin
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    overflow: hidden;
    background: #f7f9f7;
    margin-bottom: 14px;
}

.image-upload input {
    display: none;
}

.image-upload img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: none;
}

.image-upload span {
<<<<<<< HEAD
    color: transparent;
}

/* FORM */
input,
textarea,
select {
    width: 100%;
    padding: 7px;
    margin: 0 0 5px;
    border-radius: 4px;
    border: 1px solid #aaa;
    font-size: 15px;
    box-sizing: border-box;
}

textarea {
    height: 64px;
    resize: none;
}

select {
    height: 34px;
    font-size: 12px;
    padding-right: 25px;
}

button {
    background: #991000;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
}

/* ADD PRODUCT BUTTON */



.small-label {
    display: block;
    font-size: 14px;
    margin: 0 0 2px;
    color: #333;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    align-items: end;
}

.rate-buttons {
    display: flex;
    gap: 5px;
}

.rate-buttons button {
    background: white;
    color: #991000;
    border: 1px solid #aaa;
    border-radius: 4px;
    font-size: 14px;
    padding: 4px;
}

.rate-option.selected-rate {
    background: #991000;
    color: white;
    border-color: #991000;
}



.bottom-row {
    align-items: center;
}

.quantity-box {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 15px;
}


.quantity-box input {
    width: 38px;
    height: 22px;
    margin: 0;
    padding: 2px;
    text-align: center;
    border: 1px solid #aaa;
    border-radius: 4px;
    background: white;
    font-size: 14px;
}

.quantity-box button {
    display: flex;
    align-items: center;
    width: 18px;
    height: 18px;
    background: white;
    color: black;
    padding: 0;
    font-weight: bold;
    justify-content: center;
    line-height: 1;

    position: relative;
    top: -4px;
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    margin-top: 8px;
}

.action-buttons button {
    padding: 9px;
    background: #991000;
    color: white;
    border-radius: 8px;
    font-size: 18px;
}



/* PRODUCT LIST */
.product {
    display: grid;
    grid-template-columns: 38px 1fr 32px;
    gap: 8px;
    align-items: center;
    padding: 4px;
    margin-bottom: 10px;
    border: 1px solid #777;
    border-radius: 4px;
    background: white;
}

.product img {
    width: 34px;
    height: 34px;
    object-fit: cover;
    border-radius: 6px;
    background: #d9d9d9;
}

.product strong {
    font-size: 18px;
}

.product div {
    font-size: 16px;
    line-height: 1.5;
}


/* RESPONSIVE */
@media (max-width: 768px) {
    .stats,
    .grid {
        grid-template-columns: 1fr;
    }

    .dashboard {
        padding: 0 12px;
    }
}
/* NAV */
nav {
    background: #3A7D5D;
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
}

nav a {
    color: white;
    margin-left: 15px;
    text-decoration: none;
}

/* Move Per Hour / Per Day / Per Piece boxes upward */
.rate-box {
    transform: translateY(-6px); /* adjust -4px, -8px, etc. */
}

.rate-box .small-label {
    position: relative;
    top: 8px;
}

.top-form-row {
    display: grid;
    grid-template-columns: 230px 1fr;
    gap: 14px;
    align-items: start;
    margin-bottom: 10px;
}

.name-desc-box {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}

.image-upload {
    height: 185px;
    margin-bottom: 0;
}

.name-desc-box textarea {
    height: 110px;
}

.product {
    cursor: pointer;
}

.product.selected {
    border: 2px solid #991000;
    background: #fff3f1;
}
=======
    position: absolute;
    color: #888;
}

>>>>>>> origin/polin
</style>
</head>

<body>

<nav>
    <h1>Seller Dashboard</h1>
    <div>
        <a href="index.php"><i class="fa-solid fa-house"></i></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
        <a href="orders.php"><i class="fa-solid fa-box"></i></a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i></a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
    </div>
</nav>

<div class="dashboard">

<!-- STATS -->
<div class="stats">

    <div class="stat">
        <h2><?= $total ?></h2>
        <p>Total</p>
    </div>

    <div class="stat">
        <h2><?= $active ?></h2>
        <p>Active</p>
    </div>

    <div class="stat">
        <h2><?= $out ?></h2>
        <p>Sold Out</p>
    </div>

</div>

<div class="grid">

<!-- ADD PRODUCT -->
<div class="card">

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="selected_product_id" id="selected_product_id">
   <div class="top-form-row">
    <label class="image-upload">
        <input type="file" name="image" id="imageInput" required>
        <img id="preview">
        <span id="uploadText">Click to upload image</span>
    </label>

<<<<<<< HEAD
    <div class="name-desc-box">
        <input type="text" name="name" placeholder="Product Name" required>
        <textarea name="description" placeholder="Description" required></textarea>
    </div>
</div>
=======
    <label class="image-upload">
        <input type="file" name="image" id="imageInput" required>
        <img id="preview">
        <span id="uploadText">Click to upload image</span>
    </label>
>>>>>>> origin/polin



<<<<<<< HEAD
<label class="small-label">If Applicable:</label>
<input type="text" name="location" placeholder="Location">
=======
    <input type="number" name="price" placeholder="Price" required>
>>>>>>> origin/polin

<div class="form-row">
    <input type="number" name="price" placeholder="Price" required>

    <div class="rate-box">
        <label class="small-label">If Applicable:</label>
        <div class="rate-buttons">
<button type="button" class="rate-option" data-rate="Per Hour">Per Hour</button>
<button type="button" class="rate-option" data-rate="Per Day">Per Day</button>
<button type="button" class="rate-option" data-rate="Per Piece">Per Piece</button>

<input type="hidden" name="rate_type" id="rate_type">

        </div>
    </div>
</div>

<div class="form-row bottom-row">
    <div class="quantity-box">
    <span>Quantity:</span>

    <button type="button" id="minusQty">−</button>

    <input
        type="text"
        name="stock"
        id="stockInput"
        value="1"
        required
        readonly
    >

    <button type="button" id="plusQty">+</button>
</div>

    <select name="category">
<<<<<<< HEAD
=======
        <option value="1">Electronics</option>
        <option value="2">School Supplies</option>
        <option value="3">Services</option>
        <option value="4">Preloved</option>
        <option value="5">Others</option>
    </select>
>>>>>>> origin/polin

        <option value="1">Electronics</option>
        <option value="2">School Supplies</option>
        <option value="3">Services</option>
        <option value="4">Preloved</option>
        <option value="5">Rental</option>
        <option value="6">Others</option>

    </select>
</div>

<div class="action-buttons">
    <button type="submit" name="action" value="create">Create</button>
        <button type="submit"
                name="action"
                value="update"
                id="updateBtn"
                formnovalidate>
            Update
        </button>
        <button type="submit"
            name="action"
            value="delete"
            id="deleteBtn"
            formnovalidate>
        Delete
    </button>
    <button type="button" id="clearBtn">Clear</button>
</div>

</form>

</div>

<!-- PRODUCT LIST -->
<div class="card">

<h2>Products</h2>

<?php foreach ($products as $p): ?>

<div class="product"
    data-id="<?= $p->prod_id ?>"
    data-name="<?= htmlspecialchars($p->prod_name) ?>"
    data-description="<?= htmlspecialchars($p->prod_desc ?? '') ?>"
    data-price="<?= $p->prod_price ?>"
    data-stock="<?= $p->prod_stock ?>"
    data-category="<?= $p->category_id ?? '' ?>"
    data-image="<?= htmlspecialchars($p->prod_image) ?>"
    data-location="<?= htmlspecialchars($p->prod_location ?? '') ?>"
    data-rate="<?= htmlspecialchars($p->prod_rate_type ?? '') ?>"
>

    <img src="<?= htmlspecialchars($p->prod_image) ?>">

    <div>
        <strong><?= htmlspecialchars($p->prod_name) ?></strong><br>
        ₱<?= $p->prod_price ?> | Stock: <?= $p->prod_stock ?>
    </div>

<<<<<<< HEAD
=======
    <form method="POST" onsubmit="return confirm('Delete this product?')">
        <input type="hidden" name="delete_id" value="<?= $p->prod_id ?>">
        <button class="delete-btn">X</button>
    </form>

>>>>>>> origin/polin
</div>

<?php endforeach; ?>

</div>

</div>

</div>

<script>
const imageInput = document.getElementById("imageInput");
const preview = document.getElementById("preview");
const uploadText = document.getElementById("uploadText");
const clearBtn = document.getElementById("clearBtn");

imageInput.addEventListener("change", function(event) {
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = "block";
            uploadText.style.display = "none";
        };

        reader.readAsDataURL(file);
    }
});

clearBtn.addEventListener("click", function() {
    document.querySelector("form").reset();

    preview.src = "";
    preview.style.display = "none";
    uploadText.style.display = "block";
    rateOptions.forEach(btn => btn.classList.remove("selected-rate"));
    rateTypeInput.value = "";
});
</script>


<script>
const products = document.querySelectorAll(".product");

products.forEach(product => {

    product.addEventListener("click", function() {

        products.forEach(p => p.classList.remove("selected"));
        this.classList.add("selected");

        document.getElementById("selected_product_id").value = this.dataset.id;

        document.querySelector('input[name="name"]').value = this.dataset.name;

        document.querySelector('textarea[name="description"]').value =
            this.dataset.description;

        document.querySelector('input[name="price"]').value =
            this.dataset.price;

        document.querySelector('input[name="stock"]').value =
            this.dataset.stock;

        document.querySelector('select[name="category"]').value =
            this.dataset.category;

        document.querySelector('input[name="location"]').value =
            this.dataset.location;

        rateOptions.forEach(btn => btn.classList.remove("selected-rate"));

rateTypeInput.value = this.dataset.rate || "";

rateOptions.forEach(btn => {
    if (btn.dataset.rate === this.dataset.rate) {
        btn.classList.add("selected-rate");
    }
});



            preview.src = this.dataset.image;
            preview.style.display = "block";
            uploadText.style.display = "none";
    });

});


document.getElementById("updateBtn").addEventListener("click", function(event) {

    const selectedId = document.getElementById("selected_product_id").value;

    if (selectedId === "") {

        event.preventDefault();

        alert("Select product first to update it.");

        return;
    }
});



document.getElementById("deleteBtn").addEventListener("click", function(event) {
    const selectedId = document.getElementById("selected_product_id").value;

    if (selectedId === "") {
        event.preventDefault();
        alert("Select product first to Delete it.");
        return;
    }

    const confirmDelete = confirm("This product will be gone in the system and can't be retrieve anymore. Continue?");

    if (!confirmDelete) {
        event.preventDefault();
    }
});




const rateOptions = document.querySelectorAll(".rate-option");
const rateTypeInput = document.getElementById("rate_type");

rateOptions.forEach(button => {
    button.addEventListener("click", function() {
        rateOptions.forEach(btn => btn.classList.remove("selected-rate"));

        this.classList.add("selected-rate");

        rateTypeInput.value = this.dataset.rate;
    });
});


</script>


<script>

const minusQty = document.getElementById("minusQty");
const plusQty = document.getElementById("plusQty");
const stockInput = document.getElementById("stockInput");

/* PLUS */
plusQty.addEventListener("click", function () {

    let current = parseInt(stockInput.value);

    stockInput.value = current + 1;
});

/* MINUS */
minusQty.addEventListener("click", function () {

    let current = parseInt(stockInput.value);

    if (current > 1) {
        stockInput.value = current - 1;
    }
});

</script>

</body>
</html>
