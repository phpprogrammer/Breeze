<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class Security
    {
        private $memory;
        private $blacklist = array();
        private $blacklistPath;
        
        public function __construct()
        {
            $this->memory = new Memory('security');
            $this->blacklistPath = DEF_PATH.'blacklist.txt';
            if (!file_exists($this->blacklistPath)) {
                file_put_contents($this->blacklistPath, '');
            }
            $this->blacklist = file($this->blacklistPath);
            
            if ($this->memory->get('anti_flooding', true)) {
                $this->antiFloodingFilter();
            }
            if ($this->memory->get('ip_blacklist', false)) {
                $this->ipFilter();
            }
            if (! $this->memory->get('robot_allowed', true)) {
                $this->robotFilter();
            }
        }
        
        public function antiFloodingFilter()
        {
            $res = Application::$db->select('security_activities', 'IP=:ip', array('ip' => $_SERVER['REMOTE_ADDR']), 'time');
            if (! empty($res)) {
                $time = $res[0]['time'];
                $currTime = Timer::micro(2);
                $diff = intval(($currTime - $time) * 1000);
                
                if ($diff < $this->memory->get('interval_between_requests', 1000)) {
                    $irt = (float)$this->memory->get('illegal_request_timeout', 10000)/1000;
                    Application::$db->update('security_activities', array('time' => number_format($currTime + $irt, 2, '.', '')), 'IP=:ip', array('ip' => $_SERVER['REMOTE_ADDR']));
                    new Error('400', array('time' => (int)$irt));
                    exit();
                } else {
                    Application::$db->update('security_activities', array('time' => $currTime), 'IP=:ip', array('ip' => $_SERVER['REMOTE_ADDR']));
                }
            } else {
                Application::$db->insert('security_activities', array('IP' => $_SERVER['REMOTE_ADDR'], 'time' => Timer::micro(2)));
            }
        }
        
        public function ipFilter()
        {
            if ($this->is_blocked($_SERVER['REMOTE_ADDR'])) {
                new Error('403');
            }
        }
        
        public function robotFilter()
        {
            if (UserAgent::is_robot()) {
                new Error('403');
            }
        }
        
        public function blockIP($ip = null, $unblock = false)
        {
            if (empty($ip)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            if ($unblock === false) {
                if (! $this->is_blocked($ip)) {
                    $this->blacklist[] = $ip . PHP_EOL;
                    file_put_contents($this->blacklistPath, implode('', $this->blacklist));
                    return true;
                }
            } else {
                for ($i = 0; $i < count($this->blacklist); $i++) {
                    if ($this->blacklist[$i] === $ip.PHP_EOL) {
                        unset($this->blacklist[$i]);
                        file_put_contents($this->blacklistPath, implode('', $this->blacklist));
                        return true;
                    }
                }
            }
            return false;
        }
        
        public function is_blocked($ip)
        {
            return in_array($ip.PHP_EOL, $this->blacklist);
        }
        
        public function flush_blocked()
        {
            $this->blacklist = array();
            file_put_contents($this->blacklistPath, '');
        }
    }