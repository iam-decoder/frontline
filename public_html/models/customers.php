<?php

loadLibrary('tabledata', false);

class Customers_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "customers";
        $this
            ->_addAllowableFields(array(
                'customerName',
                'contactLastName',
                'contactFirstName',
                'phone',
                'addressLine1',
                'addressLine2',
                'city',
                'state',
                'postalCode',
                'country',
            ))
            ->_disableIdentifierEscaping()
            ->_addAllowableField("CONCAT(`emp`.`firstName`, ' ', `emp`.`lastName`) as 'Sales Rep'", true)
            ->_enableIdentifierEscaping()
            ->_addAllowableFields(array(
                'creditLimit'
            ))
            ->_addJoin('employees', 'emp', "emp.employeeNumber = main.salesRepEmployeeNumber");
    }
}