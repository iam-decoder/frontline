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
        if (!$this->isLoggedIn()) {
            redirect("/");
        }
        session()->destroy()->flashData("success_message", "You have successfully logged out.")->handleFlash();
        echo $this->getContent("page/body");
        return true;
    }
}
