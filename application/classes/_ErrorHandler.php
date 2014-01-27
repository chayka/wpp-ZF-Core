<?php

class ErrorHandler extends Zend_Controller_Plugin_ErrorHandler{
    /**
     * Route shutdown hook -- Ccheck for router exceptions
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        if(!$this->_handleFatalError($request)){
            parent::routeShutdown($request);
        }
    }

    /**
     * Post dispatch hook -- check for exceptions and dispatch error handler if
     * necessary
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        if(!$this->_handleFatalError($request)){
            parent::routeShutdown($request);
        }
    }

    /**
     * Handle errors and exceptions
     *
     * If the 'noErrorHandler' front controller flag has been set,
     * returns early.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    protected function _handleFatalError(Zend_Controller_Request_Abstract $request)
    {
        $frontController = Zend_Controller_Front::getInstance();
        if ($frontController->getParam('noErrorHandler')) {
            return false;
        }

        $error = error_get_last();
        $wasFatal = ($error && ($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR));
        if ($wasFatal) {
            $frontController = Zend_Controller_Front::getInstance();
            $errorHandler = $frontController->getPlugin('Zend_Controller_Plugin_ErrorHandler');
            $request = $frontController->getRequest();
            $response = $frontController->getResponse();
            $errorHandler->postDispatch($request);
            // Add the fatal exception to the response in a format that ErrorHandler will understand
            $response->setException(new Exception(
                    "Fatal error: $error[message] at $error[file]:$error[line]", $error['type']));

            // Call ErrorHandler->_handleError which will forward to the Error controller
            $handleErrorMethod = new ReflectionMethod('Zend_Controller_Plugin_ErrorHandler', '_handleError');
            $handleErrorMethod->setAccessible(true);
            $r = $handleErrorMethod->invoke($errorHandler, $request);
            // Discard any view output from before the fatal
//            ob_end_clean();

            // Now display the error controller:
            $frontController->dispatch($request, $response);
        }
        
/////////////////////////////////        
        
        $response = $this->getResponse();

        if ($this->_isInsideErrorHandlerLoop) {
            $exceptions = $response->getException();
            if (count($exceptions) > $this->_exceptionCountAtFirstEncounter) {
                // Exception thrown by error handler; tell the front controller to throw it
                $frontController->throwExceptions(true);
                throw array_pop($exceptions);
            }
        }

        // check for an exception AND allow the error handler controller the option to forward
        if (($response->isException()) && (!$this->_isInsideErrorHandlerLoop)) {
            $this->_isInsideErrorHandlerLoop = true;

            // Get exception information
            $error            = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
            $exceptions       = $response->getException();
            $exception        = $exceptions[0];
            $exceptionType    = get_class($exception);
            $error->exception = $exception;
            switch ($exceptionType) {
                case 'Zend_Controller_Router_Exception':
                    if (404 == $exception->getCode()) {
                        $error->type = self::EXCEPTION_NO_ROUTE;
                    } else {
                        $error->type = self::EXCEPTION_OTHER;
                    }
                    break;
                case 'Zend_Controller_Dispatcher_Exception':
                    $error->type = self::EXCEPTION_NO_CONTROLLER;
                    break;
                case 'Zend_Controller_Action_Exception':
                    if (404 == $exception->getCode()) {
                        $error->type = self::EXCEPTION_NO_ACTION;
                    } else {
                        $error->type = self::EXCEPTION_OTHER;
                    }
                    break;
                default:
                    $error->type = self::EXCEPTION_OTHER;
                    break;
            }

            // Keep a copy of the original request
            $error->request = clone $request;

            // get a count of the number of exceptions encountered
            $this->_exceptionCountAtFirstEncounter = count($exceptions);

            // Forward to the error handler
            $request->setParam('error_handler', $error)
                    ->setModuleName($this->getErrorHandlerModule())
                    ->setControllerName($this->getErrorHandlerController())
                    ->setActionName($this->getErrorHandlerAction())
                    ->setDispatched(false);
        }
    }
    
}

