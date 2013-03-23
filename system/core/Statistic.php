<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    use \system\core\Application as App;
    
    class Statistic
    {
        private $ua;
        
        public function __construct()
        {
            $this->ua = App::$userAgent;
            if (! App::$db->existsTable('statistics')) { $this->build(); }
        }
        
        private function build()
        {
            App::$db->createTable(
                'statistics',
                array(
                    'id' => 'int(11) not null auto_increment',
                    'date' => 'date not null',
                    'time' => 'time not null',
                    'IP' => 'tinytext collate utf8_unicode_ci not null',
                    'System' => 'tinytext collate utf8_unicode_ci not null',
                    'SystemVersion' => 'tinytext collate utf8_unicode_ci not null',
                    'Browser' => 'tinytext collate utf8_unicode_ci not null',
                    'BrowserVersion' => 'tinyint not null',
                    'BrowserFullVersion' => 'tinytext collate utf8_unicode_ci not null',
                    'Engine' => 'tinytext collate utf8_unicode_ci not null',
                    'Type' => 'tinytext collate utf8_unicode_ci not null'
                )
            );
        }
        
        public function loader()
        {
            $date = date('y-m-d');
            $ip = $_SERVER['REMOTE_ADDR'];
            
            if ((!isset($_SESSION['visited']) || $_SESSION['visited'] !== true) && !$this->exists($date, $ip) && ENVIRONMENT !== 'development') {
                $this->save($date, $ip, $this->ua->systemName(), $this->ua->systemVersion(), $this->ua->browserName(), $this->ua->browserVersion(), $this->ua->browserFullVersion(), $this->ua->engine(), $this->ua->type());
            }
            $this->makeCookie('visited');
        }
        
        private function makeCookie($name, $value = true, $expiry = NULL)
        {
            $_SESSION['visited'] = true;
        }
        
        private function eatCookie($name)
        {
            $_SESSION['visited'] = false;
        }
        
        public function save($date, $ip, $os, $sv, $br, $bv, $bfv, $en, $tp)
        {
            App::$db->insert("statistics", array(
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
        }
        
        public function exists($date, $ip)
        {
            return App::$db->exists("statistics", "date='$date' and IP='$ip'");
        }
    }