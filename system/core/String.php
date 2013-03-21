<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class String
    {   
        
        public static function is_path($path = "")
        {
            return substr_count($path, '.') || substr_count($path, DS);
        }
        
        public static function cleanFilename($path = "")
        {
            return str_replace(array('/', '\\', '!', '@', '#', '$', '%', '^', '&', '*', '<', '>', '?'), '', $path);
        }
        
        public static function path_trim_root($path = "", $wrap = 1)
        {
            return self::path_wrap(substr($path, strlen(ROOT)), 1);
        }
        
        public static function path_trim($path, $opt = 0)
        {
            switch ($opt) {
                case 0:
                    $path = ltrim(rtrim($path, DS));
                    break;
                case 1:
                    $path = ltrim($path, DS);
                    break;
                case 2:
                    $path = rtrim($path, DS);
                    break;
            }
            return $path;
        }
        
        public static function path_wrap($path, $opt = 0)
        {
            if (strlen($path) > 1 || (strlen($path) === 1 && $path[0] !== DS)) {
                $path = self::path_trim($path, 0);
                switch ($opt) {
                    case 0:
                        $path = DS . $path . DS;
                        break;
                    case 1:
                        $path = DS . $path;
                        break;
                    case 2:
                        $path = $path . DS;
                        break;
                }
            } else {
                $path = "";
            }
            return $path;
        }
        
        public static function camelize($str, $lcfirst = true)
        {
            $str = preg_replace("/([_-\s]?([a-z0-9]+))/e", "ucwords('\\2')", $str);
            return ($lcfirst ? strtolower($str[0]) : strtoupper($str[0])) + substr($str, 1);
        }
        
        public static function capitalize($str)
        {
            return ucfirst($str);
        }
        
        public static function classify($str)
        {
            $str = self::camelize($str);
            return $str = self::capitalize($str);
        }
        
        public static function dasherize($str)
        {
            return str_replace(array(' ', '_'), '-', $str);
        }
        
        public static function decode($str)
        {
            return html_entity_decode($str);
        }
        
        public static function encode($str) 
        {
            return htmlentities($str);
        }
        
        public static function extension($str)
        {
            $ext = explode('.', $str);
            return end($ext);
        }
        
        public static function lower($str)
        {
            return strtolower($str);
        }
        
        public static function upper($str)
        {
            return strtoupper($str);
        }
        
        public static function remove_numbers($string)
        {
            return str_replace(array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0"), '', $string);
        }
        
        public static function randLetter()
        {
            return chr(97 + mt_rand(0, 25));
        }
    }