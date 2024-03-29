<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
        
    final class Application
    {
        public static $author;
        public static $db;
        public static $log;
        public static $memory;
        public static $router;
        public static $security;
        public static $timers = array();
        public static $userAgent;
        public static $version;
        public static $codename;
        public static $view;
        
        public static function launchController($name = '', $act = '', $vars = array())
        {
            $q = str_replace(DS, '\\', rtrim(substr(CTRL_PATH, strlen(ROOT)), DS) . DS . ltrim($name, DS));
            $target = new $q($vars, $name, $act);
            
            if (method_exists($target, '_broadcast')) {
                $target->_broadcast($vars);
            }
            if (method_exists($target, $act)) {
                $target->$act($vars);
            } else {
                return new Error('404');
            }
            
            return $target;
        }
        
        public static function launchUtility($name = '', $act = '', $vars = array())
        {
            $q = str_replace(DS, '\\', rtrim(substr(UTL_PATH, strlen(ROOT)), DS) . DS . ltrim($name, DS));
            $target = new $q($vars, $name, $act, true);
            
            if (method_exists($target, '_broadcast')) {
                $target->_broadcast($vars);
            }
            if (method_exists($target, $act)) {
                $target->$act($vars);
            } else {
                return new Error('404');
            }
            return $target;
        }
    }