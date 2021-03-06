<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ZendB_Controller_Rest
 *
 * @author borismossounov
 */
class RestController extends Zend_Rest_Controller{
    protected $modelClassName;
    
    public function getModelClassName() {
        return $this->modelClassName;
    }

    public function setModelClassName($modelClassName) {
        $this->modelClassName = $modelClassName;
    }

    public function init(){
        Util::turnRendererOff();
        $this->parseRequest();
//        die(InputHelper::getParam('action'));
    }
    
    public function createAction($respond = true){
        InputHelper::setParam('action', 'create');
        $class = $this->getModelClassName();
        $model = new $class();
        $model = InputHelper::getModelFromInput($model);
        $meta = InputHelper::getParam('meta', array());
//        Util::print_r($model); die();
        $errors = $model->getValidationErrors();
        if(!empty($errors)){
            JsonHelper::respondErrors($errors);
        }else{
            $id = $model->insert();
            if ($id) {
                if(count($meta)){
                    foreach ($meta as $key=>$value){
                        if(strpos($key, 'wp_')===false && !is_serialized($meta[$key])){
                            $model->updateMeta($key, $value);
                        }
                    }
                }
                $model = call_user_func(array($this->getModelClassName(), 'selectById'), $id, false);
                apply_filters($class . '.created', $model);
                if ($respond) {
                    JsonHelper::respond($model);
                }
            } else {
                JsonHelper::respondError('Failed to create entity');
            }
        } 
        
        return $model;
    }
    
    public function updateAction($respond = true){
        InputHelper::setParam('action', 'update');
        $id = InputHelper::getParam('id');
        $class = $this->getModelClassName();
        $model = call_user_func(array($class, 'selectById'), $id);
        $model = InputHelper::getModelFromInput($model);
        $meta = InputHelper::getParam('meta', array());
        $errors = $model->getValidationErrors();
        if(!empty($errors)){
            JsonHelper::respondErrors($errors);
        }else{ 
            try{
            if($model->update()){
//                Util::print_r($meta);
                if(count($meta) && is_array($meta)){
                    foreach ($meta as $key=>$value){
                        if(strpos($key, 'wp_')===false && !is_serialized($meta[$key])){
                            $model->updateMeta($key, $value);
                        }
                    }
                }
                $model = call_user_func(array($class, 'selectById'), $id, false);
                apply_filters($class.'.updated', $model);
                if($respond){
    //                $json = $model->packJsonItem();
    //                $json = array_intersect_key($json, InputHelper::getParams());
    //                JsonHelper::respond($json);
    //                print_r($model); die('@');
                    JsonHelper::respond($model);
                }
            }else{
                JsonHelper::respondError('failed');
            }
            }catch(Exception $e){
                JsonHelper::respondError($e->getMessage(), $e->getCode());
            }
        }
        
        return $model;
        
    }
    
    public function deleteAction($respond = true){
        $id = InputHelper::getParam('id');
        $class = $this->getModelClassName();
        $table = call_user_func(array($class, 'getDbTable'));
        $key =  call_user_func(array($class, 'getDbIdColumn'));
        $model = call_user_func(array($class, 'selectById'), $id);
        if(!$model->validateInput(array(), 'delete')){
            $errors = $model->getValidationErrors();
            JsonHelper::respondErrors($errors);
        }
        $result = $model->delete();//WpDbHelper::delete($table, $key, $id);
        if($result){
            apply_filters($class.'.deleted', $model);
        }
        if($respond){
            JsonHelper::respond(null, $result?0:1);
        }
        return $result;
    }
    
    public function readAction($respond = true){
        $id = InputHelper::getParam('id');
        $model = call_user_func(array($this->getModelClassName(), 'selectById'), $id);
        if($respond){
            JsonHelper::respond($model, $model?0:1);
        }
        return $model;
    }

    public function listAction($respond = true){
        
    }
    
    public function indexAction($respond = true) {
        return $this->listAction($respond);
    }
    
    public function getAction($respond = true) {
        return $this->readAction($respond);
    }

    public function postAction($respond = true) {
        return $this->createAction($respond);
    }
    
    public function putAction($respond = true) {
        return $this->updateAction($respond);
    }
    
    public function parseRequest(){
        $putdata = fopen("php://input", "r");
        $req = '';
        while($data = fread($putdata, 1024)){
            $req.=$data;
        }
        fclose($putdata);
        if($req){
            $params = json_decode($req, true);
            if(!$params){
                parse_str($req, $params);
            }
//            print_r($req);
            if($params){
                foreach($params as $key=>$value){
                    InputHelper::setParam($key, $value);
                }
            }
        }
    }
    
}
