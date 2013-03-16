<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class Translator
    {
        private static $data = array();
        private static $lang = 'en';
        
        public static function load($lang, array $strs)
        {
            if (! is_string($lang)) {
                return false;
            }
            
            if (empty(self::$data[$lang])) {
                self::$data[$lang] = array();
            }
            
            self::$data[$lang] = array_merge(self::$data[$lang], $strs);
        }
        
        public static function setLang($lang)
        {
            return self::$lang = $lang;        
        }
        
        public static function translate($key, array $vars = array(), $lang = "")
        {
            if (empty($lang)) {
                $lang = self::$lang;
            }
            if (isset(self::$data[$lang], self::$data[$lang][$key])) {
                $str = self::$data[$lang][$key];
                $keys = array_keys($vars);
                $i = 0;
                while (strpos($str, '%') !== false && $i < count($keys)) {
                    $str = str_replace("%".$keys[$i]."%", $vars[$keys[$i]], $str);
                    $i++;
                }
            }
            if (empty($str)) {
                $str = " [$lang:$key] ";
            }
            return $str;
        }
            
        public static function import($name, $lang = "")
        {
            if (empty($lang)) {
                $lang = self::$lang;
            }
            $path = LANG_PATH . $lang . DS . $name . '.php';
            if (is_readable($path)) {
                include_once($path);
                return true;
            }
            return false;
        }
    }