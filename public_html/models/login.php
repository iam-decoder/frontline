<?php

class Login_Model extends Model
{

    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "users";
    }

    public function authenticate($username, $password)
    {
        if (!empty($username) && !empty($password)) {
            $salt = $this->_getSalt($username);
            if ($salt === false) {
                return false;
            }

            try {
                $hashedPassword = $this->hashPassword($password, $salt);
                $query = $this->_db->prepare("SELECT id FROM {$this->_table_name} WHERE email=? AND password=?");
                $query->bindParam(1, $username, PDO::PARAM_STR);
                $query->bindParam(2, $hashedPassword, PDO::PARAM_STR);
                $query->execute();
                if ($query->rowCount() === 1) {
                    $user = $query->fetch(PDO::FETCH_ASSOC);
                    return $user['id'];
                } else {
                    controller()->addError("Something went wrong, please try your request again. [LI108]");
                }
            } catch (PDOException $ex) {
                controller()->addError($ex->getMessage() . " [LI105]");
            }
        }
        return false;
    }

    protected function _getSalt($username)
    {
        try {
            $query = $this->_db->prepare("SELECT salt FROM {$this->_table_name} WHERE email=?");
            $query->bindParam(1, $username, PDO::PARAM_STR);
            $query->execute();
            if ($query->rowCount() === 1) {
                $salt = $query->fetch(PDO::FETCH_ASSOC);
                return $salt['salt'];
            } else {
                controller()->addError("Something went wrong, please try your request again. [LI107]");
            }
        } catch (PDOException $ex) {
            controller()->addError($ex->getMessage() . " [LI104]");
        }
        return false;
    }

    protected function hashPassword($password, $salt)
    {
        if (!empty($password) && !empty($salt)) {
            return hash_hmac("sha256", $password, $salt);
        }
        return false;
    }
}