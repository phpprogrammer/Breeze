<?php 
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    abstract class Controller
    {
        protected $access;
        protected $view;
        protected $vars;
        protected $userManager;
        protected $post = array();
        protected $get = array();
        protected $__dir = "";
        
        final public function __construct($vars = array(), $name = "", $action = "", $utl = false)
        {
            $this->vars = $vars;
            $this->view = Application::$view;
            $this->userManager = new UserManager();
            $this->user = $this->userManager->getUser();
            $this->access = new AccessControl($this->user);
            
            if (!empty($_POST)) {
                $this->post = $_POST;
                unset($_POST);
            }
            if (!empty($_GET)) {
                $this->get = $_GET;
                unset($_GET);
            }
            if (!empty($name)) {
                $this->__dir = ltrim(dirname($name), '.');
            }
            
            if ($utl === false) {
                if ($this->access->isAllowed('www/'.$name.'/'.$action) === false) {
                    new Error('403');
                }
            } else {
                if (! $this->access->isRoot()) {
                    //new Error('403');
                }
            }
        }
        
        final protected function _redirectTo($action="", $httpredirect=false)
        {
            if ($httpredirect === true) {
                Application::$router->redirect(rtrim(dirname($_SERVER['REQUEST_URI']), DS).DS.$action);
            } else {
                if (method_exists($this, $action)) {
                    $this->$action($this->vars);
                    $this->view->expose(ltrim(rtrim($this->__dir . DS, DS), DS) . trim(basename(str_replace("\\", DS, get_class($this)))) . '_' . $action);
                }
            }
        }
        
        final protected function _post($key)
        {
            if (isset($this->post[$key])) {
                return $this->post[$key];
            }
            return null;
        }
        
        final protected function _get($key)
        {
            if (isset($this->post[$key])) {
                return $this->post[$key];
            }
            return null;
        }
    }