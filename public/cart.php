<?php
session_start();

if (!isset($_SESSION['cart_items'])) {
    $_SESSION['cart_items'] = [
        ["cart_id" => 1, "name" => "Item Name", "price" => 0, "quantity" => 1, "image" => ""],
        ["cart_id" => 2, "name" => "Item Name", "price" => 0, "quantity" => 1, "image" => ""],
        ["cart_id" => 3, "name" => "Item Name", "price" => 0, "quantity" => 1, "image" => ""],
        ["cart_id" => 4, "name" => "Item Name", "price" => 0, "quantity" => 1, "image" => ""]
    ];
}

if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];

    foreach ($_SESSION['cart_items'] as $key => $item) {
        if ($item['cart_id'] == $remove_id) {
            unset($_SESSION['cart_items'][$key]);
        }
    }

    $_SESSION['cart_items'] = array_values($_SESSION['cart_items']);

    header("Location: cart.php");
    exit;
}

$items = $_SESSION['cart_items'];
$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Cart</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f3f7f5;
    color: #111;
}

nav {
    height: 58px;
    background: #810C01;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 26px;
}

nav h1 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.nav-links {
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

.page {
    display: flex;
    gap: 34px;
    max-width: 1090px;
    margin: 40px auto;
}

.cart-container {
    width: 635px;
    min-height: 550px;
    background: white;
    border-radius: 22px;
    padding: 18px 32px 30px;
    box-shadow: 0 3px 4px rgba(0,0,0,0.25);
}

.cart-container h2 {
    font-size: 32px;
    margin: 0 0 18px;
}

.cart-row {
    display: flex;
    align-items: center;
    margin-bottom: 18px;
}

.cart-check {
    width: 18px;
    margin-right: 14px;
    accent-color: #6c5aa6;
}

.cart-item {
    flex: 1;
    height: 72px;
    border: 1px solid #333;
    border-radius: 6px;
    display: flex;
    align-items: center;
    padding: 6px;
    background: white;
    cursor: pointer;

}

.cart-item img {
    width: 62px;
    height: 56px;
    object-fit: cover;
    border-radius: 10px;
    background: #d9d9d9;
}

.cart-info {
    flex: 1;
    margin-left: 14px;
}

.cart-info h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 600;
}

.cart-info p {
    margin: 1px 0;
    font-size: 13px;
}

.remove-btn {
    background: #810C01;
    color: white;
    padding: 10px 27px;
    border-radius: 14px;
    text-decoration: none;
    font-size: 16px;
    text-transform: uppercase;
}

.cart-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 45px;
}

.total {
    font-size: 22px;
    font-weight: bold;
}

.checkout-btn {
    background: #810C01;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 10px 22px;
    font-size: 16px;
    cursor: pointer;
    text-transform: uppercase;
}

.right-panel {
    width: 395px;
    min-height: 550px;
    background: white;
    border-radius: 22px;
    padding: 16px 44px;
    box-shadow: 0 3px 4px rgba(0,0,0,0.25);
}

.preview-img {
    height: 138px;
    border-radius: 28px;
    box-shadow: 0 3px 5px rgba(0,0,0,0.15);
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 10px;
    overflow: hidden;
}

.preview-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.details {
    background: #f3f7f5;
    border-radius: 0 24px 24px 24px;
    padding: 15px 18px;
    box-shadow: 0 3px 4px rgba(0,0,0,0.25);
    margin-bottom: 14px;
}

.details h3 {
    margin: 0 0 12px;
}

.details p {
    font-size: 13px;
    margin: 8px 0;
}

.qty-control {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 12px 0;
    font-size: 13px;
}

.qty-control button {
    border: none;
    background: transparent;
    font-weight: bold;
    font-size: 18px;
    cursor: pointer;
}

.qty-num {
    border: 1px solid #333;
    padding: 1px 7px;
    border-radius: 4px;
}

.date-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    margin-bottom: 18px;
}

.date-row input[type="date"] {
    width: 120px;
    height: 30px;
    border: 1px solid #999;
    border-radius: 7px;
    padding: 0 8px;
    font-size: 12px;
}

.borrower h3 {
    margin: 0 0 12px;
    font-size: 18px;
}

.borrower input {
    width: 100%;
    height: 34px;
    border: 1px solid #999;
    border-radius: 7px;
    margin-bottom: 8px;
    padding: 0 12px;
}

.two-input {
    display: flex;
    gap: 16px;
}

.cart-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.cart-modal-box {
    width: 410px;
    background: white;
    border-radius: 24px;
    padding: 22px 32px;
    position: relative;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.close-modal {
    position: absolute;
    top: 12px;
    right: 18px;
    font-size: 25px;
    cursor: pointer;
}

.modal-img {
    height: 145px;
    border-radius: 26px;
    box-shadow: 0 3px 5px rgba(0,0,0,0.2);
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 14px;
}

.modal-details {
    background: #f3f7f5;
    border-radius: 0 24px 24px 24px;
    padding: 16px 20px;
    box-shadow: 0 3px 5px rgba(0,0,0,0.2);
    margin-bottom: 14px;
}

.modal-details h3 {
    margin: 0 0 12px;
}

.modal-details p {
    margin: 8px 0;
    font-size: 14px;
}

.modal-qty {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.modal-qty button {
    border: none;
    background: transparent;
    font-weight: bold;
    font-size: 18px;
}

.modal-qty span {
    border: 1px solid #333;
    border-radius: 4px;
    padding: 1px 8px;
}

.modal-date {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    font-size: 14px;
}

.modal-date input {
    width: 82px;
    height: 24px;
    border: 1px solid #999;
    border-radius: 7px;
}

.modal-borrower h3 {
    margin-bottom: 12px;
}

.modal-borrower input {
    width: 100%;
    height: 34px;
    border: 1px solid #999;
    border-radius: 8px;
    margin-bottom: 9px;
    padding: 0 12px;
}

.modal-borrower div {
    display: flex;
    gap: 14px;
}

.modal-add-btn {
    width: 100%;
    margin-top: 10px;
    background: #810C01;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 12px;
    font-size: 15px;
    cursor: pointer;
}

</style>
</head>

<body>

<nav>
    <h1>IskoHub</h1>

    <div class="nav-links">
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
        <a href="orders.php"><i class="fa-solid fa-box"></i> Order History</a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> Sell</a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
    </div>
</nav>

<div class="page">

    <div class="cart-container">
        <h2>Cart</h2>

        <?php foreach ($items as $item): ?>
            <?php
                $price = is_numeric($item['price']) ? $item['price'] : 0;
                $quantity = is_numeric($item['quantity']) ? $item['quantity'] : 0;

                $subtotal = $price * $quantity;
                $total += $subtotal;
            ?>

            <div class="cart-row">
                <input 
                    type="checkbox" 
                    class="cart-check"
                    checked
                    onclick="showDetails(
                        '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>',
                        '<?= $price ?>',
                        '<?= $quantity ?>',
                        '<?= htmlspecialchars($item['image'], ENT_QUOTES) ?>'
                    )"
                >

                <div class="cart-item">
                    <img src="<?= htmlspecialchars($item['image']) ?>">

                    <div class="cart-info">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p>Price: <?= $price == 0 ? '' : '₱' . number_format($price, 2) ?></p>
                        <p>Quantity: <?= $quantity ?></p>
                    </div>

                    <a class="remove-btn" href="cart.php?remove=<?= $item['cart_id'] ?>">Remove</a>

                </div>
            </div>

        <?php endforeach; ?>

        <div class="cart-footer">
            <div class="total">Total: ₱<?= number_format((float)$total, 2) ?></div>
            <button class="checkout-btn" type="button" onclick="openReceiptModal()">
                Place Order
            </button>
        </div>
    </div>

    <?php if (!empty($items)): ?>

        <div class="right-panel">
            <div class="preview-img">
                <img id="detailImage" src="<?= htmlspecialchars($items[0]['image']) ?>">
            </div>

            <div class="details">
                <h3>Details</h3>
                <p>Item Name: <span id="detailName"><?= htmlspecialchars($items[0]['name']) ?></span></p>
                <p>Price: ₱<span id="detailPrice"><?= $items[0]['price'] ?></span></p>
                <p>Seller:</p>
            </div>

            <div class="qty-control">
                Quantity:

                <button type="button" onclick="minusQty()">−</button>

                <span class="qty-num" id="detailQty">
                    <?= $items[0]['quantity'] ?>
                </span>

                <button type="button" onclick="plusQty()">+</button>
            </div>

            <div class="date-row">
                From:
                <input type="date" id="fromDate">

                To
                <input type="date" id="toDate">
            </div>

            <div class="borrower">
                <h3>Borrower Information</h3>
                <input type="text" id="borrowerName" placeholder="Full Name">
                <input type="text" id="studentNo" placeholder="Student No.">

                <div class="two-input">
                    <input type="text" id="age" placeholder="Age">
                    <input type="text" id="gender" placeholder="Gender">
                </div>
            </div>
        </div>

    <?php else: ?>

        <div class="right-panel">
            <div class="preview-img">
                No item selected
            </div>

            <div class="details">
                <h3>Details</h3>
                <p>Item Name:</p>
                <p>Price:</p>
                <p>Seller:</p>
            </div>

            <div class="qty-control">
                Quantity:

                <button type="button" onclick="minusQty()">−</button>

                <span class="qty-num" id="detailQty">0</span>

                <button type="button" onclick="plusQty()">+</button>
            </div>

            <div class="date-row">
                From:
                <input type="date" id="fromDate">

                To
                <input type="date" id="toDate">
            </div>

            <div class="borrower">
                <h3>Borrower Information</h3>
                <input type="text" id="borrowerName" placeholder="Full Name">
                <input type="text" id="studentNo" placeholder="Student No.">

                <div class="two-input">
                    <input type="text" id="age" placeholder="Age">
                    <input type="text" id="gender" placeholder="Gender">
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<script>
function showDetails(name, price, qty, image) {
    document.getElementById("detailName").innerText = name;
    document.getElementById("detailPrice").innerText = price;
    document.getElementById("detailQty").innerText = qty;
    document.getElementById("detailImage").src = image;
}

function plusQty() {

    let qtyBox =
        document.getElementById("detailQty");

    let qty =
        parseInt(qtyBox.innerText) || 0;

    qty++;

    qtyBox.innerText = qty;
}

function minusQty() {

    let qtyBox =
        document.getElementById("detailQty");

    let qty =
        parseInt(qtyBox.innerText) || 0;

    if (qty > 0) {
        qty--;
    }

    qtyBox.innerText = qty;
}

function openCartModal() {
    document.getElementById("cartModal").style.display = "flex";
}

function closeCartModal() {
    document.getElementById("cartModal").style.display = "none";
}

function openReceiptModal() {

    let borrowerName =
        document.getElementById("borrowerName")?.value || "";

    let studentNo =
        document.getElementById("studentNo")?.value || "";

    let age =
        document.getElementById("age")?.value || "";

    let gender =
        document.getElementById("gender")?.value || "";

    let fromDate =
        document.getElementById("fromDate")?.value || "";

    let toDate =
        document.getElementById("toDate")?.value || "";

    let item =
        document.getElementById("detailName")?.innerText || "";

    let price =
        document.getElementById("detailPrice")?.innerText || "0";

    let qty =
        document.getElementById("detailQty")?.innerText || "0";

    document.getElementById("receiptName").innerText =
        borrowerName;

    document.getElementById("receiptStudent").innerText =
        studentNo;

    document.getElementById("receiptAge").innerText =
        age;

    document.getElementById("receiptGender").innerText =
        gender;

    document.getElementById("receiptFrom").innerText =
        fromDate;

    document.getElementById("receiptTo").innerText =
        toDate;

    document.getElementById("receiptItem").innerText =
        item;

    document.getElementById("receiptPrice").innerText =
        price;

    document.getElementById("receiptQty").innerText =
        qty;

    let total =
        (parseFloat(price) || 0) *
        (parseInt(qty) || 0);

    document.getElementById("receiptTotal").innerText =
        total.toFixed(2);

    document.getElementById("receiptModal").style.display =
        "flex";
}
function closeReceiptModal() {
    document.getElementById("receiptModal").style.display = "none";
}

function confirmOrder() {

    let orders =
        JSON.parse(localStorage.getItem("orders")) || [];

    let newOrder = {
        orderNo: "#" + (orders.length + 1),
        date: new Date().toLocaleDateString(),
        total:
            document.getElementById("receiptTotal").innerText
    };

    orders.push(newOrder);

    localStorage.setItem(
        "orders",
        JSON.stringify(orders)
    );

    window.location.href = "orders.php";
}

</script>

<!-- CART MODAL -->
<div class="cart-modal" id="cartModal">
    <div class="cart-modal-box">

        <span class="close-modal" onclick="closeCartModal()">×</span>

        <div class="modal-img">
            IMG
        </div>

        <div class="modal-details">
            <h3>Details</h3>
            <p><b>Item Name:</b> <span id="modalName">Item Name</span></p>
            <p><b>Price:</b> ₱<span id="modalPrice">0</span></p>
            <p><b>Seller:</b></p>
        </div>

        <div class="modal-qty">
            Quantity:
            <button>−</button>
            <span>1</span>
            <button>+</button>
        </div>

        <div class="modal-date">
            From: <input type="text">
            To: <input type="text">
        </div>

        <div class="modal-borrower">
            <h3>Borrower Information</h3>
            <input type="text" placeholder="Full Name">
            <input type="text" placeholder="Student No.">

            <div>
                <input type="text" placeholder="Age">
                <input type="text" placeholder="Gender">
            </div>
        </div>

        <button class="modal-add-btn">Add to Cart</button>

    </div>
</div>

<div class="cart-modal" id="receiptModal">
    <div class="cart-modal-box">

        <span class="close-modal" onclick="closeReceiptModal()">×</span>

        <h2 style="text-align:center;">Receipt</h2>

        <div class="modal-details">
            <h3>Borrower Information</h3>
            <p><b>Name:</b> <span id="receiptName"></span></p>
            <p><b>Student No:</b> <span id="receiptStudent"></span></p>
            <p><b>Age:</b> <span id="receiptAge"></span></p>
            <p><b>Gender:</b> <span id="receiptGender"></span></p>
            <p><b>From:</b> <span id="receiptFrom"></span></p>
            <p><b>To:</b> <span id="receiptTo"></span></p>
        </div>

        <div class="modal-details">
            <h3>Order Details</h3>
            <p><b>Item:</b> <span id="receiptItem"></span></p>
            <p><b>Price:</b> ₱<span id="receiptPrice"></span></p>
            <p><b>Quantity:</b> <span id="receiptQty"></span></p>
            <p><b>Total:</b> ₱<span id="receiptTotal"></span></p>
        </div>

        <button class="modal-add-btn" onclick="window.location.href='orders.php'">
            Confirm Order
        </button>

    </div>
</div>



</body>
</html>