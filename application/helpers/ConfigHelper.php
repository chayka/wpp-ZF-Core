<?php

require_once 'Zend/Config/Ini.php';

class ConfigHelper {

    public static function getInstance() {
        if (!Zend_Registry::isRegistered('config')) {
            $section = str_replace(array('www.', '.'), array('', '_'), $_SERVER['SERVER_NAME']);
            $config = null;
            try{
                $config = new Zend_Config_Ini(PathHelper::getConfigIniFile('application'), $section);
            }catch(Exception $e){
                $config = new Zend_Config_Ini(PathHelper::getConfigIniFile('application'), 'production');
            }
            Zend_Registry::set('config', $config);
        }

        return Zend_Registry::get('config');
    }
    
    public static function getVars() {
        if (!Zend_Registry::isRegistered('vars')) {
            $vars = null;
            $vars = new Zend_Config_Ini(PathHelper::getConfigIniFile('vars'), null,
                              array('skipExtends'        => true,
                                    'allowModifications' => true));
            Zend_Registry::set('vars', $vars);
        }

        return Zend_Registry::get('vars');
    }
    
    public static function updateVars() {
        $vars = self::getVars();
        $writer = new Zend_Config_Writer_Ini(array('config'   => $vars,
                                                   'filename' => PathHelper::getConfigIniFile('vars')));
        $writer->write();        
    }

}

