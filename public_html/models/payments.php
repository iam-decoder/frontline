<?php

loadLibrary('tabledata', false);

class Payments_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "payments";
        $this->_searchable = array(
            "customer" => "cst.customerName",
            "checknumber" => "checkNumber",
            "paidon" => "paymentDate",
            "amount" => "amount"
        );
        $this
            ->_addAllowableFields(array(
                'cst.customerName as "customer"',
                'checkNumber as "checkNumber"',
                'paymentDate as "paidOn"',
                'amount as "amount"'
            ))
            ->_addJoin('customers', 'cst', 'cst.customerNumber = main.customerNumber');
    }
}