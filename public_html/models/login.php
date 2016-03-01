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
                $hashedPassword = $this->_hashPassword($password, $salt);
                $query = $this->_db->prepare("SELECT `id` FROM {$this->_table_name} WHERE `email`=? AND `password`=?");
                $query->bindParam(1, $username, PDO::PARAM_STR);
                $query->bindParam(2, $hashedPassword, PDO::PARAM_STR);
                $query->execute();
                if ($query->rowCount() === 1) {
                    $user = $query->fetch(PDO::FETCH_ASSOC);
                    return $user['id'];
                }
            } catch (PDOException $ex) {
                controller()->addError($ex->getMessage() . " [LI105]");
            }
        }
        return false;
    }

    public function newUser($username, $password)
    {
        if (!empty($username) && !empty($password)) {
            try {
                $salt = sha1(microtime());
                $hashedPassword = $this->_hashPassword($password, $salt);
                $query = $this->_db->prepare("INSERT INTO {$this->_table_name} (`email`, `password`, `salt`) VALUES (?,?,?)");
                $query->bindParam(1, $username, PDO::PARAM_STR);
                $query->bindParam(2, $hashedPassword, PDO::PARAM_STR);
                $query->bindParam(3, $salt, PDO::PARAM_STR);
                $query->execute();
                if ($query->rowCount() === 1) {
                    return $this->_db->lastInsertId();
                }
            } catch (PDOException $ex) {
                controller()->addError($ex->getMessage() . " [LI103]");
            }
        }
        return false;
    }

    public function setResetToken($username)
    {
        if (!empty($username)) {
            try {
                $reset_key = $this->_newResetToken();
                if(!$reset_key){
                    throw new Exception("Sorry, there was a problem. Please refresh the page and try again.");
                }
                $query = $this->_db->prepare("UPDATE {$this->_table_name} SET `resetToken`=? WHERE `email`=?");
                $query->bindParam(1, $reset_key, PDO::PARAM_STR);
                $query->bindParam(2, $username, PDO::PARAM_STR);
                $query->execute();
                if($query->rowCount() === 1){
                    return $reset_key;
                } else {
                    controller()->addError($query->rowCount() . " rows were affected");
                }
            } catch (PDOException $ex) {
                controller()->addError($ex->getMessage() . " [LI102]");
            } catch (Exception $ex) {
                controller()->addError($ex->getMessage() . " [LI101]");
            }
        }
        return false;
    }

    public function setPassword($username, $password, $reset_key = null){
        if(!empty($username) && !empty($password)){
            try {
                $salt = sha1(microtime());
                $hashedPassword = $this->_hashPassword($password, $salt);
                $null1 = null;
                $null2 = null;
                $query = $this->_db->prepare("UPDATE {$this->_table_name} SET `resetToken`=?, `password`=?, `salt`=? WHERE `email`=? AND `resetToken`=?");
                $query->bindParam(1, $null1, PDO::PARAM_NULL);
                $query->bindParam(2, $hashedPassword, PDO::PARAM_STR);
                $query->bindParam(3, $salt, PDO::PARAM_STR);
                $query->bindParam(4, $username, PDO::PARAM_STR);
                if(empty($reset_key)) {
                    $query->bindParam(5, $null2, PDO::PARAM_NULL);
                } else {
                    $query->bindParam(5, $reset_key, PDO::PARAM_STR);
                }
                $query->execute();
                if($query->rowCount() === 1){
                    return true;
                }
            } catch (PDOException $ex) {
                controller()->addError($ex->getMessage() . " [LI106]");
            }
        }
        return false;
    }

    public function getByResetToken($token)
    {
        if(!empty($token)){
            try {
                $query = $this->_db->prepare("SELECT `id`, `email` FROM {$this->_table_name} WHERE `resetToken`=?");
                $query->bindParam(1, $token, PDO::PARAM_STR);
                $query->execute();
                if ($query->rowCount() !== 0) {
                    $user = $query->fetch(PDO::FETCH_ASSOC);
                    return array('email' => $user['email'], 'id' => $user['id']);
                }
            } catch (PDOException $ex) {
                controller()->addError($ex->getMessage() . " [LI107]");
            }
        }
        return false;
    }

    protected function _getSalt($username)
    {
        try {
            $query = $this->_db->prepare("SELECT `salt` FROM {$this->_table_name} WHERE `email`=?");
            $query->bindParam(1, $username, PDO::PARAM_STR);
            $query->execute();
            if ($query->rowCount() === 1) {
                $salt = $query->fetch(PDO::FETCH_ASSOC);
                return $salt['salt'];
            }
        } catch (PDOException $ex) {
            controller()->addError($ex->getMessage() . " [LI104]");
        }
        return false;
    }

    protected function _hashPassword($password, $salt)
    {
        if (!empty($password) && !empty($salt)) {
            return hash_hmac("sha256", $password, $salt);
        }
        return false;
    }

    protected function _newResetToken()
    {
        return md5(microtime());
    }
}