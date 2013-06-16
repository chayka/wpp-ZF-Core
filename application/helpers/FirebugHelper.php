<?php

class FirebugHelper{
    protected static $instance;
    
    public static function getInstance($environment = 'development'){
        if(!self::$instance){
            self::$instance = new Zend_Log();

            if ('development' == $environment) {
                self::$instance->addWriter(new Zend_Log_Writer_Firebug());
            } else {
                self::$instance->addWriter(new Zend_Log_Writer_Null());
            }
        }
        
        return self::$instance;
    }
    
    public static function sign($message){
        return 'php: '.$message;
    }
    
    public static function log($message, $priority = Zend_Log::NOTICE){
        return self::getInstance()
                    ->log(self::sign($message), $priority);
    }
    
    public static function info($message){
        return self::getInstance()
                    ->info(self::sign($message));
    }
    
    public static function warn($message){
        return self::getInstance()
                    ->warn(self::sign($message));
    }
    
    public static function error($message){
        return self::getInstance()
                    ->error(self::sign($message));
    }
    
    public static function dir($payload){
        return self::getInstance()->log(JsonHelper::encode($payload));
    }
}