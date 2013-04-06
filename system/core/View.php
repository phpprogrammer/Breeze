<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    use \system\core\Application as App;
    
    class View
    {
        private $style_path;
        private $script_path;
        private $subviews = array();
        private $css = array();
        private $scripts = array();
        private static $instance;
        
        public static function getInstance()
        {
            return self::$instance;
        }
        
        private static function setInstance(View $instance)
        {
            self::$instance = $instance;
        }
        
        public function __construct()
        {
            Subview::$tpl_path = TPL_PATH;
            $this->style_path = STYLE_PATH . App::$memory->get('style', 'default') . DS;
            $this->script_path = SCRIPTS_PATH;
            self::setInstance($this);
            return $this;
        }
        
        public function prependView(viewInterface &$view)
        {
            array_unshift($this->subviews, $view);
            return $this;
        }
        
        public function appendView(viewInterface &$view)
        {
            $this->subviews[] = $view;
            return $this;
        }
        
        public function setPath(Array $path)
        {
            if (!empty($path['tpl'])) {
                Subview::$tpl_path = $path['tpl'];
            }
            if (!empty($path['style'])) {
                $this->style_path = $path['style'];
            }
            if (!empty($path['script'])) {
                $this->script_path = $path['script'];
            }
            return $this;
        }
        
        public function display()
        {
            echo '<!doctype html><html><head>', $this->getHeaders(), '</head><body>';
            
            foreach ($this->subviews as $subview) {
                $subview->render($this);
            }
            
            echo '</body></html>';
        }
        
        public function getHeaders()
        {
            $memory = new Memory('headers');
            $headers = '<title>'.$memory->get('site_title', '').'</title>';
            $headers .= '<meta charset="'.$memory->get('charset', 'utf-8').'" />';
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
            $headers .= "<base href=\"http://".String::glue($_SERVER['HTTP_HOST'], App::$router->params['base_uri'])."\">";
            return $headers;
        }
        
        public function add()
        {
            $args = func_get_args();
    
            foreach ($args as $path) {
                $e = String::extension($path);

                if (($e == 'css' || $e == 'less') && is_readable($this->style_path.$path)) {
                    $this->css[] = $this->style_path.$path;
                } else if ($e == 'js' && is_readable($this->script_path.$path)) {
                    $this->scripts[] = $this->script_path.$path;
                } else if ($e == 'js' && is_readable(SYS_PATH.'scripts'.DS.$path)) {
                    $this->scripts[] = SYS_PATH.'scripts'.DS.$path;
                }
            }
            return $this;
        }
        
        private function getCSS()
        {
            return Optimization::joinFiles($this->css, Application::$memory->get('css_compression', true));
        }
        
        private function getScripts()
        {
            return Optimization::joinFiles($this->scripts, Application::$memory->get('js_compression', true));
        }
    }
    
    interface viewInterface
    {
        public function render();
    }
    
    class Subview
    {
        private $models = array();
        private $data = array();
        private $subviews = array();
        public static $tpl_path;
        
        
        public function set($key, $value)
        {
            array_set($this->data, $key, $value);
            return $this;
        }
        
        public function get($key)
        {
            return array_get($this->data, $key);
        }
        
        public function addModel($name, $object)
        {
            $this->models[(string)$name] = $object;
            return $this;
        }
        
        public function hasModel($name)
        {
            return isset($this->models[(string)$name]);
        }
        
        public function getModel($name, $contract = null)
        {
            if(!isset($this->models[(string)$name])) {
                throw new ViewException('The model '.$name.' is not defined.', 10);
            }
            
            if($contract !== null) {
                if(!is_a($this->models[(string)$name], $contract)) {
                    throw new ViewException('Model contract '.$contract.' is not satisfied for '.$name, 11);
                }
            }
            return $this->models[(string)$name];
        }
        
        public function addSubview($name, viewInterface &$view)
        {
            $this->subviews[(string)$name] = $view;
            return $this;
        }
        
        public function subview($name)
        {
            return $this->subviews[(string)$name];
        }
        
        public function import($tpl_name)
        {
            if (! empty($tpl_name)) {
                extract($this->data);
                extract($this->models);
                include(String::glue(self::$tpl_path, $tpl_name . '.php'));
            }
        }
    }
    
    class ViewException extends CoreException {}