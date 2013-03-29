<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    use \system\core\Application as App;
    
    ini_set('short_open_tag', 1);
    
    App::$memory = new Memory('configuration', DEF_PATH.'configuration.php');
    App::$timers['page_loading'] = new Timer();
    
    if (function_exists('ob_gzhandler') && App::$memory->get('gzip_compression') && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
        ini_set('zlib.output_compression_level', App::$memory->get('gzip_compression_level'));
        ob_start('ob_gzhandler');
    }
    
    App::$version = '0.1.3 α';
    App::$author = 'Tomasz Sapeta';
    
    if (defined('ENVIRONMENT')) {
        switch (ENVIRONMENT) {
            case 'development':
                error_reporting(E_ALL);
                ini_set('display_errors', 1);
                App::$memory->insert('cache_expiry', 1);
                break;
            case 'testing' || 'production':
                error_reporting(0);
                ini_set('display_errors', 0);
                break;
            default:
                exit("Environment isn't set correctly!");
        }
    } else {
        define('ENVIRONMENT', 'production');
    }
    
    $dump_paths = array(
        CONFIG_PATH,
        TEMP_PATH,
        CACHE_PATH,
        LOGS_PATH,
        OPT_PATH
    );
    $error_paths = array();
    foreach ($dump_paths as $path) {
        if ('777' !== substr(sprintf('%o', fileperms($path)), -3)) {
            $error_paths[] = $path;
        }
    }
    if (!empty($error_paths)) {
        new Error('repair_permissions', array('paths' => $error_paths));
    }
    
    App::$db = new Database();
    App::$router = new Router();
    App::$view = new View();
    App::$userAgent = new UserAgent();
    App::$statistic = new Statistic();
    App::$log = new Log(LOGS_PATH.'framework.log');
    
    Session::start();
    App::$security = new Security();
    
    if (App::$memory->get('statistics') === true && App::$router->params['utility'] !== true) {
        App::$statistic->loader();
    }
    
    foreach (App::$memory->get('helpers_autoload') as $value) {
        if(is_readable($path = HELP_PATH.$value.'.php')) {
            require $path;
        }
    }
    
    if (!file_exists(ROOT.'.htaccess') && file_exists(DEF_PATH.'htaccess')) {
        file_put_contents(ROOT.'.htaccess', file_get_contents(DEF_PATH.'htaccess'));
    }
    
    
    $params = App::$router->params;
    
    if ($params['language']) {
        Translator::setLang($params['language']);
        Translator::import('main');
    }

    $controller;
    if ($params['utility'] !== true) {
        App::$view->path(VIEW_PATH);
        $controller = App::loadController($params['controller'], $params['action'], $params['vars']);
    } else {
        define('SELF', UTL_PATH . rtrim($params['location'], DS) . DS);
        App::$view->path(SELF.'views'.DS, SELF.'styles'.DS, SELF.'scripts'.DS);
        $controller = App::loadUtility($params['controller'], $params['action'], $params['vars']);
        $params['controller'] = String::leaveRoot($params['controller']);
    }
    
    App::$db->quit();
    Session::save();

    App::$view->expose($params['controller'].'_'.$params['action'], $controller->_getData());
    
    ob_end_flush();