<?php
    
    use \system\core\String;
    
    function write($str="", $vars=array(), $lang=null)
    {
        echo Translator::translate($str, $vars, $lang);
    }
    
    function debug()
    {
        if (ENVIRONMENT !== 'production') {
            $db = debug_backtrace();
            $db = $db[0];
            $args = func_get_args();
            echo "[".String::path_trim_root($db['file'])." (".$db['line'].")] {<br/>";
            foreach ($args as $key => $value) {
                for ($i=0;$i<10;$i++) {echo "&nbsp;";}
                if (isset($value)) {
                    echo "($key): &nbsp;" . var_export($value, true) . "<br/>";
                }
            }
            echo "}<br/>";
        }
    }
    
    function import_view($viewName)
    {
        \system\core\Application::$view->import($viewName);
    }