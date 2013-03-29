<?php
    
    namespace system\core;
        
    defined('ROOT') or die();
    
    use \system\core\Application as App;
    
    class Role
    {
        private $user_id = 0;
        private $id = 0;
        private $name = 'Guest';
        private $hash = 'guest';
        private $root = false;
        private $resources = array();
        
        public function __construct($user_id)
        {
            $this->user_id = $user_id;
            $this->init();
            return $this;
        }
        
        private function init()
        {
            if ($this->user_id !== 0) {
                $result = App::$db->select('users join roles on users.role = roles.id', 'users.id=:id', array('id' => $this->user_id), 'roles.id, roles.name, roles.hash, roles.root');
                
                if (isset($result[0], $result[0]['id'])) {
                    $this->id = $result[0]['id'];
                    $this->name = $result[0]['name'];
                    $this->hash = $result[0]['hash'];
                    $this->root = (bool)$result[0]['root'];
                }
            }
        }
        
        public function getResources()
        {
            $result = null;
            if ($this->id !== 0) {
                $result = App::$db->select('resources right join accesses on resources.id = accesses.resource_id', 'accesses.role_id=:rid', array('rid' => $this->id), 'resources.hash, accesses.access');
            } else {
                $result = App::$db->select('resources', '', array(), 'hash, access');
            }
            
            if (! empty($result)) {
                foreach ($result as $row => $value) {
                    if (($this->id !== 0 || (bool)$value['access'] === true) && (!$this->isRoot() || (bool)$value['access'] === false)) {
                        $this->resources[$value['hash']] = (bool)$value['access'];
                    }
                }
            } else {
                $this->resources = array();
            }
            return $this->resources;
        }
        
        public function isRoot()
        {
            return $this->root;
        }
        
        public function getName()
        {
            return $this->name;
        }
        
        public function getID()
        {
            return $this->id;
        }
    }