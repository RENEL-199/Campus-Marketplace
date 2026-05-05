<?php

// ================= USER =================
class User {
    private string $username;

    public function __construct(string $username) {
        $this->username = $username;
    }

    public function getName(): string {
        return $this->username;
    }
}

// ================= PRODUCT BASE =================
class Product {
    protected string $name;
    protected float $price;
    protected string $description;

    public function __construct(string $name, float $price, string $description) {
        $this->name = $name;
        $this->price = $price;
        $this->description = $description;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getPrice(): float {
        return $this->price;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getType(): string {
        return "General";
    }
}

// ================= PHYSICAL PRODUCT =================
class PhysicalProduct extends Product {
    private int $stock;

    public function __construct(string $name, float $price, string $description, int $stock) {
        parent::__construct($name, $price, $description);
        $this->stock = $stock;
    }

    public function getType(): string {
        return "Physical";
    }
}

// ================= SERVICE PRODUCT =================
class ServiceProduct extends Product {
    private string $schedule;

    public function __construct(string $name, float $price, string $description, string $schedule) {
        parent::__construct($name, $price, $description);
        $this->schedule = $schedule;
    }

    public function getType(): string {
        return "Service";
    }

    public function getSchedule(): string {
        return $this->schedule;
    }
}

// ================= CART =================
class Cart {
    private array $items = [];

    public function add(Product $product): void {
        $this->items[] = $product;
    }

    public function getItems(): array {
        return $this->items;
    }

    public function getTotal(): float {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getPrice();
        }
        return $total;
    }
}

// ================= MARKETPLACE =================
class Marketplace {
    private array $products = [];

    public function addProduct(Product $product): void {
        $this->products[] = $product;
    }

    public function getProducts(): array {
        return $this->products;
    }
}

// ================= SAMPLE DATA =================
$user = new User("Ren");

$market = new Marketplace();

// Physical items (preloved/student items)
$market->addProduct(new PhysicalProduct(
    "2nd Hand Calculator",
    350,
    "Casio calculator, slightly used",
    3
));

$market->addProduct(new PhysicalProduct(
    "Notebook Bundle",
    120,
    "5 notebooks, unused",
    10
));

// Services (very relevant!)
$market->addProduct(new ServiceProduct(
    "Printing Service",
    5,
    "Black & white printing per page",
    "Available 9AM - 5PM"
));

$market->addProduct(new ServiceProduct(
    "Canva Design Service",
    150,
    "Poster / Pubmat design",
    "Delivery in 1-2 days"
));

// ================= CART =================
$cart = new Cart();

// simulate adding items
$cart->add($market->getProducts()[0]);
$cart->add($market->getProducts()[2]);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Campus Marketplace</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            margin: 0;
        }
        header {
            background: #0b74de;
            color: white;
            padding: 20px;
        }
        .container {
            padding: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        .type {
            font-size: 12px;
            color: gray;
        }
        .price {
            color: #0b74de;
            font-weight: bold;
        }
        .cart {
            margin-top: 30px;
            background: white;
            padding: 15px;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<header>
    <h1>Campus Essentials Marketplace</h1>
    <p>Welcome, <?php echo $user->getName(); ?> 👋</p>
</header>

<div class="container">

    <h2>Available Listings</h2>
    <div class="grid">
        <?php foreach ($market->getProducts() as $product): ?>
            <div class="card">
                <div class="type"><?php echo $product->getType(); ?></div>
                <h3><?php echo htmlspecialchars($product->getName()); ?></h3>
                <p><?php echo htmlspecialchars($product->getDescription()); ?></p>
                <div class="price">₱<?php echo number_format($product->getPrice(), 2); ?></div>

                <?php if ($product instanceof ServiceProduct): ?>
                    <small>Schedule: <?php echo $product->getSchedule(); ?></small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="cart">
        <h2>🛒 Your Cart</h2>
        <?php foreach ($cart->getItems() as $item): ?>
            <p><?php echo $item->getName(); ?> - ₱<?php echo $item->getPrice(); ?></p>
        <?php endforeach; ?>
        <hr>
        <strong>Total: ₱<?php echo number_format($cart->getTotal(), 2); ?></strong>
    </div>

</div>

</body>
</html>