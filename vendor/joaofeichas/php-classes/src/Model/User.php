<?php

namespace JoaoFeichas\Model;

use Exception;
use JoaoFeichas\DB\Sql;
use JoaoFeichas\Mailer;
use JoaoFeichas\Model;

class User extends Model
{
    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";

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

    public static function getForgot($email)
    {
        $sql = new Sql();

        $results = $sql->select("
        SELECT *
        FROM tb_persons
        INNER JOIN tb_users USING(idperson)
        WHERE tb_persons.desemail = :EMAIL;
        ", array(
            ':EMAIL' => $email
        ));

        if (count($results) === 0) {
            throw new Exception("Não foi possível recuperar a senha.");
        } else {
            $data = $results[0];
            
            $resultsRecovery = $sql->select("CALL sp_userspasswordsrecoveries_create(:IDUSER, :DESIP)", array(
                ':IDUSER' => $data["iduser"],
                ':DESIP' => $_SERVER["REMOTE_ADDR"]
            ));

            if (count($resultsRecovery) === 0) {
                throw new Exception("Não foi possível recuperar a senha.");
            } else {
                $dataRecovey = $resultsRecovery[0];

                $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, User::SECRET, $dataRecovey["idrecovery"], MCRYPT_MODE_ECB));

                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da Hcode Store", "forgot", array(
                    "name" => $data["desperson"],
                    "link" => $link
                ));

                $mailer->send();

                return $data;
            }
        }
    }

    public static function validForgotDecrypt($code)
    {

        $idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, User::SECRET, base64_decode($code), MCRYPT_MODE_CBC);

        $sql = new Sql();

        $results = $sql->select("
SELECT * FROM tb_userspasswordsrecoveries
INNER JOIN tb_users USING (iduser)
INNER JOIN tb_persons USING (idperson)
WHERE
tb_userspasswordsrecoveries.idrecovery = :idrecovery
AND
tb_userspasswordsrecoveries.dtrecovery IS NULL
AND
DATE_ADD(tb_userspasswordsrecoveries.dtregister, INTERVAL 1 HOUR) >= NOW();  
        ", array(
            ":idrecovery" => $idrecovery
        ));

        if (count($results) === 0) {
            throw new Exception("Não foi possível recuperar a senha.");
        } else {
            return $results[0];
        }

    }

    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
            ":idrecovery" => $idrecovery
        ));
    }

    public function setPassword($password)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ':password' => $password,
            ':iduser' => $this->getiduser()
        ));
    }
}
