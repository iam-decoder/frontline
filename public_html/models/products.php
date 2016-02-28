<?php

loadLibrary('tabledata', false);

class Products_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "products";
        $this->_addAllowableFields(array('*'));
    }
}