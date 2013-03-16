<?php 

namespace system\core;

defined('ROOT') or die();

use \system\libraries\UserManager;

abstract class Controller
{
	protected $view;
	protected $vars;
	protected $userManager;
	protected $post = array();
	protected $get = array();
	protected $__dir = "";
	
	final public function __construct($vars = array(), $name = "", $action = "")
	{
		$this->vars = $vars;
		$this->view = Application::$view;
		$this->userManager = new UserManager();
		$this->user = $this->userManager->getUser();
		
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
	}
	
	final protected function _redirectTo($action="", $httpredirect=false)
	{
		if ($httpredirect === true) {
			Application::$router->redirect(rtrim(dirname($_SERVER['REQUEST_URI']), DS).DS.$action);
		} else {
			if(method_exists($this, $action)) {
				$this->$action($this->vars);
				$this->view->expose(ltrim(rtrim($this->__dir . DS, DS), DS) . trim(basename(str_replace("\\", DS, get_class($this)))) . '_' . $action);
			}
		}
	}
	
	final protected function _post($key)
	{
		if (isset($this->post[$key]))
			return $this->post[$key];
		else
			return null;
	}
}