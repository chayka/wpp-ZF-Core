<?php

class ZF_Core_ErrorController extends Zend_Controller_Action
{

    public function init() {
        Util::turnRendererOn();
    }
    
    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        
        if (!$errors || !$errors instanceof ArrayObject) {
            $this->view->message = 'You have reached the error page';
            return;
        }
        
        $message = '';
        $exception = '';
        $httpResponseCode = 400;
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
//                // 404 error -- controller or action not found
//                $httpResponseCode = 404;
//                $this->getResponse()->setHttpResponseCode($httpResponseCode);
//                $priority = Zend_Log::NOTICE;
//                $message = 'Page not found';
//                break;
                WpHelper::setNotFound(true);
                return;
            default:
                // application error
                $httpResponseCode = 500;
                $this->getResponse()->setHttpResponseCode($httpResponseCode);
                $priority = Zend_Log::CRIT;
                $message = 'Application error';
                break;
        }
        
        // Log exception, if logger available
        $log = $this->getLog();
        if ($log) {
            $log->log($message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $errors->request->getParams());
        }
        
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $exception = $errors->exception;
        }
        
        
        if($this->view){
            $this->view->message   = $message;
            $this->view->exception   = $exception;
            $this->view->request   = $errors->request;
        }else{
            JsonHelper::respondError($message, $exception->getCode(), array('backtrace'=>$exception->getTraceAsString()), $httpResponseCode);
        }
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap || !$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }

    public function notFound404Action(){
        $this->getResponse()->setHttpResponseCode(404);
        $frag = PostModel::query()
                ->postType(ZF_Core::POST_TYPE_CONTENT_FRAGMENT)
                ->postSlug('not-found')
                ->selectOne();
        $this->view->post = $frag;
    }

}

