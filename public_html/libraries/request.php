<?php

/**
 * Class Request
 *
 * @author Travis Neal
 */
class Request
{
    protected

        /**
         * Stores the raw php://input string for retrieving quicker.
         *
         * @var string
         */
        $_input,

        /**
         * Holds values received in GET requests.
         *
         * @var array
         */
        $_get = array(),

        /**
         * Holds values received in POST requests.
         *
         * @var array
         */
        $_post = array(),

        /**
         * Holds values received in PUT requests.
         *
         * @var array
         */
        $_put = array();


    /**
     * Request constructor.
     *
     * Reads the php://input for use in parsing out POST and PUT request data.
     */
    public function __construct()
    {
        $this->_input = file_get_contents("php://input");
        $this->_populateParameters();
    }

    /**
     * Returns whether the request was made through an AJAX medium.
     *
     * @return bool
     */
    public function isAjax()
    {
        // TODO: generate tokens with a lifespan that get passed back and forth on each request to the server.
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) === "XMLHTTPREQUEST");
    }

    /**
     * Returns the type of the current request.
     *
     * ex. "GET"
     * ex. "POST"
     * ex. "PUT"
     *
     * @return string
     */
    public function method()
    {
        //always uppercase, default to get
        return strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "GET");
    }

    /**
     * Returns if the current request is a POST request.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->method() === "POST";
    }

    /**
     * Returns if the current request is a GET request.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->method() === "GET";
    }


    /**
     * Returns if the current request is a PUT request.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->method() === "PUT";
    }

    /**
     * Returns the domain used for current request if the server is not named.
     *
     * @return bool
     */
    public function host()
    {
        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];   //Can be change from client headers.
        }
        return null;
    }

    /**
     * Returns whether the current request was made under a secure socket layer.
     *
     * @return bool|null
     */
    public function ssl()
    {
        if (isset($_SERVER['HTTPS'])) {
            return !empty($_SERVER['HTTPS']);
        } elseif (isset($_SERVER['SERVER_PORT'])) {
            return (string)$_SERVER['SERVER_PORT'] === '443';
        } elseif (isset($_SERVER['REQUEST_SCHEME'])) {
            return strtolower($_SERVER['REQUEST_SCHEME']) === 'https';
        }
        return null;
    }

    /**
     * Returns the part of the url after the domain name, but before the query string.
     *
     * @return null|string
     */
    public function uri()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $questionMarkIndex = strpos($_SERVER['REQUEST_URI'], '?');
            if ($questionMarkIndex !== false) {
                return rtrim(substr($_SERVER['REQUEST_URI'], 0, $questionMarkIndex), '/');
            }
            return rtrim($_SERVER['REQUEST_URI'], '/');
        }
        return null;
    }

    /**
     * Returns the query string of the current request.
     *
     * @return null|string
     */
    public function query()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
    }

    /**
     * Returns the protocol and domain name of the current request.
     *
     * @return null|string
     */
    public function baseUrl()
    {
        $host = $this->host();
        if (!empty($host)) {
            return "http" . ($this->ssl() ? "s" : "") . "://$host/";
        }
        return null;
    }

    /**
     * Returns the full url of the request without the querystring.
     *
     * @return null|string
     */
    public function url()
    {
        $base = $this->baseUrl();
        $uri = $this->uri();
        if (!empty($base) && !empty($uri)) {
            return rtrim($base, '/') . $uri;
        }
        return null;
    }

    /**
     * Returns the full url of the current request including protocol, domain name, uri, and query string.
     *
     * @return null|string
     */
    public function fullUrl()
    {
        $url = $this->url();
        $query_string = $this->query();
        if (!empty($url) && $this->isGet() && !empty($query_string)) {
            return rtrim($url, '/') . "?$query_string";
        } elseif (!empty($url) && $this->isGet()) { // in case server fills querystring on POST requests as well.
            return $url;
        }
        return null;
    }

    /**
     * Returns the ip address used to make the request.
     *
     * @return null|string
     */
    public function ip()
    {
        // Don't use HTTP_X_FORWARDED_FOR or HTTP_CLIENT_IP as they can be changed by the client.
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
     * Returns the parameter by name of the current request.
     *
     * Using a cascading style, finds the value of the $path passed in.
     *
     * If no $path is supplied, then the entire array of values parsed in from a GET request is returned.
     *
     * @param null|string|array $path
     * @return array|string|null
     */
    public function get($path = null, $clean_xss = true)
    {
        if (is_string($path)) {
            $path = preg_replace("/([.|:-])/", "/", $path);
            $path = explode("/", $path);
        }
        if ($clean_xss) {
            return xss()->clean(is_array($path) ? $this->_valueByLevel($this->_get,
                array_values($path)) : $this->_get);
        } else {
            return is_array($path) ? $this->_valueByLevel($this->_put, array_values($path)) : $this->_put;
        }
    }

    /**
     * Returns the parameter by name of the current request.
     *
     * Using a cascading style, finds the value of the $path passed in.
     *
     * If no $path is supplied, then the entire array of values parsed in from a POST request is returned.
     *
     * @param null|string|array $path
     * @return array|string|null
     */
    public function post($path = null, $clean_xss = true)
    {
        if (is_string($path)) {
            $path = preg_replace("/([.|:-])/", "/", $path);
            $path = explode("/", $path);
        }
        if ($clean_xss) {
            return xss()->clean(is_array($path) ? $this->_valueByLevel($this->_post,
                array_values($path)) : $this->_post);
        } else {
            return is_array($path) ? $this->_valueByLevel($this->_put, array_values($path)) : $this->_put;
        }
    }

    /**
     * Returns the parameter by name of the current request.
     *
     * Using a cascading style, finds the value of the $path passed in.
     *
     * If no $path is supplied, then the entire array of values parsed in from a PUT request is returned.
     *
     * @param null|string|array $path
     * @return array|string|null
     */
    public function put($path = null, $clean_xss = true)
    {
        if (is_string($path)) {
            $path = preg_replace("/([.|:-])/", "/", $path);
            $path = explode("/", $path);
        }
        if ($clean_xss) {
            return xss()->clean(is_array($path) ? $this->_valueByLevel($this->_put,
                array_values($path)) : $this->_put);
        } else {
            return is_array($path) ? $this->_valueByLevel($this->_put, array_values($path)) : $this->_put;
        }
    }

    /**
     * Removes data sent with the request, usually used when removing things like Csrf tokens.
     *
     * @param string $key
     * @param null|string $type request type to remove key from
     * @return $this
     */
    public function unsetData($key, $type = null)
    {
        $normalized_type = !empty($type) ? $type : $this->method();
        switch (strtoupper($normalized_type)) {
            case 'GET':
                unset($_GET[$key], $this->_get[$key]);
                break;
            case 'POST':
                unset($_POST[$key], $this->_post[$key]);
                break;
            case 'PUT':
                unset($this->_put[$key]);
                break;
        }
        return $this;
    }

    /**
     * Gets a value from an array in a recursive fashion.
     *
     * @param array $array the array to look through.
     * @param array $path
     * @return mixed|null
     */
    protected function _valueByLevel(&$array, $path)
    {
        if (is_array($path) && !empty($path)) {
            $key = array_shift($path);
            if (array_key_exists($key, $array)) {
                return $this->_valueByLevel($array[$key], $path);
            } else {
                return null;
            }
        }
        return $array;
    }

    /**
     * Fills the parameter holder for the current request type.
     *
     * @return $this
     */
    protected function _populateParameters()
    {
        switch ($this->method()) {
            case 'GET':
                $this->_get = $_GET;
                break;
            case 'POST':
                $this->_post = $this->_parseInput();
                break;
            case 'PUT':
                $this->_put = $this->_parseInput();
                break;
        }
        return $this;
    }

    /**
     * Uses the php://input to parse out query strings passed outside of urls
     *
     * @return array
     */
    protected function _parseInput()
    {
        $input_vars = array();
        parse_str($this->_input, $input_vars);
        return $input_vars;
    }
}