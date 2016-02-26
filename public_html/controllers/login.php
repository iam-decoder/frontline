<?php

class Login extends Controller
{
    protected
        $_auth_failed_message = 'Invalid email/password combination.',
        $_auth_failed_field = 'username',
        $_loginModel;

    public function __construct()
    {
        parent::__construct();
        $this->_auto_render = false;
    }

    public function handleRequest()
    {
        if ($this->_validateRequest()) {
            $this->LogUserIn($this->_request->post('username'), $this->_request->post('password'));
        } else {
            $this->renderErrors();
        }
    }

    protected function LogUserIn($username, $password)
    {
        $password_text = crypto()->publicDecrypt($password);
        if($password_text === false) {
            $this->addError($this->_auth_failed_message, $this->_auth_failed_field);
            $this->renderErrors();
        } elseif(empty($username)) {
            $this->addError($this->_auth_failed_message, $this->_auth_failed_field);
            $this->renderErrors();
        } else {
            $this->_loginModel = loadModel("login");
            if($this->_loginModel !== false) {
                $user_id = $this->_loginModel->authenticate($username, $password_text);
                if ($user_id !== false) {
                    session()->setData('logged_in', true)->setData('userId', $user_id)->setData('username', $username);
                    echo $this->getContent("page/body");
                    return true;
                } else {
                    if($this->hasErrors()){
                        $this->renderErrors();
                    }
                    $this->addError($this->_auth_failed_message, $this->_auth_failed_field);
                    $this->renderErrors();
                }
            }
        }
        $this->addError("Something went wrong, please try again.");
        $this->renderErrors(500);
    }

    protected function _validateRequest()
    {
        if (!$this->_request->isAjax() || !$this->_request->isPost()) {
            $this->addError("Invalid Request");
            return false;
        }
        if (!$this->_request->post('username')){
            $this->addError("Required Field", 'username');
        }
        if (!$this->_request->post('password')){
            $this->addError("Required Field", 'password');
        }
        if($this->hasErrors()) {
            return false;
        }
        return true;
    }
}
