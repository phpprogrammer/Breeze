<?php
    
    function write($str="", $vars=array(), $lang=null)
    {
        echo \system\core\Translator::translate($str, $vars, $lang);
    }
    
    function debug()
    {
        if (ENVIRONMENT !== 'production') {
            $db = debug_backtrace();
            $db = $db[0];
            $args = func_get_args();
            echo "[".\system\core\String::path_trim_root($db['file'])." (".$db['line'].")] {<br/>";
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
    
    function array_set(Array &$array, $key, $value)
    {
        $keys = explode('.', $key);
        $s =& $array;
        
        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (! isset($s[$k]) || ! is_array($s[$k])) {
                $s[$k] = array();
            }
            $s =& $s[$k];
        }
        $k = reset($keys);
        if ($value === '++' || $value === '--') {
            if (! isset($s[$k]) || ! is_integer($s[$k])) {
                $s[$k] = 0;
            }
            switch ($value) {
                case '++':
                    $value = $s[$k] + 1;
                    break;
                case '--':
                    $value = $s[$k] - 1;
                    break;
            }
        }
        if (!isset($s[$k]) || $s[$k] !== $value) {
            if ($value === null) {
                unset($s[$k]);
            } else {
                $s[$k] = $value;
            }
            return true;
        } else {
            return false;
        }
    }
    
    function array_get(Array &$array, $arg1)
    {
        if (strpos($arg1, '.')) {
            $args = explode('.', $arg1);
        } else {
            $args = func_get_args();
            array_shift($args);
        }
        $s =& $array;
        while (count($args) > 1) {
            $key = array_shift($args);
            if (isset($s[$key])) {
                $s =& $s[$key];
            } else {
                return null;
            }
        }
        $key = reset($args);
        if (!empty($s[$key])) {
            return $s[$key];
        } else {
            return null;
        }
    }
    
    if (! function_exists("array_column")) {
        function array_column($array, $column)
        {
            foreach ($array as $row) {
                $ret[] = $row[$column];
            }
            return $ret;
        }
    }