<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    class Timer
    {
        public $result;
        private $start;
        private $stop;
        private $inMS = false;
        
        public function __construct($ms = false)
        {
            $this->start($ms);
            return $this;
        }
        
        public function start($ms = false)
        {
            if ($ms === true) {
                $this->inMS();
            }
            return $this->start = $this->micro();
        }
        
        public function stop($precision = 5)
        {
            $this->stop = $this->micro();
            if ($this->inMS === true) {
                return $this->result = round(($this->stop - $this->start)*1000);
            } else {
                return $this->result = number_format($this->stop - $this->start, $precision);
            }
        }
        
        private function micro()
        {
            list($msec, $sec) = explode(" ", microtime());
            return ((float)$msec + (float)$sec);
        }
        
        public function inMS()
        {
            $this->inMS = true;
        }
    }