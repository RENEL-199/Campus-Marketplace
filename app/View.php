<?php

class View {

    public function renderProductCard(Product $product): string {

<<<<<<< HEAD
        // SHORT DESCRIPTION (80 chars max)
        $desc = $product->prod_desc ?? '';

        $shortDesc = strlen($desc) > 80
            ? substr($desc, 0, strrpos(substr($desc, 0, 80), ' ')) . "..."
            : $desc;
=======
        // SHORT DESCRIPTION
        $desc = $product->prod_desc ?? '';

        $shortDesc = strlen($desc) > 18
            ? substr($desc, 0, 18) . "......"
            : $desc . "......";

        $price = number_format((float)$product->prod_price, 0);
>>>>>>> origin/polin

        return "
        <div class='card'>
            <img src='{$product->prod_image}' alt='product'>

            <div class='card-content'>
<<<<<<< HEAD
                <div class='container-2'>
                    <h3>{$product->prod_name}</h3>
                    <span class='category-tag'>{$product->category_name}</span>
                </div>

                <p>
                    {$shortDesc} 
                    <a href='#' onclick='openProduct({$product->prod_id}); return false;'>
                        Read more
                    </a>
                </p>

                <div class='container-2'>
                    <div class='price'>₱{$product->prod_price}</div>
                    <div class='stock'>Stock: {$product->prod_stock}</div>
                </div>

                <button onclick='openProduct({$product->prod_id})'>
=======

                <div class='card-top-row'>

                    <h3>{$product->prod_name}</h3>

                    <span class='category-tag'>{$product->category_name}</span>

                </div>

                <p class='card-description'>
                    {$shortDesc}
                    <a href='#' onclick='openProduct({$product->prod_id}); return false;'>
                        read more
                    </a>
                </p>

                <div class='card-price-stock-row'>
                    <div class='price'>₱{$price}</div>
                    <div class='stock'>Stock: {$product->prod_stock}</div>
                </div>

                <button type='button' onclick='openProduct({$product->prod_id})'>
>>>>>>> origin/polin
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