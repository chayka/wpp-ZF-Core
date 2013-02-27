<?php

/**
 * Description of DbHelper
 *
 * @author Digital
 */
class DbHelper {

    /**
     *
     * @return Zend_Db_Adapter_Mysqli 
     */
    public static function getAdapter() {
        if (!Zend_Registry::isRegistered('db')) {
            $dbConfig = new Zend_Config_Xml(PathHelper::getDatabaseConfigFilename(), 'database', true);
            $db = Zend_Db::factory($dbConfig);
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
    
}

