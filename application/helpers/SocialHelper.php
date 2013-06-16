<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SocialHelper
 *
 * @author borismossounov
 */
class SocialHelper {

    /**
     * https://developers.facebook.com/docs/facebook-login/getting-started-web/
     */
    public static function fbInit($appId = 'YOUR_APP_ID'){
        $html = new Zend_View();
        $html->setScriptPath(ZF_CORE_APPLICATION_PATH.'/views/scripts/social/');
//        print_r($params);
        $html->appId = $appId;
        echo $html->render('fb-init.phtml');
    }
}


