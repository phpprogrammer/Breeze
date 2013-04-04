<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    ini_set('max_execution_time', 60);
    
    class SystemUpdater
    {
        public $currentVersion;
        public $latestVersion;
        public $latestUpdate;
        private $_log = true;
        private $updateUrl = "";
        private $tempDir = TEMP_PATH;
        
        public function __construct()
        {
            $memory = new Memory('updates');
            $this->currentVersion =& Application::$version;
            $this->updateUrl = rtrim($memory->get('update_url', ''), "/");
            $this->_log = $memory->get('logging', true);
            
            if ($this->_log) {
                $this->log = new Log('updates');
                $this->log->flush();
            }
        }
        
        public function log($message)
        {
            if ($this->_log) {                
                $this->log->write($message);
            }
        }
        
        public function checkUpdate()
        {
            $this->log('Checking for a new update. . .');
            
            $updateFile = $this->updateUrl.'/update.ini';
            
            $update = @ file_get_contents($updateFile);
            
            if ($update === false) {
                $this->log('Could not retrieve update file `'.$updateFile.'`!');
                return false;
            } else if (!empty($update)) {
                $ini = parse_ini_string($update, true);
                
                if (! empty($ini) && is_array($ini)) {
                    $this->log('New version found `'.$ini['version'].'`.');
                    $this->latestVersion = $ini['version'];
                    $this->latestUpdate = $ini['url'];
                    
                    if ($ini['version'] > $this->currentVersion) {
                        return true;
                    }
                    return false;
                } else {
                    $this->log('Unable to parse update file!');
                    return false;
                }
            } else {
                $this->log('Update file is empty!');
                return false;
            }
        }
        
        public function downloadUpdate($updateUrl, $updateFile)
        {
            $this->log('Downloading update...');
            
            $update = @ file_get_contents($updateUrl);
            
            if ($update === false) {
                $this->log('Could not download update `'.$updateUrl.'`!');
                return false;
            }
            
            $handle = fopen($updateFile, 'w');
            
            if (!$handle) {
                $this->log('Could not save update file `'.$updateFile.'`!');
                return false;
            }
            
            if (!fwrite($handle, $update)) {
                $this->log('Could not write to update file `'.$updateFile.'`!');
                return false;
            }
            
            fclose($handle);
            
            return true;
        }
        
        public function install($updateFile)
        {
            $zip = zip_open($updateFile);
                
            while ($file = zip_read($zip)) {				
                $filename = zip_entry_name($file);
                if ($filename === '.' || $filename === '..' || $filename === '.DS_Store' || $filename[0] === '_') {
                    continue;
                }
                $foldername = String::glue(ROOT, dirname($filename));
                $filepath = String::glue(ROOT, $filename);
                
                if (substr($filename, -1, 1) === '/' || strrchr($filename, '.') === '.DS_Store') {
                    continue;
                }
                
                $this->log('Updating `'.$filename.'`!');
                
                if (!is_dir($foldername)) {
                    if (!mkdir($foldername, 0755, true)) {
                        $this->log('Could not create folder `'.$foldername.'`!');
                    }
                }
                
                $contents = zip_entry_read($file, zip_entry_filesize($file));
                
                if (!is_writable($filepath)) {
                    $this->log('Could not update `'.$filepath.'`, not writeable!');
                    continue;
                }
                
                $updateHandle = @fopen($filepath, 'w');
                
                if (!$updateHandle) {
                    $this->log('Could not update file `'.$filepath.'`!');
                    continue;
                }
                
                if (!fwrite($updateHandle, $contents)) {
                    $this->log('Could not write to file `'.$filepath.'`!');
                    continue;
                }
                
                fclose($updateHandle);
            }
            
            zip_close($zip);
            
            $unlink = unlink(rtrim($this->tempDir, '/').'/'.ltrim($this->latestUpdate, '/'));
            if ($unlink) {
                $this->log('Package '.$this->latestUpdate.' deleted.');
            } else {
                $this->log('Package '.$this->latestUpdate.' wasn\'t deleted.');
            }
            
            $this->log('Update `'.$this->latestVersion.'` installed.');
            
            return true;
        }
        
        public function update()
        {
            if ((is_null($this->latestVersion)) || (is_null($this->latestUpdate))) {
                $this->checkUpdate();
            }
            
            if ((is_null($this->latestVersion)) || (is_null($this->latestUpdate))) {
                return false;
            }
            
            if ($this->latestVersion > $this->currentVersion) {
                $this->log('Updating...');
                
                if ($this->tempDir[strlen($this->tempDir)-1] != '/');
                    $this->tempDir = $this->tempDir.'/';
                
                if ((!is_dir($this->tempDir)) && (!mkdir($this->tempDir, 0777, true))) {
                    $this->log('Temporary directory `'.$this->tempDir.'` does not exist and could not be created!');
                    return false;
                }
                
                if (!is_writable($this->tempDir)) {
                    $this->log('Temporary directory `'.$this->tempDir.'` is not writeable!');
                    return false;
                }
                
                $updateFile = rtrim($this->tempDir, '/').'/'.ltrim($this->latestUpdate, '/');
                $updateUrl = rtrim($this->updateUrl, '/').'/'.ltrim($this->latestUpdate, '/');
                
                if (!is_file($updateFile)) {
                    if (!$this->downloadUpdate($updateUrl, $updateFile)) {
                        $this->log('Failed to download update!');
                        return false;
                    }
                    
                    $this->log('Latest update downloaded to `'.$updateFile.'`.');
                }
                else {
                    $this->log('Latest update already downloaded to `'.$updateFile.'`.');
                }
                
                return $this->install($updateFile);
            }
            else {
                $this->log('No update available!');
                return false;
            }
        }
    }