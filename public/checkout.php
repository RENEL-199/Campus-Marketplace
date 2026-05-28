<?php

$items = [
    [
        "name" => "Item Name",
        "price" => "",
        "quantity" => "",
        "image" => ""
    ],
    [
        "name" => "Item Name",
        "price" => "",
        "quantity" => "",
        "image" => ""
    ],
    [
        "name" => "Item Name",
        "price" => "",
        "quantity" => "",
        "image" => ""
    ]
];

$total = "";
?>

<!DOCTYPE html>
<html>
<head>
<title>Checkout</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial;
    background:#eef1ef;
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

/* MAIN CONTAINER */

.checkout-container{
    width:1060px;
    min-height:560px;
    background:white;
    margin:38px auto;
    border-radius:24px;
    padding:44px 62px 28px;
    box-shadow:0 2px 5px rgba(0,0,0,0.2);
}

/* TOP CONTENT */

.checkout-content{
    display:flex;
    gap:38px;
}

/* LEFT SIDE */

.items-list{
    width:470px;
}

.checkout-item{
    background:#f1f4f2;
    height:96px;
    border-radius:24px;
    display:flex;
    align-items:center;
    padding:16px;
    margin-bottom:30px;
    box-shadow:0 2px 5px rgba(0,0,0,0.2);
}

.img-box{
    width:68px;
    height:60px;
    background:#d9d9d9;
    border-radius:12px;
}

.item-info{
    margin-left:22px;
}

.item-info h3{
    margin:0;
    font-size:23px;
    font-weight:500;
}

.item-info p{
    margin:0;
    font-size:14px;
}

/* RIGHT SIDE */

.info-box{
    width:410px;
    height:345px;
    background:#f1f4f2;
    border-radius:0 24px 24px 24px;
    padding:18px 22px;
    box-shadow:0 2px 5px rgba(0,0,0,0.2);
}

.info-box h2{
    margin:0 0 14px;
    font-size:22px;
    font-weight:500;
}

/* INPUTS */

.info-box input{
    width:258px;
    height:33px;
    border:1px solid #999;
    border-radius:8px;
    margin-bottom:11px;
    padding:0 10px;
    font-size:14px;
}

/* PAYMENT */

.payment-title{
    margin-top:48px;
    font-size:22px;
}

.payment-buttons{
    display:flex;
    gap:14px;
    margin-top:10px;
}

.payment-buttons button{
    background:white;
    border:1px solid #b64d42;
    border-radius:10px;
    padding:10px 14px;
    color:#777;
    cursor:pointer;
}

/* BOTTOM */

.bottom-row{
    display:flex;
    justify-content:flex-end;
    align-items:center;
    gap:150px;
    margin-top:78px;
}

.total{
    font-size:25px;
    font-weight:bold;
}

.place-btn{
    background:#990b00;
    color:white;
    border:none;
    border-radius:14px;
    padding:12px 28px;
    font-size:17px;
    cursor:pointer;
}

.receipt-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.receipt-box {
    width: 390px;
    background: white;
    border-radius: 22px;
    padding: 24px 30px;
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

.receipt-section {
    background: #f1f4f2;
    border-radius: 0 20px 20px 20px;
    padding: 15px;
    margin-top: 14px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.receipt-section p {
    margin: 7px 0;
    font-size: 14px;
}

.confirm-btn {
    width: 100%;
    margin-top: 18px;
    background: #810C01;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 12px;
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

<div class="checkout-container">

    <div class="checkout-content">

        <!-- LEFT -->

        <div class="items-list">

            <?php foreach($items as $item): ?>

            <div class="checkout-item">

                <div class="img-box"></div>

                <div class="item-info">
                    <h3>Item Name</h3>
                    <p>Price:</p>
                    <p>Quantity:</p>
                </div>

            </div>

            <?php endforeach; ?>

        </div>

        <!-- RIGHT -->

        <div class="info-box">

            <h2>Information:</h2>

            <input type="text" id="receiverName" placeholder="Receiving Person">

            <input type="text" id="receiverAddress" placeholder="Receiving Address">

            <input type="text" id="receiverContact" placeholder="Contact Number">

            <div class="payment-title">
                Payment Method
            </div>

            <div class="payment-buttons">

                <button type="button" onclick="selectPayment('Cash on Delivery')">
                    Cash on Delivery
                </button>

                <button type="button" onclick="selectPayment('Gcash')">
                    Gcash
                </button>

            </div>

        </div>

    </div>

    <div class="bottom-row">

        <div class="total">
            Total:
        </div>

        <button class="place-btn" type="button" onclick="openReceiptModal()">
            PLACE ORDER
        </button>

    </div>

</div>

<div class="receipt-modal" id="receiptModal">
    <div class="receipt-box">

        <span class="close-modal" onclick="closeReceiptModal()">×</span>

        <h2 style="text-align:center;">Receipt</h2>

        <div class="receipt-section">
            <h3>Receiver Information</h3>
            <p><b>Name:</b> <span id="receiptName"></span></p>
            <p><b>Address:</b> <span id="receiptAddress"></span></p>
            <p><b>Contact:</b> <span id="receiptContact"></span></p>
            <p><b>Payment:</b> <span id="receiptPayment"></span></p>
        </div>

        <div class="receipt-section">
            <h3>Order Summary</h3>
            <p><b>Item:</b> Item Name</p>
            <p><b>Price:</b></p>
            <p><b>Quantity:</b></p>
            <p><b>Total:</b> <span id="receiptTotal"></span></p>
        </div>

        <button class="confirm-btn" onclick="window.location.href='orders.php'">
            Confirm Order
        </button>

    </div>
</div>

<script>
let selectedPayment = "";

function selectPayment(payment) {
    selectedPayment = payment;
    alert(payment + " selected");
}

function openReceiptModal() {
    document.getElementById("receiptName").innerText =
        document.getElementById("receiverName").value;

    document.getElementById("receiptAddress").innerText =
        document.getElementById("receiverAddress").value;

    document.getElementById("receiptContact").innerText =
        document.getElementById("receiverContact").value;

    document.getElementById("receiptPayment").innerText =
        selectedPayment;

    document.getElementById("receiptTotal").innerText =
        "₱0.00";

    document.getElementById("receiptModal").style.display =
        "flex";
}

function closeReceiptModal() {
    document.getElementById("receiptModal").style.display =
        "none";
}



</script>

</body>
</html>