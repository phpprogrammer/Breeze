<?php	
	define('DS', DIRECTORY_SEPARATOR);
	define('ROOT', rtrim(dirname(__FILE__), DS) . DS);
	define('DEFAULT_PATH', $_SERVER['SCRIPT_NAME']);
	require_once(ROOT . 'system/defaults/paths.php');
		
	function __autoload($path) {
		$path = ROOT . str_replace('\\', DS, $path) . '.php';
		if(is_readable($path)) {
			require_once($path);
		}
	}
	
	// 'development' || 'testing' || 'production'
	define('ENVIRONMENT', 'development');
	
	// And away we go...
	
	require_once(CORE_PATH . 'Bootstrap.php');