<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminController
 *
 * @author borismossounov
 */
class AdminController extends Zend_Controller_Action{

    public function init(){

    }
    
    public function indexAction(){
//        Util::turnRendererOff();
        wp_enqueue_style('zf-core-admin-less', ZF_CORE_URL.'res/css/zf-core-admin.less');
//        echo "Hi, i'm admin area";
    }
}
