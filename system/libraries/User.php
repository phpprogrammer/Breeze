<?php

namespace system\libraries;

defined('ROOT') or die();

use \system\core\Application as App;

class User
{
	private $guest = true;
	private $id = "";
	private $name = "";
	private $role;
	private $permissions = array();
	
	public function __construct($id = null, $name = null)
	{
		if (isset($id, $name)) {
    		Session::setUserID($id);
			$this->guest = false;
			$this->id = $id;
			$this->name = $name;
			//$this->getUserPermissions();
			//$this->touchActivity();
		}
		return $this;
	}
	
	public function name()
	{
		return $this->name;
	}
	
	public function getUserRole()
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