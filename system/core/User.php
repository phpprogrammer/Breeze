<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    use \system\core\Application as App;
    
    class User
    {
        private $guest = true;
        private $id = 0;
        private $name = "";
        private $role;
        private $permissions = array();
        
        public function __construct($id = null, $name = null)
        {
            if (isset($id)) {
                Session::setUserID($id);
                $this->guest = false;
                $this->id = $id;
                if (isset($name)) {
                    $this->name = $name;
                } else {
                    $res = App::$db->select('users', 'id=:id', array('id' => $id), 'user');
                    if (!empty($res)) {
                        $this->name = $res[0]['user'];
                    }
                }
                $this->getUserRole();
            }
            return $this;
        }
        
        public function name()
        {
            return $this->name;
        }
        
        public function id()
        {
            return $this->id;
        }
        
        public function &getUserRole()
        {
            if (!isset($this->role) || !($this->role instanceof Role))
                $this->role = new Role($this->id);
            return $this->role;
        }
        
        public function &getUserPermissions()
        {
            $this->getUserRole();
            $this->permissions = $this->role->getPermissions();
            return $this->permissions;
        }
        
        public function touchActivity()
        {
            if (Session::get('userdata.last_seen') + App::$memory->get('activity_expiry_time') < time()) {
                $time = time();
                App::$db->update('users', array('last_seen' => date('Y-m-d H:i:s', $time)), 'user=:u and last_session=:s', array('u' => $this->id, 's' => Session::id()));
                Session::set('userdata.last_seen', $time);
                return true;
            }
            return false;
        }
        
        public function destroy()
        {
            Session::setUserID(0);
            unset($this);
            return null;
        }
    }