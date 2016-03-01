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
                'phone as "phone"',
                'addressLine1 as "addressLine-1"',
                'addressLine2 as "addressLine-2"',
                'city as "city"',
                'state as "state"',
                'postalCode as "postalCode"',
                'country as "country"',
                'territory as "territory"'
            ));
    }
}