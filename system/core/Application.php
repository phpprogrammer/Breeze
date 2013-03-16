<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
        
    final class Application
    {
        public static $version;
        public static $author;	
        public static $db;
        public static $memory;
        public static $router;
        public static $statistic;
        public static $timers = array();
        public static $userAgent;
        public static $view;
        
        public static function loadController($name = "", $act = "", $vars = array())
        {
            $q = str_replace(DS, "\\", rtrim(substr(CTRL_PATH, strlen(ROOT)), DS) . DS . ltrim($name, DS));
            $target = new $q($vars, $name, $act);
            
            if (method_exists($target, '_broadcast'))
                $target->_broadcast($vars);
            if (method_exists($target, $act))
                $target->$act($vars);
            else
                return new Error('404');
            
            return $target;
        }
        
        public static function loadModel($name)
        {
            
        }
        
        public static function loadUtility($name = "", $act = "", $vars = array())
        {
            $q = str_replace(DS, "\\", rtrim(substr(UTL_PATH, strlen(ROOT)), DS) . DS . ltrim($name, DS));
            $target = new $q($vars);
            
            if (method_exists($target, $act))
                $target->$act($vars);
            else
                return new Error('404');
            return $target;
        }
        
        public static function controllerExist($name, $path = CTRL_PATH)
        {
            return is_readable(rtrim($path, DS).DS.trim($name, DS).'.php');
        }
        public static function utilityExist($name, $path = UTL_PATH)
        {
            return is_readable(rtrim($path, DS).DS.trim($name, DS).'.php');
        }
    }