<?php

class Home extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->_setContentFile("page/home");
    }

    public function handleRequest()
    {
        parent::handleRequest();
    }
}