<?php

namespace JoaoFeichas\Model;

use Exception;
use JoaoFeichas\DB\Sql;
use JoaoFeichas\Mailer;
use JoaoFeichas\Model;

class User extends Model
{
    const SESSION = "User";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";
    const SUCCESS = "UserSuccess";

    const ENCRYPT_METHOD = "AES-256-CBC";
    const SECRET = "HcodePhp7_Secret";

    public static function getFromSession()
    {
        $user = new User();

        if (isset($_SESSION[User::SESSION]) && (int) $_SESSION[User::SESSION]['iduser'] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }

        return $user;
    }

    public function checkLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int) $_SESSION[User::SESSION]["iduser"] > 0
        ) {
            return false;
        } else {
            if ($inadmin === true && (bool) $_SESSION[User::SESSION]["inadmin"] === true) {
                return true;
            } else if ($inadmin === false) {
                return true;
            } else {
                return false;
            }
        }
    }

    public static function login($login, $password)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users u INNER JOIN tb_persons p ON u.idperson = p.idperson WHERE u.deslogin = :LOGIN", array(
            ':LOGIN' => $login
        ));

        if (count($results) === 0) {
            throw new Exception("Usuário inexistente ou senha inválida.");
        }

        $data = $results[0];

        if (password_verify($password, $data["despassword"]) === true) {
            $user = new User();

            // $data['desperson'] = utf8_encode($data['desperson']);
            $data['desperson'] = $data['desperson'];

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        } else {
            throw new Exception("Usuário inexistente ou senha inválida.");
        }
    }

    public static function verifyLogin($inadmin = true)
    {
        if (!User::checkLogin($inadmin)) {
            if ($inadmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
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
            // ':DESPERSON' => utf8_decode($this->getdesperson()),
            ':DESPERSON' => $this->getdesperson(),
            ':DESLOGIN' => $this->getdeslogin(),
            ':DESPASSWORD' => User::getPasswordHash($this->getdespassword()),
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
            // ':DESPERSON' => utf8_decode($this->getdesperson()),
            ':DESPERSON' => $this->getdesperson(),
            ':DESLOGIN' => $this->getdeslogin(),
            // ':DESPASSWORD' => User::getPasswordHash($this->getdespassword()),
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

    public static function getForgot($email, $inadmin = true)
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

                // mcrypt is deprecated on PHP 7.x
                // $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, User::SECRET, $dataRecovey["idrecovery"], MCRYPT_MODE_ECB));
                $code = base64_encode(openssl_encrypt($dataRecovey["idrecovery"], User::ENCRYPT_METHOD, User::SECRET, OPENSSL_RAW_DATA, substr(hash('sha256', User::SECRET), 0, 16)));

                if ($inadmin === true) {
                    $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
                } else {
                    $link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
                }

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
        // $idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, User::SECRET, base64_decode($code), MCRYPT_MODE_CBC);
        $idrecovery = openssl_decrypt(base64_decode($code), User::ENCRYPT_METHOD, User::SECRET, OPENSSL_RAW_DATA, substr(hash('sha256', User::SECRET), 0, 16));

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

    public static function setError($msg)
    {
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getError()
    {
        $msg = (isset($_SESSION[User::ERROR])) && $_SESSION[User::ERROR] ? $_SESSION[User::ERROR] : '';

        User::clearError();

        return $msg;
    }

    public static function clearError()
    {
        $_SESSION[User::ERROR] = NULL;
    }

    public static function setSuccess($msg)
    {
        $_SESSION[User::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = (isset($_SESSION[User::SUCCESS])) && $_SESSION[User::SUCCESS] ? $_SESSION[User::SUCCESS] : '';

        User::clearSuccess();

        return $msg;
    }

    public static function clearSuccess()
    {
        $_SESSION[User::SUCCESS] = NULL;
    }

    public static function setErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }

    public static function getErrorRegister()
    {
        $msg = (isset($_SESSION[User::ERROR_REGISTER])) && $_SESSION[User::ERROR_REGISTER] ? $_SESSION[User::ERROR_REGISTER] : '';

        User::clearErrorRegister();

        return $msg;
    }

    public static function clearErrorRegister()
    {
        $_SESSION[User::ERROR_REGISTER] = NULL;
    }

    public static function checkLoginExist($login)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
            ':deslogin' => $login
        ]);

        return (count($results) > 0);
    }

    public static function getPasswordHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT, [
            'cost' => 12
        ]);
    }
}
