<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class CSSMin
    {	
        public static function minify($source)
        {
            $source = preg_replace( '#/\*.*?\*/#s', '', $source );
            $source = str_replace(array("\t","\n","\r","  "), '', $source);
            $source = str_replace(
                array("{ ",   " {",   "} ",   " }",   ";}",   ": ",   " :",   "; ",   " ;",   ", ",   " ,",   "( ",   " (",   ") ",   " )"), 
                array("{",    "{",    "}",    "}",    "}",    ":",    ":",    ";",    ";",    ",",    ",",    "(",    "(",    ")",    ")"), 
            $source);
            $source = str_replace('0.', '.', $source); 
            
            return $source;
        }
    }