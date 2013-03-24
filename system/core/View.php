<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    use \system\core\Application as App;
    
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
        private $scriptPath;
        private $css = array();
        private $scripts = array();
        
        public function __construct()
        {
            $this->path = VIEW_PATH;
            $this->stylePath = STYLE_PATH . Application::$memory->get('style', 'default') . DS;
            $this->scriptPath = SCRIPTS_PATH;
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
        
        public function data(Array $data = array())
        {
            if (! empty($data)) {
                $this->data = $this->data + $data;
            }
        }
        
        public function path($path, $stylePath = "", $scriptPath = "")
        {
            if (empty($stylePath)) {
                $stylePath = STYLE_PATH . Application::$memory->get('style', 'default') . DS;
            }
            if (empty($scriptPath)) {
                $scriptPath = SCRIPTS_PATH;
            }
            $this->path = rtrim($path, DS) . DS;
            $this->stylePath = rtrim($stylePath, DS) . DS;
            $this->scriptPath = rtrim($scriptPath, DS) . DS;
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
        
        public function expose($target = null, $data = array())
        {
            if ($this->displayed) {
                return false;
            }
            if (isset($target)) {
                $this->target = $target;
            }
            $this->data($data);
            $this->add_baseData();
            $this->add_headers();
            
            extract($this->data);
            
            if ($this->viewExists()) {
                $timer = App::$timers['page_loading']->stop();
                include_once $this->path.$this->target.'.php';
                $this->displayed = true;
            }
        }
        
        public function import($viewName)
        {
            include $this->path.$viewName.'.php';
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
            $this->data['baseurl'] = VIEW_PATH;
            $this->data['def_path'] = DEFAULT_PATH;
            $this->data['uri'] = App::$router->params['uri'];
        }
        
        private function add_headers()
        {
            $memory = new Memory('headers');
            $headers = '<title>'.$memory->get('site_title').'</title>';
            $headers .= '<meta charset="utf-8" />';
            foreach ($memory->getPrefix('meta_') as $key => $value) {
                $key = str_replace('meta_', '', $key);
                $headers .= "<meta name=\"$key\" content=\"$value\" />";
            }
            
            if (! empty($this->css) && $href = $this->getCSS() ) {
                if (App::$memory->get('reduce_http_requests', false)) {
                    $headers .= "<style type=\"text/css\">".file_get_contents($href)."</style>";
                } else {
                    $headers .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".String::path_trim_root($href)."\" />";
                }
            }
            if (! empty($this->scripts) && $href = $this->getScripts() ) {
                if (App::$memory->get('reduce_http_requests', false)) {
                    $headers .= "<script type=\"text/javascript\">".file_get_contents($href)."</script>";
                } else {
                    $headers .= "<script type=\"text/javascript\" src=\"".String::path_trim_root($href)."\"></script>";
                }
            }
            $headers .= "<base href=\"http://".$_SERVER['HTTP_HOST'].rtrim($_SERVER['REQUEST_URI'], "/")."/"."\">";
            $this->data['headers'] = $headers;
        }
        
        public function add()
        {
            $args = func_get_args();
    
            foreach ($args as $path) {
                $e = String::extension($path);

                if (($e == 'css' || $e == 'less') && is_readable($this->stylePath.$path)) {
                    $this->css[] = $path;
                } else if ($e == 'js' && is_readable($this->scriptPath.$path)) {
                    $this->scripts[] = $path;
                }
            }
            return $this;
        }
        
        private function viewExists()
        {
            return is_readable($this->path.$this->target.'.php');
        }
        
        private function getCSS()
        {
            return Optimization::joinFiles($this->stylePath, $this->css, Application::$memory->get('css_compression'));
        }
        
        private function getScripts()
        {
            return Optimization::joinFiles($this->scriptPath, $this->scripts, Application::$memory->get('js_compression'));
        }
    }