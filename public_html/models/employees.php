<?php

loadLibrary('tabledata', false);

class Employees_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "employees";
        $this->_searchable = array(
            "employeenumber" => "employeeNumber",
            "lastname" => "lastName",
            "firstname" => "firstName",
            "extension" => "extension",
            "email" => "email",
            "officecode" => "officeCode",
            "reportsto" => "CONCAT(`repto`.`firstName`, ' ', `repto`.`lastName`)",
            "title" => "jobTitle",
            "city" => "off.city"
        );
        $this
            ->_addAllowableFields(array(
                'lastName as "lastName"',
                'firstName as "firstName"',
                'extension as "extension"',
                'email as "email"',
                'off.city as "city"',
                'jobTitle as "title"'
            ))
            ->_disableIdentifierEscaping()
            ->_addAllowableField("CONCAT(`repto`.`firstName`, ' ', `repto`.`lastName`) as 'reportsTo'", true)
            ->_enableIdentifierEscaping()
            ->_addJoin($this->_table_name, 'repto', "repto.employeeNumber = main.reportsTo")
            ->_addJoin('offices', 'off', 'off.officeCode = main.officeCode');
    }
}