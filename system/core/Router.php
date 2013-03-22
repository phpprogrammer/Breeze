<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
        
    class Router
    {
        public $params = array('utility' => false);
        private $url = '';
        private $splitter;
        private $defaultControllerName;
        private $defaultAction;
        private $memory;
        
        public function __construct()
        {
            $this->memory = new Memory('routing');
            $this->sitemap = new Memory(DEF_PATH.'sitemap.php');
            
            $this->splitter = $this->memory->get('splitter', '/');
            $this->defaultControllerName = $this->memory->get('default_controller', 'home');
            $this->defaultAction = $this->memory->get('default_action', 'index');
            
            if ($this->checkSitemap() === false) {
                $this->createSitemap();
            }
            
            if (isset($_SERVER['REQUEST_URI'])) {
                $this->run();
            }
            return $this;
        }
        
        public function setDefaultController($value)
        {
            return $this->defaultControllerName = $value;
        }
        
        public function setDefaultAction($value)
        {
            return $this->defaultAction = $value;
        }
        
        public function setSplitter($value)
        {
            return $this->splitter = $value;
        }
        
        public function run()
        {
            $this->url = substr($_SERVER['REQUEST_URI'], strlen(dirname($_SERVER['SCRIPT_NAME'])));
            if (!empty($this->url) && in_array(String::extension($this->url), $this->memory->get('allowed_extensions', array('css','js','less','jpg','png','gif'))) ) {
                include_once(String::glue(ROOT, $this->url));
                exit();
            } else {
                return $this->route();
            }
        }
        
        public function redirect($uri)
        {
            header("Location: $uri");
        }
        
        public function getSitemap()
        {
            return unserialize($this->sitemap->get('sitemap'));
        }
        
        private function route()
        {
            $arr = array_filter(explode($this->splitter, $this->url));
            $path = "";
            
            if (isset($arr[0]) && $arr[0] === '!') {
                $this->params['utility'] = true;
                array_shift($arr);
                $path = UTL_PATH;
            } else {
                $path = CTRL_PATH;
            }
            
            while (isset($arr[0]) && is_dir($path.$arr[0])) {
                $path = rtrim($path, DS).DS.$arr[0].DS;
                array_shift($arr);
            }
    
            if ($this->params['utility']) {
                $this->params['location'] = String::path_trim(substr($path, strlen(UTL_PATH)));
            } else {
                $this->params['location'] = String::path_trim(substr($path, strlen(CTRL_PATH)));
            }
            
            if (Application::$memory->get('multilingual') === true) {
                if(isset($arr[0]) && in_array($arr[0], array_keys(Application::$memory->get('languages')))) {
                    $this->params['language'] = $arr[0];
                    array_shift($arr);
                } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && in_array(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2), array_keys(Application::$memory->get('languages')))) {
                    $this->params['language'] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                } elseif (isset($_SESSION['language']) && in_array($_SESSION['language'], array_keys(Application::$memory->get('languages')))) {
                    $this->params['language'] = $_SESSION['language'];
                } else {
                    $this->params['language'] = Application::$memory->get('default_language');
                }
            } else {
                $this->params['language'] = false;
            }
            
            if (isset($arr[0])) {
                $this->params['controller'] = String::path_wrap($this->params['location'], 2) . $arr[0];
                array_shift($arr);
            } else {
                $this->params['controller'] = String::path_wrap($this->params['location'], 2) . $this->defaultControllerName;
            }
    
            if (isset($arr[0])) {
                $this->params['action'] = $arr[0];
                array_shift($arr);
            } else {
                $this->params['action'] = $this->defaultAction;
            }
            
            $this->params['vars'] = $arr;
            
            if ($this->trace() === false) {
                new Error('404');
            }
            
            $arr =& $this->params['vars'];
            for ($i = 0; $i < count($arr); $i++) {
                if (isset($arr[$i]) && ($key = strstr($arr[$i], '=', true))) {
                    $arr[$key] = substr(strstr($arr[$i], '='), 1);
                    unset($arr[$i]);
                }
            }
            
            $this->params['uri'] = $_SERVER['REQUEST_URI'];
            $this->params['self'] = $_SERVER['PHP_SELF'];
            $this->params['host'] = $_SERVER['HTTP_HOST'];
            @ $this->params['referer'] = $_SERVER['HTTP_REFERER'];
            
            return $this->params;
        }
        
        public function trace($cs = false)
        {
            if ($this->params['utility'] !== true) {
                $sm = $this->sitemap->get('sitemap', null, true);
                $bomb = array_filter(explode(DS, $this->params['controller'].DS.$this->params['action']));
                $i = 0;
                
                while ($i < count($bomb)) {
                    $value = $bomb[$i];
                    if (isset($sm[$value])) {
                        if (is_array($sm[$value])) {
                            $sm = $sm[$value];
                        } elseif ($i+1 < count($bomb)) {
                            return false;
                        }
                    } elseif (in_array($value, $sm)) {
                        return true;
                    } else {
                        if ($cs === true) {
                            return false;
                        } else {
                            $cs = true;
                            break;
                        }
                    }
                    $i++;
                }
                if ($cs === true) {
                    if ($this->params['action'] !== $this->params['controller']) {
                        array_unshift($this->params['vars'], $this->params['action']);
                    }
                    $this->params['action'] = basename($this->params['controller']);
                    $this->params['controller'] = String::path_wrap($this->params['location'], 2) . Application::$memory->get('default_controller');
                    
                    return $this->trace(true);
                }
                return true;
            } else {
                return is_readable(rtrim(UTL_PATH, DS).DS.trim($this->params['controller'], DS).'.php');
            }
        }
        
        private function checkSitemap()
        {
            if ($this->sitemap->get('sitemap') == null) {
                return false;
            }
            $iterator = new \DirectoryIterator(CTRL_PATH);
            $mtime = $this->sitemap->get('time');
            $file;
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    if ($fileinfo->getMTime() > $mtime) {
                        return false;
                    }
                }
            }
            return true;
        }
        
        private function createSitemap()
        {
            function scan_dir($path, $ns)
            {
                $path = rtrim($path, DS).DS;
                $ns = rtrim($ns, "\\")."\\";
                $sd = scandir($path);
                $return = array();
                
                foreach ($sd as $key => $value) {
                    if ($value[0] !== '.') {
                        if (is_dir($path.$value)) {
                            $return[$value] = scan_dir($path.$value, $ns.$value);
                        } elseif (is_file($path.$value)) {
                            $tmp = get_class_methods($ns.strstr($value, '.', true));
                            if (! empty($tmp)) {
                                array_walk($tmp, function($v, $k) use(&$tmp) {
                                    if (substr($v, 0, 1) === "_") {
                                        unset($tmp[$k]);
                                    }
                                });
                                $return[strstr($value, '.', true)] = $tmp;
                            }
                        }
                    }
                }
                return $return;
            }
            
            $path = CTRL_PATH;
            $ns = str_replace(DS, "\\", String::path_wrap(substr(CTRL_PATH, strlen(ROOT))));
            $result = scan_dir($path, $ns); // + scan_dir(UTL_PATH, str_replace(DS, "\\", String::path_wrap(substr(UTL_PATH, strlen(ROOT)))));
            
            $this->sitemap->insert('time', time())->insert('sitemap', serialize($result))->save();
            return $this;
        }
    }