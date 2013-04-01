<?php
    
    namespace system\core;
    
    defined('LOGS_PATH') or die();
    
    class Log
    {
        private $path;
        private $memory;
        
        public function __construct($path)
        {
            $this->memory = new Memory('logs');
            
            if (! String::is_path($path)) {
                $this->path = LOGS_PATH . $path . ".log";
            } else {
                $this->path = $path;
            }
        }
        
        public function write($text, $type = "")
        {
            $message = "[".date('d-m-Y H:i:s')."] ";
            
            if ($this->memory->get('ip_address', true) === true) {
                $message .= " [IP: ".$_SERVER['REMOTE_ADDR']."]";
            }
            if ($this->memory->get('user_id', true) === true && class_exists("Session")) {
                $message .= " [UID: ".Session::getUserID()."]";
            }
            if (! empty($type)) {
                $message .= " [".strtoupper($type)."]";
            }
            $message .= ": ".ucfirst($text);
            $message = trim($message).PHP_EOL;
            
            file_put_contents($this->path, $message, FILE_APPEND);
            if (is_readable($this->path)) {
                $file = file($this->path);
                if (count($file) + 1 > $this->memory->get('max_lines', 100)) {
                    $this->shift();
                }
            }
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
                    unset($file[0]);
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