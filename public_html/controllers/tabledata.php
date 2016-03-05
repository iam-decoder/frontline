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
    ),
        $_table_data = array();

    public function __construct()
    {
        parent::__construct();
        $this->_auto_render = false;
        if (!$this->isLoggedIn()) {
            redirect("/");
        }
    }

    public function handleRequest()
    {
        $this->_validateRequest();
        if ($this->_request->get('table')) {
            if ($this->_request->get('getTemplate') === "true") {
                echo $this->getContent("tables/" . $this->_request->get('table'));
            } else {
                $this->_fetchTableData($this->_request->get('table'));
                if ($this->hasErrors()) {
                    $this->renderErrors();
                }
                echo json_encode($this->getTableData());
            }
        }
        die;
    }

    public function getTableData()
    {
        return $this->_table_data;
    }

    protected function _fetchTableData($table)
    {
        $table_data = array('records' => array());
        $table = $this->_getModelName($table);
        $this->_dataTableModel = loadModel($table);
        if (!$this->_dataTableModel) {
            $this->addError("Sorry, something went wrong. Please refresh the page and try again. [M18]");
            $this->renderErrors(500);
        }

        //queries
        $queries = $this->_request->get("queries", true);
        if (!empty($queries)) {
            foreach ($queries as $type => $query) {
                if (method_exists($this->_dataTableModel, $type . "Query")) {
                    $this->_dataTableModel->{$type . "Query"}($query);
                }
            }
        }

        $table_data['queryRecordCount'] = $this->_dataTableModel->countRows();

        $offset = $this->_request->get("page", true);
        if (is_null($offset)) {
            $offset = 0;
        } elseif ($offset > 0) {
            $offset = ((int)$offset) - 1;
        } else {
            $offset = 0;
        }
        $this->_dataTableModel->setOffset($offset * $this->_request->get("perPage"));

        $limit = $this->_request->get("perPage", true);
        if (is_null($limit)) {
            $limit = null;
        } else {
            $limit = (int)$limit;
        }
        $this->_dataTableModel->setLimit($limit);

        $sorts = $this->_request->get("sorts");
        if (!empty($sorts) && is_array($sorts)) {
            foreach ($sorts as $col => $dir) {
                $this->_dataTableModel->addOrderBy($this->_translateColName($table, $col),
                    (int)$dir === 1 ? 'asc' : 'desc');
            }
        }

        $table_data['records'] = $this->_dataTableModel->fetchAllowable();
        $this->_table_data = $table_data;
        return $this;
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

    protected function _translateColName($table, $dynaId)
    {
        $translations = array(
            'Employees' => array(
                'lastName' => 'lastName',
                'firstName' => 'firstName',
                'extension' => 'extension',
                'email' => 'email',
                'city' => 'off.city',
                'title' => 'jobTitle',
                'reportsTo' => ':reportsTo'
            ),
            'Customers' => array(
                'customer' => 'customerName',
                'contactLastName' => 'contactLastName',
                'contactFirstName' => 'contactFirstName',
                'phone' => 'phone',
                'addressLine-1' => 'addressLine1',
                'addressLine-2' => 'addressLine2',
                'city' => 'city',
                'state' => 'state',
                'postalCode' => 'postalCode',
                'country' => 'country',
                'salesRep' => ':salesRep' //
            ),
            'Offices' => array(
                'phone' => 'phone',
                'addressLine-1' => 'addressLine1',
                'addressLine-2' => 'addressLine2',
                'city' => 'city',
                'state' => 'state',
                'postalCode' => 'postalCode',
                'country' => 'country',
                'territory' => 'territory'
            ),
            'Orderdetails' => array(
                'orderNumber' => 'orderNumber',
                'productName' => 'prd.productName',
                'quantity' => 'quantityOrdered',
                'priceEach' => 'priceEach',
                'orderLine' => 'orderLineNumber'
            ),
            'Orders' => array(
                'orderNumber' => 'orderNumber',
                'orderedOn' => 'orderDate',
                'requiredBy' => 'requiredDate',
                'shippedOn' => 'shippedDate',
                'status' => 'status',
                'comments' => 'comments',
                'customer' => 'cst.customerName'
            ),
            'Payments' => array(
                'customer' => 'cst.customerName',
                'checkNumber' => 'checkNumber',
                'paidOn' => 'paymentDate',
                'amount' => 'amount'
            ),
            'Productlines' => array(//no translations necessary
            ),
            'Products' => array(
                'code' => 'productCode',
                'name' => 'productName',
                'line' => 'productLine',
                'scale' => 'productScale',
                'vendor' => 'productVendor',
                'description' => 'productDescription',
                'quantity' => 'quantityInStock',
                'price' => 'buyPrice',
                'msrp' => 'MSRP'
            ),

        );
        if (array_key_exists($table, $translations)) {
            $table_translation = $translations[$table];
            if (array_key_exists($dynaId, $table_translation)) {
                return $table_translation[$dynaId];
            }
        }
        return $dynaId;
    }

    protected function _validateRequest()
    {
        if (!$this->isLoggedIn() || !$this->_request->isAjax() || !$this->_request->isGet()) {
            $this->addError("Invalid Request. [TD1]");
            $this->renderErrors();
        }
        if ($this->hasErrors()) {
            $this->renderErrors();
        }
        return true;
    }
}