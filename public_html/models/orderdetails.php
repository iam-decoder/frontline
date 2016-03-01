<?php

loadLibrary('tabledata', false);

class Orderdetails_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "orderdetails";
        $this
            ->_addAllowableFields(array(
                'orderNumber as "orderNumber"',
                'prd.productName as "productName"',
                'quantityOrdered as "quantity"',
                'priceEach as "priceEach"',
                'orderLineNumber as "orderLine"'
            ))
            ->_addJoin('products', 'prd', "prd.productCode = main.productCode");
    }
}