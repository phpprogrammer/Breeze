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
        private $cache;
        private $db_name;
        private $changed_db = false;
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
            $options = array(
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            );
    
            $dsn = strtolower($memory->get('driver')) . ":host=".$memory->get('host').";dbname=".($this->db_name = $memory->get('database')).";port=".$memory->get('port');
    
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
                $error = array("Error" => $this->error);
                if (! empty($this->sql))
                    $error["SQL Statement"] = $this->sql;
                if (! empty($this->bind))
                    $error["Bind Parameters"] = trim(print_r($this->bind, true));
    
                $backtrace = debug_backtrace();
                if (! empty($backtrace)) {
                    foreach ($backtrace as $info) {
                        if ($info["file"] != __FILE__) {
                            $error["Backtrace"] = $info["file"] . " at line " . $info["line"];
                        }
                    }
                }
    
                $msg = "";
                if ($this->errorMsgFormat == "html") {
                    if (! empty($error["Bind Parameters"])) {
                        $error["Bind Parameters"] = "<pre>" . $error["Bind Parameters"] . "</pre>";
                    }
                    $css = trim(file_get_contents(dirname(__FILE__) . "/error.css"));
                    $msg .= '<style type="text/css">' . "\n" . $css . "\n</style>";
                    $msg .= "\n" . '<div class="db-error">' . "\n\t<h3>SQL Error</h3>";
                    foreach ($error as $key => $val) {
                        $msg .= "\n\t<label>" . $key . ":</label>" . $val;
                    }
                    $msg .= "\n\t</div>\n</div>";
                } else if($this->errorMsgFormat == "text") {
                    $msg .= "SQL Error\n" . str_repeat("-", 50);
                    foreach ($error as $key => $val) {
                        $msg .= "\n\n$key:\n$val";
                    }
                }
    
                $func = $this->errorCallbackFunction;
                $func($msg);
            }
        }
    
        public function delete($table, $where, $bind = "")
        {
            $sql = "DELETE FROM " . $table . " WHERE " . $where . ";";
            $this->run($sql, $bind);
        }
    
        private function filter($table, $info)
        {
            $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver == 'sqlite') {
                $sql = "PRAGMA table_info('" . $table . "');";
                $key = "name";
            } elseif ($driver == 'mysql') {
                $sql = "DESCRIBE " . $table . ";";
                $key = "Field";
            } else {
                $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '" . $table . "';";
                $key = "column_name";
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
            $sql = "insert into " . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
            $bind = array();
            foreach ($fields as $field) {
                $bind[":$field"] = $info[$field];
            }
            return $this->run($sql, $bind);
        }
    
        public function run($sql, $bind = "")
        {
            $this->sql = trim($sql);
            $this->bind = $this->cleanup($bind);
            $this->error = "";
    
            try {
                $pdostmt = $this->prepare($this->sql);
                if ($pdostmt->execute($this->bind) !== false) {
                    if (stripos($this->sql, "select") === 0 || stripos($this->sql, "describe") === 0 || stripos($this->sql, "pragma") === 0) {
                        return $pdostmt->fetchAll(PDO::FETCH_ASSOC);
                    } elseif (stripos($this->sql, "insert") === 0 || stripos($this->sql, "update") === 0 || stripos($this->sql, "delete") === 0) {
                        return $pdostmt->rowCount();
                    }
                }
            } catch (PDOException $e) {
                $this->error = $e->getMessage();
                $this->debug();
                return false;
            }
        }
    
        public function select($table, $where = "", $bind = "", $fields = "*", $useCache = true)
        {
            $sql = "select " . $fields . " from " . $table;
            if (! empty($where)) {
                $sql .= " where " . $where;
            }
            $sql .= ";";
            if ($useCache === true && $this->check_cache($sql) === true) {
                return $this->cache->load();
            }
            $result = $this->run($sql, $bind);
            if ($useCache === true) {
                $this->cache->save($result);
            }
            return $result;
        }
    
        public function exists($table, $where, $bind = "")
        {
            $sql = "select 1 from " . $table;
            if (! empty($where)) {
                $sql .= " where " . $where . " limit 1";
            }
            $sql .= ";";
            $result = $this->run($sql, $bind);
            return count($result) === 1 ? true : false;
        }
    
        public function update($table, $info, $where = "", $bind = "")
        {
            $fields = $this->filter($table, $info);
            $fieldSize = sizeof($fields);
    
            $sql = "update " . $table . " set ";
            for ($f = 0; $f < $fieldSize; ++$f) {
                if($f > 0)
                    $sql .= ", ";
                $sql .= $fields[$f] . " = :update_" . $fields[$f];
            }
            if (!empty($where)) {
                $sql .= " where " . $where;
            }
            $sql .= ";";
    
            $bind = $this->cleanup($bind);
            foreach ($fields as $field) {
                $bind[":update_$field"] = $info[$field];
            }
    
            return $this->run($sql, $bind);
        }
        
        public function describe($table)
        {
            return $this->run("describe ".$table.";");
        }
        
        public function flush($table)
        {
            $this->exec("flush table ".$table.";");
        }
        
        public function useDatabase($dbname)
        {
            $this->changed_db = true;
            $this->exec("use ".$dbname.";");
        }
        
        public function quit()
        {
            if ($this->changed_db === true) {
                $this->exec("use ".$this->db_name.";");
                $this->changed_db = false;
            }
        }
        
        public function showDatabases()
        {
            $dbs = $this->query("show databases;");
            return $this->_toArray($dbs);
        }
        
        public function showTables($dbname = "")
        {
            if (!empty($dbname)) {
                $dbname = "from ".$dbname;
            }
            $tbs = $this->query("show tables ".$dbname.";");
            return $this->_toArray($tbs);
        }
        
        public function showColumns($table)
        {
            if (!empty($table)) {
                $table = "from ".$table;
            }
            $cls = $this->query("show columns ".$table.";");
            return $this->_toArray($cls);
        }
        
        public function info($table = "", $fields = "*")
        {
            $dbname = "";
            if (strpos($table, ".") === false) {
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
        
        public function infoSchema($dbname = "", $fields = "*")
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
        
        public function createTable($table, $info, $primary_key = null, $engine = "InnoDB")
        {
            $sql = "create table if not exists " . $table . " (";
            foreach ($info as $key => $value) {
                $sql .= "`".$key."` ". $value . ",";
            }
            
            if (! isset($primary_key)) {
                $primary_key = array_keys($info);
                $primary_key = $primary_key[0];
            }
            $sql .= " primary key (`". $primary_key ."`)";
            $sql .= ") engine=". $engine ." default charset=utf8 collate=utf8_unicode_ci auto_increment=1;";
            echo $sql ."<br/>";
            return $this->exec($sql);
        }
    
        public function existsTable($table, $db_name = null)
        {
            if (! isset($db_name)) {
                $db_name = $this->db_name;
            }
            try {
                $result = $this->select("information_schema.tables", "table_schema=:schema and table_name=:name limit 1", array('schema' => $db_name, 'name' => $table));
            } catch (Exception $e) {
                return false;
            }
            return count($result) > 0 ? true : false;
        }
    
        public function setErrorCallbackFunction($errorCallbackFunction, $errorMsgFormat="html")
        {
            if (in_array(strtolower($errorCallbackFunction), array("echo", "print"))) {
                $errorCallbackFunction = "print_r";
            }
            if (function_exists($errorCallbackFunction)) {
                $this->errorCallbackFunction = $errorCallbackFunction;
                if (! in_array(strtolower($errorMsgFormat), array("html", "text"))) {
                    $errorMsgFormat = "html";
                }
                $this->errorMsgFormat = $errorMsgFormat;
            }
        }
        
        private function _toArray(\PDOStatement $statement)
        {
            $return = array();
            
            while (($row = $statement->fetchColumn(0)) !== false) {
                $return[] = $row;
            }
            return $return;
        }
        
        private function check_cache($sql)
        {
            $this->cache = new Cache($sql, CACHE_PATH.'database'.DS);
            return $this->cache->exists();
        }
    }