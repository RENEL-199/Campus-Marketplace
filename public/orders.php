<?php
// temporary orders only, no database
?>

<!DOCTYPE html>
<html>
<head>
<title>Order History</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
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

nav a::after {
    content: "";
    position: absolute;
    width: 0%;
    height: 2px;
    bottom: -3px;
    left: 0;
    background: var(--accent);
    transition: 0.3s;
}
nav a:hover::after {
    width: 100%;
}

.history-container {
    width: 1035px;
    min-height: 580px;
    background: white;
    margin: 34px auto;
    border-radius: 24px;
    padding: 30px 64px;
    box-shadow: 0 3px 4px rgba(0,0,0,0.25);
}

.history-container h2 {
    font-size: 34px;
    margin-bottom: 26px;
}

.order {
    height: 74px;
    border: 1px solid #333;
    border-radius: 6px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    padding: 6px;
}

.order-img {
    width: 68px;
    height: 60px;
    background: #d9d9d9;
    border-radius: 10px;
    margin-right: 14px;
}

.order-info {
    flex: 1;
}

.order-info h3 {
    font-size: 23px;
    margin-bottom: 2px;
}

.order-info p {
    font-size: 14px;
    margin: 0;
}

.view-btn {
    background: #970d03;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 10px 46px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
}

/* MODAL */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
}

.modal-content {
    position: relative;
    background: white;
    width: 340px;
    padding: 22px;
    border-radius: 12px;
    font-family: 'Courier New', monospace;
}

.close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 20px;
    cursor: pointer;
    font-weight: bold;
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

<div class="history-container">

    <h2>Order History</h2>

<div id="ordersContainer"></div>

</div>

<div class="modal" id="receiptModal">
    <div class="modal-content">

        <span class="close" onclick="closeModal()">✕</span>

        <div id="receiptContent">
            Loading...
        </div>

    </div>
</div>

<script>

let orders =
    JSON.parse(localStorage.getItem("orders")) || [];

let container =
    document.getElementById("ordersContainer");

if (orders.length === 0) {

    container.innerHTML =
        "<p>No orders yet.</p>";

} else {

    orders.reverse().forEach((order, index) => {

        container.innerHTML += `

        <div class="order">

            <div class="order-img"></div>

            <div class="order-info">

                <h3>Order No. ${order.orderNo}</h3>

                <p>Date: ${order.date}</p>

                <p>Total: ₱${order.total}</p>

            </div>

            <button class="view-btn"
                onclick="openReceipt(${index})">

                Check Receipt

            </button>

        </div>

        `;
    });
}

function openReceipt(index) {

    let order = orders[index];

    document.getElementById("receiptContent").innerHTML = `

        <h2 style="text-align:center;margin-bottom:12px;">
            Receipt
        </h2>

        <hr>

        <p><b>Order No:</b>
            ${order.orderNo}
        </p>

        <p><b>Date:</b>
            ${order.date}
        </p>

        <p><b>Total:</b>
            ₱${order.total}
        </p>

        <hr>

        <p>
            Order placed successfully.
        </p>

    `;

    document.getElementById("receiptModal").style.display =
        "flex";
}

function closeModal() {

    document.getElementById("receiptModal").style.display =
        "none";
}

</script>

</body>
</html>