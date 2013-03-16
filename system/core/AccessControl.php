<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class AccessControl
    {
        private $account = array('anonymous' => true);
        private $role;
        private $rules = array();
        private $size  = 0;
        
        public function __construct(User &$user = null)
        {
            $this->insertNode(-1, '', true);
            if (isset($user)) {
                $this->setAccount($user);
            }
            return $this;
        }
        
        public function setAccount(User &$user)
        {
            if ($user->id() > 0) {
                $this->account['anonymous'] = false;
            }
            
            $this->setRole($user->getUserRole());   
            $res = $this->role->getResources();
            foreach ($res as $key => $value) {
                $this->insertRule($key, (bool)$value);
            }
        }
        
        public function setRole(Role &$role)
        {
            $this->role = $role;
        }
        
        public function setState($state)
        {
            
        }
        
        public function isAnonymous()
        {
            return $this->account['anonymous'];
        }
        
        public function isRoot()
        {
            return $this->role->isRoot();
        }
        
        public function isAllowed($rule)
        {
            $items = explode('/', $rule);
            $id = 0;
            $i = 0;
            $cnt = sizeof($items);
            foreach ($items as $item) {
                if (is_null($id = $this->findNode($id, $item))) {
                    if (! $this->isRoot()) {
                        return false;
                    } else {
                        return true;
                    }
                } elseif ($i === $cnt - 1) {
                    return $this->rules[$id][1];
                } elseif ($this->rules[$id][1] === false) {
                    return false;
                }
                $i++;
            }
            return false;
        }
        
        public function insertRule($resource, $state)
        {
            $items = explode('/', $resource);
            
            $id = 0;
            $x = null;
            $i = 0;
            $cnt = sizeof($items);
            
            foreach ($items as $item) {
                if (is_null($x = $this->findNode($id, $item))) {
                    $x = $this->insertNode($id, $item, $i !== $cnt - 1 ? false : $state);
                } elseif ($i === $cnt - 1) {
                    $this->rules[$x][1] = $state;
                }
                $id = $x;
                $i++;
            }
        }
        
        private function insertNode($parent, $name, $state)
        {
            $this->rules[$this->size] = array(
                0 => $name,
                1 => $state,
                2 => $parent,
                3 => -1,
                4 => -1,
                5 => -1
            );
            
            if ($parent >= 0) {
                if ($this->rules[$parent][5] != -1) {
                    $this->rules[$this->rules[$parent][5]][3] = $this->size;
                    $this->rules[$parent][5] = $this->size;
                } else {
                    $this->rules[$parent][5] = $this->rules[$parent][4] = $this->size;
                }
            }
            $this->size++;
            return $this->size - 1;
        }
        
        private function findNode($parent, $name)
        {
            if (isset($this->rules[$parent])) {
                $id = $this->rules[$parent][4];
                while ($id !== -1) {
                    if ($this->rules[$id][0] == $name) {
                        return $id;
                    }
                    $id = $this->rules[$id][3];
                }
            }
            return null;
        }
    }