<?php

class Product
{
    private string $name;
    private string $description;
    private float $price;
    private string $image;

    public function __construct(string $name, string $description, float $price, string $image)
    {
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->image = $image;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): string
    {
        return number_format($this->price, 2);
    }

    public function getImage(): string
    {
        return $this->image;
    }
}

class Catalog
{
    private array $products;

    public function __construct(array $products = [])
    {
        $this->products = $products;
    }

    public function addProduct(Product $product): void
    {
        $this->products[] = $product;
    }

    public function getProducts(): array
    {
        return $this->products;
    }
}

class HomePage
{
    private Catalog $catalog;

    public function __construct(Catalog $catalog)
    {
        $this->catalog = $catalog;
    }

    public function render(): string
    {
        $html = '';
        foreach ($this->catalog->getProducts() as $product) {
            $html .= $this->renderProduct($product);
        }
        return $html;
    }

    private function renderProduct(Product $product): string
    {
        return sprintf(
            '<article class="product-card">
                <img src="%s" alt="%s" />
                <h3>%s</h3>
                <p>%s</p>
                <div class="price">$%s</div>
                <button type="button">Add to cart</button>
            </article>',
            $product->getImage(),
            htmlspecialchars($product->getName()),
            htmlspecialchars($product->getName()),
            htmlspecialchars($product->getDescription()),
            $product->getPrice()
        );
    }
}

$catalog = new Catalog();
$catalog->addProduct(new Product(
    'Nova Wireless Headphones',
    'Comfortable noise-cancelling headphones with long battery life.',
    129.99,
    'https://via.placeholder.com/320x220?text=Headphones'
));
$catalog->addProduct(new Product(
    'Pulse Smartwatch',
    'Track activity, heart rate, and notifications with one stylish watch.',
    189.00,
    'https://via.placeholder.com/320x220?text=Smartwatch'
));
$catalog->addProduct(new Product(
    'Aura Portable Speaker',
    'Compact Bluetooth speaker with rich sound and durable design.',
    74.50,
    'https://via.placeholder.com/320x220?text=Speaker'
));

$page = new HomePage($catalog);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Market Home</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #333;
        }
        header {
            background: #0b74de;
            color: white;
            padding: 24px 20px;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 2rem;
        }
        header p {
            margin: 10px 0 0;
            font-size: 1rem;
            opacity: 0.9;
        }
        .container {
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 20px;
        }
        .grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        .product-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
        }
        .product-card:hover {
            transform: translateY(-4px);
        }
        .product-card img {
            width: 100%;
            display: block;
        }
        .product-card h3 {
            margin: 16px;
            font-size: 1.25rem;
        }
        .product-card p {
            margin: 0 16px 16px;
            color: #555;
            line-height: 1.5;
        }
        .product-card .price {
            margin: 0 16px 16px;
            font-weight: bold;
            color: #0b74de;
        }
        .product-card button {
            width: calc(100% - 32px);
            margin: 0 16px 20px;
            padding: 12px;
            border: none;
            background: #0b74de;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        .product-card button:hover {
            background: #095bb5;
        }
        .hero {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 32px;
        }
        .hero-text {
            flex: 1 1 320px;
        }
        .hero-text h2 {
            margin: 0 0 12px;
            font-size: 2rem;
        }
        .hero-text p {
            margin: 0;
            line-height: 1.7;
            color: #555;
        }
        .hero-image {
            flex: 1 1 320px;
            min-height: 220px;
            background: url('https://via.placeholder.com/640x380?text=Tech+Shop') center/cover no-repeat;
            border-radius: 18px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Tech Market</h1>
        <p>Discover premium electronics for daily life with modern style and smart design.</p>
    </header>
    <main class="container">
        <section class="hero">
            <div class="hero-text">
                <h2>Shop smart devices for your home and lifestyle</h2>
                <p>Explore our selection of wireless headphones, smartwatches, and portable speakers designed for comfort, convenience, and premium sound.</p>
            </div>
            <div class="hero-image"></div>
        </section>
        <section>
            <h2>Featured Products</h2>
            <div class="grid">
                <?php echo $page->render(); ?>
            </div>
        </section>
    </main>
</body>
</html>
