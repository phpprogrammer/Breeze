<?php
    
    namespace system\core;
        
    defined('ROOT') or die();
    
    class Serialization
    {
        private $path;
        private $data;
        
        public function __construct($path)
        {
            $this->path = $path;
            $this->read();
        }
        
        public function read()
        {
            if (file_exists($this->path)) { 
                $this->data = unserialize(file_get_contents($this->path));
            } else {
                return false;
            }
        }
        
        public function load()
        {
            return $this->data;
        }
        
        public function save($object)
        {
            return file_put_contents($this->path, serialize($object));
        }
        
        public function flush()
        {
            return file_put_contents($this->path, "");
        }
        
        public function close()
        {
            $this->__destruct();
        }
        
        public function __destruct()
        {
            
        }
    }