<?php

namespace system\core;

defined('ROOT') or die();

use \system\libraries\String;
use \system\libraries\Timer;
use \system\libraries\Optimization;

class View
{
    private $target;
    private $path;
    private $data = array();
    private $timer;
    private $displayed = false;
    private $mode = 0;
    private static $instance;
    private $stylePath;
    private $css = array();
    private $scripts = array();
    
    public function __construct()
    {
        $this->path = VIEW_PATH;
        $this->stylePath = STYLE_PATH . Application::$memory->get('style') . DS;
        self::setInstance($this);
        return $this;
    }
    
    private static function setInstance(View $instance)
    {
        self::$instance = $instance;
    }
    
    public static function getInstance()
    {
        return self::$instance;
    }
    
    public function add_data($data)
    {
        if (! empty($data)) {
            $this->data = $this->data + $data;
        }
    }
    
    public function path($path)
    {
        $this->path = rtrim($path, DS) . DS;
        return $this;
    }
    
    public function mode($mode)
    {
        $this->mode = $mode;
        return $this;
    }
    
    public function target($target)
    {
        $this->target = String::cleanFilename($target);
        return $this;
    }
    
    public function expose($target = null, $data = null)
    {
        if ($this->displayed) {
            return false;
        }
        if (isset($target)) {
            $this->target = $target;
        }
        $this->add_data($data);
        $this->add_baseData();
        $this->add_headers();
        
        extract($this->data);
        
        if ($this->viewExists()) {
            $timer = Application::$timers['page_loading']->stop();
            include_once $this->path.$this->target.'.php';
            $this->displayed = true;
        }
    }
    
    public function turnOff()
    {
        $this->displayed = true;
    }
    
    public function displayError($code)
    {
        new Error($code);
    }
    
    private function add_baseData()
    {
        $this->data['baseurl'] = DEFAULT_PATH.VIEW_PATH;
        $this->data['def_path'] = DEFAULT_PATH;
    }
    
    private function add_headers()
    {
        $headers = '<meta charset="utf-8" />';
        foreach (Application::$memory->getPrefix('meta_') as $key => $value) {
            $key = str_replace('meta_', '', $key);
            $headers .= "<meta name=\"$key\" content=\"$value\" />";
        }
        if (! empty($this->css) && $href = $this->getCSS() ) {
            if (Application::$memory->get('reduce_http_requests')) {
                $headers .= "<style type=\"text/css\">".file_get_contents($href)."</style>";
            } else {
                $headers .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".String::path_trim_root($href)."\" />";
            }
        }
        if (! empty($this->scripts) && $href = $this->getScripts() ) {
            if (Application::$memory->get('reduce_http_requests')) {
                $headers .= "<script type=\"text/javascript\">".file_get_contents($href)."</script>";
            } else {
                $headers .= "<script type=\"text/javascript\" src=\"".String::path_trim_root($href)."\"></script>";
            }
        }
        $this->data['headers'] = $headers;
    }
    
    public function add()
    {
        $args = func_get_args();

        foreach ($args as $path) {
            $e = String::extension($path);
        
            if ($e == 'css' || $e == 'less') {
                $this->css[] = $path;
            } else if ($e == 'js') {
                $this->scripts[] = $path;
            }
        }
        return $this;
    }
    
    private function viewExists()
    {
        return file_exists($this->path.$this->target.'.php');
    }
    
    private function getCSS()
    {
        return Optimization::joinFiles($this->stylePath, $this->css, Application::$memory->get('css_compression'));
    }
    
    private function getScripts()
    {
        return Optimization::joinFiles(SCRIPTS_PATH, $this->scripts, Application::$memory->get('js_compression'));
    }
}