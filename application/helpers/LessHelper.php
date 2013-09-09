<?php

class LessHelper {
    
    protected static $instance = null;
    
    public static function getInstance(){
        if(!self::$instance){
            require "library/lessc.inc.php";

            self::$instance = new lessc();
//            self::$instance->setFormatter('compressed');
        }
        return self::$instance;
    }
    
    public static function __callStatic($name, $arguments) {
        try{
            return call_user_func_array(array(self::getInstance(), $name), $arguments);
        }catch(Exception $e){
            
        }
        return null;
    }
    
    /**
     * Check if input file or its dependancies were updated and rebuilds output if necessary
     * 
     * @param type $inputFile
     * @param type $outputFile 
     * @return string output file if ok
     */
    public static function smartCompile($inputFile, $outputFile = null) {
        // load the cache
        $cacheFile = FileSystem::setExtension($inputFile, 'cache');
//        $cacheFile = $inputFile . ".cache";
        if(!$outputFile){
            $outputFile = FileSystem::setExtension($inputFile, 'css');
        }

        if (file_exists($cacheFile)) {
            $cache = unserialize(file_get_contents($cacheFile));
        } else {
            $cache = $inputFile;
        }

        try{
            $less = self::getInstance();
            $newCache = $less->cachedCompile($cache);

            if (!is_array($cache) || $newCache["updated"] > $cache["updated"]) {
                file_put_contents($cacheFile, serialize($newCache));
                file_put_contents($outputFile, $newCache['compiled']);
                
            }
            return file_exists($outputFile)?$outputFile:null;
            
        }catch(Exception $e){
            die($e->getMessage());
        }
        
        return null;
    }

}