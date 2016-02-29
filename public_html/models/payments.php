<?php

loadLibrary('tabledata', false);

class Payments_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "payments";
        $this
            ->_addAllowableFields(array('cst.customerName as Customer', 'checkNumber', 'paymentDate', 'amount'))
            ->_addJoin('customers', 'cst', 'cst.customerNumber = main.customerNumber');
    }
}