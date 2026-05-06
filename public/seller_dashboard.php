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


if (isset($_POST['delete_id'])) {

    $stmt = $pdo->prepare("DELETE FROM products WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['delete_id'], $user_id]);

    header("Location: seller_dashboard.php");
    exit;
}


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

    header("Location: seller_dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


$total = count($products);
$active = 0;
$out = 0;

foreach ($products as $p) {
    if ($p['stock'] > 0) $active++;
    else $out++;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seller Dashboard</title>
<link rel="stylesheet" href="../assets/index-style.css">

<style>

/* PAGE LAYOUT */
.dashboard {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

/* GRID LAYOUT */
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
}

/* STATS */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.stat {
    background: white;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.06);
}

.stat h2 {
    color: var(--primary);
}

/* PRODUCTS */
.product {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.product img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
}

/* DELETE */
.delete-btn {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
}

/* FORM */
input, textarea, select {
    width: 100%;
    padding: 10px;
    margin-top: 8px;
    margin-bottom: 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

button {
    width: 100%;
    padding: 12px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}

.image-upload {
    width: 100%;
    height: 220px;
    border: 2px dashed var(--secondary);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    overflow: hidden;
    background: #fafafa;
    flex-direction: column;
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
    color: var(--text-light);
    position: absolute;
}

/* NAV */
nav {
    background: var(--primary);
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

</style>

</head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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


<div class="card">

<h2>Add Product</h2>

<form method="POST" enctype="multipart/form-data">

        <label class="image-upload">
            <input type="file" name="image" accept="image/*" id="imageInput" required>
            <img id="preview">
            <span id="uploadText">Click to upload image</span>
        </label>

    <input type="text" name="name" placeholder="Product Name" required>

    <textarea name="description" placeholder="Description" required></textarea>

    <input type="text" name="price" placeholder="Price" required>

    <input type="number" name="stock" placeholder="Stock" required>

    <select name="category">
        <option>Electronics</option>
        <option>School Supplies</option>
        <option>Services</option>
        <option>Preloved</option>
        <option>Others</option>
    </select>

    <button type="submit">Add Product</button>

</form>

</div>


<div class="card">

<h2>Products</h2>

<?php foreach ($products as $p): ?>

<div class="product">

    <img src="<?= $p['image'] ?>">

    <div>
        <strong><?= htmlspecialchars($p['name']) ?></strong><br>
        ₱<?= $p['price'] ?> | Stock: <?= $p['stock'] ?>
    </div>

    <form method="POST" onsubmit="return confirm('Delete?')">
        <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
        <button class="delete-btn">X</button>
    </form>

</div>

<?php endforeach; ?>

</div>

</div>

</div>

<script>
document.getElementById("imageInput").addEventListener("change", function(event) {
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            const img = document.getElementById("preview");
            img.src = e.target.result;
            img.style.display = "block";
            document.getElementById("uploadText").style.display = "none";
        };

        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>