<?php

namespace system\core;

defined('ROOT') or die();

class Memory
{
    private $path;
    private $alt_path;
    private $data = array();
    
    public function __construct($path, $alt = NULL)
    {
        return $this->read($path, $alt);
    }
    
    public function isReadable()
    {
        return is_readable($this->path) || (isset($this->alt_path) && is_readable($this->alt_path));
    }
    
    public function read($path, $alt = NULL)
    {
        $this->path = $path;
        if (isset($alt)) {
            $this->alt_path = $alt;
        }
        
        $this->toArray();
        
        return $this;
    }
    
    public function get($var, $unserialize = false)
    {
        $return = null;
        if (isset($this->data[$var])) {
            $return = $this->data[$var];
        } else {
            if (is_readable($this->path)) {
                include($this->path);
                
                if (isset($$var)) {
                    $return = $$var;
                }
            }   
            if (isset($this->alt_path) && is_readable($this->alt_path)) {
                include($this->alt_path);
                
                if (isset($$var)) {
                    $return = $$var;
                }
            }
        }
        if ($unserialize) {
            return unserialize($return);
        } else {
            return $return;
        }
    }
    
    public function getPrefix($prefix = "_")
    {
        $array = array();
        
        ksort($this->data);
        foreach ($this->data as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $array[substr($key, strlen($prefix))] = $value;
            } elseif (!empty($array)) {
                break;
            }
        }
        
        return $array;
    }
    
    public function insert($key, $value, $serialize = false)
    {
        if (isset($key, $value)) {
            if ($serialize) {
                $this->data[$key] = serialize($value);
            } else {
                $this->data[$key] = $value;
            }
        }
        return $this;
    }
    
    public function delete($key)
    {
        if (isset($key, $this->data[$key])) {
            unset($this->data[$key]);
        }
        return $this;
    }
    
    public function save()
    {
        if (! empty($this->data)) {
            ksort($this->data);
            $c = "<?php\n";
            foreach ($this->data as $key => $value) {
                $c .= "\t$" . $key . " = " . str_replace(array("\n","\t"), "", var_export($value, true)) . ";\n";
            }
            file_put_contents($this->path, $c);
            return true;
        }
        return false;
    }
    
    private function toArray($additive = false)
    {
        if (!$additive) {
            $this->data = array();
        }
        
        $content = $this->fileContent();
        preg_match_all('/^[\t\s]*\$([a-zA-Z0-9\_]*)[\t\s]*\=[\t\s]*(.*)\;[\t\s]*$/m', $content, $matches);
        
        if (isset($matches[1], $matches[2])) {
            foreach ($matches[1] as $key => $value) {
                //$this->data[$value] = $matches[2][$key];
                eval('$this->data[$value] = ' . $matches[2][$key] . ';');
            }
        }
                    
        return $this->data;
    }
    
    private function fileContent()
    {
        $c = '';
        
        if (! is_readable($this->path)) {
            file_put_contents($this->path, "<?php\n\n?>");
        } else {
            $c = file_get_contents($this->path);
        }
        if ($this->alt_path && is_readable($this->alt_path)) {
            $c = file_get_contents($this->alt_path) . "\n" . $c;
        }
        return str_replace(array("<?php\n", "\n?>"), '', $c);
    }
}