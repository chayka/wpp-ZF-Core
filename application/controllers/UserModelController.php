<?php

require_once 'application/models/users/UserModel.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdmissionApplicationController
 *
 * @author borismossounov
 */
class ZFCore_UserModelController extends RestController {
    public function init(){
        parent::init();
        $this->setModelClassName('UserModel');
//        die(Util::getFront()->getRequest()->getMethod());
    }
    
    public function createAction($respond = true) {
        $this->detectMe();
        return parent::createAction($respond);
    }

    public function deleteAction($respond = true) {
        return parent::deleteAction($respond);
    }

    public function listAction($respond = true) {
//        echo 'list ';
//        parent::listAction($respond);

        $params = InputHelper::getParams();
        unset($params['action']);
        unset($params['controller']);
        unset($params['module']);
        $page = InputHelper::getParam('paged', 1);
        
        $users = UserModel::selectUsers($params);
        $found = WpDbHelper::rowsFound();
        
        JsonHelper::respond(array('total'=>$found, 'page'=>$page, 'items'=>$users));
        
    }

    public function readAction($respond = true) {
        $this->detectMe();
        return parent::readAction($respond);
    }

    public function updateAction($respond = true) {
        return parent::updateAction($respond);
    }
    
    public function detectMe(){
        $id = InputHelper::getParam('id', 0);
        if($id == 'me'){
            $id = get_current_user_id();
            InputHelper::setParam('id', $id?$id:0);
        }
    }

}
