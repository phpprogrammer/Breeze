<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class Explorer implements explorerInterface
    {
        private $path = '';
        private $file = '';
        
        public function __construct($path = '')
        {
            if (!empty($path)) {
                $this->open($path);
            }
            return $this;
        }
        
        public function open($path = '')
        {
            if (!empty($path)) {
                if ($path[0] !== DS) {
                    $path = String::glue($this->path, $path);
                }
                if (!is_dir($path)) {
                    if (!mkdir($path, 0755, true)) {
                        throw new ExplorerException('Couldn\'t create folder "'.$path.'"!', 10);
                    }
                }
                $this->path = $path;
            } else {
                throw new ExplorerException('The path parameter isn\'t specified!', 11);
            }
        }
        
        public function create($file = '')
        {
            $path = $this->tread($file);
            if (fopen($path, 'w')) {
                throw new ExplorerException('Couldn\'t create file "'.$path.'"!', 20);
            }
            return fclose($handler);
        }
        
        public function read($file, $callback = '')
        {
            $path = $this->tread($file);
            
            $handler = fopen($path, 'r');
            
            if ($handler === false) {
                throw new ExplorerException('Couldn\'t open file "'.$path.'"!', 30);
            }
            $content = '';
            if (is_callable($callback)) {
                $number = 0;
                while (false !== ($line = fgets($handler))) {
                    ++$number;
                    $content .= $line . PHP_EOL;
                    call_user_func($callback, $line, $number);
                }
            } else {
                $content = fread($handler, filesize($path));
            }
            
            fclose($handler);
            return $content;
        }
        
        public function write($file, $value)
        {
            $path = $this->tread($file);
            
            if (!is_writable(dirname($path))) {
                throw new ExplorerException('Couldn\'t write to file "'.$path.'", it isn\'t writable!', 40);
            }
            
            $handler = fopen($path, 'w');
            
            if ($handler === false) {
                throw new ExplorerException('Couldn\'t open file "'.$path.'"!', 41);
            }
            if (flock($handler, LOCK_EX)) {
                if (!fwrite($handler, $value)) {
                    throw new ExplorerException('Couldn\'t write to file "'.$path.'"!', 42);
                }
                flock($handler, LOCK_UN);
                return fclose($handler);
            } else {
                fclose($handler);
                throw new ExplorerException('Couldn\'t get the lock file "'.$path.'"!', 43);
            }
        }
        
        public function append()
        {
            
        }
        
        public function prepend()
        {
            
        }
        
        private function tread(&$file)
        {
            if (substr_count($file, DS)) {
                $this->open(dirname($file));
                $file = basename($file);
            }
            return String::glue($this->path, $file);
        }
    }
    
    class ExplorerException extends CoreException {}
    
    interface explorerInterface
    {
        public function write($file, $value);
        public function read($file, $callback);
    }