<?php

loadLibrary('tabledata', false);

class Offices_Model extends Tabledata_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->_table_name = "offices";
        $this
            ->_addAllowableFields(array(
                'phone',
                'addressLine1',
                'addressLine2',
                'city',
                'state',
                'postalCode',
                'country',
                'territory'
            ));
    }
}