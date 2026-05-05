<?php

class View {

    public function renderProductCard(Product $product): string {

        // SHORT DESCRIPTION (80 chars max)
        $shortDesc = strlen($product->description) > 80
            ? substr($product->description, 0, strrpos(substr($product->description, 0, 80), ' ')) . "..."
            : $product->description;

        return "
        <div class='card'>
            <img src='{$product->image}' alt='product'>

            <div class='card-content'>
                <div class='container-2'>
                    <h3>{$product->name}</h3>
                    <span class='category-tag'>{$product->category}</span>
                </div>

                <p>
                    {$shortDesc} 
                    <a href='#' onclick='openProduct({$product->id}); return false;'>
                        Read more
                    </a>
                </p>

                <div class='container-2'>
                    <div class='price'>₱{$product->price}</div>
                    <div class='stock'>Stock: {$product->stock}</div>
                </div>

                <button onclick='openProduct({$product->id})'>
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