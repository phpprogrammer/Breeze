<?php

namespace system\libraries;
	
defined('ROOT') or die();

use \system\core\Application as App;

class Role
{
	private $user_id;
	private $role_id;
	private $role_name;
	private $permissions;
	
	public function __construct($user_id="")
	{
		if (isset($user_id)) {
			$this->user_id = $user_id;
			$this->init();
		}
		return $this;
	}
	
	private function init()
	{
		$this->role_id = Session::get('userdata.role.id');
		$this->role_name = Session::get('userdata.role.name');
		
		if (Session::get('userdata.last_seen') + App::$memory->get('activity_expiry_time') < time() || !isset($this->role_id) || !isset($this->role_name)) {
			$rls = App::$db->select('user_role join roles on user_role.role_id = roles.id', 'user_role.user_id=:uid', array('uid' => $this->user_id), 'user_role.role_id, roles.name', false);
			if (!empty($rls)) {
				$this->role_id = $rls[0]['role_id'];
				$this->role_name = $rls[0]['name'];
				Session::set('userdata.role.id', $this->role_id);
				Session::set('userdata.role.name', $this->role_name);
			}
		}
	}
	
	public function &getPermissions()
	{
		if (!isset($this->permissions) || !($this->permissions instanceof Permissions))
			$this->permissions = new Permissions($this->role_id);
		return $this->permissions;
	}
	
	public function getName()
	{
		return $this->role_name;
	}
	
	public function getID()
	{
		return $this->role_id;
	}
}