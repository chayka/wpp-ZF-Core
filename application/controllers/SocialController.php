<?php

class ZFCore_SocialController extends Zend_Controller_Action{

    public function init(){
        Util::turnRendererOff();
    }

    public function fbChannelAction(){
        die('<script src="//connect.facebook.net/en_US/all.js"></script>');
    }
}