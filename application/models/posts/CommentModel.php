<?php

require_once 'application/helpers/JsonHelper.php';

/**
 * Description of CommentModel
 *
 * @author borismossounov
 */
class CommentModel implements DbRecordInterface, JsonReadyInterface{

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
    protected $isApproved;
    protected $agent;
    protected $type;

    protected $wpComment;


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
        $this->setDtCreatedGMT($date);
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

    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getUrl() {
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
        return $this->dtCreatedGMT;
    }

    public function setDtCreatedGMT($dtCreatedGMT) {
        $this->dtCreatedGMT = $dtCreatedGMT;
    }

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
        $this->parent = $parentId;
    }

    public function getWpComment() {
        return $this->wpComment;
    }

    public function setWpComment($wpComment) {
        $this->wpComment = $wpComment;
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
        $obj->setDtCreatedGMT(DateHelper::dbStrToDatetime($wpRecord->comment_date_gmt));
        $obj->setAgent($wpRecord->comment_agent);
        $obj->setType($wpRecord->comment_type);
        
        $obj->setWpComment($wpRecord);
        
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
        $this->setDtCreatedGMT(new Zend_Date());
	$this->setIp(preg_replace( '/[^0-9a-fA-F:., ]/', '',$_SERVER['REMOTE_ADDR'] ));
	$this->setAgent(substr($_SERVER['HTTP_USER_AGENT'], 0, 254));
        $dbRecord = $this->packDbRecord(false);
        $id = wp_insert_comment($dbRecord);
        $this->setId($id);
        return $id;
    }
    
    public function update(){
        $dbRecord = $this->packDbRecord(true);
        unset($dbRecord['comment_date']);
        unset($dbRecord['comment_date_gmt']);
        return wp_update_comment($dbRecord);
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
        return wp_delete_comment( $commentId, $forceDelete );
    }

    /**
     *
     * @param integer $id
     * @return CommentModel 
     */
    public static function selectById($id){
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
        $jsonItem['post_id'] = $this->getPostId();
        $jsonItem['author'] = $this->getAuthor();
        $jsonItem['email'] = $this->getEmail();
        $jsonItem['url'] = $this->getUrl();
//        $jsonItem['comment_author_IP'] = $this->getIp();
        $jsonItem['user_id'] = $this->getUserId();
        $jsonItem['content'] = $this->getContent();
        $jsonItem['karma'] = $this->getKarma();
        $jsonItem['approved'] = $this->getIsApproved();
        $jsonItem['agent'] = $this->getAgent();
        $jsonItem['parent_id'] = $this->getParentId();
        $jsonItem['type'] = $this->getType();
        $jsonItem['date'] = DateHelper::datetimeToDbStr($this->getDtCreated());
//        $jsonItem['comment_date_gmt'] = DateHelper::datetimeToDbStr($this->getDtCreatedGMT());
        
        return $jsonItem;
    }
}
