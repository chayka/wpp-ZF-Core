<?php

//require_once 'application/helpers/ErrorHelper.php';

class WpHelper {
    private static $instance;
    private $title = '';
    private $description = '';
    private $navMenu = '';
    private $navMenuId = '';
    private $sidebarStatic = false;
    private $sidebarId = '';
    private $pageTemplate = '';
    private $request = null;
    private $query = null;
    private $trunk;
    private $notFound = false;
    
    private $posts = null;
    
    public static function isAdmin(){
        $userId = get_current_user_id();
        if(!$userId) return false;
        $user = get_user_by('id', $userId);
        $isAdmin = in_array('administrator', $user->roles);
        return $isAdmin;
    }

    public function __construct() {
        $this->initNavMenuId();
        $this->initSideBarId();
        $this->request = Util::getFront()->getRequest();
    }
    
    public static function getInstance($refresh = false){
        if($refresh || !self::$instance){
            self::$instance = new WpHelper();
        }
        
        return self::$instance;
    }
    
    
    public function _getTitle() {
        return $this->title;
    }

    public function _setTitle($title) {
        $this->title = $title;
    }

    public function _getDescription() {
        return $this->description;
    }

    public function _setDescription($description) {
        $this->description = $description;
    }

    public function _getNavMenu() {
        return $this->navMenu;
    }

    public function _setNavMenu($navMenu) {
        $this->navMenu = $navMenu;
    }

    public function _getNavMenuId() {
        return $this->navMenuId;
    }

    public function _setNavMenuId($navMenuId) {
        $this->navMenuId = $navMenuId;
    }

    public function _getSidebarStatic() {
        return $this->sidebarStatic;
    }

    public function _setSidebarStatic($sidebarStatic) {
        $this->sidebarStatic = $sidebarStatic;
    }

    public function _getSidebarId() {
        return $this->sidebarId;
    }

    public function _setSidebarId($sidebarId) {
        $this->sidebarId = $sidebarId;
    }

    public function _getRequest() {
        return $this->request;
    }

    public function _setRequest($request) {
        $this->request = $request;
    }

    public function _getPosts() {
        return $this->posts;
    }

    public function _setPosts($posts) {
        $this->posts = $posts;
    }
    
    public function _getQuery() {
        return $this->query;
    }

    public function _setQuery($query) {
        $this->query = $query;
    }

    public function _getTrunk() {
        return $this->trunk;
    }

    public function _setTrunk($trunk) {
        $this->trunk = $trunk;
    }
    
    public function _getNotFound(){
        return $this->notFound;
    }
    
    public function _setNotFound($notFound = true){
        $this->notFound = $notFound;
    }
    
    public function _getPageTemplate() {
        return $this->pageTemplate;
    }

    public function _setPageTemplate($pageTemplate) {
        $this->pageTemplate = $pageTemplate;
    }

        public static function setQuery($query){
        self::getInstance()->_setQuery($query);
    }
    
    public static function getQuery(){
        return self::getInstance()->_getQuery();
    }

    public static function setTrunk($trunk){
        self::getInstance()->_setTrunk($trunk);
    }
    
    public static function getTrunk(){
        return self::getInstance()->_getTrunk();
    }

    public static function setPostTitle($title){
        self::getInstance()->_setTitle($title);
    }
    
    public static function getPostTitle(){
        return self::getInstance()->_getTitle();
    }

    public static function setPostDescription($value){
        self::getInstance()->_setDescription($value);
    }
    
    public static function getPostDescription(){
        return self::getInstance()->_getDescription();
    }
    
    public static function setNavMenu($value){
        self::getInstance()->_setNavMenu($value);
    }
    
    public static function getNavMenu(){
        return self::getInstance()->_getNavMenu();
    }
    
    public static function getNavMenuId(){
        return self::getInstance()->_getNavMenuId();
    }
    
    public static function setSideBarId($id){
        return self::getInstance()->_setSideBarId($id);
    }
    
    public static function getSideBarId(){
        return self::getInstance()->_getSideBarId();
    }
    
    public static function setSideBarStatic($value){
        self::getInstance()->_setSidebarStatic($value);
    }
    
    public static function getSideBarStatic(){
        return self::getInstance()->_getSidebarStatic();
    }
    
    public static function getRequest(){
        return self::getInstance()->_getRequest();
    }
    
    public static function setPosts($posts){
        self::getInstance()->_setPosts($posts);
    }
    
    public static function getPosts(){
        return self::getInstance()->_getPosts();
    }
    
    public static function getNotFound(){
        return self::getInstance()->_getNotFound();
    }
    
    public static function setNotFound($notFound = true){
        Util::turnRendererOff();
        return self::getInstance()->_setNotFound($notFound);
    }
    
    public static function getPageTemplate() {
        return self::getInstance()->_getPageTemplate();
    }

    public function setPageTemplate($pageTemplate) {
        self::getInstance()->_setPageTemplate($pageTemplate);
    }
    
    public function initNavMenuId($id = ''){
        $this->navMenuId = null;
        if(!$id){
            $controller = Util::getFront()->getRequest()->getControllerName();
            $action = Util::getFront()->getRequest()->getActionName();
            if(has_nav_menu($controller.'-'.$action)){
                $this->navMenuId =  $controller.'-'.$action;
            }elseif(has_nav_menu($controller)){
                $this->navMenuId = $controller;
            }elseif(has_nav_menu($action)){
                $this->navMenuId = $action;
            }
        }elseif(has_nav_menu($id)){
            $this->navMenuId = $id;
        }
        
        return $this->navMenuId;
    }
    
    public function initSideBarId($id = ''){
        global $wp_registered_sidebars;
        $this->sidebarId = null;

        if(!$id){
            $controller = Util::getFront()->getRequest()->getControllerName();
            $action = Util::getFront()->getRequest()->getActionName();
            if(isset($wp_registered_sidebars[$controller.'-'.$action])){
                $this->sidebarId = $controller.'-'.$action;
            }elseif(isset($wp_registered_sidebars[$controller])){
                $this->sidebarId = $controller;
            }elseif(isset($wp_registered_sidebars[$action])){
                $this->sidebarId = $action;
            }
        }elseif(isset($wp_registered_sidebars[$id])){
            $this->sidebarId = $id;
        }
        
        return $this->sidebarId;
    }
    
    public static function packErrorsToJson(WP_Error $errors){
        $codes = $errors->get_error_codes();
        $json = array();
        foreach ($codes as $code) {
            $json[$code] = $errors->get_error_message($code);
        }
        return $json;
    }

    public static function outputErrors(WP_Error $errors, $payload = null){
        foreach($errors->errors as $code => $error){
            $newMessage = preg_replace('%<strong>[^<]*</strong>:\s*%m', '', $errors->get_error_message($code));
            switch($code){
                    case 'invalid_username':
                        $newMessage = "Пользователя с таким адресом не существует.";
                        break;
                    case 'incorrect_password':
                        $newMessage = "Вы ввели неверный пароль.";
                        break;
                    case 'username_exists':
                        $newMessage = "Это имя пользователя уже зарегистрировано.";
                        break;
                    case 'email_exists':
                        $newMessage = "Этот e-mail уже зарегистрирован.";
                        break;
                    case 'invalid_username':
                        $newMessage = "Пользователя с таким адресом не существует.";
                        break;
                    case 'empty_username':
                    case 'empty_password':
                    case 'authentication_failed':
                        break;
                    default:
            }
            if($newMessage){
                $errors->errors[$code] = array($newMessage);
            }
        }
        ErrorHelper::errors(self::packErrorsToJson($errors), $payload);
//        die(JsonHelper::packResponse($payload, 1, self::packErrorsToJson($errors)));
    }
    
    public static function outputError($message = '', $code = 1){
        ErrorHelper::error($message, $code);
//        die(JsonHelper::packResponse(NULL, $code, $errorMsg));
    }
    
    public static function apiAuthRequired(){
        $userId = get_current_user_id();
        if(!$userId){
            ErrorHelper::error('Необходимо авторизоваться на сайте', ErrorHelper::CODE_AUTH_REQUIRED);
        }
    }
    
    public static function slug($title){
        // Возвращаем результат.
        $table = array( 
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 
            'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'J', 
            'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 
            'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 
            'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'CSH', 'Ь' => '', 
            'Ы' => 'Y', 'Ъ' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 

            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 
            'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 
            'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 
            'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 
            'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'csh', 'ь' => '', 
            'ы' => 'y', 'ъ' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 
        ); 

        $title = str_replace( 
            array_keys($table), 
            array_values($table),$title 
        ); 
        $title = sanitize_title($title);
        return $title;
    }
    
}
