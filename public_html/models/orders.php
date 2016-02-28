<?php

loadLibrary('tabledata', false);

class Orders_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "orders";
        $this
            ->_addAllowableFields(array(
                'orderNumber',
                'orderDate',
                'requiredDate',
                'shippedDate',
                'status',
                'comments',
                'cst.customerName as Customer'
            ))
            ->_addJoin('customers', 'cst', "cst.customerNumber = main.customerNumber");
    }
}