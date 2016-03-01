<?php

class Controller
{
    protected
        $_request,
        $_session,
        $_errors = array(),
        $_http_errors = array(
        400 => "400 Bad Request",
        500 => "500 Internal Server Error"
    ),
        $_auto_render = true,
        $_content_file = null,
        $_show_login_form = true;

    public function __construct()
    {
        $this->_request = request();
        $this->_session = session();
    }

    public function handleRequest()
    {

    }

    public function renderView()
    {
        echo $this->getContent("page");
    }

    public function renderErrors($status_code = 400)
    {
        if (!empty($this->_errors)) {
            if (!headers_sent()) {
                if (!array_key_exists($status_code, $this->_http_errors)) {
                    $status_code = 500;
                }
                header("HTTP/1.1 {$this->_http_errors[$status_code]}");
            }
            if ($this->_request->isAjax()) {
                echo json_encode($this->_errors);
            } else {
                $html = "";
                foreach ($this->_errors as $i => $error) {
                    if ($i === 'fields') {
                        foreach ($error as $field => $error_msg) {
                            $html .= "<p>Field '$field': $error_msg</p>";
                        }
                    } else {
                        $html .= "<p>$error</p>";
                    }
                }
                echo $html;
            }
        }
        die;
    }

    public function addError($error_message, $form_field = false)
    {
        if (is_string($error_message)) {
            if ($form_field !== false) {
                if (!array_key_exists('fields', $this->_errors) || !is_array($this->_errors['fields'])) {
                    $this->_errors['fields'] = array();
                }
                if (is_string($form_field)) {
                    $this->_errors['fields'][$form_field] = $error_message;
                }
            } else {
                $this->_errors[] = $error_message;
            }
        }
    }

    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    public function getContent($file = null)
    {
        if ($file === true) {
            $file = $this->_content_file;
        }
        if (!empty($file) && is_string($file)) {
            $file = strtolower(substr($file, -6) === ".phtml" ? $file : $file . ".phtml");
            if (file_exists(VIEWPATH . $file)) {
                ob_start();
                require(VIEWPATH . $file);
                return ob_get_clean();
            }
        }
        return "";
    }

    public function isLoggedIn()
    {
        return $this->_session->getData('logged_in') === true;
    }

    public function showLoginForm($value = null)
    {
        if(is_null($value)) {
            return $this->_show_login_form;
        } else {
            $this->_show_login_form = (bool)$value;
        }
    }

    public function __destruct()
    {
        if ($this->_auto_render) {
            $this->renderView();
        }
    }

    protected function _setContentFile($file = null)
    {
        if (!empty($file) && is_string($file)) {
            $this->_content_file = $file;
            return true;
        }
        return false;
    }
}