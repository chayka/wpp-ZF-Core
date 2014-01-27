<?php

class ZFCore_Bootstrap extends WpPluginBootstrap//Zend_Application_Bootstrap_Bootstrap
{
    const MODULE = 'ZF_Core';

//    public function run(){
//        $this->setupRouting();
//    }
    
    public function getModuleName() {
        return self::MODULE;
    }
    
    public function setupRouting(){
        $front = Util::getFront();
//        $cd = $front->getControllerDirectory();
//        $front->addControllerDirectory($cd['default'], self::MODULE);        
//        
//        $router = $front->getRouter();
        $router = parent::setupRouting();
        $r = new Zend_Rest_Route($front, array('module'=>self::MODULE), array(
            self::MODULE => array(
                'post-model',
                'comment-model',
                'user-model',
            ),
        ));

//        $router->addRoute(self::MODULE, new Zend_Controller_Router_Route(':controller/:action/*', array('controller' => 'index', 'action'=>'index', 'module'=>self::MODULE)));
        $router->addRoute('not-found-404', new Zend_Controller_Router_Route('not-found-404', array('controller' => 'error', 'action'=>'not-found-404', 'module'=>self::MODULE), array()));
        $router->addRoute('autocomplete-taxonomy', new Zend_Controller_Router_Route('autocomplete/taxonomy/:taxonomy', array('controller' => 'autocomplete', 'action'=>'taxonomy', 'module'=>self::MODULE)));
        $router->addRoute('update-meta-box', new Zend_Controller_Router_Route('metabox/update/:meta_box_id', array('controller' => 'metabox', 'action'=>'update', 'module'=>self::MODULE)));
//        $router->addRoute('upload-attachment', new Zend_Controller_Router_Route('upload/attachment/*', array('controller' => 'post-model', 'action'=>'upload', 'module'=>self::MODULE)));
        $router->addRoute('zf-setup', new Zend_Controller_Router_Route('zf-setup/:action/*', array('controller' => 'admin', 'action'=>'index', 'module'=>self::MODULE)));
        $router->addRoute('restfull', $r);

    }

}

