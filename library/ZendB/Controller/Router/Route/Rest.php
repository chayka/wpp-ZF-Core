<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rest
 *
 * @author borismossounov
 */
class ZendB_Controller_Router_Route_Rest extends Zend_Controller_Router_Route{

    public function __construct($collection, $modelClass, $classPath = null, Zend_Translate $translator = null, $locale = null) {
        $route = $collection.'/:id/*';
        $defaults = array(
            'id' => 0, 
            'controller' => 'restful',
            'action' => 'read',
            );
        parent::__construct($route, $defaults, $reqs, $translator, $locale);
    }


    public function match($path, $partial = false){
        $return = parent::match($path, $partial);
    }
        
}

class RestfulController extends Zend_Controller_Action{
    
    public function init(){
        Util::turnRendererOff();
    }
    
    public function createAction($respond = true){
        
    }
    
    public function readAction($respond = true){
        
    }
    
    public function updateAction($respond = true){
        
    }
    
    public function deleteAction($respond = true){
        
    }
    
    public function respond(){
        
    }
    
}

