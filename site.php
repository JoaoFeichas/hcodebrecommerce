<?php

use JoaoFeichas\Model\Category;
use JoaoFeichas\Model\Product;
use JoaoFeichas\Page;

$app->get('/', function () {
    $products = Product::listAll();

    $page = new Page();

    $page->setTpl("index", [
        'products' => Product::checkList($products)
    ]);
});

$app->get("/categories/:idcategories", function ($idcategory) {
    $category = new Category();

    $category->get((int) $idcategory);

    $page = new Page();

    $page->setTpl("category", [
        'category' => $category->getValues(),
        'products' => Product::checkList($category->getProducts())
    ]);
});
