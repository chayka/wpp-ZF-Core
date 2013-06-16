<?php

/**
 * Description of DbHelper
 *
 * @author Digital
 */
class DbHelper {
    
    protected static $adapters = array();

    /**
     *      <host>localhost</host>
            <username>analitix</username>
            <password>analitix</password>
            <dbname>analitix</dbname>

     * @return Zend_Db_Adapter_Mysqli 
     */
    public static function getAdapter($params, $adapter = 'Mysqli') {
        $adapterId = null;
        
        if(!empty($params)){
            $host = 'localhost';
            $dbName = '';
            if(is_string($params)){
                $params = explode('.', $params);
                if(count($params)>1){
                    $host = Util::getItem($params, 0, 'localhost');
                    $dbName = Util::getItem($params, 1);
                }else{
                    $host = 'localhost';
                    $dbName = Util::getItem($params, 0);
                }                    $adapterId = $params;
            }else if(is_array($params)){
                $host = Util::getItem($params, 'host', 'localhost');
                $dbName = Util::getItem($params, 'dbname');
            }
//            Util::print_r($params);
//            Util::print_r(array($host, $dbName, self::$adapters));
            if($dbName){
                $adapterId = $host.'.'.$dbName;
            }
        }

        
        if(empty(self::$adapters) && empty($adapterId)){
            throw new Exception('Unable to find db adapter', 1);
        }
        
        if(!empty(self::$adapters) && !$adapterId){
            $adapterId = key(reset(self::$adapters));
        }
        
        if (empty(self::$adapters[$adapterId]) && !empty($params) && is_array($params)) {
            try{
                $db = Zend_Db::factory($adapter, $params);
                $db->query('set names utf8');
                $db->query('set collation_connection=utf8_general_ci');
                $db->setFetchMode(Zend_Db::FETCH_ASSOC);
                Zend_Db_Table_Abstract::setDefaultAdapter($db);
                self::$adapters[$adapterId] = $db;
            }catch(Exception $e){
                throw $e;
            }
        }
        return self::$adapters[$adapterId];
    }

    public static function close() {
        if (Zend_Registry::isRegistered('db')) {
            Zend_Registry::get('db')->closeConnection();
            Zend_Registry::set('db', null);
        }
    }
    
}

