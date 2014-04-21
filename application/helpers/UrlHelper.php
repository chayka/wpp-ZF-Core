<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlHelper
 *
 * @author borismossounov
 */
class UrlHelper {

    /**
     * 
     * @param type $clearParams
     * @return Zend_Controller_Router_Abstract
     */
    public static function getRouter($clearParams = true){
        $router = Util::getFront()?Util::getFront()->getRouter():null;

        if($router && $clearParams){
            $router->clearParams();
        }

        return $router;
    }
    
    public static function assemble($userParams, $name=null, $reset=false, $encode=true){
        return self::getRouter()->assemble($userParams, $name, $reset, $encode);
    }
}
