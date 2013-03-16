<?php

namespace system\libraries;

defined('ROOT') or die();

use \system\core\Application as App;

class Permissions
{
	private $permissions;
	
	public function __construct($role_id="")
	{
		if (isset($role_id)) {
			$this->permissions = array();
			$this->role_id = $role_id;
			$this->init();
			return $this;
		}
		return null;
	}
	
	private function init()
	{
		$this->permissions = Session::get('userdata.role.permissions');
		
		if (Session::get('userdata.last_seen') + App::$memory->get('activity_expiry_time') < time() || empty($this->permissions)) {
			$perms = App::$db->select('role_perm join permissions on role_perm.perm_id = permissions.id', 'role_perm.role_id=:rid', array('rid' => $this->role_id), 'permissions.name');
			$this->permissions = array();
			foreach ($perms as $key => $value) {
				$this->permissions[] = $value['name'];
			}
			Session::set('userdata.role.permissions', $this->permissions);
		}
	}
	
	public function isAllowed($action="")
	{
		if (empty($action)) return false;
		
		if (isset($this->permissions[$action]) && $this->permissions[$action] === true)
			return true;
		else
			return false;
	}
}