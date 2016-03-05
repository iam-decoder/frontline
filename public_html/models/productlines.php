<?php

loadLibrary('tabledata', false);

class Productlines_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "productlines";
        $this->_searchable = array(
            "productline" => "productLine",
            "textdescription" => "textDescription",
            "htmldescription" => "htmlDescription",
            "image" => "image"
        );
        $this->_addAllowableFields(array('*'));
    }
}