<?php
require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/ProductRepository.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$user_id = current_user_id();
$repo = new ProductRepository();

/* DELETE */
if (isset($_POST["delete_id"])) {
    $repo->delete($_POST["delete_id"], $user_id);
    header("Location: sell.php");
    exit;
}

/* ADD PRODUCT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["name"])) {

    $name = $_POST["name"];
    $desc = $_POST["description"];
    $price = $_POST["price"];
    $category = $_POST["category"];
    $stock = (int) $_POST["stock"];

    $image = "uploads/default.png";

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

    $product = new Product(
        0,
        $user_id,
        $name,
        $desc,
        $price,
        $image,
        $category,
        $stock
    );

    $repo->add($product);

    header("Location: sell.php");
    exit;
}

/* ONLY SHOW USER PRODUCTS */
$products = $repo->getByUser($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sell Item</title>
    <link rel="stylesheet" href="../assets/sell-style.css">
</head>

<body>

<nav>
    <h1>Campus Market</h1>
    <div>
        <a href="index.php">Home</a>
        <a href="cart.php">Cart</a>
        <a href="orders.php">Orders</a>
        <a href="sell.php">Sell</a>
        <a href="account.php">Account</a>
    </div>
</nav>

<div class="container">

<!-- SELL FORM -->
<div class="card">
    <h2>Sell an Item</h2>

    <form method="POST" enctype="multipart/form-data">


        <label class="image-upload">
            <input type="file" name="image" accept="image/*" id="imageInput" required>
            <img id="preview">
            <span id="uploadText">Click to upload image</span>
        </label>

    
        <input type="text" name="name" placeholder="Product Name" required>

        <textarea name="description" placeholder="Description" required></textarea>

        <input type="text" name="price" placeholder="Price (e.g. ₱500)" required>

        <input type="number" name="stock" placeholder="Stock Quantity" min="1" required>

        <!-- CATEGORY -->
        <select name="category" required>
            <option value="">Select Category</option>
            <option>Electronics</option>
            <option>School Supplies</option>
            <option>Services</option>
            <option>Preloved</option>
            <option>Others</option>
        </select>

        <div class="form-actions">
            <button type="button" id="clearBtn">Clear</button>
            <button type="submit">Add Product</button>
        </div>

    </form>
</div>

<!-- PRODUCTS LIST -->
<div class="card">
    <h2>Marketplace</h2>
<div class="products">
    <?php foreach ($products as $product): ?>

        <div class="product-card">

            <div class="container-2">
                <h3><?= $product->name ?></h3>

                <!-- DELETE ONLY FOR OWNER -->
                <form method="POST" onsubmit="return confirm('Delete?')">
                    <input type="hidden" name="delete_id" value="<?= $product->id ?>">
                    <button class="delete-btn">Delete</button>
                </form>

            </div>

        </div>

    <?php endforeach; ?>
</div>
</div>

</div>

<!-- IMAGE PREVIEW -->
<script>
document.getElementById("imageInput").addEventListener("change", function(event) {
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            document.getElementById("preview").src = e.target.result;
            document.getElementById("preview").style.display = "block";
            document.getElementById("uploadText").style.display = "none";
        };

        reader.readAsDataURL(file);
    }
});

/* CLEAR FORM */
document.getElementById("clearBtn").addEventListener("click", function() {
    const form = document.querySelector("form");
    form.reset();

    document.getElementById("preview").src = "";
    document.getElementById("preview").style.display = "none";
    document.getElementById("uploadText").style.display = "block";
});
</script>

</body>
</html>