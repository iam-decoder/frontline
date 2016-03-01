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
                'customerName as "customer"',
                'contactLastName as "contactLastName"',
                'contactFirstName as "contactFirstName"',
                'phone as "phone"',
                'addressLine1 as "addressLine-1"',
                'addressLine2 as "addressLine-2"',
                'city as "city"',
                'state as "state"',
                'postalCode as "postalCode"',
                'country as "country"',
            ))
            ->_disableIdentifierEscaping()
            ->_addAllowableField("CONCAT(`emp`.`firstName`, ' ', `emp`.`lastName`) as 'salesRep'", true)
            ->_enableIdentifierEscaping()
            ->_addAllowableFields(array(
                'creditLimit'
            ))
            ->_addJoin('employees', 'emp', "emp.employeeNumber = main.salesRepEmployeeNumber");
    }
}