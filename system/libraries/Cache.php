<?php

namespace system\libraries;

defined('ROOT') or die();

use \system\core\Application;

class Cache
{
    private $path;
    private $srl;
    private $expiryTime;
    
    public function __construct($name, $path = null)
    {
        if (!isset($path)) {
            $path = CACHE_PATH;
        }
        $this->path = $path.md5($name).'.php';
        $this->expiryTime = Application::$memory->get('cache_expiry');
        $this->srl = new Serialization($this->path);
        return $this;
    }
    
    public function save($value)
    {
        return $this->srl->save($value);
    }
    
    public function load()
    {
        return $this->srl->load();
    }
    
    public function exists()
    {
        if (file_exists($path = $this->path)) {
            if ($this->expiryTime === 0 || filectime($path) + $this->expiryTime > time()) {
                return true;
            }
        }
        return false;
    }
    
    public function delete()
    {
        unlink($this->path.'.php');
    }
    
    public static function _save($name, $value, $path = null)
    {
        if (!isset($path)) {
            $path = CACHE_PATH;
        }
        $srl = new Serialization($path.md5($name).'.php');
        
        if ($srl->save($value) !== false) {
            return true;
        } else {
            return false;
        }
    }
    
    public static function _load($name, $path = null)
    {
        if (!isset($path)) {
            $path = CACHE_PATH;
        }
        if (self::_find($name, $path)) {
            $srl = new Serialization($path.md5($name).'.php');
            return $srl->load();
        } else {
            return false;
        }
    }
    
    public static function _delete($name, $path = null)
    {
        if (!isset($path)) {
            $path = CACHE_PATH;
        }
        if (self::_find($name, $path)) {
            unlink($path.md5($name).'.php');
        }
    }
    
    public static function _flush($path = null)
    {
        if (!isset($path)) {
            $path = CACHE_PATH;
        }
        if ($dir = opendir($path)) {
            while (false !== ($file = readdir($dir))) {
                if ($file[0] != '.') { 
                    unlink($path.$file);
                }
            }
        }
    }
    
    public static function _find($name, $path = null, $time = 3600)
    {
        if (!isset($path)) {
            $path = CACHE_PATH;
        }
        if (file_exists($path = $path.md5($name).'.php')) {
            if (filectime($path) + $time > time()) {
                return true;
            }
        }
        return false;
    }
}