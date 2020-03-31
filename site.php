<?php

use JoaoFeichas\Model\Product;
use JoaoFeichas\Page;

$app->get('/', function () {
    $products = Product::listAll();

    $page = new Page();

    $page->setTpl("index", [
        'products' => Product::checkList($products)
    ]);
});
