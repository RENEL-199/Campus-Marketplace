<?php

class View {

    public function renderProductCard(Product $product): string {

        // SHORT DESCRIPTION (80 chars max)
        $desc = $product->prod_desc ?? '';

        $shortDesc = strlen($desc) > 80
            ? substr($desc, 0, strrpos(substr($desc, 0, 80), ' ')) . "..."
            : $desc;

        $price = number_format((float)$product->prod_price, 0);

        return "
        <div class='card'>
            <img src='{$product->prod_image}' alt='product'>

            <div class='card-content'>
                <div class='card-top-row'>
                    <h3>{$product->prod_name}</h3>
                    <span class='category-tag'>{$product->category_name}</span>
                </div>

                <p class='card-description'>
                    {$shortDesc} 
                    <a href='#' onclick='openProduct({$product->prod_id}); return false;'>
                        Read more
                    </a>
                </p>

                <div class='card-price-stock-row'>
                    <div class='price'>₱{$price}</div>
                    <div class='stock'>Stock: {$product->prod_stock}</div>
                </div>

                <button type='button' onclick='openProduct({$product->prod_id})'>
                    View Item
                </button>
            </div>
        </div>
        ";
    }

    public function renderProducts(array $products): string {
        $html = "<div class='grid'>";

        foreach ($products as $product) {
            $html .= $this->renderProductCard($product);
        }

        $html .= "</div>";

        return $html;
    }
}
