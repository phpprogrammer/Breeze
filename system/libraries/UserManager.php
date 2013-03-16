<?php

namespace system\libraries;

defined('ROOT') or die();

use \system\core\Application as App;

class UserManager
{
    private $mCrypt = 'sha1';
    private static $loggedIn = false;
    private static $user;   
    
    public static function isLoggedIn()
    {
        $uid = Session::getUserID();
        if (isset($uid) && is_int($uid) && $uid > 0) {
            return self::$loggedIn = true;
        } else {
            return self::$loggedIn = false;
        }
    }
    
    public static function getUsername()
    {
        if (self::isLoggedIn()) {
            return self::$user->name();
        }
        return null;
    }
    
    public function __construct()
    {
        if (self::isLoggedIn()) {
            self::$user = new User(Session::getUserID());
        } else {
            self::$user = new User(0);
        }
        return $this;
    }
    
    public function setCryptMethod($mcrypt)
    {
        if (strtolower($mcrypt) === 'sha1' || strtolower($mcrypt) === 'md5') {
            $this->mCrypt = $mcrypt;
        }
    }
    
    public function register($user, $password, $options)
    {
        
    }
    
    public function login($user = null, $password = null)
    {
        if (empty($user) || empty($password)) {
            return false;
        }
        $mcrypt = $this->mCrypt;
        
        if (self::$loggedIn) {
            echo 'Jesteś już zalogowany!';
            return true;
        } elseif ($id = App::$db->select('users', 'user=:u and password=:p', array('u' => $user, 'p' => $password=$mcrypt($password)), 'id', false) && !empty($id)) {
            $id = $id[0]['id'];
            
            $this->clearFailedAttempts();
            
            App::$db->update(
                'users', 
                array('last_login' => time(), 'last_seen' => time(), 'last_session' => Session::getHash(), 'last_IP' => $_SERVER['REMOTE_ADDR']), 
                'user=:u and password=:p', 
                array('u' => $user, 'p' => $password)
            );
            unset($password);
            
            self::$user = new User($id, $user);
            self::$loggedIn = true;
                                
                    echo 'Zalogowano!';
            return true;
        } elseif ($this->issetUser($user)) {
            //...
        } else {
            $fa = $this->incrementFailedAttempts();
            if ($fa >= App::$memory->get('max_login_failed_attempts')) {
                //block ip...
            }
            return false;
        }
    }
    
    public function logout()
    {
        self::$user->destroy();
        self::$loggedIn = false;
        Session::destroy();
    }
    
    public function incrementFailedAttempts()
    {
        return Session::set('login_failed_attempts', '++');
    }
    
    public function clearFailedAttempts()
    {
        return Session::set('login_failed_attempts', null);
    }
    
    public function getUser()
    {
        return self::$user;
    }
    
    public function issetUser($user)
    {
        if (empty($user)) {
            return null;
        }
        return App::$db->exists('users', 'user=:u', array('u' => $user));
    }
}