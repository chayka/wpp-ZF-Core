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

    public static function getRouter($clearParams = true){
        $router = Util::getFront()->getRouter();

        if($clearParams){
            $router->clearParams();
        }

        return $router;
    }
}
