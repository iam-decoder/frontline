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
        'httpsonly' => false,
        'webreqonly' => true
    ),
        $_flags = array(
        "startOnConstruct" => true,
        "saveOnDestruct" => true
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

    public function generateToken()
    {
        return $this->_newFormToken();
    }

    public function save()
    {
        $this->_saveSession();
    }

    public function destroy()
    {
        $this->_cookie_data = array();
        return $this;
    }

    protected function _newFormToken()
    {
        //generateSomethingRandom
        $token_string = "test";

        return crypto()->encrypt($token_string);
    }

    protected function _getSessionId()
    {
        $this->_session_id = array_key_exists($this->_cookie['name'],
            $_COOKIE) ? $_COOKIE[$this->_cookie['name']] : $this->_newSessionId();
        return $this;
    }

    protected function _newSessionId()
    {
        return md5(microtime());
    }

    protected function _readSession()
    {
        $contents = "{}";
        if (is_string($this->_session_id) && !empty($this->_session_id)) {
            if (file_exists($this->_session_path . $this->_session_id . $this->_session_file_ext)) {
                if (is_readable($this->_session_path . $this->_session_id . $this->_session_file_ext)) {
                    $fh = fopen($this->_session_path . $this->_session_id . $this->_session_file_ext, "r");
                    $contents = fread($fh,
                        filesize($this->_session_path . $this->_session_id . $this->_session_file_ext));
                    fclose($fh);
                }
            }
        }
        $this->_cookie_data = json_decode($contents, true);
        return $this;
    }

    protected function _saveSession()
    {
        if (!is_writable($this->_session_path)) {
            throw new Exception("Can't write to {$this->_session_path}");
        }
        $fh = fopen($this->_session_path . $this->_session_id . $this->_session_file_ext, "w");
        fwrite($fh, json_encode($this->_cookie_data));
        fclose($fh);

        if (!isset($_COOKIE[$this->_cookie['name']])) {
            setcookie(
                $this->_cookie['name'],
                $this->_session_id,
                (time() + $this->_cookie['life']),
                $this->_cookie['path'],
                $this->_cookie['domain'],
                $this->_cookie['httpsonly'],
                $this->_cookie['webreqonly']
            );
        }
        return $this;
    }

    public function __destruct()
    {
        if ($this->_flags['saveOnDestruct']) {
            $this->_saveSession();
        }
    }
}