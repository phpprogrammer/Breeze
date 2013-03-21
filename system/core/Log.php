<?php
    
    namespace system\core;
    
    defined('LOGS_PATH') or die();
    
    class Log
    {
        private $path;
        private $memory;
        
        public function __construct($path = LOGS_PATH)
        {
            $this->path = $path;
            $this->memory = new Memory('logs');
        }
        
        public function write($text, $type = "info")
        {
            $message = "[".date('d-m-Y H:i:s')."] ";
            
            if ($this->memory->get('ip_address')) {
                $message .= "[IP: ".$_SERVER['REMOTE_ADDR']."] ";
            }
            if ($this->memory->get('user_id')) {
                $message .= "[UID: ".Session::getUserID()."] ";
            }
            
            $message .= "[".strtoupper($type)."]: ".ucfirst($text) . PHP_EOL;
            $file = file($this->path);
            if (count($file) + 1 > $this->memory->get('max_lines', 25)) {
                $this->shift();
            }
            file_put_contents($this->path, $message, FILE_APPEND);
        }
        
        public function info($message)
        {
            $this->write($message, 'info');
        }
        
        public function notice($message)
        {
            $this->write($message, 'notice');
        }
        
        public function warning($message)
        {
            $this->write($message, 'warning');
        }
        
        public function error($message)
        {
            $this->write($message, 'error');
        }
        
        public function debug($var)
        {
            $this->write(var_export($var, true), 'debug');
        }
        
        public function shift($count = 1)
        {
            if (! is_int($count) || $count <= 0) {
                $count = 1;
            }
            $file = file($this->path);
            for ($i = 0; $i < $count; $i++) {
                if (isset($file[0])) {
                    array_shift($file);
                } else {
                    break;
                }
            }
            file_put_contents($this->path, implode(PHP_EOL, $file));
        }
        
        public function getLatest($count = 3)
        {
            $file = file($this->path);
            $search = count($file) - 1;
            $return = array();
            
            for ($i = 0; $i < $count; $i++) {
                if (isset($file[$search])) {
                    $return[] = $file[$search--];
                } else {
                    break;
                }
            }
            return $return;
        }
        
        public function flush()
        {
            file_put_contents($this->path, '');
        }
    }