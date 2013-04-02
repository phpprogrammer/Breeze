<?php
    
    namespace system\core;
    
    defined('ROOT') or die();

    class Optimization
    {
        
        public static function joinFiles($array, $compress = false)
        {
            $target = OPT_PATH;
            if (! is_dir($target)) {
                mkdir($target);
            }
    
            $content = '';
            $ext = String::extension($array[0]);
            $ext = $ext === 'less' ? 'css' : $ext;
            $end = substr(sha1(serialize($array)), 0, 10).".".$ext;
            
            if (file_exists($target.$end)) {
                $targetTime = filectime($target.$end);
                foreach ($array as $file) {
                    if (file_exists($file) && $targetTime < filectime($file)) {
                        unlink($target.$end);
                        break;
                    }
                }
            }
            
            if (! file_exists($target.$end)) {
                $lessContent = '';
                foreach ($array as $file) {
                    if (! file_exists($file)) {
                        continue;
                    }
                    if (String::extension($file) == 'less') {
                        $lessContent .= str_replace('[$dir]', substr(dirname($file), strlen(ROOT)), file_get_contents($file));
                    } else {
                        $content .= str_replace('[$dir]', substr(dirname($file), strlen(ROOT)), file_get_contents($file));
                    }
                }
                if (!empty($lessContent)) {
                    $less = new LessCompiler();
                    $content .= $less->parse($lessContent);
                }
                file_put_contents($target.$end, $content);
                if ($compress) {
                    self::compress($target.$end);
                }
            }
            return $target.$end;
        }
        
        public static function compress($path)
        {
            $ext = String::extension($path);
            $content = file_get_contents($path);
            switch ($ext) {
                case 'js':  
                    $content = JSMin::minify($content);
                    break;
                case 'css':
                    $content = CSSMin::minify($content);
                    break;
            }
            return file_put_contents($path, $content);
        }
        
        public static function _flush($path = null)
        {
            if (!isset($path)) {
                $path = OPT_PATH;
            }
            if ($dir = opendir($path)) {
                while (false !== ($file = readdir($dir))) {
                    if ($file[0] != '.') { 
                        unlink($path.$file);
                    }
                }
            }
        }
        
        private static function getExtension($path)
        {
            return String::extension($path);
        }
        
        private static function setExtension(&$path, $ext)
        {
            $bomb = explode('.', $path);
            $bomb[count($bomb)-1] = $ext;
            return $path = implode('.', $bomb);
        }
    }