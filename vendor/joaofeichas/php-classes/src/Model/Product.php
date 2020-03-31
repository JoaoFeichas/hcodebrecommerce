<?php

namespace JoaoFeichas\Model;

use Exception;
use JoaoFeichas\DB\Sql;
use JoaoFeichas\Model;

class Product extends Model
{
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }

    public static function checkList($list)
    {
        foreach ($list as &$row) {
            $p = new Product();
            $p->setData($row);
            $row = $p->getValues();
        }

        return $list;
    }

    public function save()
    {
        $sql = new Sql();

        /*
            pdescategory VARCHAR(64)
        */
        $results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
            ':idproduct' => $this->getidproduct(),
            ':desproduct' => $this->getdesproduct(),
            ':vlprice' => $this->getvlprice(),
            ':vlwidth' => $this->getvlwidth(),
            ':vlheight' => $this->getvlheight(),
            ':vllength' => $this->getvllength(),
            ':vlweight' => $this->getvlweight(),
            ':desurl' => $this->getdesurl()
        ));

        $this->setData($results[0]);

        Category::updateFile();
    }

    public function get($idproduct)
    {
        $sql = new Sql();

        $result = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
            ':idproduct' => $idproduct
        ]);

        $this->setData($result[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
            ':idproduct' => $this->getidproduct()
        ]);

        Category::updateFile();
    }

    public function checkPhoto(Type $var = null)
    {
        if (file_exists(
            $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
            "res" . DIRECTORY_SEPARATOR .
            "site" . DIRECTORY_SEPARATOR .
            "img" . DIRECTORY_SEPARATOR .
            "products" . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg"
         )) {
             $url =  "/res/site/img/products/" . $this->getidproduct() . ".jpg";
         } else {
            $url = "/res/site/img/product.jpg";
         }

         return $this->setdesphoto($url);
    }

    public function getValues()
    {
        $this->checkPhoto();

        $values = parent::getValues();

        return $values;
    }

public function setPhoto($file)
{
    $extension = explode('.', $file['name']);
    $extension = end($extension);

    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($file["tmp_name"]);
            break;
        case 'gif':
            $image = imagecreatefromgif($file["tmp_name"]);
        break;
        case 'png':
            $image = imagecreatefrompng($file["tmp_name"]);
        break;
    }

    $dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
    "res" . DIRECTORY_SEPARATOR .
    "site" . DIRECTORY_SEPARATOR .
    "img" . DIRECTORY_SEPARATOR .
    "products" . DIRECTORY_SEPARATOR .
    $this->getidproduct() . ".jpg";

    imagejpeg($image, $dist);

    imagedestroy($image);

    $this->checkPhoto();
}

    public static function updateFile()
    {
        $categories = Category::listAll();

        $html = [];

        foreach ($categories as $row) {
            array_push($html, '<li><a href="/categories/' . $row['idproduct'] . '">' . $row['descategory'] . '</a></li>');
        }

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
    }
}
