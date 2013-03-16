<?php

namespace system\libraries;

defined('ROOT') or die();

class AccessControl
{
    public $account = array('anonymous' => true);
    public $role;
    
    private $rules = array();
    private $size  = 0;
    
    public function __construct()
    {
        $this->insertNode(-1, '', true);
    }
    
    public function setAccount()
    {
        
    }
}