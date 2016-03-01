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
        session()->flashData("success_message", "You have successfully logged out.")->handleFlash();
        echo $this->getContent("page/body");
        return true;
    }
}
