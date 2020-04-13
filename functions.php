<?php

use JoaoFeichas\Model\Cart;
use JoaoFeichas\Model\User;

function formatPrice($vlprice)
{
    if (!$vlprice > 0) $vlprice = 0;

    return number_format($vlprice, 2, ',', '.');
}

function checkLogin($inadmin = true)
{
    return User::checkLogin($inadmin);
}

function getUserName()
{
    $user = User::getFromSession();

    return $user->getdesperson();
}

function getCartNrQtd()
{
    $cart = Cart::getFromSession();

    $totals = $cart->getProductsTotal();

    return $totals['nrqtd'];
}

function getCartVlSubTotal()
{
    $cart = Cart::getFromSession();

    $totals = $cart->getProductsTotal();

    return formatPrice($totals['vlprice']);
}
