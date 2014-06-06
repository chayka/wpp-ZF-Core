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
    public static function fbInit($appId = 'YOUR_APP_ID', $locale = 'en_US', $fbLoginJsCallback = null){
        $html = new Zend_View();
        $html->setScriptPath(ZF_CORE_APPLICATION_PATH.'/views/scripts/social/');
//        print_r($params);
        $html->appId = $appId;
        $html->locale = $locale;
        $html->callback = $fbLoginJsCallback;
        echo $html->render('fb-init.phtml');
    }

    public static function linkedInInit($locale = 'en_US'){
        $html = new Zend_View();
        $html->setScriptPath(ZF_CORE_APPLICATION_PATH.'/views/scripts/social/');
//        print_r($params);
        $html->locale = $locale;
        echo $html->render('linked-in-init.phtml');
    }

}


