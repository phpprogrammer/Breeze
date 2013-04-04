<?php
    
    namespace system\core;
    
    defined('ROOT') or die();
    
    use \PDO;
    
    class Database extends PDO
    {
        private $error;
        private $sql;
        private $bind;
        private $errorCallbackFunction;
        private $errorMsgFormat;
        private $db_name;
        private $changed_db = false;
        private $_cache;
        private $_cache_group;
        private $_cache_expiry;
        private static $instance;
    
        public static function setInstance(Database $instance)
        {
            if (! isset(self::$instance))
                self::$instance = $instance;
        }
    
        public static function getInstance()
        {
            if (isset(self::$instance))
                return self::$instance;
        }
    
        public function __construct()
        {
            $memory = new Memory('database');
            if (! $memory->isReadable()) {
                return null;
            }
            $this->_cache = $memory->get('cache', false);
            $this->_cache_group = $memory->get('cache_group', 'database');
            $this->_cache_expiry = $memory->get('cache_expiry', 600);
            
            $options = array(
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            );
            
            $dsn = strtolower($memory->get('driver')) . ':host='.$memory->get('host').';dbname='.($this->db_name = $memory->get('database')).';port='.$memory->get('port');
            
            try {
                parent::__construct($dsn, $memory->get('user'), $memory->get('password'), $options);
                $this->useDatabase($this->db_name);
            } catch (PDOException $e) {
                $this->error = $e->getMessage();
            }
        }
    
        private function debug()
        {
            if (! empty($this->errorCallbackFunction)) {
                $error = array('Error' => $this->error);
                if (! empty($this->sql))
                    $error['SQL Statement'] = $this->sql;
                if (! empty($this->bind))
                    $error['Bind Parameters'] = trim(print_r($this->bind, true));
    
                $backtrace = debug_backtrace();
                if (! empty($backtrace)) {
                    foreach ($backtrace as $info) {
                        if ($info['file'] != __FILE__) {
                            $error['Backtrace'] = $info['file'] . ' at line ' . $info['line'];
                        }
                    }
                }
    
                $msg = '';
                if ($this->errorMsgFormat == 'html') {
                    if (! empty($error['Bind Parameters'])) {
                        $error['Bind Parameters'] = '<pre>' . $error['Bind Parameters'] . '</pre>';
                    }
                    $css = trim(file_get_contents(dirname(__FILE__) . '/error.css'));
                    $msg .= '<style type="text/css">' . "\n" . $css . "\n</style>";
                    $msg .= "\n" . '<div class="db-error">' . "\n\t<h3>SQL Error</h3>";
                    foreach ($error as $key => $val) {
                        $msg .= "\n\t<label>" . $key . ":</label>" . $val;
                    }
                    $msg .= "\n\t</div>\n</div>";
                } else if($this->errorMsgFormat == 'text') {
                    $msg .= "SQL Error\n" . str_repeat("-", 50);
                    foreach ($error as $key => $val) {
                        $msg .= "\n\n$key:\n$val";
                    }
                }
    
                $func = $this->errorCallbackFunction;
                $func($msg);
            }
        }
    
        public function delete($table, $where, $bind = '')
        {
            $sql = 'delete from ' . $table . ' where ' . $where . ';';
            return $this->run($sql, $bind);
        }
    
        private function filter($table, $info)
        {
            $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver == 'sqlite') {
                $sql = 'pragma table_info(\'' . $table . '\');';
                $key = 'name';
            } elseif ($driver == 'mysql') {
                $sql = 'describe ' . $table . ';';
                $key = 'Field';
            } else {
                $sql = 'select column_name from information_schema.columns where table_name = \'' . $table . '\';';
                $key = 'column_name';
            }
    
            if (false !== ($list = $this->run($sql))) {
                $fields = array();
                foreach($list as $record)
                    $fields[] = $record[$key];
                return array_values(array_intersect($fields, array_keys($info)));
            }
            return array();
        }
    
        private function cleanup($bind)
        {
            if (! is_array($bind)) {
                if (!empty($bind)) {
                    $bind = array($bind);
                } else {
                    $bind = array();
                }
            }
            return $bind;
        }
    
        public function insert($table, $info)
        {
            $fields = $this->filter($table, $info);
            $sql = 'insert into ' . $table . ' (' . implode($fields, ', ') . ') VALUES (:' . implode($fields, ', :') . ');';
            $bind = array();
            foreach ($fields as $field) {
                $bind[':'.$field] = $info[$field];
            }
            return $this->run($sql, $bind);
        }
    
        public function run($sql, $bind = '')
        {
            $this->sql = trim($sql);
            $this->bind = $this->cleanup($bind);
            $this->error = '';
    
            try {
                $pdostmt = $this->prepare($this->sql);
                if ($pdostmt->execute($this->bind) !== false) {
                    if (stripos($this->sql, 'select') === 0 || stripos($this->sql, 'describe') === 0 || stripos($this->sql, 'pragma') === 0) {
                        return $pdostmt->fetchAll(PDO::FETCH_ASSOC);
                    } elseif (stripos($this->sql, 'insert') === 0 || stripos($this->sql, 'update') === 0 || stripos($this->sql, 'delete') === 0) {
                        return $pdostmt->rowCount();
                    }
                }
            } catch (PDOException $e) {
                $this->error = $e->getMessage();
                $this->debug();
                return false;
            }
        }
    
        public function select($table, $where = '', $bind = '', $fields = '*')
        {
            $sql = 'select ' . $fields . ' from ' . $table;
            if (! empty($where)) {
                $sql .= ' where ' . $where;
            }
            $sql .= ';';
            if ($this->_cache === true && $result = $this->_retrieveCache($sql) && !empty($result)) {
                return $result;
            } else {
                $result = $this->run($sql, $bind);
                if ($this->_cache === true && !empty($result)) {
                    $this->_storeCache($sql, $result);
                }
            }
            return $result;
        }
    
        public function exists($table, $where, $bind = '')
        {
            $sql = 'select 1 from ' . $table;
            if (! empty($where)) {
                $sql .= ' where ' . $where . ' limit 1';
            }
            $sql .= ';';
            $result = $this->run($sql, $bind);
            return count($result) === 1 ? true : false;
        }
    
        public function update($table, $info, $where = '', $bind = '')
        {
            $fields = $this->filter($table, $info);
            $fieldSize = sizeof($fields);
    
            $sql = 'update ' . $table . ' set ';
            for ($f = 0; $f < $fieldSize; ++$f) {
                if($f > 0)
                    $sql .= ', ';
                $sql .= $fields[$f] . ' = :update_' . $fields[$f];
            }
            if (!empty($where)) {
                $sql .= ' where ' . $where;
            }
            $sql .= ';';
    
            $bind = $this->cleanup($bind);
            foreach ($fields as $field) {
                $bind[':update_'.$field] = $info[$field];
            }
    
            return $this->run($sql, $bind);
        }
        
        public function alter($target, $actions, $bind = '')
        {
            $target = trim($target);
            $actions = trim($actions);
            if (stripos($target, 'database') !== 0 && stripos($target, 'table') !== 0) {
                if (strpos($target, '.') > 0) {
                    $target = 'table '.$target;
                } else {
                    $target = 'database '.$database;
                }
            }
            if (!empty($bind)) {
                foreach ($bind as $key => $value) {
                    $actions = str_replace(':'.$key, $value, $actions);
                }
            }
            return $this->run('alter '.$target.' '.$actions.';');
        }
        
        public function describe($table)
        {
            $result = $this->run('describe '.$table.';');
            if (!empty($result)) {
                foreach ($result as $index => $row) {
                    $pos = strpos($row['Type'], '(');
                    $result[$index]['DataType'] = substr($row['Type'], 0, $pos == false ? strlen($row['Type']) : $pos);
                    $result[$index]['Length'] = filter_var($row['Type'], FILTER_SANITIZE_NUMBER_INT);
                    $result[$index]['Default'] = $row['Default'] === 'NULL' ? '' : $row['Default'];
                    $result[$index]['Auto_increment'] = stripos($row['Extra'], 'auto_increment') !== false;
                    $result[$index]['Null'] = $row['Null'] === 'YES';
                }
            }
            return $result;
        }
        
        public function flush($table)
        {
            $this->exec('flush table '.$table.';');
        }
        
        public function useDatabase($dbname)
        {
            $this->changed_db = true;
            $this->exec('use '.$dbname.';');
        }
        
        public function quit()
        {
            if ($this->changed_db === true) {
                $this->exec('use '.$this->db_name.';');
                $this->changed_db = false;
            }
        }
        
        public function showDatabases()
        {
            $dbs = $this->query('show databases;');
            return $this->_toArray($dbs, true);
        }
        
        public function showTables($schema = '')
        {
            if (!empty($schema)) {
                $schema = 'from '.$schema;
            }
            $tbs = $this->query('show tables '.$schema.';');
            return $this->_toArray($tbs, true);
        }
        
        public function showColumns($table = '')
        {
            if (!empty($table)) {
                $table = 'from '.$table;
            }
            $cls = $this->query('show columns '.$table.';');
            return $this->_toArray($cls);
        }
        
        public function showEngines($fields = '*')
        {
            return $this->select('information_schema.engines', 'not support="NO" and not engine="PERFORMANCE_SCHEMA" order by "engine" asc', '', $fields);
        }
        
        public function showCollation($fields = '*')
        {
            return $this->select('information_schema.collations', '1 order by "collation_name" asc', '', $fields);
        }
        
        public function info($table = '', $fields = '*')
        {
            $dbname = '';
            if (strpos($table, '.') === false) {
                $dbname = $this->db_name;
            } else {
                list($dbname, $table) = explode('.', $table);
            }
            $return = $this->select(
                'information_schema.tables',
                'TABLE_SCHEMA=:db and TABLE_NAME=:table',
                array('db' => $dbname, 'table' => $table), 
                $fields
            );
            if (!empty($return)) {
                return $return[0];
            } else {
                return null;
            }
        }
        
        public function infoSchema($dbname = '', $fields = '*')
        {
            if (empty($dbname)) {
                $dbname = $this->db_name;
            }
            $return = $this->select(
                'information_schema.schemata',
                'SCHEMA_NAME=:db',
                array('db' => $dbname),
                $fields
            );
            if (!empty($return)) {
                return $return[0];
            } else {
                return null;
            }
        }
        
        public function count($table)
        {
            $return = $this->select($table, null, null, 'count(*) "count"');
            if (!empty($return)) {
                return intval($return[0]['count']);
            } else {
                return 0;
            }
        }
        
        public function getDatabasesSize()
        {
            $return = array();
            $sel = $this->select(
                'information_schema.tables group by table_schema',
                null,
                null,
                'table_schema "name", Round(Sum(data_length + index_length) / 1024, 0) "size"'
            );
            foreach ($sel as $key => $value) {
                $return[$value['name']] = $value['size'];
            }
            return $return;
        }
        
        public function createTable($table, $info, $primary_key = null, $engine = 'InnoDB')
        {
            $sql = 'create table if not exists ' . $table . ' (';
            foreach ($info as $key => $value) {
                $sql .= '`'.$key.'` '. $value . ',';
            }
            
            if (! isset($primary_key)) {
                $primary_key = array_keys($info);
                $primary_key = $primary_key[0];
            }
            $sql .= ' primary key (`'. $primary_key .'`)';
            $sql .= ') engine='. $engine .' default charset=utf8 collate=utf8_unicode_ci auto_increment=1;';
            return $this->exec($sql);
        }
        
        public function issetTable($table, $schema = '')
        {
            return in_array($table, $this->showTables($schema));
        }
        
        public function existsTable($table, $schema = null)
        {
            if (! isset($schema)) {
                $schema = $this->db_name;
            }
            try {
                $result = $this->select('information_schema.tables', 'table_schema=:schema and table_name=:name limit 1', array('schema' => $schema, 'name' => $table));
            } catch (Exception $e) {
                return false;
            }
            return count($result) > 0 ? true : false;
        }
        
        public function renameTable($old, $new, $schema)
        {
            if (!is_array($old)) {
                $old = array($old);
            }
            if (!is_array($new)) {
                $new = array($new);
            }
            $sql = 'rename table ';
            foreach ($old as $index => $value) {
                if (!isset($new[$index])) {
                    break;
                }
                if (isset($schema)) {
                    $value = $schema.'.'.$value;
                    $new[$index] = $schema.'.'.$new[$index];
                }
                if ($index !== 0)
                    $sql .= ', ';
                $sql .= $value.' to '.$new[$index];
            }
            $this->run($sql.';');
            return true;
        }
        
        public function setErrorCallbackFunction($errorCallbackFunction, $errorMsgFormat = 'html')
        {
            if (in_array(strtolower($errorCallbackFunction), array('echo', 'print'))) {
                $errorCallbackFunction = 'print_r';
            }
            if (function_exists($errorCallbackFunction)) {
                $this->errorCallbackFunction = $errorCallbackFunction;
                if (! in_array(strtolower($errorMsgFormat), array('html', 'text'))) {
                    $errorMsgFormat = 'html';
                }
                $this->errorMsgFormat = $errorMsgFormat;
            }
        }
        
        public function turnOnCache()
        {
            $this->_cache = true;
        }
        
        public function turnOffCache()
        {
            $this->_cache = false;
        }
        
        private function _toArray(\PDOStatement $statement, $sort = false)
        {
            $return = array();
            
            while (($row = $statement->fetchColumn(0)) !== false) {
                $return[] = $row;
            }
            if ($sort) {
                sort($return);
            }
            return $return;
        }
        
        private function _storeCache($sql, $result)
        {
            $cache = new Cache();
            $cache->openGroup($this->_cache_group);
            $cache->store($sql, $result);
        }
        
        private function _retrieveCache($sql)
        {
            $cache = new Cache();
            $cache->openGroup($this->_cache_group);
            if ($cache->exists($sql, $this->_cache_expiry)) {
                return $cache->retrieve();
            } else {
                return null;
            }
        }
    }