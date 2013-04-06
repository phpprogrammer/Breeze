<?php	
    define('DS', DIRECTORY_SEPARATOR);
    define('ROOT', rtrim(dirname(__FILE__), DS) . DS);
    require(ROOT . 'system/defaults/paths.php');
    
    function __autoload($path) {
        $path = ROOT . str_replace('\\', DS, $path) . '.php';
        if(is_readable($path)) {
            require($path);
        }
    }
    use \system\core\String;
    define('DEFAULT_PATH', String::path_wrap(String::rbreak($_SERVER['SCRIPT_NAME'], 'index.php')));
    
    // 'development' || 'testing' || 'production'
    define('ENVIRONMENT', 'development');
    
    // And away we go...
    
    require(CORE_PATH . 'Bootstrap.php');