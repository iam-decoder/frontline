<?php

class Reset extends Controller
{
    protected
        $_key_verified = false,
        $_username = null,
        $_current_token = null,
        $_loginModel,
        $_use_email = false;

    public function __construct()
    {
        parent::__construct();
        $this->_auto_render = false;
        $this->showLoginForm(false);
    }

    public function handleRequest()
    {
        if ($this->isLoggedIn()) {
            redirect("/");
        }
            if ($this->_request->isGet()) {
                $this->show();
            } elseif ($this->_request->isAjax() && $this->_request->isPost()) {
                $this->resetProcess();
            }
    }

    public function keyVerified($value = null){
        if(is_null($value)) {
            return $this->_key_verified;
        } else {
            $this->_key_verified = (bool)$value;
        }
    }

    public function getUsername()
    {
        return $this->_username;
    }

    public function getCurrentToken()
    {
        return $this->_current_token;
    }

    protected function show()
    {
        $reset_key = $this->_request->get("rKey", true);
        if(!empty($reset_key)){
            $this->_loginModel = loadModel("login");
            $user_name = $this->_loginModel->getByResetToken($reset_key);
            if(!empty($user_name) && isset($user_name['email']) && !empty($user_name['email'])){
                $this->keyVerified(true);
                $this->_username = $user_name['email'];
                $this->_current_token = $reset_key;
            }
            $this->_setContentFile("page/reset");
            echo $this->getContent("page");
            die;
        }
        if($this->_request->isAjax()) {
            $this->_setContentFile("page/reset");
            echo $this->getContent("page/body");
            die;
        }
        $this->addError("Invalid request. [R1]");
        $this->renderErrors();
    }

    protected function resetProcess()
    {
        $username = $this->_request->post("username", true);
        $password = $this->_request->post("password", true);
        $token = $this->_request->post("token", true);

        $this->_loginModel = loadModel("login");

        if(empty($password) && empty($token)){ //then they must only be on the first part where they tell us their email.
            if(!empty($username)){
                $reset_key = $this->_loginModel->setResetToken($username);
                if($this->hasErrors()){
                    $this->renderErrors();
                }
                if($this->_use_email) {
                    if($reset_key !== false) {
                        mail($username, "Password Reset - FES Evaluation",
                            "Please use this link to reset your password: " . request()->baseUrl() . "reset?rKey=$reset_key");
                    }
                    session()->flashData("success_message",
                        "If there's an account for {$username}, then you should receive an email at that address shortly with a link to reset your password.");
                } else {
                    if($reset_key !== false){
                        session()->flashData("success_message", 'Since this is an evaluation app, please use <a href="' . request()->baseUrl() . 'reset?rKey=' . $reset_key . '">this link</a> to reset your password');
                    } else {
                        session()->flashData("error_message", 'Since this is an evaluation app, this is to inform you that there is no email address of <b>' . $username . '</b> in the database.');
                    }
                }
                redirect("/");
            } else {
                $this->addError("This is a required field.", "username");
                $this->renderErrors();
            }
        } else {
            if(empty($username) || empty($token)){
                $this->addError("Something went wrong, please refresh the page and try again. [R7]");
            }
            if(empty($password)){
                $this->addError("This is a required field.", "password");
            }
            if($this->hasErrors()){
                $this->renderErrors();
            }
            $user = $this->_loginModel->getByResetToken($token);
            $result = $this->_loginModel->setPassword($username, crypto()->publicDecrypt($password), $token);
            if($this->hasErrors()){
                $this->renderErrors();
            }
            if($result === true && is_array($user) && isset($user['id']) && !empty($user['id'])){ //try to skip the login form
                session()->setData('logged_in', true)->setData('userId', $user['id'])->setData('username', $username)->flashData("success_message", "Your password has been changed and you have been logged in.");
                echo $this->_request->baseUrl();
                die;
            } elseif($result === true) {
                session()->flashData("success_message", "Your password has been changed, please log in below.");
                echo $this->_request->baseUrl();
                die;
            }
            $this->addError("Something went wrong, please refresh the page and try again. [R8]");
            $this->renderErrors();
        }
    }
}