<?php

class Logout extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->_auto_render = false;
    }

    public function handleRequest()
    {
        session()->destroy();
        echo $this->getContent("page/body");
        return true;
    }
}
