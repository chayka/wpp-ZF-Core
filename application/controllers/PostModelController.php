<?php

require_once 'application/models/posts/PostModel.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdmissionApplicationController
 *
 * @author borismossounov
 */
class ZFCore_PostModelController extends RestController {
    public function init(){
        parent::init();
        $this->setModelClassName('PostModel');
//        die(Util::getFront()->getRequest()->getMethod());
    }
    
    public function createAction($respond = true) {
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
        
        $posts = PostModel::selectPosts($params);
        $found = PostModel::postsFound();
        
        JsonHelper::respond(array('total'=>$found, 'page'=>$page, 'items'=>$posts));
        
    }

    public function readAction($respond = true) {
        return parent::readAction($respond);
    }

    public function updateAction($respond = true) {
        return parent::updateAction($respond);
    }

}
