<?php

require_once 'Zend/Db/Adapter/Mysqli.php';

interface DbRecordInterface{
    public static function unpackDbRecord($dbRecord);
    public function packDbRecord();
    public function insert();
    public function update();
    public function delete();
}

class Zend_Db_Adapter_Wpdb extends Zend_Db_Adapter_Mysqli{

    public function __construct($config) {
        global $wpdb;
        parent::__construct($config);
        $this->_connection = $wpdb->dbh;
    }
}

class WpDbHelper {

    /**
     *
     * @return Zend_Db_Adapter_Mysqli 
     */
    public static function getAdapter() {
        global $wpdb;
        if (!Zend_Registry::isRegistered('db')) {
//            $dbConfig = new Zend_Config_Xml(PathHelper::getDatabaseConfigFilename(), 'database', true);
//            $db = Zend_Db::factory($dbConfig);
            $db = new Zend_Db_Adapter_Wpdb(array(
                'host' => 'localhost',
                'dbname' => 'wp',
                'username' => 'wp',
                'password' => 'wp'
            ));
//            print_r()
//            die('(@)');
            $db->query('set names utf8');
            $db->query('set collation_connection=utf8_general_ci');
            $db->setFetchMode(Zend_Db::FETCH_ASSOC);
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
            Zend_Registry::set('db', $db);
        }
        return Zend_Registry::get('db');
    }

    public static function close() {
        if (Zend_Registry::isRegistered('db')) {
            Zend_Registry::get('db')->closeConnection();
            Zend_Registry::set('db', null);
        }
    }
    
    public static function dbInstall($currentVersion, $versionOptionName, $sqlPath, $versionHistory = array('1.0')) {
        global $wpdb;
        $installedVer = get_option($versionOptionName);
        
//        $versionHistory = array('1.0', '1.1');
//        $versionHistory = array('1.0');
        
        $queries = array();
        if(!$installedVer){
            $filename = $sqlPath.'/install.'.$currentVersion.'.sql';
            if(file_exists($filename)){
                $cnt = file_get_contents($filename);
                $tmp = preg_split("%;\s*%m", $cnt);
                foreach($tmp as $query){
                    $queries[] = str_replace('{prefix}', $wpdb->prefix, $query);
                }
            }
        }elseif ($installedVer != $currentVersion){
            $found = false;
            foreach ($versionHistory as $ver){
                if($found){
                    $filename = $sqlPath.'/update.'.$ver.'.sql';
                    if(file_exists($filename)){
                        $cnt = file_get_contents($sqlPath.'/update.'.$ver.'.sql');
                        $tmp = preg_split("%;\s*%m", $cnt);
                        foreach($tmp as $query){
                            $queries[] = str_replace('{prefix}', $wpdb->prefix, $query);
                        }
                    }
                }
                if(!$found && $ver==$installedVer){
                    $found = true;
                }
            }
        }
        
        foreach($queries as $query){
            $wpdb->query($query);
        }
        
        add_option($versionOptionName, $currentVersion);
    }

    public static function dbUpdate($currentVersion, $versionOptionName, $sqlPath, $versionHistory = array('1.0')) {
        if (get_site_option($versionOptionName) != $currentVersion) {
            self::dbInstall($currentVersion, $versionOptionName, $sqlPath, $versionHistory);
        }
    }
    
    public static function dbTable($table){
        global $wpdb;
        return $wpdb->prefix.$table;
    }

}

