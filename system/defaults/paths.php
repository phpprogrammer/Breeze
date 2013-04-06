<?php defined('ROOT') or die();

	$paths = array(
		'APP'			=>		'application/',
		'CONFIG'		=>		'application/configs/',
		'CTRL'			=>		'application/controllers/',
		'ERRORS'		=>		'application/errors/',
		'IMG'			=>		'application/images/',
		'LANG'			=>		'application/languages/',
		'MODEL'			=>		'application/models/',
		'SCRIPTS'		=>		'application/scripts/',
		'STYLE'			=>		'application/styles/',
		'TEMP'			=>		'application/temporary/',
		'CACHE'			=>		'application/temporary/caches/',
		'LOGS'			=>		'application/temporary/logs/',
		'OPT'			=>		'application/temporary/optimized/',
		'VIEW'			=>		'application/views/',
        'TPL'           =>      'application/views/templates/',
		'SYS'			=>		'system/',
		'CORE'			=>		'system/core/',
		'DEF'			=>		'system/defaults/',
		'HELP'			=>		'system/helpers/',
		'LIB'			=>		'system/libraries/',
		'MOD'			=>		'system/modules/',
		'UTL'			=>		'system/utilities/'
	);
	
	foreach($paths as $key => $value) {
		define($key . '_PATH', str_replace(DS.DS, DS, ROOT . DS . str_replace(array("/", "\\"), array(DS, DS), $value) . DS));
	}
	
	unset($paths);
?>