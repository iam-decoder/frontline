<?php

class Session
{
    protected
        $_session_id,
        $_session_path,
        $_cookie_data = array(),
        $_session_file_ext = ".sess",
        $_cookie = array(
        'domain' => null,
        'path' => "/",
        'life' => 86400,
        'name' => "frontline",
        'transfer_suffix' => "_crc",
        'httpsonly' => false,
        'webreqonly' => true
    ),
        $_csrf = array(
        'token_name' => 'fes_token'
    ),
        $_flags = array(
        "startOnConstruct" => true,
        "saveOnDestruct" => true,
        "csrfValidation" => true
    );

    public function __construct($flags = null)
    {
        if (is_array($flags)) {
            $this->_flags = array_merge($this->_flags, $flags);
        }
        $this->_session_path = ROOTPATH . "sessions/";
        $this->_getSessionId();
        if ($this->_flags['startOnConstruct']) {
            $this->start();
        }
    }

    public function start()
    {
        if ($this->_session_id !== false) {
            $this->_readSession();
            if (!$this->_verifyIp()) {
                $this->refresh();
            }
            if (request()->isGet()) {
                $this->_newCsrfToken();
            } else {
                $this->_validateCsrf();
            }
        }
        return $this;
    }

    public function setData($key, $value)
    {
        $this->_cookie_data[$key] = $value;
        return $this;
    }

    public function getData($key)
    {
        if (array_key_exists($key, $this->_cookie_data)) {
            return $this->_cookie_data[$key];
        }
        return null;
    }

    public function unsetData($key)
    {
        unset($this->_cookie_data[$key]);
        return $this;
    }

    public function save()
    {
        $this->_saveSession();
    }

    public function refresh()
    {
        $this->_cookie_data = array('sessionIp' => request()->ip());
        $this->_destroyCookie();
        $this->_session_id = $this->_newSessionId();
        $this->_newCsrfToken()->_createCookie(true);
        return $this;
    }

    public function destroy()
    {
        $this->_cookie_data = array('sessionIp' => request()->ip());
        $this->_destroyCookieAndFile();
        $this->_session_id = $this->_newSessionId();
        $this->_newCsrfToken()->_createCookie(true);
        return $this;
    }

    public function getCsrfToken()
    {
        if (!empty($this->_cookie_data['csrf'])) {
            return array("name" => $this->_csrf['token_name'], "value" => $this->_cookie_data['csrf']);
        } else {
            return false;
        }
    }

    protected function _newCsrfToken()
    {
        if($this->_flags['csrfValidation']) {
            $this->_cookie_data['csrf'] = hash_hmac("sha256", $this->_newSessionId(), microtime());
        }
        return $this;
    }

    protected function _validateCsrf()
    {
        if($this->_flags['csrfValidation']) {
            $form_csrf = request()->post($this->_csrf['token_name']);
            if (isset($this->_cookie_data['csrf']) && !empty($form_csrf)) {
                if ($this->_cookie_data['csrf'] === $form_csrf) {
                    request()->unsetData($this->_csrf['token_name'], 'post');
                    return $this;
                }
            }
            if (!headers_sent()) {
                header("HTTP/1.1 400 Bad Request");
            }
            die("Invalid Request. [CS404]");
        }
        return $this;
    }

    protected function _getSessionId()
    {
        if (array_key_exists($this->_cookie['name'] . $this->_cookie['transfer_suffix'], $_COOKIE)
            && !empty($_COOKIE[$this->_cookie['name'] . $this->_cookie['transfer_suffix']])
        ) {
            $this->_session_id = $_COOKIE[$this->_cookie['name'] . $this->_cookie['transfer_suffix']];
            $this->_destroyCookie(true);
            $this->_createCookie();
        } elseif (array_key_exists($this->_cookie['name'], $_COOKIE) && !empty($_COOKIE[$this->_cookie['name']])) {
            $this->_session_id = $_COOKIE[$this->_cookie['name']];
        } else {
            $this->_session_id = $this->_newSessionId();
        }
        return $this;
    }

    protected function _newSessionId()
    {
        $proposed = md5(microtime());
        return file_exists($this->_session_path . $proposed . $this->_session_file_ext) ? $this->_newSessionId() : $proposed; //ensure that no sessions can collide.
    }

    protected function _verifyIp()
    {
        if (!empty($this->_cookie_data) && !array_key_exists('sessionIp', $this->_cookie_data)) {
            return false;
        }
        return $this->_cookie_data['sessionIp'] === request()->ip();
    }

    protected function _readSession()
    {
        $ip_address = request()->ip();
        $contents = '{"sessionIp": "' . $ip_address . '"}'; //only set the ip address on a new session.
        if (is_string($this->_session_id)
            && !empty($this->_session_id)
            && file_exists($this->_session_path . $this->_session_id . $this->_session_file_ext)
            && is_readable($this->_session_path . $this->_session_id . $this->_session_file_ext)
        ) {
            $fh = fopen($this->_session_path . $this->_session_id . $this->_session_file_ext, "r");
            $contents = fread($fh,
                filesize($this->_session_path . $this->_session_id . $this->_session_file_ext));
            fclose($fh);
        }
        $this->_cookie_data = json_decode($contents, true);
        return $this;
    }

    protected
    function _saveSession()
    {
        if (!is_writable($this->_session_path)) {
            throw new Exception("Can't write to {$this->_session_path}");
        }
        $fh = fopen($this->_session_path . $this->_session_id . $this->_session_file_ext, "w");
        fwrite($fh, json_encode($this->_cookie_data));
        fclose($fh);

        if (!isset($_COOKIE[$this->_cookie['name'] . $this->_cookie['transfer_suffix']]) && !isset($_COOKIE[$this->_cookie['name']])) {
            $this->_createCookie();
        }
        return $this;
    }

    protected
    function _createCookie(
        $transfer_cookie = false
    ) {
        return setcookie(
            $transfer_cookie ? $this->_cookie['name'] . $this->_cookie['transfer_suffix'] : $this->_cookie['name'],
            $this->_session_id,
            (time() + $this->_cookie['life']),
            $this->_cookie['path'],
            $this->_cookie['domain'],
            $this->_cookie['httpsonly'],
            $this->_cookie['webreqonly']
        );
    }

    protected
    function _destroyCookie(
        $transfer_cookie = false
    ) {
        return setcookie($transfer_cookie ? $this->_cookie['name'] . $this->_cookie['transfer_suffix'] : $this->_cookie['name'],
            '', time() - 3600);
    }

    protected
    function _destroyFile()
    {
        $unlinked = true;
        if (file_exists($this->_session_path . $this->_session_id . $this->_session_file_ext)) {
            $unlinked = unlink($this->_session_path . $this->_session_id . $this->_session_file_ext);
        }
        return $unlinked;
    }

    protected
    function _destroyCookieAndFile()
    {
        return ($this->_destroyCookie() && $this->_destroyFile());
    }

    public
    function __destruct()
    { //ensure that we always save the session, even if exiting or dieing.
        if ($this->_flags['saveOnDestruct']) { //unless this is set...
            $this->_saveSession();
        }
    }
}