<?php

class ZF_Core_OptionsController extends Zend_Controller_Action{

    public function init(){
        Util::turnRendererOff();
    }
    
    public function indexAction(){
    }
    
    public function setAction(){
        $scope = InputHelper::getParam('scope', ''); // could be site
        $prefix = InputHelper::getParam('prefix', '');
        $options = InputHelper::getParams();
        unset($options['module']);
        unset($options['controller']);
        unset($options['action']);
        unset($options['scope']);
        unset($options['prefix']);
        foreach($options as $key=>$value){
//            $match = array();
//            $isSite = false;
            $prfixedKey = $prefix?$prefix.'.'.$key:$key;
//            if(preg_match('%^site\.(.*)$%', $key, $match)){
//                $isSite = true;
//                $prfixedKey = $prefix?$prefix.'.'.$key:$key;
//            }
            if('site' == $scope){
                update_site_option($prfixedKey, $value);
                $options[$key] = get_site_option($prfixedKey, '');
            }else{
                update_option($prfixedKey, $value);
                $options[$key] = get_option($prfixedKey, '');
            }
            
        }
//        die('dd');
//        echo "trash";
//        JsonHelper::respondError('Scary error');
        JsonHelper::respond($options);
    }
    
    public function getAction(){
        
        $optionKeys = InputHelper::getParam('options', '');
        $scope = InputHelper::getParam('scope', ''); // could be site
        $prefix = InputHelper::getParam('prefix', '');
        $options = array();
        if($optionKeys){
            $optionKeys = explode(' ', $optionKeys);
            foreach($optionKeys as $key){
                $prfixedKey = $prefix?$prefix.'.'.$key:$key;
                $options[$key] = 'site' == $scope?
                    get_site_option($prfixedKey, ''):
                    get_option($prfixedKey, '');
            }
        }
        
        JsonHelper::respond($options);
    }
}