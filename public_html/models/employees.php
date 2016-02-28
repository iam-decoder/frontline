<?php

loadLibrary('tabledata', false);

class Employees_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "employees";
        $this
            ->_addAllowableFields(array(
                'lastName',
                'firstName',
                'extension',
                'email',
                'off.city',
                'jobTitle as Title'
            ))
            ->_disableIdentifierEscaping()
            ->_addAllowableField("CONCAT(`repto`.`firstName`, ' ', `repto`.`lastName`) as 'Reports To'", true)
            ->_enableIdentifierEscaping()
            ->_addJoin($this->_table_name, 'repto', "repto.employeeNumber = main.reportsTo")
            ->_addJoin('offices', 'off', 'off.officeCode = main.officeCode');
    }
}