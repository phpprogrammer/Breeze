<?php

namespace system\libraries {
    
    defined('ROOT') or die();
    
    use \system\core\Application;
    use \system\core\Database;
    
    class Statistic {
        private $ua;
        
        public function __construct() {
            $this->ua = Application::$userAgent;
            $db =& Application::$db;
            if(! $db->existsTable('statistics')) { $this->build($db); }
            
            if(Application::$memory->get('statistics') === true && Application::$router->params['utility'] !== true) {
                $this->loader();
            }
        }
        
        private function build(Database $db) {
            $db->exec("CREATE TABLE IF NOT EXISTS `statistics` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `date` date NOT NULL,
                `time` time NOT NULL,
                `IP` tinytext COLLATE utf8_unicode_ci NOT NULL,
                `System` tinytext COLLATE utf8_unicode_ci NOT NULL,
                `SystemVersion` tinytext COLLATE utf8_unicode_ci NOT NULL,
                `Browser` tinytext COLLATE utf8_unicode_ci NOT NULL,
                `BrowserVersion` tinyint COLLATE utf8_unicode_ci NOT NULL,
                `BrowserFullVersion` tinytext COLLATE utf8_unicode_ci NOT NULL,
                `Engine` tinytext COLLATE utf8_unicode_ci NOT NULL,
                `Type` tinytext COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`id`) ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1");
        }
        
        private function loader() {
            $date = date('y-m-d');
            $ip = $_SERVER['REMOTE_ADDR'];
            
            if((!isset($_SESSION['visited']) || $_SESSION['visited'] !== true) && !$this->exists($date, $ip) && ENVIRONMENT !== 'development') {
                $this->save($date, $ip, $this->ua->systemName(), $this->ua->systemVersion(), $this->ua->browserName(), $this->ua->browserVersion(), $this->ua->browserFullVersion(), $this->ua->engine(), $this->ua->type());
            }
            $this->makeCookie('visited');
        }
        
        private function makeCookie($name, $value = true, $expiry = NULL) {
            //if(!isset($expiry)) $expiry = mktime(23, 59, 59, date("m"), date("d"), date("y"));
            //setcookie($name, $value, $expiry);
            $_SESSION['visited'] = true;
        }
        
        private function eatCookie($name) {
            //setcookie($name, "", time() - 3600);
            $_SESSION['visited'] = false;
        }
        
        public function save($date, $ip, $os, $sv, $br, $bv, $bfv, $en, $tp) {
            Application::$database->insert("statistics", array(
                'date' => $date,
                'time' => date("H:i:s"),
                'IP' => $ip,
                'System' => $os,
                'SystemVersion' => $sv,
                'Browser' => $br,
                'BrowserVersion' => $bv,
                'BrowserFullVersion' => $bfv,
                'Engine' => $en,
                'Type' => $tp
            ));
            // (date, time, IP, System, SystemVersion, Browser, BrowserVersion, BrowserFullVersion, Engine, Type) values ('$date', CURTIME(), '$ip', '$os', '$sv', '$br', '$bv', '$bfv', '$en', '$tp');");
        }
        
        public function exists($date, $ip) {
            return Application::$db->exists("statistics", "date='$date' and IP='$ip'");
        }
    }
}