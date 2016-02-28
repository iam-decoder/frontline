<?php

class Tabledata extends Controller
{
    protected
        $_dataTableModel,
        $_modelTranslations = array(
        'plns' => "Productlines",
        'prd' => "Products",
        'orddt' => "Orderdetails",
        'flsi' => "Filesindex",
        'emp' => "Employees",
        'cust' => "Customers",
        'ofcs' => "Offices",
        'ord' => "Orders",
        'pmnts' => "Payments"
    );

    public function __construct()
    {
        parent::__construct();
        $this->_auto_render = false;
    }

    public function handleRequest()
    {
        $this->_validateRequest();
        $this->_getTableData($this->_request->get('table'));
    }

    protected function _getTableData($table)
    {
        $table = $this->_getModelName($table);
        $this->_dataTableModel = loadModel($table);
        if (!$this->_dataTableModel) {
            $this->addError("Sorry, something went wrong. Please refresh the page and try again. [M18]");
            $this->renderErrors(500);
        }
        if ($this->hasErrors()) {
            $this->renderErrors();
        }
        echo json_encode($this->_dataTableModel->fetchAllowable());
        die;
    }

    protected function _getModelName($key)
    {
        $key = strtolower($key);
        if (!array_key_exists($key, $this->_modelTranslations)) {
            $this->addError("Sorry, but we couldn't find a data table for that. [TD4]");
            $this->renderErrors(400);
        }
        return $this->_modelTranslations[$key];
    }

    protected function _validateRequest()
    {
        if (!$this->isLoggedIn() || !$this->_request->isAjax() || !$this->_request->isGet()) {
            $this->addError("Invalid Request. [TD1]");
            $this->renderErrors();
        }
        if (!$this->_request->get('table')) {
            $this->addError("A table type is required. [TD2]");
        }
        if ($this->hasErrors()) {
            $this->renderErrors();
        }
        return true;
    }
}