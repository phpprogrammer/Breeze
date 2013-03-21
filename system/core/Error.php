<?php
    
    namespace system\core;
        
    defined('ROOT') or die();
    
    class Error
    {
        public function __construct($code, $data = array())
        {
            extract($data);
            include ERRORS_PATH.$code.'.php';
            exit();
        }
    }