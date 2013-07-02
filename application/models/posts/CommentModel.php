<?php

require_once 'application/helpers/JsonHelper.php';
require_once 'application/helpers/InputHelper.php';
require_once 'application/helpers/WpDbHelper.php';

/**
 * Description of CommentModel
 *
 * @author borismossounov
 */
class CommentModel implements DbRecordInterface, JsonReadyInterface, InputReadyInterface{

    protected $id;
    protected $postId;
    protected $parentId;
    protected $userId;
    protected $author;
    protected $email;
    protected $url;
    protected $ip;
    protected $dtCreated;
    protected $dtCreatedGMT;
    protected $content;
    protected $karma;
    protected $karmaDelta;
    protected $isApproved;
    protected $agent;
    protected $type;

    protected $wpComment;

    protected $validationErrors = array();

    protected static $commentsCacheById = array();
    protected static $commentsCacheByPostId = array();

    /**
     * PostModel constructor
     *
     * @param integer $id
     */
    public function __construct() {
        $this->init();
    }

    public function init(){
        $this->setId(0);
        $date = new Zend_Date();
        $this->setDtCreated($date);
        $user = UserModel::currentUser();
        if($user && $user->getId()){
            $this->setUserId($user->getId());
            $this->setAuthor($user->getDisplayName());
            $this->setEmail($user->getEmail());
            $this->setUrl($user->getUrl());
        }
        $this->setDtCreated(new Zend_Date());
        $this->setIsApproved(0);
//        $this->setDtCreatedGMT($date);
        $this->setKarma(0);
    }
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getPostId() {
        return $this->postId;
    }

    public function setPostId($postId) {
        $this->postId = $postId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }
    
    public function getUser(){
        return $this->getUserId()?UserModel::selectById($this->getUserId()):null;
    }

    public function getAuthor() {
        $user = $this->getUser();
        if($user && $user->getId()){
            return $user->getDisplayName()?$user->getDisplayName():$user->getLogin();
        }
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getEmail() {
        $user = $this->getUser();
        if($user && $user->getId()){
            return $user->getEmail();
        }
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getUrl() {
        $user = $this->getUser();
        if($user && $user->getId()){
            return $user->getUrl();
        }
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getIp() {
        return $this->ip;
    }

    public function setIp($ip) {
        $this->ip = $ip;
    }

    public function getDtCreated() {
        return $this->dtCreated;
    }

    public function setDtCreated($dtCreated) {
        $this->dtCreated = $dtCreated;
    }

    public function getDtCreatedGMT() {
        return new Zend_Date(get_gmt_from_date(DateHelper::datetimeToDbStr($this->getDtCreated())));
//        return $this->dtCreatedGMT;
    }

//    public function setDtCreatedGMT($dtCreatedGMT) {
//        $this->dtCreatedGMT = $dtCreatedGMT;
//    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function getKarma() {
        return $this->karma;
    }

    public function setKarma($karma) {
        $this->karma = $karma;
    }
    
    public function getKarmaDelta(){
        if(!$this->getId()){
            return 0;
        }
//        echo ' get ';
//        Util::print_r($_SESSION);
        $votes = Util::getItem($_SESSION, 'comment_votes', array());
        $today = date('Y-m-d/').get_current_user_id();
        foreach ($votes as $date => $comments) {
            if($date != $today){
                unset($_SESSION['comment_votes'][$date]);
            }
        }
        if(!$_SESSION['comment_votes'][$today]){
            $_SESSION['comment_votes'][$today] = array();
        }
        
        return Util::getItem($_SESSION['comment_votes'][$today], $this->getId(), 0);
    }
    
    public function setKarmaDelta($delta){
        if(!$this->getId()){
            return 0;
        }
        $today = date('Y-m-d/').get_current_user_id();
        $votes = Util::getItem($_SESSION, 'comment_votes', array());
        foreach ($votes as $date => $comments) {
            if($date != $today){
                unset($_SESSION['comment_votes'][$date]);
            }
        }
        if(!$_SESSION['comment_votes'][$today]){
            $_SESSION['comment_votes'][$today] = array();
        }
        
//        echo ' set ';
//        Util::print_r($_SESSION);
        
        $_SESSION['comment_votes'][$today][$this->getId()] = $delta;
    }
    
    public function vote($delta){
        global $wpdb;
        if(!$this->getId()){
            return 0;
        }
        
        $vote = $this->getKarmaDelta();
        if($delta > 1){
            $delta = 1;
        }elseif($delta < -1){
            $delta = -1;
        }
//        printf('[vote: %d, delta: %d]', $vote, $delta);
        
        if(($delta > 0 && $vote <=0) 
        || ($delta < 0 && $vote >=0)){
            $table = self::getDbTable();
            $idCol = self::getDbIdColumn();
            $sql = WpDbHelper::prepare("
                UPDATE $table
                SET comment_karma = comment_karma + (%d)
                WHERE $idCol = %d
                ", $delta, $this->getId());
            if($wpdb->query($sql)){
                $sqlKarma = WpDbHelper::prepare("
                SELECT comment_karma FROM $table
                WHERE $idCol = %d
                ", $this->getId());
                $this->setKarma($wpdb->get_var($sqlKarma));
                $this->setKarmaDelta($vote+$delta);
                return $delta;
            }
        }
        
        return 0;
    }
    
    public function voteUp(){
        return $this->vote(1);
    }
    
    public function voteDown(){
        return $this->vote(-1);
    }
    
    public function getIsApproved() {
        return $this->isApproved;
    }

    public function setIsApproved($isApproved) {
        $this->isApproved = $isApproved;
    }

    public function getAgent() {
        return $this->agent;
    }

    public function setAgent($agent) {
        $this->agent = $agent;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getParentId() {
        return $this->parentId;
    }

    public function setParentId($parentId) {
        $this->parentId = $parentId;
    }

    public function getWpComment() {
        return $this->wpComment;
    }

    public function setWpComment($wpComment) {
        $this->wpComment = $wpComment;
    }

    public static function getDbIdColumn() {
        return 'comment_ID';
    }

    public static function getDbTable() {
        global $wpdb;
        return $wpdb->comments;
    }

    public function getValidationErrors() {
        return $this->validationErrors;
    }

    public function unpackInput($input = array()) {
        if(empty($input)){
            $input = InputHelper::getParams();
        }
        $input = array_merge($this->packJsonItem(), $input);

//        $this->setId(Util::getItem($input, 'id', 0));
        $this->setContent(Util::getItem($input, 'comment_content'));

        if(!$this->getId()){
            $this->setPostId(Util::getItem($input, 'comment_post_ID'));
            
            $parentId = Util::getItem($input, 'comment_parent', 0);
            $parentStatus = ( 0 < $parentId ) ? wp_get_comment_status($parentId) : '';
            $this->setParentId(( 'approved' == $parentStatus || 'unapproved' == $parentStatus ) ? $parentId : 0);

            $user = UserModel::currentUser();
            if($user && $user->getId()){
                $this->setUserId($user->getId());
                $this->setAuthor($user->getDisplayName()?$user->getDisplayName():$user->getLogin());
                $this->setEmail($user->getEmail());
                $this->setUrl($user->getUrl());
            }else{
                $this->setUserId(0);
                $this->setAuthor(Util::getItem($input, 'comment_author'));
                $this->setEmail(Util::getItem($input, 'comment_author_email'));
                $this->setUrl(Util::getItem($input, 'comment_author_url'));
            }
            $this->setType(Util::getItem($input, 'comment_type', ''));
            $dbRec = $this->packDbRecord(false);
            unset($dbRec['comment_approved']);
            $this->setIsApproved(wp_allow_comment($dbRec));
        }
        
    }

    public function validateInput($input = array(), $action = 'create') {
        if('create' == $action){
            $postId = Util::getItem($input, 'comment_post_ID', 0);
            $post = get_post($postId);

            if (empty($post->comment_status)) {
                do_action('comment_id_not_found', $postId);
                exit;
            }

            // get_post_status() will get the parent status for attachments.
            $status = get_post_status($post);

            $status_obj = get_post_status_object($status);
            $msgCommentsClosed = __('Sorry, comments are closed for this item.');
            if (!comments_open($postId)) {
                $this->validationErrors['comment_closed'] = $msgCommentsClosed;
                return false;
            } elseif ('trash' == $status) {
                $this->validationErrors['comment_on_trash'] = $msgCommentsClosed;
                return false;
            } elseif (!$status_obj->public && !$status_obj->private) {
                $this->validationErrors['comment_on_draft'] = $msgCommentsClosed;
                return false;
            } elseif (post_password_required($postId)) {
                $this->validationErrors['comment_on_password_protected'] = $msgCommentsClosed;
                return false;
            } 

            // If the user is logged in
            $user = wp_get_current_user();
            if ($user->exists()) {
                if (current_user_can('unfiltered_html')) {
                    if (wp_create_nonce('unfiltered-html-comment_' . $postId) != $_POST['_wp_unfiltered_html_comment']) {
                        kses_remove_filters(); // start with a clean slate
                        kses_init_filters(); // set up the filters
                        InputHelper::permitHtml('comment_content');
                    }
                }
            } else {
                if (get_option('comment_registration') || 'private' == $status){
                    $this->validationErrors[ErrorHelper::CODE_AUTH_REQUIRED] = __('Sorry, you must be logged in to post a comment.');
                    return false;
                }else{
                    if (!Util::getItem($input, 'comment_author')) {
                        $this->validationErrors['comment_author'] = 'Необходимо заполнить';
                    }
                    if (!Util::getItem($input, 'comment_author_email')) {
                        $this->validationErrors['comment_author_email'] = 'Необходимо заполнить';
                    }
                }
            }

            if (!Util::getItem($input, 'comment_content')) {
                $this->validationErrors['comment_content'] = 'Введите комментарий';
            }
            
            if(!empty($this->validationErrors)){
                return false;
            }
            
        }elseif('update' == $action){
            AclHelper::apiOwnershipRequired($this);
            if (!Util::getItem($input, 'comment_content')) {
                $this->validationErrors['comment_content'] = 'Введите комментарий';
                return false;
            }
        }
        
        return true;
    }

    public static function unpackDbRecord( $wpRecord){
        
        $obj = new self();

        $obj->setId($wpRecord->comment_ID);
        $obj->setPostId($wpRecord->comment_post_ID);
        $obj->setUserId($wpRecord->user_id);
        $obj->setParentId($wpRecord->comment_parent);
        $obj->setAuthor($wpRecord->comment_author);
        $obj->setEmail($wpRecord->comment_author_email);
        $obj->setUrl($wpRecord->comment_author_url);
        $obj->setIp($wpRecord->comment_author_IP);
        $obj->setContent($wpRecord->comment_content);
        $obj->setKarma($wpRecord->comment_karma);
        $obj->setIsApproved($wpRecord->comment_approved);
        $obj->setDtCreated(DateHelper::dbStrToDatetime($wpRecord->comment_date));
//        $obj->setDtCreatedGMT(DateHelper::dbStrToDatetime($wpRecord->comment_date_gmt));
        $obj->setAgent($wpRecord->comment_agent);
        $obj->setType($wpRecord->comment_type);
        
        $obj->setWpComment($wpRecord);
        
        self::$commentsCacheById[$obj->getId()] = $obj;
        self::$commentsCacheByPostId[$obj->getPostId()][$obj->getId()]=$obj->getId();
        
        return $obj;
    }

    public function unpackMeta($meta){
//        print_r($meta);
//        $this->setNickname($meta['nickname'][0]);
    }

    public function packDbRecord($forUpdate = true){
        $dbRecord = array();
        if($forUpdate){
            $dbRecord['comment_ID'] = $this->getId();
        }
        $dbRecord['comment_post_ID'] = $this->getPostId();
        $dbRecord['comment_author'] = $this->getAuthor();
        $dbRecord['comment_author_email'] = $this->getEmail();
        $dbRecord['comment_author_url'] = $this->getUrl();
        $dbRecord['comment_author_IP'] = $this->getIp();
        $dbRecord['user_id'] = $this->getUserId();
        $dbRecord['comment_content'] = $this->getContent();
        $dbRecord['comment_karma'] = $this->getKarma();
        $dbRecord['comment_approved'] = $this->getIsApproved();
        $dbRecord['comment_agent'] = $this->getAgent();
        $dbRecord['comment_parent'] = $this->getParentId();
        $dbRecord['comment_type'] = $this->getType();
        $dbRecord['comment_date'] = DateHelper::datetimeToDbStr($this->getDtCreated());
        $dbRecord['comment_date_gmt'] = DateHelper::datetimeToDbStr($this->getDtCreatedGMT());
        
        return $dbRecord;
    }

    public function insert(){
        $this->setDtCreated(new Zend_Date());
//        $this->setDtCreatedGMT(new Zend_Date());
	$this->setIp(preg_replace( '/[^0-9a-fA-F:., ]/', '',$_SERVER['REMOTE_ADDR'] ));
	$this->setAgent(substr($_SERVER['HTTP_USER_AGENT'], 0, 254));
        $dbRecord = $this->packDbRecord(false);
//        Util::print_r($dbRecord);
//        die();
        $id = wp_insert_comment($dbRecord);
        $this->setId($id);
        return $id;
    }
    
    public function update($forUpdate = false){
        $dbRecord = $this->packDbRecord(true);
        unset($dbRecord['comment_date']);
        unset($dbRecord['comment_date_gmt']);
        wp_update_comment($dbRecord);
        return true;
    }
    
    public function delete($forceDelete = 0){
        return self::deleteById($this->getId(), $forceDelete);
    }
    
    /**
     * Deletes user with the specified $userId from db table
     *
     * @param integer $commentId
     * @return boolean
     */
    public static function deleteById($commentId = 0, $forceDelete = 0) {
        $item = Util::getItem(self::$commentsCacheById, $commentId);
        if($item){
            unset(self::$commentsCacheByPostId[$item->getPostId()][$item->getId()]);
            unset(self::$commentsBy[$item->getId()]);
        }
        return wp_delete_comment( $commentId, $forceDelete );
    }

    /**
     *
     * @param integer $id
     * @return CommentModel 
     */
    public static function selectById($id, $useCache = true){
        if($useCache){
            $record = Util::getItem(self::$commentsCacheById, $id);
            if($record){
                return $record;
            }
        }
        $wpRecord = get_comment($id);
        return $wpRecord?self::unpackDbRecord($wpRecord):null;
    }


    public static function selectComments($wpCommentsQueryArgs){
//        print_r($wpCommentsQueryArgs);
        $comments = array();
        $dbRecords = get_comments($wpCommentsQueryArgs);
        foreach ($dbRecords as $dbRecord) {
            $comment = self::unpackDbRecord($dbRecord);
            $comments[$comment->getId()] = $comment;
        }
        
        return $comments;
    }
    
    public static function selectPostComments($postId, $sinceCommentId = 0){
        global $wpdb;
        $select = $wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_id = %d AND comment_id > %d", $postId, $sinceCommentId);
        $dbRecords = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_post_id = $postId AND comment_id > $sinceCommentId");
        foreach ($dbRecords as $dbRecord) {
            $comments[] = self::unpackDbRecord($dbRecord);
        }
    }
    
    public static function selectMeta($comment_id, $key, $single){
        return get_comment_meta($comment_id, $key, $single);
    }

    public function loadMeta(){
        $meta = self::selectMeta($this->getId());
        $this->unpackMeta($meta);
    }
    
    public function populateWpGlobals(){
        global $comment;
        $comment = $this->getWpComment();
    }
    
    public function packJsonItem() {
        $jsonItem = array();
        $jsonItem['id'] = $this->getId();
        $jsonItem['comment_post_ID'] = $this->getPostId();
        $jsonItem['comment_author'] = $this->getAuthor();
        $jsonItem['comment_author_email'] = $this->getEmail();
        $jsonItem['comment_author_url'] = $this->getUrl();
//        $jsonItem['comment_author_IP'] = $this->getIp();
        $jsonItem['user_id'] = $this->getUserId();
        $jsonItem['comment_content'] = $this->getContent();
        $jsonItem['comment_karma'] = $this->getKarma();
        $jsonItem['comment_karma_delta'] = $this->getKarmaDelta();
        $jsonItem['comment_approved'] = $this->getIsApproved();
        $jsonItem['comment_agent'] = $this->getAgent();
        $jsonItem['comment_parent'] = $this->getParentId();
        $jsonItem['comment_type'] = $this->getType();
        $jsonItem['comment_date'] = DateHelper::datetimeToJsonStr($this->getDtCreated());
        $jsonItem['comment_date_gmt'] = DateHelper::datetimeToJsonStr($this->getDtCreatedGMT());
        
        return $jsonItem;
    }

    public static function flushCache(){
        self::$commentsCacheById = array();
        self::$commentsCacheByPostId = array();
    }
    
    public static function getCommentsCacheById($id = 0){
        if($id){
            return Util::getItem(self::$commentsCacheById, $id);
        }
        return self::$commentsCacheById;
    }
    
    public static function getCommentsCacheByPostId($postId = 0){
        $ret = array();

        if($postId){
            $commentIds = Util::getItem(self::$commentsCacheByPostId, $postId);
            foreach($commentIds as $id){
                $item = Util::getItem(self::$commentsCacheById, $id);
                if($item){
                    $ret[$id] = $item;
                }
            }
            
            return $ret;
        }
        
        foreach (self::$commentsCacheByPostId as $postId=>$commentIds){
            if($postId){
                $ret[$postId] = self::getCommentsCacheByPostId($postId);
            }
        }
        
        return $ret;
    }

}
