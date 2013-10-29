<?php
//require_once 'Zend/Acl.php';
//require_once 'Zend/Acl/Role.php';

//NlsHelper::load('application/models', 'UserModel');
require_once 'application/helpers/JsonHelper.php';
require_once 'application/helpers/InputHelper.php';
require_once 'application/helpers/WpDbHelper.php';

/**
 * Class implemented to handle user actions and manipulations
 * Used for authentification, registration, update, delete and userpics management 
 *
 */
class UserModel implements DbRecordInterface, JsonReadyInterface, InputReadyInterface{
    const SESSION_KEY = '_user';
    
    protected static $userCacheById = array();
    protected static $userCacheByEmail = array();
    protected static $userCacheByLogin = array();

    protected static $currentUser;
    
    /**
     * User Id
     *
     * @var integer
     */
    protected $id;

    /**
     * User login
     *
     * @var string
     */
    protected $login;
    
    protected $password;
    
    protected $nicename;
    
    protected $url;
    
    protected $displayName;
    
//    protected $nickname;
    
    protected $firstName;
    
    protected $lastName;
    
    protected $description;
    
    protected $richEditing;
    
    protected $role;
    
    /**
     * User e-mail
     *
     * @var string
     */
    protected $email;


    /**
     * User registration date
     *
     * @var string
     */
    protected $registered;

    protected $jabber;
    
    protected $aim;
    
    protected $yim;
    
    protected $wpUser;

    /**
     * UserModel constructor
     *
     * @param integer $id
     */
    public function __construct() {
        $this->init();
    }

    public function init(){
//        echo "UserModel::init";
        $this->setId(0);
        $this->setLogin('guest');
        $this->setEmail('');
        $this->setRegistered(new Zend_Date());
        
    }
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getLogin() {
        return $this->login;
    }

    public function setLogin($login) {
        $this->login = $login;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getNicename() {
        return $this->nicename;
    }

    public function setNicename($nicename) {
        $this->nicename = $nicename;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getDisplayName() {
        return $this->displayName;
    }

    public function setDisplayName($displayName) {
        $this->displayName = $displayName;
    }

//    public function getNickname() {
//        return $this->nickname;
//    }
//
//    public function setNickname($nickname) {
//        $this->nickname = $nickname;
//    }

    public function getFirstName() {
        return $this->firstName;
    }

    public function setFirstName($firstName) {
        $this->firstName = $firstName;
    }

    public function getLastName() {
        return $this->lastName;
    }

    public function setLastName($lastName) {
        $this->lastName = $lastName;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getRichEditing() {
        return $this->richEditing;
    }

    public function setRichEditing($richEditing) {
        $this->richEditing = $richEditing;
    }

    public function getRole() {
        $caps = array();
        if($this->getId()){
            $caps = get_user_meta( $this->getId(), 'wp_capabilities', false );
        }
        return $this->role?$this->role:key($caps);
    }

    public function setRole($role) {
        $this->role = $role;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getRegistered() {
        return $this->registered?$this->registered:new Zend_Date();
    }

    public function setRegistered($registered) {
        $this->registered = $registered;
    }

    public function getJabber() {
        return $this->jabber;
    }

    public function setJabber($jabber) {
        $this->jabber = $jabber;
    }

    public function getAim() {
        return $this->aim;
    }

    public function setAim($aim) {
        $this->aim = $aim;
    }

    public function getYim() {
        return $this->yim;
    }

    public function setYim($yim) {
        $this->yim = $yim;
    }
    
    public function getProfileLink(){
        return get_author_posts_url($this->getId(), $this->getNicename());
    }

    public function getWpUser() {
        return $this->wpUser;
    }

    public function setWpUser($wpUser) {
        $this->wpUser = $wpUser;
    }

    public static function getDbIdColumn() {
        return 'ID';
    }

    public static function getDbTable() {
        global $wpdb;
        return $wpdb->users;
    }

    public static function unpackDbRecord( $wpRecord){
        
        $obj = new self();

        $obj->setId($wpRecord->ID);
        $obj->setLogin($wpRecord->user_login);
        $obj->setEmail($wpRecord->user_email);
        $obj->setNicename($wpRecord->nice_name);
        $obj->setUrl($wpRecord->user_url);
        $obj->setDisplayName($wpRecord->display_name);
//        $obj->setNickname($wpRecord->nickname);
        $obj->setFirstName($wpRecord->first_name);
        $obj->setLastName($wpRecord->last_name);
        $obj->setDescription($wpRecord->description);
        $obj->setRichEditing($wpRecord->rich_editing);        
        $obj->setRegistered(DateHelper::dbStrToDatetime($wpRecord->user_registered));
        $obj->setRole($wpRecord->role);
        $obj->setJabber($wpRecord->jabber);
        $obj->setAim($wpRecord->aim);
        $obj->setYim($wpRecord->yim);
        
        $obj->setWpUser($wpRecord);
        
        self::$userCacheById[$obj->getId()] = $obj;
        self::$userCacheByEmail[$obj->getEmail()] = $obj->getId();
        self::$userCacheByLogin[$obj->getLogin()] = $obj->getId();
        
        return $obj;
    }

    public function packDbRecord($forUpdate = true){
        $dbRecord = array();
        if($forUpdate){
            $dbRecord['ID'] = $this->getId();
        }
        if(!empty($this->password)){
            $dbRecord['user_pass'] = $forUpdate?
                wp_hash_password($this->getPassword()):
                $this->getPassword();
        }
        $dbRecord['user_login'] = $this->getLogin();
        $dbRecord['user_nicename'] = $this->getNicename();
        $dbRecord['user_url'] = $this->getUrl();
        $dbRecord['user_email'] = $this->getEmail();
        $dbRecord['display_name'] = $this->getDisplayName();
//        $dbRecord['nickname'] = $this->getNickname();
        $dbRecord['first_name'] = $this->getFirstName();
        $dbRecord['last_name'] = $this->getLastName();
        $dbRecord['description'] = $this->getDescription();
        $dbRecord['rich_editing'] = $this->getRichEditing();
        $dbRecord['user_registered'] = DateHelper::datetimeToDbStr($this->getRegistered());
        $dbRecord['role'] = $this->getRole();
        $dbRecord['jabber'] = $this->getJabber();
        $dbRecord['aim'] = $this->getAim();
        $dbRecord['yim'] = $this->getYim();
        
        return $dbRecord;
    }

    public function insert(){
        $this->setRegistered(new Zend_Date());
        $dbRecord = $this->packDbRecord(false);
        $id = wp_insert_user($dbRecord);
        $this->setId($id);
        return $id;
    }
    
    public function update(){
        $dbRecord = $this->packDbRecord();
        unset($dbRecord['user_login']);
        unset($dbRecord['user_registered']);
        return wp_update_user($dbRecord);
    }
    
    public function updateProfile(){
        
    }
    
    public function delete($reassignUserId = 0){
        return self::deleteById($this->getId(), $reassignUserId);
    }
    
    /**
     * Deletes user with the specified $userId from db table
     *
     * @param integer $userId
     * @return boolean
     */
    public static function deleteById($userId = 0, $reassignUserId = 0) {
        $item = Util::getItem(self::$userCacheById, $userId);
        if($item){
            unset(self::$userCacheByEmail[$item->getEmail()]);
            unset(self::$userCacheByLogin[$item->getLogin()]);
            unset(self::$userCacheById[$userId]);
        }
        return wp_delete_user( $userId, $reassignUserId );
    }

    /**
     *
     * @param integer $id
     * @return UserModel 
     */
    public static function selectById($id, $useCache = true){
        if($useCache){
            $item = Util::getItem(self::$userCacheById, $id);
            if($item){
                return $item;
            }
        }
        $wpRecord = get_user_by('id', $id);
        return $wpRecord?self::unpackDbRecord($wpRecord):null;
    }

    /**
     *
     * @param string $login
     * @return UserModel 
     */
    public static function selectByLogin($login, $useCache = true){
        if($useCache){
            $id = Util::getItem(self::$userCacheByLogin, $login);
            $item = Util::getItem(self::$userCacheById, $id);
            if($item){
                return $item;
            }
        }
        $wpRecord = get_user_by('login', $login);
        return $wpRecord?self::unpackDbRecord($wpRecord):null;
    }

    public static function selectByEmail($email, $useCache = true){
        if($useCache){
            $id = Util::getItem(self::$userCacheByEmail, $email);
            $item = Util::getItem(self::$userCacheById, $id);
            if($item){
                return $item;
            }
        }
        $wpRecord = get_user_by('email', $email);
        return $wpRecord?self::unpackDbRecord($wpRecord):null;
    }

    public static function selectBySlug($slug){
        $wpRecord = get_user_by('slug', $slug);
        return $wpRecord?self::unpackDbRecord($wpRecord):null;
    }

    public static function selectUsers($wpUserQueryArgs){
        $users = array();
//        $dbRecords = get_users($wpUserQueryArgs);
	$args = wp_parse_args( $wpUserQueryArgs );
	$args['count_total'] = true;

	$user_search = new WP_User_Query($args);

	$dbRecords = (array) $user_search->get_results();
        foreach ($dbRecords as $dbRecord) {
            $users[] = self::unpackDbRecord($dbRecord);
        }
        
        return $users;
    }
    
    public static function query(){
        return new UserQueryModel();
    }
    
    /**
     * 
     * @global type $wpdb
     * @global type $current_user
     * @return UserModel
     */
    public static function currentUser(){
        global  $wpdb, $current_user;
        
        if(empty(self::$currentUser)){

            wp_get_current_user();

            if($current_user && $current_user->ID){
                $role = $wpdb->prefix . 'capabilities';
                $roles = array_keys($current_user->$role);
                $current_user->role = reset($roles);
                if(!$current_user->role){
                    $current_user->role = 'guest';
                }
                self::$currentUser = self::unpackDbRecord($current_user);
            }else{
                self::$currentUser = new UserModel();
                self::$currentUser->setRole('guest');
            }

        }
        
        return self::$currentUser;
    }
    
    public function packJsonItem() {
        $jsonItem = array();
        $jsonItem['id'] = $this->getId();
        $jsonItem['user_login'] = $this->getLogin();
        $jsonItem['user_nicename'] = $this->getNicename();
        $jsonItem['user_url'] = $this->getUrl();
        $jsonItem['user_email'] = $this->getEmail();
        $jsonItem['display_name'] = $this->getDisplayName();
//        $jsonItem['nickname'] = $this->getNickname();
        $jsonItem['first_name'] = $this->getFirstName();
        $jsonItem['last_name'] = $this->getLastName();
        $jsonItem['description'] = $this->getDescription();
        $jsonItem['rich_editing'] = $this->getRichEditing();
        $jsonItem['user_registered'] = DateHelper::datetimeToJsonStr($this->getRegistered());
        $jsonItem['role'] = $this->getRole();
        $jsonItem['jabber'] = $this->getJabber();
        $jsonItem['aim'] = $this->getAim();
        $jsonItem['yim'] = $this->getYim();
        $jsonItem['profile_link'] = $this->getProfileLink();
        
        return $jsonItem;
    }
    
    public function getValidationErrors() {
        return array();
    }

    public function unpackInput($input = array()) {
        if(empty($input)){
            $input = InputHelper::getParams();
        }
        $input = array_merge($this->packJsonItem(), $input);

        $this->setId(Util::getItem($input, 'id', 0));
        $this->setLogin(Util::getItem($input, 'user_login'));
        $this->setEmail(Util::getItem($input, 'user_email'));
        $this->setNicename(Util::getItem($input, 'nice_name'));
        $this->setUrl(Util::getItem($input, 'user_url'));
        $this->setDisplayName(Util::getItem($input, 'display_name'));
//        $this->setNickname(Util::getItem($input, 'nickname'));
        $this->setFirstName(Util::getItem($input, 'first_name'));
        $this->setLastName(Util::getItem($input, 'last_name'));
        $this->setDescription(Util::getItem($input, 'description'));
        $this->setRichEditing(Util::getItem($input, 'rich_editing'));        
        $this->setRegistered(DateHelper::jsonStrToDatetime(Util::getItem($input, 'user_registered')));
        $this->setRole(Util::getItem($input, 'role'));
        $this->setJabber(Util::getItem($input, 'jabber'));
        $this->setAim(Util::getItem($input, 'aim'));
        $this->setYim(Util::getItem($input, 'yim'));
        
    }

    public function validateInput($input = array(), $action = 'create') {
        return true;
    }
    
    /**
     * returns true if the user is administrator
     *
     * @return boolean
     */
    public function isAdmin() {
        if (is_multisite()) {
            $super_admins = get_super_admins();
            if (is_array($super_admins) && in_array($this->getLogin(), $super_admins))
                return true;
        } elseif($this->getWpUser()) {
            if ($this->getWpUser()->has_cap('delete_users'))
                return true;
        }
        
        return false;
    }

    public static function loginExists($login) {

        return username_exists($login);
    }

    public static function emailExists($email) {

        return email_exists($email);
    }
    
    public static function checkActivationKey($key, $login){
	global $wpdb;

        $key = preg_replace('/[^a-z0-9]/i', '', $key);

        if (empty($key) || !is_string($key)) {
            return new WP_Error('invalid_key', __('Invalid key'));
        }
        if (empty($login) || !is_string($login)) {
            return new WP_Error('invalid_key', __('Invalid key'));
        }
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));

        if (empty($user)) {
            unset($_SESSION['activationkey']);
            unset($_SESSION['activationlogin']);
            unset($_SESSION['activationpopup']);
            session_commit();
            return new WP_Error('invalid_key', __('Invalid key'));
        }
        return self::unpackDbRecord($user);
    }

    public function checkPassword($password) {
        return user_pass_ok($this->getLogin(), $password);
    }

    public function changePassword($password) {
//        $this->setPassword($password);
//        return $this->update();
	wp_set_password($password, $this->getId());

	wp_password_change_notification($this->getWpUser());
    }
    
    public static function flushCache(){
        self::$userCacheByEmail = array();
        self::$userCacheByLogin = array();
        self::$userCacheById = array();
    }
    
    public static function getUserCacheById($id = 0){
        if($id){
            return Util::getItem(self::$userCacheById, $id);
        }
        return self::$userCacheById;
    }
    
    public static function getUserCacheByEmail($email = ''){
        if($email){
            $id = Util::getItem(self::$userCacheByEmail, $email);
            return Util::getItem(self::$userCacheById, $id, null);
        }
        $ret = array();
        foreach(self::$userCacheByEmail as $email => $id){
            $item = Util::getItem(self::$userCacheById, $id);
            if($item){
                $ret[$email] = $item;
            }
        }
        return $ret;
    }

    public static function getUserCacheByLogin($login = ''){
        if($login){
            $id = Util::getItem(self::$userCacheByLogin, $login);
            return Util::getItem(self::$userCacheById, $id, null);
        }
        $ret = array();
            foreach(self::$userCacheByLogin as $login => $id){
                $item = Util::getItem(self::$userCacheById, $id);
                if($item){
                    $ret[$login] = $item;
                }
            }
        return $ret;
    }

}
