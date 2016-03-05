<?php

function splitCurrentUri()
{
    return array_values(array_filter(explode("/", request()->uri())));
}

function loadController($default)
{
    $uri = splitCurrentUri();
    if (!empty($uri) && file_exists(CONTROLLERPATH . $uri[0] . ".php")) {
        require_once(CONTROLLERPATH . $uri[0] . ".php");
        $controller = ucfirst($uri[0]);
        return new $controller();
    } elseif (empty($uri)) {
        require_once(CONTROLLERPATH . "$default.php");
        $controller = ucfirst($default);
        return new $controller();
    } else {
        if (!headers_sent()) {
            header("HTTP/1.1 404 Not Found");
            require(VIEWPATH . "errors/404.phtml");
            die;
        }
    }
    return false;
}

function loadLibrary($libname, $autoload = true, $globalname = null)
{
    if (!empty($libname) && is_string($libname)) {
        $libname = strtolower(substr($libname, -4) === ".php" ? substr($libname, 0, -4) : $libname);
        if (file_exists(LIBPATH . "$libname.php")) {
            require_once(LIBPATH . "$libname.php");
            $className = ucfirst($libname);
            if ($autoload === true) {
                $GLOBALS[(empty($globalname) ? $libname : $globalname)] = new $className();
            }
            return true;
        }
    }
    return false;
}

function loadModel($modelname)
{
    if (!empty($modelname) && is_string($modelname)) {
        $modelname = strtolower(substr($modelname, -4) === ".php" ? substr($modelname, 0, -4) : $modelname);
        if (file_exists(MODELPATH . "$modelname.php")) {
            require_once(MODELPATH . "$modelname.php");
            $className = ucfirst($modelname) . "_Model";
            return new $className();
        }
    }
    return false;
}

function xss()
{
    return $GLOBALS['xss'];
}

function request()
{
    return $GLOBALS['request'];
}

function session()
{
    return $GLOBALS['session'];
}

function crypto()
{
    return $GLOBALS['crypto'];
}

function controller()
{
    return $GLOBALS['controller'];
}

function redirect($url)
{
    session()->save();
    header("Location: $url");
    die;
}