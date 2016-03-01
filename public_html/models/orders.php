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
                'orderNumber as "orderNumber"',
                'orderDate as "orderedOn"',
                'requiredDate as "requiredBy"',
                'shippedDate as "shippedOn"',
                'status as "status"',
                'comments as "comments"',
                'cst.customerName as "customer"'
            ))
            ->_addJoin('customers', 'cst', "cst.customerNumber = main.customerNumber");
    }
}