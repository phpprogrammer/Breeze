<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class Security
    {
        private $memory;
        
        public function __construct()
        {
            $this->memory = new Memory('security');
        }
        
        public function antiFloodingFilter()
        {
            $eT = Session::getElapsedTime();
            if ($eT !== 0 && $eT < $this->memory->get('interval_between_requests', 1000)) {
                $irt = (float)$this->memory->get('illegal_request_timeout', 10000)/1000;
                Session::touchActivity(Timer::micro(2) + $irt);
                new Error('400', array('time' => (int)$irt ));
            }
        }
        
        public function blockIP($ip)
        {
            
        }
    }