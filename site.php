<?php

use JoaoFeichas\Page;

$app->get('/', function () {
    $page = new Page();

    $page->setTpl("index");
});
