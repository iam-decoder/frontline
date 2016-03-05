<?php

class Home extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->isLoggedIn()) {
            $this->_setContentFile("page/home");
        }
    }

    public function handleRequest()
    {
        parent::handleRequest();
    }
}