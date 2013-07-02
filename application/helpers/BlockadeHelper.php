<?php

class BlockadeHelper{
    protected static $isBlocked = null;
    protected static $title = null;
    protected static $message = null;
    
    public static function block($block = 1){
        update_site_option('BlockadeHelper.isBlocked', $block);
        return self::$isBlocked = get_site_option('BlockadeHelper.isBlocked');
    }
    
    public static function unblock(){
        return self::block(0);
    }
    
    public static function isBlocked(){
        if(self::$isBlocked === null){
            self::$isBlocked = get_site_option('BlockadeHelper.isBlocked', 0);
        }
        
        return self::$isBlocked;
    }
    
    public static function setMessage($message){
        update_site_option('BlockadeHelper.message', $message);
        return self::$message = get_site_option('BlockadeHelper.message');
    }
    
    public static function getMessage($default = ''){
        if(!$default){
            $default = "Сайт временно закрыт.\nПриносим извинения за временные неудобства";
        }
        if(self::$message === null){
            self::$message = get_site_option('BlockadeHelper.message', $default);
        }
        
        return self::$message;
    }
    
    public static function setTitle($title){
        update_site_option('BlockadeHelper.title', $title);
        return self::$title = get_site_option('BlockadeHelper.title');
    }
    
    public static function getTitle($default = ''){
        if(!$default){
            $default = "Сайт заблокирован";
        }
        if(self::$title === null){
            self::$title = get_site_option('BlockadeHelper.title', $default);
        }
        
        return self::$title;
    }
    
    public static function inspectUri($uri){
        if(!self::isBlocked()){
            return true;
        }
        if(AclHelper::isAdmin()){
            return true;
        }

        $allowedRoutes = array(
//            'wp-admin',
            'wp-login.php',
            'auth'
        );
        $m=array();
//            die('['.$uri.']');
        preg_match('%^(\/api|\/widget)?\/([^\/\?]*)%i', $uri, $m);

        $isApi = Util::getItem($m, 1);
        $route = Util::getItem($m, 2);
        if(in_array($route, $allowedRoutes)){
            return true;
        }

        header("HTTP/1.1 503 Service Unavailable");
        if($isApi){
            JsonHelper::respondError(self::getMessage(), 'site_blocked');
        }else{
            ZF_Query::processRequest('/blockade/');
        }
        
        return false;
    }
    
}
