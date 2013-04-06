<?php
    
    namespace application\views;
    
    defined('ROOT') or die();
    
    use \system\core;
    
    class TestView extends core\Subview implements core\viewInterface
    {
        public function __construct(){ return $this; }
        
        public function render()
        {
            $this->import('home_index');
        }
    }