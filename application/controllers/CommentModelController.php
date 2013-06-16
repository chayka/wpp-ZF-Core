<?php

require_once 'application/models/posts/CommentModel.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdmissionApplicationController
 *
 * @author borismossounov
 */
class ZFCore_CommentModelController extends RestController {
    public function init(){
        parent::init();
        $this->setModelClassName('CommentModel');
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
        $page = InputHelper::getParam('page', 1);
        $offset = InputHelper::getParam('offset', 0);
        $number = InputHelper::getParam('number', 30);
        
        if(!$offset && $page){
            $offset = ($page-1)*$number; 
            $params['offset'] = $offset;
        }
        
        $params['count'] = false;
        $comments = CommentModel::selectComments($params);
        $params['count'] = true;
        $params['offset'] = 0;
        $params['number'] = 0;
        $found = get_comments($params);
        
        JsonHelper::respond(array('total'=>$found, 'page'=>$page, 'items'=>$comments));
        
    }

    public function readAction($respond = true) {
        return parent::readAction($respond);
    }

    public function updateAction($respond = true) {
        return parent::updateAction($respond);
    }

}
