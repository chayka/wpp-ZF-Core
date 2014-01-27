<?php
//require_once 'ErrorHandler.php';

class WpPluginBootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    
    public function getModuleName(){
        return 'SomeModule';
    }

    public function run(){
//        die('run');
        $this->setupModule();
        $this->setupErrorHandler();
        $this->setupRouting();
    }
    
    public function setupModule(){
        $front = Util::getFront();
        $cd = $front->getControllerDirectory();
        $front->addControllerDirectory(ABSPATH.'wp-content/plugins/wpp-ZF-Core/application/controllers', 'ZF_Core');
        if($this->getModuleName()!='ZF_Core'){
            $front->addControllerDirectory($cd['default'], $this->getModuleName());        
        }
        
    }
    
    public function setupErrorHandler(){
        $front = Util::getFront();
        $plugin = new Zend_Controller_Plugin_ErrorHandler(
//        $plugin = new ErrorHandler(
        array(
            'module' => 'ZF_Core',
            'controller' => 'error',
            'action' => 'error'
        ));
        $front->registerPlugin($plugin);
        register_shutdown_function(array($this, 'onApplicationShutdown'));
    }
    
    /**
     * 
     * @return Zend_Controller_Router_Rewrite
     */
    public function setupRouting(){
        $front = Util::getFront();
        $router = $front->getRouter();
        $router->addRoute($this->getModuleName(), new Zend_Controller_Router_Route(':controller/:action/*', array('controller' => 'index', 'action'=>'index', 'module'=>$this->getModuleName())));
        return $router;
    }

    public function onApplicationShutdown() {
        $error = error_get_last();
        $wasFatal = ($error && ($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR));
        if ($wasFatal) {
            Util::httpRespondCode(500);
//            $frontController = Zend_Controller_Front::getInstance();
//            $errorHandler = $frontController->getPlugin('Zend_Controller_Plugin_ErrorHandler');
//            $request = $frontController->getRequest();
//            $response = $frontController->getResponse();
//            $errorHandler->postDispatch($request);
//            // Add the fatal exception to the response in a format that ErrorHandler will understand
//            $response->setException(new Exception(
//                    "Fatal error: $error[message] at $error[file]:$error[line]", $error['type']));
//
//            // Call ErrorHandler->_handleError which will forward to the Error controller
//            $handleErrorMethod = new ReflectionMethod('Zend_Controller_Plugin_ErrorHandler', '_handleError');
//            $handleErrorMethod->setAccessible(true);
//            $r = $handleErrorMethod->invoke($errorHandler, $request);
//            // Discard any view output from before the fatal
////            ob_end_clean();
//
//            // Now display the error controller:
//            $frontController->dispatch($request, $response);
        }
    }

}

