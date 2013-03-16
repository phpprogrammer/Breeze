<?php
    
    namespace system\core;
        
    defined('ROOT') or die();
    
    class Error
    {
        public function __construct($code, $title = null, $message = null)
        {
            include ERRORS_PATH.$code.'.php';
            exit();
        }
    }