<?php

class Model {

    protected
        $_db = null;

    public function __construct()
    {
        try {
            $this->_db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER,
                DB_PASS);
        } catch(PDOException $ex) {
            echo $ex->getMessage();
            die;
        }
    }

    public function getTableContents()
    {
        if(!empty($this->_table_name)) {
            try {
                //connect as appropriate as above
                $query = $this->_db->query("SELECT * FROM {$this->_table_name}");
                return $query->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $ex) {
                controller()->addError($ex->getMessage());
            }
        }
        return false;
    }
}