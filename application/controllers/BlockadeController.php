<?php

class ZF_Core_BlockadeController extends Zend_Controller_Action{

    public function init(){
        Util::turnRendererOn();
    }

    public function indexAction(){
//        die('locked');
        echo $this->view->render('blockade/blockade.phtml');
        die();
    }
}
