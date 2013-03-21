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
    
    App::$version = '0.1.2 α';
    App::$author = 'Tomasz Sapeta';
    
    App::$db = new Database();
    App::$router = new Router();
    App::$view = new View();
    App::$security = new Security();
    App::$userAgent = new UserAgent();
    App::$statistic = new Statistic();
    App::$log = new Log(LOGS_PATH.'framework.log');
    
    Session::start();

App::$log->write('dupa', 'info');
    
    if (App::$memory->get('anti_flooding_filter') === true) {
        App::$security->antiFloodingFilter();
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
    
    if ($params['utility'] !== true) {
        App::$view->path(VIEW_PATH, STYLE_PATH . App::$memory->get('style') . DS);
        App::loadController($params['controller'], $params['action'], $params['vars']);
    } else {
        define('SELF', UTL_PATH . rtrim($params['location'], DS) . DS);
        App::$view->path(SELF . 'views' . DS);
        App::loadUtility($params['controller'], $params['action'], $params['vars']);
        $params['controller'] = String::leaveRoot($params['controller']);
    }
    
    Session::save();

    App::$view->expose($params['controller'].'_'.$params['action']);
    
    ob_end_flush();