<?php

loadLibrary('tabledata', false);

class Products_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "products";
        $this->_addAllowableFields(array(
            'productCode as "code"',
            'productName as "name"',
            'productLine as "line"',
            'productScale as "scale"',
            'productVendor as "vendor"',
            'productDescription as "description"',
            'quantityInStock as "quantity"',
            'buyPrice as "price"',
            'MSRP as "msrp"'
        ));
    }
}