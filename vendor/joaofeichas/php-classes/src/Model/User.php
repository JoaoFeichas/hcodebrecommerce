<?php

namespace JoaoFeichas\Model;

use Exception;
use JoaoFeichas\DB\Sql;
use JoaoFeichas\Model;

class User extends Model
{
    const SESSION = "User";

    public static function login($login, $password)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ':LOGIN' => $login
        ));

        if (count($results) === 0) {
            throw new Exception("Usuário inexistente ou senha inválida.");
        }

        $data = $results[0];

        if (password_verify($password, $data["despassword"]) === true) {
            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        } else {
            throw new Exception("Usuário inexistente ou senha inválida.");
        }
    }

    public function setData($data = array())
    {
        foreach ($data as $key => $value) {
            $this->{"set" . $key}($value);
        }
    }

    public static function verifyLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
            ||
            (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
        ) {
            header("Location: /admin/login");
            exit;
        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }

    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users INNER JOIN tb_persons USING (idperson) ORDER BY tb_persons.desperson");
    }

    public function save()
    {
        $sql = new Sql();

        /*
            pdesperson VARCHAR(64),
            pdeslogin VARCHAR(64),
            pdespassword VARCHAR(256),
            pdesemail VARCHAR(128),
            pnrphone BIGINT,
            pinadmin TINYINT
        */
        $results = $sql->select("CALL sp_users_save(:DESPERSON, :DESLOGIN, :DESPASSWORD, :DESEMAIL, :NRPHONE, :INADMIN)", array(
           ':DESPERSON' => $this->getdesperson(),
            ':DESLOGIN' => $this->getdeslogin(),
            ':DESPASSWORD' => $this->getdespassword(),
            ':DESEMAIL' => $this->getdesemail(),
            ':NRPHONE' => $this->getnrphone(),
            ':INADMIN' => $this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function get($iduser)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users INNER JOIN tb_persons USING(idperson) WHERE tb_users.iduser = :IDUSER", array(
            ":IDUSER" => $iduser
        ));

        $this->setData($results[0]);
    }

    public function update()
    {
        $sql = new Sql();

        /*
            pdesperson VARCHAR(64),
            pdeslogin VARCHAR(64),
            pdespassword VARCHAR(256),
            pdesemail VARCHAR(128),
            pnrphone BIGINT,
            pinadmin TINYINT
        */
        $results = $sql->select("CALL sp_usersupdate_save(:IDUSER, :DESPERSON, :DESLOGIN, :DESPASSWORD, :DESEMAIL, :NRPHONE, :INADMIN)", array(
            ':IDUSER' => $this->getiduser(),
           ':DESPERSON' => $this->getdesperson(),
            ':DESLOGIN' => $this->getdeslogin(),
            ':DESPASSWORD' => $this->getdespassword(),
            ':DESEMAIL' => $this->getdesemail(),
            ':NRPHONE' => $this->getnrphone(),
            ':INADMIN' => $this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:IDUSER)", array(
            ":IDUSER" => $this->getiduser()
        ));
    }
}
