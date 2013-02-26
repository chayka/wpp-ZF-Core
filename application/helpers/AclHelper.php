<?php

require_once 'Zend/Acl.php';
require_once 'Zend/Acl/Role.php';
require_once 'Zend/Acl/Resource.php';

class AclHelper {

    protected static $acl = null;
    
    public static function getInstance() {
        if (empty(self::$acl)) {

            $acl = new Zend_Acl();

            // Add groups to the Role registry using Zend_Acl_Role
            // Guest does not inherit access controls
            $acl->addRole(new Zend_Acl_Role('guest'));

            // Member inherits from guest
            $acl->addRole(new Zend_Acl_Role('member'), 'guest');

            // Administrator does not inherit access controls
            $acl->addRole(new Zend_Acl_Role('administrator'));

//            $acl->add(new Zend_Acl_Resource('answer'));
//            $acl->add(new Zend_Acl_Resource('auth'));
//            $acl->add(new Zend_Acl_Resource('autocomplete'));
//            $acl->add(new Zend_Acl_Resource('comment'));
//            $acl->add(new Zend_Acl_Resource('question'));
//            $acl->add(new Zend_Acl_Resource('search'));
//            $acl->add(new Zend_Acl_Resource('user'));
//            $acl->add(new Zend_Acl_Resource('vote'));

            $acl->allow(null, 'auth', array('login', 'logout', 'join', 'forgot-password', 'change-password', 'check-email', 'check-name'));

            // Guest may only view content
            $acl->allow('guest', 'index');

            // Member inherits view privilege from guest, but also needs additional privileges
//            $acl->allow('member', 'search');
//            $acl->allow('member', 'user', array('edit', 'get', 'update', 'profile'));
//            $acl->allow('member', 'comment');
//            $acl->allow('guest', 'question', 'view');
//            $acl->allow('member', 'question');
//            $acl->allow('member', 'answer');
//            $acl->allow('member', 'vote');

//            $acl->deny('advert', 'user', 'delete');
//            $acl->deny('advert', 'user', 'register');

            // Administrator inherits nothing, but is allowed all privileges
            $acl->allow('administrator');
//            $acl->deny('administrator', 'user', 'register');

            self::$acl = $acl;
        }

        return self::$acl;
    }

    public static function isAllowed($privelege = null, $resource = null, $role = null) {
        global $wpdb, $current_user;
        if (empty($role)) {
            $role = UserModel::currentUser()->getRole();
        }
        if (empty($resource)) {
            $resource = Util::getFront()->getRequest()->getControllerName();
        }
        if (empty($privelege)) {
            $privelege = Util::getFront()->getRequest()->getActionName();
        }

        return self::getInstance()->isAllowed($role, $resource, $privelege);
    }

    public static function apiAuthRequired($message = ''){
        $userId = get_current_user_id();
        if(!$userId){
            if(!$message){
                $message = 'Необходимо авторизоваться на сайте';
            }
            ErrorHelper::error($message, ErrorHelper::CODE_AUTH_REQUIRED);
        }
    }
    
    public static function isAuthorized(){
        $userId = get_current_user_id();
        return !empty ($userId);
    }
    
    public static function apiOwnershipForbidden(/*AG_PostModel*/ $obj, $message = ''){
        $user = UserModel::currentUser();
        if($obj->getUserId() == $user->getId() && $user->getRole()!='administrator'){
            if(!$message){
                $message = 'Данная операция с собственным объектом невозможна';
            }
            ErrorHelper::error($message, ErrorHelper::CODE_PERMISSION_REQUIRED);
        }
    }
    
    public static function isOwner(/*AG_PostModel*/ $obj){
        $user = UserModel::currentUser();
        return ($obj->getUserId() == $user->getId() || $user->getRole()=='administrator');
    }
    
    public static function apiOwnershipRequired(/*AG_PostModel*/ $obj, $message = ''){
        $user = UserModel::currentUser();
        if($obj->getUserId() != $user->getId() && $user->getRole()!='administrator'){
            if(!$message){
                $message = 'У вас недостаточно прав для модификации данного объекта';
            }
            ErrorHelper::error($message, ErrorHelper::CODE_PERMISSION_REQUIRED);
        }
    }
    
    public static function isNotOwner(/*AG_PostModel*/ $obj){
        $user = UserModel::currentUser();
        return ($obj->getUserId() != $user->getId() || $user->getRole()=='administrator');
    }
    
    public static function isAdmin(){
        $user = UserModel::currentUser();
        return ($user->getRole()=='administrator');
    }

    public static function show404() {
        header("Location: /404/");
    }

}