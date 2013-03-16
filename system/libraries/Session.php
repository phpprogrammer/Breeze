<?php

namespace system\libraries;

defined('ROOT') or die();

use \system\core\Application as App;
use \system\core\Memory;

interface sessionInterface
{
    public function __construct($memory);
    public function get($arg1);
    public function set($key, $value);
    public function getID();
    public function getHash();
    public function getUserID();
    public function setUserID($user_id);
    public function save();
    public function debug();
    public function destroy();
}

class Session
{
    private static $instance;
    private static $started = false;
    private static $memory;
    
    public static function start()
    {
        if (self::$started == false) {
            self::$memory = new Memory(CONFIG_PATH.'session.php');
            $ues = self::$memory->get('use_extended_session');
            
            if ($ues === true) {
                self::$started = true;
                self::$instance = new ExtendedSession(self::$memory);
            } elseif (is_string($ues)) {
                self::$started = true;
                self::$instance = new $ues(self::$memory);
            } else {
                
            }
        }
    }
    
    public static function set($key, $value)
    {
        return call_user_func_array(array(self::$instance, 'set'), func_get_args());
    }
    
    public static function get($arg1)
    {
        return call_user_func_array(array(self::$instance, 'get'), func_get_args());
    }
    
    public static function save()
    {
        return self::$instance->save();
    }
    
    public static function getID()
    {
        return self::$instance->getID();
    }
    
    public static function getHash()
    {
        return self::$instance->getHash();
    }
    
    public static function getUserID()
    {
        return self::$instance->getUserID();
    }
    
    public static function setUserID($uid)
    {
        return self::$instance->setUserID($uid);
    }
    
    public static function debug()
    {
        return self::$instance->debug();
    }
    
    public static function destroy()
    {
        return self::$instance->destroy();
    }
    
    public static function getStartTime()
    {
        if (self::$started) {
            return $_SESSION['start_time'];
        } else {
            return false;
        }
    }
    
    private static function compare($old, $new)
    {
        $wn = str_replace(array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0"), '', $old);
        $sl = strlen($wn);
                                
        if ($new[0] !== $old[22] || $new[3] !== $old[5] || $new[6] !== $old[13] || $new[7] !== $old[7] || $new[10] !== $old[18]) {
            return false;
        } else if ($new[2] !== self::count($old, '0') || $new[5] !== self::count($old, $wn[$sl-2]) || $new[9] !== self::count($old, $wn[3 % $sl]) 
                 || $new[11] !== self::count($old, $wn[11 % $sl]) || $new[13] !== self::count($old, '6', 7)) {
            
            return false;
        }
        return true;
    }
    
    private static function count($str, $search, $add=0)
    {
        return ((intval(substr_count($str, $search)) + $add) % 10).'';
    }
}

class ExtendedSession implements sessionInterface
{
    private $cookie_name;
    private $cookie_expire;
    private $hash;
    private $id;
    private $user_id = 0;
    private $data = array();
    private $is_changed = false;
    
    public function __construct($memory)
    {
        $this->cookie_name = $memory->get('cookie_name');
        $this->cookie_expire = $memory->get('cookie_expire');
        
        if ($memory->get('destroy_session_global_var') === true) {
            foreach ($_SESSION as $key => $value) {
                unset($_SESSION[$key]);
            }
        }
        
        if (! is_integer($this->cookie_expire)) {
            $this->cookie_expire = 3600;
            $memory->insert('cookie_expire', 3600)->save();
        }
        
        if (! isset($_COOKIE[$this->cookie_name])) {
            $_COOKIE[$this->cookie_name] = '';
        }

        if (strlen($_COOKIE[$this->cookie_name]) !== 40 || !App::$db->exists('sessions', 'hash=:hash', array('hash' => $_COOKIE[$this->cookie_name]))) {
            $this->_create();
        } else {
            $this->hash = $_COOKIE[$this->cookie_name];
        }
        
        echo $this->hash ."<br/>";
        
        $session = App::$db->select(
            'sessions',
            'hash=:hash and IP=:ip and browser=:b and time > :t', 
            array('hash' => $this->hash, 'ip' => $_SERVER['REMOTE_ADDR'], 'b' => substr(UserAgent::getInstance()->browserName(), 0, 16), 't' => (time() - $this->cookie_expire)),
            'id, user_id, data'
        );
        
        if (! empty($session) && isset($session[0]['id'], $session[0]['user_id'], $session[0]['data'])) {
            $this->id = intval($session[0]['id']);
            $this->user_id = intval($session[0]['user_id']);
            
            $data = unserialize($session[0]['data']);
            if (is_array($data)) {
                $this->data = $data;
                return $this;
            }
        }
        $this->data = array();
        return $this;
    }
    
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $s =& $this->data;
        
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
            $this->is_changed = true;
        }
        if ($value === null) {
            unset($s[$k]);
            return null;
        } else {
            return $s[$k] = $value;
        }
    }
    
    public function get($arg1)
    {
        if (strpos($arg1, '.')) {
            $args = explode('.', $arg1);
        } else {
            $args = func_get_args();
        }
        $s =& $this->data;
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
    
    public function save()
    {
        if ($this->is_changed === true) {
            $data = '';
            if (!empty($this->data)) {
                $data = serialize($this->data);
            }
            
            $old_hash = $this->hash;
            $this->_generateHash();
            
            App::$db->update(
                'sessions',
                array('hash' => $this->hash, 'time' => time(), 'data' => $data),
                'hash=:hash and user_id=:uid and IP=:ip and browser=:b',
                array('hash' => $old_hash, 'uid' => $this->user_id, 'ip' => $_SERVER['REMOTE_ADDR'], 'b' => substr(UserAgent::getInstance()->browserName(), 0, 16))
            );
            
            $_COOKIE[$this->cookie_name] = $this->hash;
            setcookie($this->cookie_name, $this->hash, time() + $this->cookie_expire);
            
            return true;
        }
    }
    
    public function getID()
    {
        return $this->id;
    }
    
    public function getHash()
    {
        return $this->hash;
    }
    
    public function setUserID($user_id)
    {
        $user_id = intval($user_id);
        App::$db->update(
            'sessions',
            array('user_id' => $user_id),
            'id=:id and hash=:hash',
            array('id' => $this->id, 'hash' => $this->hash)
        );
        $this->user_id = $user_id;
    }
    
    public function getUserID()
    {
        return $this->user_id;
    }
    
    public function debug()
    {
        return debug($this->data);
    }
    
    public function destroy()
    {
        return null;
    }
    
    private function _create()
    {
        $this->_generateHash();
        $_COOKIE[$this->cookie_name] = $this->hash;
        setcookie($this->cookie_name, $this->hash, time() + $this->cookie_expire);
               
        App::$db->insert('sessions', array(
            'hash' => $this->hash,
            'user_id' => 0,
            'IP' => $_SERVER['REMOTE_ADDR'],
            'browser' => substr(UserAgent::getInstance()->browserName(), 0, 16),
            'time' => time(),
            'data' => ''
        ));
    }
    
    private function _generateHash()
    {
        $this->_flush();
        do {
            $this->hash = sha1(uniqid(((time()/(77) + 100)*3).$_SERVER['REMOTE_ADDR']));
        } while (App::$db->exists('sessions', 'hash=:hash', array('hash' => $this->hash)));
        return $this->hash;
    }
    
    private function _flush()
    {
        App::$db->delete('sessions', 'time < :t', array('t' => (time() - $this->cookie_expire)));
        App::$db->flush('sessions');
    }
}

class SimpleSession implements sessionInterface
{
    private $user_id = 0;
    private $start_time = 0;
    private $data = array();
    
    public function __construct($memory) {
        session_start();
        session_regenerate_id(true);
        
        $this->start_time = time();
        $this->data =& $_SESSION;
        
        if ($this->user_id = $this->get('userdata.id')) {
            $this->set('userdata.id', null);
        }
        
        return $this;
    }
    
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $s =& $this->data;
        
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
                    return ++$s[$k];
                case '--':
                    return --$s[$k];
            }
        }
        if ($value === null) {
            unset($s[$k]);
            return null;
        } else {
            return $s[$k] = $value;
        }
    }
    
    public function get($arg1)
    {
        if (strpos($arg1, '.')) {
            $args = explode('.', $arg1);
        } else {
            $args = func_get_args();
        }
        $s =& $this->data;
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
    
    public function save()
    {
        
    }
    
    public function getID()
    {
        return 0;
    }
    
    public function getHash()
    {
        return session_id();
    }
    
    public function setUserID($user_id)
    {
        $this->user_id = $user_id;
    }
    
    public function getUserID()
    {
        return $this->user_id;
    }
    
    public function debug()
    {
        return debug($_SESSION);
    }
    
    public function destroy()
    {
        unset($_SESSION);
        session_destroy();
    }
}