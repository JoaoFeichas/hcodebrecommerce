<?php

namespace JoaoFeichas\Model;

use JoaoFeichas\DB\Sql;
use JoaoFeichas\Model;

class Order extends Model
{
    const ERROR = "OrderError";
    const SUCCESS = "OrderSuccess";

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal )", [
            ':idorder' => $this->getidorder(),
            ':idcart' => $this->getidcart(),
            ':iduser' => $this->getiduser(),
            ':idstatus' => $this->getidstatus(),
            ':idaddress' => $this->getidaddress(),
            ':vltotal' => $this->getvltotal()
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public function get($idorder)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT *
        FROM tb_orders o
        INNER JOIN tb_ordersstatus os ON o.idstatus = os.idstatus
        INNER JOIN tb_carts c ON o.idcart = c.idcart
        INNER JOIN tb_users u ON o.iduser = u.iduser
        INNER JOIN tb_addresses a ON u.idperson = a.idperson
        INNER JOIN tb_persons p ON u.idperson = p.idperson
        WHERE o.idorder = :idorder
        GROUP BY o.idorder", [
            ':idorder' => $idorder
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT *
        FROM tb_orders o
        INNER JOIN tb_ordersstatus os ON o.idstatus = os.idstatus
        INNER JOIN tb_carts c ON o.idcart = c.idcart
        INNER JOIN tb_users u ON o.iduser = u.iduser
        INNER JOIN tb_addresses a ON o.idaddress = a.idaddress
        INNER JOIN tb_persons p ON u.idperson = p.idperson
        ORDER BY o.dtregister DESC");
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", [
            ':idorder' => $this->getidorder()
        ]);
    }

    public function getCart(): Cart
    {
        $cart = new Cart();

        $cart->get((int) $this->getidcart());

        return $cart;
    }

    public static function setError($msg)
    {
        $_SESSION[Order::ERROR] = $msg;
    }

    public static function getError()
    {
        $msg = (isset($_SESSION[Order::ERROR])) && $_SESSION[Order::ERROR] ? $_SESSION[Order::ERROR] : '';

        Order::clearError();

        return $msg;
    }

    public static function clearError()
    {
        $_SESSION[Order::ERROR] = NULL;
    }

    public static function setSuccess($msg)
    {
        $_SESSION[Order::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = (isset($_SESSION[Order::SUCCESS])) && $_SESSION[Order::SUCCESS] ? $_SESSION[Order::SUCCESS] : '';

        Order::clearSuccess();

        return $msg;
    }

    public static function clearSuccess()
    {
        $_SESSION[Order::SUCCESS] = NULL;
    }

    public static function getPage($page = 1, $itemsPerPage = 10)
    {
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_orders o
            INNER JOIN tb_ordersstatus os ON o.idstatus = os.idstatus
            INNER JOIN tb_carts c ON o.idcart = c.idcart
            INNER JOIN tb_users u ON o.iduser = u.iduser
            INNER JOIN tb_addresses a ON o.idaddress = a.idaddress
            INNER JOIN tb_persons p ON u.idperson = p.idperson
            ORDER BY o.dtregister DESC
            LIMIT $start, $itemsPerPage
        ");

        $resulTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return [
            'data' => $results,
            'total' => (int) $resulTotal[0]["nrtotal"],
            'pages' => ceil($resulTotal[0]["nrtotal"] / $itemsPerPage)
        ];
    }

    public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
    {
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_orders o
            INNER JOIN tb_ordersstatus os ON o.idstatus = os.idstatus
            INNER JOIN tb_carts c ON o.idcart = c.idcart
            INNER JOIN tb_users u ON o.iduser = u.iduser
            INNER JOIN tb_addresses a ON o.idaddress = a.idaddress
            INNER JOIN tb_persons p ON u.idperson = p.idperson
            WHERE o.idorder = :id OR p.desperson LIKE :search
            ORDER BY o.dtregister DESC
            LIMIT $start, $itemsPerPage
        ", [
            ':search' => '%' . $search . '%',
            ':id' => $search
        ]);

        $resulTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return [
            'data' => $results,
            'total' => (int) $resulTotal[0]["nrtotal"],
            'pages' => ceil($resulTotal[0]["nrtotal"] / $itemsPerPage)
        ];
    }
}
