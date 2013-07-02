<?php

class ZFCore_BlockadeController extends Zend_Controller_Action{

    public function init(){
        Util::turnRendererOff();
    }

    public function indexAction(){
//        die('locked');
        die($this->view->render('blockade/blockade.phtml'));
    }
}
