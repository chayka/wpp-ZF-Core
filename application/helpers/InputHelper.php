<?php

require_once 'Zend/Filter.php';
require_once 'ZendB/Filter/StripSlashes.php';

interface InputReadyInterface{
    public function unpackInput($input = array());
    public function validateInput($input = array(), $action = 'create');
    public function getValidationErrors();
}

class InputHelper {

    protected static $defChain;
    protected static $htmlChain;
    protected static $chains;

    protected static function initChains($htmlAllowed = array()) {
        if (empty(self::$chains)) {
            $defChain = new Zend_Filter();
            $defChain->addFilter(new Zend_Filter_StringTrim());
            $defChain->addFilter(new ZendB_Filter_StripSlashes());
            $defChain->addFilter(new Zend_Filter_StripTags());
            self::$defChain = $defChain;
            self::$chains['*'] = $defChain;

            $htmlChain = new Zend_Filter();
            $htmlChain->addFilter(new Zend_Filter_StringTrim());
            $htmlChain->addFilter(new ZendB_Filter_StripSlashes());
            self::$htmlChain = $htmlChain;
            
            self::permitHtml($htmlAllowed);
        }
    }
    
    public static function permitHtml($htmlAllowed){
        self::initChains();
        if(is_string($htmlAllowed)){
            $htmlAllowed = preg_split('%\s*,\s*%', $htmlAllowed);
        }
        foreach ($htmlAllowed as $key ) {
            self::$chains[$key] = self::$htmlChain;   
        }
    }

    public static function setParam($param, $value){
        Util::getFront()->getRequest()->setParam($param, $value);
    }
    
    public static function getParam($param, $default = '') {
        self::initChains();
        $chain = Util::getItem(self::$chains, $param, self::$chains['*']);
        $value = Util::getFront()->getRequest()->getParam($param, $default);
        return $chain->filter($value);
    }

    public static function getParams() {
        $params = Util::getFront()->getRequest()->getParams();
        $result = array();
        foreach ($params as $key => $value) {
            $result[$key] = self::getParam($key);
        }

        return $result;
    }

    public static function storeInput($id = '') {
        if (!$_SESSION['_stored']) {
            $_SESSION['_stored'] = array();
        }
        if (empty($id)) {
            $id = Util::getFront()->getRequest()->getControllerName().'.'
                 .Util::getFront()->getRequest()->getActionName();
        }
        $_SESSION['_stored'][$id] = self::getParams();
    }

    public static function unstoreInput($id = '') {
        if (!$_SESSION['_stored']) {
            $_SESSION['_stored'] = array();
        }
        if (empty($id)) {
            $id = Util::getFront()->getRequest()->getControllerName().'.'
                 .Util::getFront()->getRequest()->getActionName();
        }
        unset($_SESSION['_stored'][$id]);
    }

    public static function storedInput($id = '') {
        if (empty($id)) {
            $id = Util::getFront()->getRequest()->getControllerName().'.'
                 .Util::getFront()->getRequest()->getActionName();
        }
        return Util::getItem($_SESSION['_stored'], $id, null);
    }

    public static function getModelInput($model, $input = array()){
        if(empty($input)){
            $input = self::getParams();
        }
        $dbRecord = $model->packDbRecord(false);
        $input = array_intersect_key($input, $dbRecord);
        return $input;
    }
    
    public static function getModelFromInput($model){
        if(!$model){
            return null;
        }
        $dbRecord = $model->packDbRecord(false);
        $params = self::getParams();
        $input = array_intersect_key($params, $dbRecord);
//        Util::print_r($input); die();
        if($model->validateInput($input, self::getParam('action'))){
            $model->unpackInput($input);
        }
        
        return $model;
    }

}
