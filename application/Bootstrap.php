<?php

class ZFCore_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    const MODULE = 'ZFCore';

    public function run(){
        $this->setupRouting();
        $front = Util::getFront();
        WpHelper::getInstance();
        parent::run();
    }
    
    public function setupRouting(){
        $front = Util::getFront();
        $cd = $front->getControllerDirectory();
        $front->addControllerDirectory($cd['default'], self::MODULE);        
        
        $router = $front->getRouter();

        $r = new Zend_Rest_Route($front, array('module'=>self::MODULE), array(
            self::MODULE => array(
                'post-model',
                'comment-model',
                'user-model',
            ),
        ));

        $router->addRoute(self::MODULE, new Zend_Controller_Router_Route(':controller/:action/*', array('controller' => 'index', 'action'=>'index', 'module'=>self::MODULE)));
        $router->addRoute('autocomplete-taxonomy', new Zend_Controller_Router_Route('autocomplete/taxonomy/:taxonomy', array('controller' => 'autocomplete', 'action'=>'taxonomy', 'module'=>self::MODULE)));
        $router->addRoute('zf-setup', new Zend_Controller_Router_Route('zf-setup/:action/*', array('controller' => 'admin', 'action'=>'index', 'module'=>self::MODULE)));
        $router->addRoute('restfull', $r);

    }

}

