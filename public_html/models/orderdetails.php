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
                'orderNumber',
                'prd.productName',
                'quantityOrdered as Quantity',
                'priceEach',
                'orderLineNumber'
            ))
            ->_addJoin('products', 'prd', "prd.productCode = main.productCode");
    }
}