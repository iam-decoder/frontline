<?php

//turn on error display
if (array_key_exists('DEVELOPER_MODE', $_SERVER)) {
    define("DEVELOPER", true);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL | E_STRICT);
} else {
    define("DEVELOPER", false);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(-1);
}

//path constants
define('ROOTPATH', dirname(__DIR__) . "/");
define('BASEPATH', __DIR__ . "/");
define('VIEWPATH', BASEPATH . "views/");
define('HELPERPATH', BASEPATH . "helpers/");
define('LIBPATH', BASEPATH . "libraries/");
define('CONTROLLERPATH', BASEPATH . "controllers/");
define('MODELPATH', BASEPATH . "models/");

//database constants
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'classicmodels');
define('DB_CHARSET', 'utf8mb4');
define('DB_USER', 'root');
define('DB_PASS', '');


//Standard files loaded on all requests
require_once(HELPERPATH . "functions.php");

//Standard classes loaded on all requests
loadLibrary("encryption", true, "crypto");
loadLibrary("xss"); //needs to run before request
loadLibrary("request"); //needs to run before session
loadLibrary("session");
loadLibrary("controller", false);
loadLibrary("model", false);

//run the request
$GLOBALS['controller'] = loadController("home");
controller()->handleRequest();



//TODO: link together records that relate - if necessary, will add. can be circumvented by new search functionality.
//TODO: "loading..." screen/modal thing. - on hold, retrieval is fast enough that this isn't needed yet.