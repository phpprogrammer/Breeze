<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class UserAgent
    {
        private $browserName = 'unknown';
        private $browserVersion = 'unknown';
        private $browserFullVersion = 'unknown';
        private $system = 'unknown';
        private $systemVersion = 'unknown';
        private $engine = 'unknown';
        private $type = 'unknown';
        private $string = '';
        private $robot = false;
        private static $instance;
        
        public function __construct()
        {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $this->string = strtolower($_SERVER['HTTP_USER_AGENT']);
                $this->recognize();
            }
            self::$instance = $this;
            return $this;
        }
        
        public static function getInstance()
        {
            return self::$instance;
        }
        
        public function browserName()
        {
            return $this->browserName;
        }
        
        public function browserVersion()
        {
            return $this->browserVersion;
        }
        
        public function browserFullVersion()
        {
            return $this->browserFullVersion;
        }
        
        public function systemName()
        {
            return $this->system;
        }
        
        public function systemVersion()
        {
            return $this->systemVersion;
        }
        
        public function engine()
        {
            return $this->engine;
        }
        
        public function type()
        {
            return $this->type;
        }
        
        public function robot()
        {
            return $this->robot;
        }
        
        public function is_windows()
        {
            return $this->system === 'Windows';
        }
        
        public function is_webkit()
        {
            return $this->engine === 'WebKit';
        }
        
        public function is_trident()
        {
            return $this->engine === 'Trident';
        }
        
        public function is_gecko()
        {
            return $this->engine === 'Gecko';
        }
        
        public function is_presto()
        {
            return $this->engine === 'Presto';
        }
        
        public function is_robot()
        {
            return $this->robot;
        }
        
        private function recognize()
        {
            $this->check('system', array(
                'windows phone' => 'Windows Phone',
                'windows' => 'Windows',
                'android' => 'Android',
                'ios' => 'iOS', 
                'like mac os' => 'iOS',
                'mac os' => 'Mac OS X',
                'ubuntu' => 'Ubuntu',
                'fedora' => 'Fedora',
                'symbian' => 'Symbian',
                'linux' => 'Linux'
            ));
            
            $this->check('browserName', array(
                'firefox' => 'Firefox',
                'chrome' => 'Chrome',
                'crios' => 'Chrome',
                'safari' => 'Safari',
                'opera' => 'Opera',
                'internet explorer' => 'Internet Explorer',
                'msie' => 'Internet Explorer',
                'webkit' => 'WebKit'
            ));
            
            $this->check('engine', array(
                'webkit' => 'WebKit',
                'trident' => 'Trident',
                'presto' => 'Presto',
                'gecko' => 'Gecko',
                'msie' => 'Trident'
            ));
            
            switch ($this->system) {
                case 'Windows':
                    $this->check('systemVersion', array(
                        'nt 6.2' => '8',
                        'nt 6.1' => '7',
                        'nt 6.0' => 'Vista',
                        'nt 5.2' => '2003',
                        'nt 5.1' => 'XP',
                        'nt 5.0' => '2000',
                        'windows 98' => '98',
                        'windows 95' => '95'
                    ));
                    break;
                case 'Mac OS X':
                    $this->check('systemVersion', array(
                        '10.9' => 'Lynx',
                        '10.8' => 'Mountain Lion',
                        '10.7' => 'Lion',
                        '10.6' => 'Snow Leopard',
                        '10.5' => 'Leopard',
                        '10.4' => 'Tiger',
                        '10.3' => 'Panther',
                        '10.2' => 'Jaguar',
                        '10.1' => 'Puma'
                    ), str_replace('_', '.', $this->string));
                    break;
                case 'iOS':
                    $this->check('systemVersion', array(
                        'os 7.0' => '7.0',
                        'os 6.1' => '6.1',
                        'os 6.0' => '6.0',
                        'os 5.1' => '5.1',
                        'os 5.0' => '5.0',
                        'os 4.3' => '4.3',
                        'os 4.2' => '4.2',
                        'os 4.1' => '4.1',
                        'os 4.0' => '4.0',
                        'os 3' => '3',
                        'os 2' => '2'
                    ), str_replace('_', '.', $this->string));
                    break;
            }
            
            switch ($this->browserName) {
                case 'Firefox':
                    $this->getVersion('firefox/');
                    break;
                case 'Chrome':
                    $this->getVersion('chrome/');
                    $this->getVersion('crios/');
                    break;
                case 'Safari':
                    $this->getVersion('version/');
                    break;
                case 'Opera':
                    $this->getVersion('version/');
                    break;
                case 'Internet Explorer':
                    $this->getVersion('msie ');
                    break;
            }
            
            $this->robot = $this->crawlerDetect();
            
            if ($this->robot !== false) {
                $this->type = 'robot';
            } elseif ($this->contain('mobile')) {
                $this->type = 'mobile';
            } else {
                $this->type = '';
            }
        }
        
        private function contain($str, $base = null)
        {
            if (empty($base)) {
                $base = $this->string;
            }
            return strpos($base, $str);
        }
        
        private function crawlerDetect()
        {
            $crawlers = array(
                array('Google', 'Google'),
                array('msnbot', 'MSN'),
                array('Rambler', 'Rambler'),
                array('Yahoo', 'Yahoo'),
                array('AbachoBOT', 'AbachoBOT'),
                array('accoona', 'Accoona'),
                array('AcoiRobot', 'AcoiRobot'),
                array('ASPSeek', 'ASPSeek'),
                array('CrocCrawler', 'CrocCrawler'),
                array('Dumbot', 'Dumbot'),
                array('FAST-WebCrawler', 'FAST-WebCrawler'),
                array('GeonaBot', 'GeonaBot'),
                array('Gigabot', 'Gigabot'),
                array('Lycos', 'Lycos spider'),
                array('MSRBOT', 'MSRBOT'),
                array('Scooter', 'Altavista robot'),
                array('AltaVista', 'Altavista robot'),
                array('IDBot', 'ID-Search Bot'),
                array('eStyle', 'eStyle Bot'),
                array('Scrubby', 'Scrubby robot')
            );
            
            foreach ($crawlers as $c) {
                if (stristr($this->string, $c[0])) {
                    return $c[1];
                }
            }
            return false;
        } 
        
        private function check($property, array $arr, $base = null)
        {
            if (! property_exists($this, $property)) {
                return false;
            }
            foreach ($arr as $key => $value) {
                if ($this->contain($key, $base)) {
                    return $this->$property = $value;
                }
            }
        }
        
        private function getVersion($str, $base = null)
        {
            if (! isset($base)) {
                $base = $this->string;
            }
            $base .= ' ';
            if ($pos = strpos($base, $str)) {
                $vers = substr($base, $pos + strlen($str));
            } else {
                return false;
            }
            
            if ($pos = strpos($vers, ' ')) {
                $vers = substr($vers, 0, $pos);
            } elseif ($pos = strpos($vers, ';')) {
                $vers = substr($vers, 0, $pos);
            } elseif ($pos = strpos($vers, ')')) {
                $vers = substr($vers, 0, $pos);
            }
            $this->browserFullVersion = $vers;
            $this->browserVersion = intval($vers);
            return $vers;
        }
    }