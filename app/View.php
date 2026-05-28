<?php

class View {

    public function renderProductCard(Product $product): string {

        $desc = $product->prod_desc ?? '';

        $shortDesc = strlen($desc) > 18
            ? substr($desc, 0, 18) . "......"
            : $desc . "......";

        $price = number_format((float)$product->prod_price, 0);

        $categoryId = (int)$product->category_id;

        // ✅ ROUTE MODAL BASED ON CATEGORY
        if ($categoryId == 3) {
            $openFunction = "openService";
        } elseif ($categoryId == 5) {
            $openFunction = "openRental";
        } else {
            $openFunction = "openProduct";
        }

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
                    <a href='#' onclick='{$openFunction}({$product->prod_id}); return false;'>
                        read more
                    </a>
                </p>

                <div class='card-price-stock-row'>
                    <div class='price'>₱{$price}</div>
                    <div class='stock'>Stock: {$product->prod_stock}</div>
                </div>

                <button type='button' onclick='{$openFunction}({$product->prod_id})'>
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