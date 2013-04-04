<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class Cache implements cacheInterface
    {
        private $expiry;
        private $path;
        private $group = '';
        private $memory;
        private $lastName = '';
        
        public function __construct($path = '')
        {
            if (empty($path)) {
                $this->path = CACHE_PATH;
            } else {
                $this->path = $path;
            }
            $this->memory = new Memory('caches');
            $this->expiry = $this->memory->get('default_expiry_time', 360);
            return $this;
        }
        
        public function store($name = '', $value = '')
        {
            $this->encode($name);
            file_put_contents($this->_getPath($name), serialize($value));
            return $this;
        }
        
        public function retrieve($name = '')
        {
            $this->encode($name);
            if ($this->exists($name)) {
                return unserialize(file_get_contents($this->_getPath($name)));
            } else {
                return '';
            }
        }
        
        public function exists($name = '', $expiry = '')
        {
            $this->encode($name);
            $path = $this->_getPath($name);
            if (empty($expiry) || $expiry <= 0) {
                $expiry = $this->expiry;
            }
            if (is_readable($path)) {
                if ($this->expiry <= 0 || filectime($path) + $expiry > time()) {
                    return true;
                }
            }
            return false;
        }
        
        public function delete($name = '')
        {
            $this->encode($name);
            unlink($this->_getPath($name));
        }
        
        public function openGroup($name, $expiry = '')
        {
            $this->group = $name;
            $ge = $this->memory->get('group_expiry_times', array());
            
            if (!empty($expiry) && $expiry > 0) {
                $this->expiry = $expiry;
                if (!isset($ge[$name])) {
                    $this->setGroupExpiry($name, $expiry);
                }
            } else {
                if (isset($ge[$name])) {
                    $expiry = $this->expiry = $ge[$name];
                } else {
                    $expiry = $this->expiry = $this->memory->get('default_expiry_time', 360);
                }
            }
            if (!is_dir($path = String::glue($this->path, $name))) {
                mkdir($path, 0777, true);
            }
            return $this;
        }
        
        public function setGroupExpiry($group, $expiry)
        {
            $this->memory->insert('group_expiry_times.'.$name, $expiry)->save();
        }
        
        public function encode(&$name)
        {
            if (!empty($name)) {
                $mcrypt = $this->memory->get('crypt_method', 'md5');
                $name = $mcrypt($name);
                $this->lastName = $name;
                return $name;
            } else if (!empty($this->lastName)) {
                return $this->lastName;
            } else {
                return '';
            }
        }
        
        private function _getPath($name)
        {
            return String::glue($this->path, $this->group, $name.'.php');
        }
        
        public static function flush($group = '', $path = '')
        {
            if (empty($path)) {
                $path = CACHE_PATH;
            }
            if (! empty($group)) {
                $path = String::glue($path, $group);
            }
            
            if ($dir = opendir($path)) {
                while (false !== ($file = readdir($dir))) {
                    if ($file[0] != '.') { 
                        unlink($path.$file);
                    }
                }
            }
        }
    }
    
    class CacheException extends CoreException {}
    
    interface cacheInterface
    {
        public function store($name, $value);
        public function retrieve($name);
        public function exists($name, $expiry);
        public function delete($name);
    }