<?php

class View {

    private function e(?string $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public function renderProductCard(Product $product): string {

        $desc = $product->prod_desc ?? '';

        $shortDesc = strlen($desc) > 18
            ? substr($desc, 0, 18) . "......"
            : $desc . "......";

        $price = number_format((float)$product->prod_price, 0);
        $categoryId = (int)$product->category_id;

        if ($categoryId === 3) {
            $openFunction = "openService";
        } elseif ($categoryId === 5) {
            $openFunction = "openRental";
        } else {
            $openFunction = "openProduct";
        }

        $image = $this->e($product->prod_image);
        $name = $this->e($product->prod_name);
        $category = $this->e($product->category_name ?? 'Uncategorized');
        $description = $this->e($shortDesc);
        $stock = (int)$product->prod_stock;
        $id = (int)$product->prod_id;

        return "
        <div class='card'>
            <img src='{$image}' alt='{$name}'>

            <div class='card-content'>

                <div class='card-top-row'>
                    <h3>{$name}</h3>
                    <span class='category-tag'>{$category}</span>
                </div>

                <p class='card-description'>
                    {$description}
                    <a href='#' onclick='{$openFunction}({$id}); return false;'>
                        read more
                    </a>
                </p>

                <div class='card-price-stock-row'>
                    <div class='price'>₱{$price}</div>
                    <div class='stock'>Stock: {$stock}</div>
                </div>

                <button type='button' onclick='{$openFunction}({$id})'>
                    View Item
                </button>

            </div>
        </div>
        ";
    }

    public function renderProducts(array $products): string {
        $html = "";

        foreach ($products as $product) {
            $html .= $this->renderProductCard($product);
        }

        return $html;
    }
}
