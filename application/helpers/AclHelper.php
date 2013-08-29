<?php

require_once 'Zend/Acl.php';
require_once 'Zend/Acl/Role.php';
require_once 'Zend/Acl/Resource.php';

class AclHelper {

    const ROLE_GUEST = 'guest';
    const ROLE_SUBSCRIBER = 'subscriber';
    const ROLE_CONTRIBUTOR = 'contributor';
    const ROLE_AUTHOR = 'author';
    const ROLE_EDITOR = 'editor';
    const ROLE_ADMINISTRATOR = 'administrator';
    
    protected static $acl = null;
    
    public static function getInstance() {
        if (empty(self::$acl)) {

            $acl = new Zend_Acl();

            // Add groups to the Role registry using Zend_Acl_Role
            // Guest does not inherit access controls
            $acl->addRole(new Zend_Acl_Role(self::ROLE_GUEST));

            // Member inherits from guest
            $acl->addRole(new Zend_Acl_Role(self::ROLE_SUBSCRIBER), 'guest');
            $acl->addRole(new Zend_Acl_Role(self::ROLE_CONTRIBUTOR), 'subscriber');
            $acl->addRole(new Zend_Acl_Role(self::ROLE_AUTHOR), 'contributor');
            $acl->addRole(new Zend_Acl_Role(self::ROLE_EDITOR), 'author');

            // Administrator does not inherit access controls
            $acl->addRole(new Zend_Acl_Role(self::ROLE_ADMINISTRATOR));

//            $acl->add(new Zend_Acl_Resource('answer'));
            $acl->add(new Zend_Acl_Resource('auth'));
//            $acl->add(new Zend_Acl_Resource('autocomplete'));
//            $acl->add(new Zend_Acl_Resource('comment'));
//            $acl->add(new Zend_Acl_Resource('question'));
//            $acl->add(new Zend_Acl_Resource('search'));
//            $acl->add(new Zend_Acl_Resource('user'));
//            $acl->add(new Zend_Acl_Resource('vote'));

            $acl->allow(null, 'auth', array('login', 'logout', 'join', 'forgot-password', 'change-password', 'check-email', 'check-name'));

            // Guest may only view content
//            $acl->allow(self::ROLE_GUEST, 'index');

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
            $acl->allow(self::ROLE_ADMINISTRATOR);
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
//            die("role: $role, $resource, $privelege");

        $res = self::getInstance()->isAllowed($role, $resource, $privelege);
        
        return $res;
            
    }
    
    public static function denyAccess($message = ''){
        $front = Util::getFront();
        if(!$message){
            $message = NlsHelper::_('Dear user, access to this page is forbidden for you.');
        }
        if($front->getParam('noViewRenderer')){
            JsonHelper::respondError($message, ErrorHelper::CODE_AUTH_REQUIRED);
        }else{
            $view = new Zend_View();
            $view->setScriptPath(ZF_CORE_APPLICATION_PATH.'/views/scripts');
            $view->message = $message;
            WpHelper::setPostTitle('Access Denied');
            echo $view->render('acl/access-denied.phtml');
            Util::turnRendererOff();
        }
        
    }

    public static function apiAuthRequired($message = ''){
        $userId = get_current_user_id();
        if(!$userId){
            if(!$message){
                $message = 'Необходимо авторизоваться на сайте';
            }
            JsonHelper::respondError($message, ErrorHelper::CODE_AUTH_REQUIRED);
        }
    }
    
    public static function permissionRequired($message = '', $privelege = null, $resource = null, $role = null){
        if(!self::isAllowed($privelege, $resource, $role)){
            if(!$message){
                $message = NlsHelper::_('Access denied');
            }
            self::denyAccess($message);
            return false;
        }
        
        return true;
    }
    
    public static function userHasPermission($message = '', $privelege = null, $resource = null, $role = null){
        return self::permissionRequired($message, $privelege, $resource, $role);
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
            JsonHelper::respondError($message, ErrorHelper::CODE_PERMISSION_REQUIRED);
        }
    }
    
    public static function isOwner(/*PostModel*/ $obj){
        $user = UserModel::currentUser();
        return ($obj->getUserId() == $user->getId() || $user->getRole()=='administrator');
    }
    
    public static function apiOwnershipRequired(/*PostModel*/ $obj, $message = ''){
        $user = UserModel::currentUser();
        if($obj->getUserId() != $user->getId() && $user->getRole()!='administrator'){
            if(!$message){
                $message = 'У вас недостаточно прав для модификации данного объекта';
            }
            JsonHelper::respondError($message, ErrorHelper::CODE_PERMISSION_REQUIRED);
        }
    }
    
    public static function isNotOwner(/*AG_PostModel*/ $obj){
        $user = UserModel::currentUser();
        return ($obj->getUserId() != $user->getId() || $user->getRole()=='administrator');
    }
    
    public static function isUserRole($role, $user = null){
        if(!$user){
            $user = UserModel::currentUser();
        }elseif(!($user instanceof UserModel)){
            $user = UserModel::unpackDbRecord($user);
        }
        return ($user->getRole()==$role);
    }

    public static function isAdmin($user = null){
//        if(!$user){
//            $user = UserModel::currentUser();
//        }elseif(!($user instanceof UserModel)){
//            $user = UserModel::unpackDbRecord($user);
//        }
//        return ($user->getRole()=='administrator');
        return self::isUserRole('administrator', $user);
    }

    public static function isEditor($user = null){
        return self::isUserRole('editor', $user);
    }

    public static function show404() {
        header("Location: /404/");
    }

}