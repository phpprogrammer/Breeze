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
            return $this->start = self::micro();
        }
        
        public function stop($precision = 6)
        {
            $this->stop = self::micro($precision);
            if ($this->inMS === true) {
                return $this->result = round(($this->stop - $this->start)*1000);
            } else {
                return $this->result = number_format($this->stop - $this->start, $precision);
            }
        }
        
        public function inMS()
        {
            $this->inMS = true;
        }
        
        public static function micro($precision = 6, $separator = '.')
        {
            list($msec, $sec) = explode(" ", microtime());
            return number_format((float)$msec + (float)$sec, $precision, $separator, '');
        }
    }