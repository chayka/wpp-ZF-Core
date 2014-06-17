<?php

require_once 'application/helpers/WpDbHelper.php';
require_once 'application/helpers/JsonHelper.php';
require_once 'application/models/posts/CommentModel.php';
require_once 'application/models/posts/PostQueryModel.php';
require_once 'application/models/taxonomies/TermQueryModel.php';
//require_once 'application/helpers/LuceneHelper.php';

class PostModel implements DbRecordInterface, JsonReadyInterface, InputReadyInterface /*, LuceneReadyInterface*/{

    
    static $wpQuery;
    static $postsFound;
    protected $id;
    protected $userId;
    protected $parentId;
    protected $guid;
    protected $type;
    
    protected $slug;
    protected $title;
    protected $content;
    protected $contentFiltered;
    protected $excerpt;
    protected $status;
    protected $pingStatus;
    protected $password;
    protected $toPing;
    protected $pinged;
    protected $menuOrder;
    protected $mimeType;
    protected $commentStatus;
    protected $commentCount;
    protected $comments;
    protected $reviewsCount;
    protected $terms;
    protected $meta;
    protected $imageData;
    protected $thumbnailId;

    protected $dtCreated;
    protected $dtCreatedGMT;
    protected $dtModified;
    protected $dtModifiedGMT;
    
    protected $wpPost;
    
    protected static $postsCacheById = array();
    protected static $postsCacheBySlug = array();
    protected static $jsonMetaFields = array();

    /**
     * PostModel constructor
     */
    public function __construct() {
        $this->init();
    }

    public function init(){
        $this->setId(0);
        $this->setDtCreated(new Zend_Date());
        $this->setStatus('draft');
        $this->setCommentStatus('open');
        $this->setPingStatus('closed');
    }
    
    /**
     * Get post id
     * 
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set post id
     * 
     * @param integer $id
     * @return PostModel 
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get author user id
     * 
     * @return integer
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * Set author user id
     * 
     * @param integer $userId
     * @return \PostModel
     */
    public function setUserId($userId) {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get parent-post id
     * 
     * @return integer
     */
    public function getParentId() {
        return $this->parentId;
    }

    /**
     * Set parent-post id
     * 
     * @param integer $parentId
     * @return \PostModel
     */
    public function setParentId($parentId) {
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * Get post guid
     * 
     * @return string
     */
    public function getGuid() {
        return $this->guid;
    }

    /**
     * Set post guid
     * 
     * @param string $guid
     */
    public function setGuid($guid) {
        $this->guid = $guid;
        return $this;
    }

    /**
     * Get post type
     * 
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set post type
     * 
     * @param string $type
     * @return \PostModel
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Get post name (slug)
     * 
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * Set post name (slug)
     * 
     * @param type $slug
     * @return \PostModel
     */
    public function setSlug($slug) {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get post title
     * 
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set post title
     * 
     * @param type $title
     * @return PostModel 
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Get post content
     * @param boolean $wpautop Set to true if you need auto-<p></p> (default: true)
     * @return string HTML content
     */
    public function getContent($wpautop = true) {
        return $wpautop?wpautop($this->content):$this->content;
    }

    /**
     * Set content
     * 
     * @param string $content
     * @return \PostModel
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    public function getContentFiltered() {
        return $this->contentFiltered;
    }

    public function setContentFiltered($contentFiltered) {
        $this->contentFiltered = $contentFiltered;
    }


    /**
     * Get post excerpt that was set or generated one
     * 
     * @param boolean $generate set to true if you need excerpt autogeneration
     * @return string
     */
    public function getExcerpt($generate = true) {
        if($generate && !$this->excerpt && $this->content){
            $text = $this->getContent();

            $text = strip_shortcodes( $text );

            $text = apply_filters('the_content', $text);
            $text = str_replace(']]>', ']]&gt;', $text);
            $excerpt_length = apply_filters('excerpt_length', 55);
            $excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
            $text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
            $this->excerpt = wp_trim_excerpt($text);
        }
        return $this->excerpt;
    }

    /**
     * Set post excerpt
     * 
     * @param string $excerpt
     * @return \PostModel
     */
    public function setExcerpt($excerpt) {
        $this->excerpt = $excerpt;
        return $this;
    }

    /**
     * Get post status
     * 
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set post status (publish|draft|deleted|future)
     * 
     * @param type $status
     * @return \PostModel
     */
    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    /**
     * Get post ping status
     * 
     * @return string
     */
    public function getPingStatus() {
        return $this->pingStatus;
    }

    /**
     * Set ping status (closed|open)
     * 
     * @param string $pingStatus
     * @return \PostModel
     */
    public function setPingStatus($pingStatus) {
        $this->pingStatus = $pingStatus;
        return $this;
    }

    /**
     * Get post password
     * 
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Set post password
     * 
     * @param type $password
     * @return \PostModel
     */
    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }

    /**
     * 
     * @return type
     */
    public function getToPing() {
        return $this->toPing;
    }

    /**
     * 
     * @param type $toPing
     * @return \PostModel
     */
    public function setToPing($toPing) {
        $this->toPing = $toPing;
        return $this;
    }

    /**
     * 
     * @return type
     */
    public function getPinged() {
        return $this->pinged;
    }

    /**
     * 
     * @param type $pinged
     * @return \PostModel
     */
    public function setPinged($pinged) {
        $this->pinged = $pinged;
        return $this;
    }

    /**
     * Get post order mark
     * 
     * @return integer
     */
    public function getMenuOrder() {
        return $this->menuOrder;
    }

    /**
     * Set post order mark
     * 
     * @param integer $menuOrder
     * @return \PostModel
     */
    public function setMenuOrder($menuOrder) {
        $this->menuOrder = $menuOrder;
        return $this;
    }

    /**
     * Get attachment mime type
     * 
     * @return string
     */
    public function getMimeType() {
        return $this->mimeType;
    }

    /**
     * Set attachment mime type
     * 
     * @param type $mimeType
     * @return \PostModel
     */
    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * Cheack if post is attachment image
     * 
     * @return boolean
     */
    public function isAttachmentImage(){
        return preg_match('%^image%', $this->getMimeType());
    }
    
    /**
     * Get comment status
     * 
     * @return string
     */
    public function getCommentStatus() {
        return $this->commentStatus;
    }

    /**
     * Set comment status  (open|closed)
     * 
     * @param string $commentStatus
     * @return \PostModel
     */
    public function setCommentStatus($commentStatus) {
        $this->commentStatus = $commentStatus;
        return $this;
    }

    /**
     * Get comment count
     * @return integer
     */
    public function getCommentCount() {
        return $this->commentCount;
    }

    /**
     * Set comment count
     * @param integer $commentCount
     * @return \PostModel
     */
    public function setCommentCount($commentCount) {
        $this->commentCount = $commentCount;
        return $this;
    }
 
    /**
     * Get post creation datetime
     * @return Zend_Date
     */
    public function getDtCreated() {
        return $this->dtCreated;
    }

    /**
     * Set date creation datetime
     * 
     * @param Zend_Date $dtCreated
     * @return \PostModel
     */
    public function setDtCreated($dtCreated) {
        $this->dtCreated = $dtCreated;
        return $this;
    }

    /**
     * Get post creation datetime (GMT)
     * @return \Zend_Date
     */
    public function getDtCreatedGMT() {
        return new Zend_Date(get_gmt_from_date(DateHelper::datetimeToDbStr($this->getDtCreated())));
//        return $this->dtCreatedGMT;
    }

//    public function setDtCreatedGMT($dtCreatedGMT) {
//        $this->dtCreatedGMT = $dtCreatedGMT;
//    }

    /**
     * Get post modification datetime
     * 
     * @return Zend_Date
     */
    public function getDtModified() {
        return $this->dtModified;
    }

    /**
     * Set post modification datetime
     * 
     * @param type $dtModified
     * @return \PostModel
     */
    public function setDtModified($dtModified) {
        $this->dtModified = $dtModified;
        return $this;
    }

    /**
     * Get post modification datetime (GMT)
     * @return \Zend_Date
     */
    public function getDtModifiedGMT() {
        return new Zend_Date(get_gmt_from_date(DateHelper::datetimeToDbStr($this->getDtModified())));
//        return $this->dtModifiedGMT;
    }
    
    /**
     * Get count of page reviews.
     * Should be used in couple with incReviewsCount()
     * 
     * @return integer
     */
    public function getReviewsCount() {
        if(!$this->reviewsCount){
            $this->reviewsCount = $this->getMeta('reviews_count');
        }
        return $this->reviewsCount?$this->reviewsCount:0;
    }
    
    /**
     * Set post reviews count. Used for model only, no database modification made.
     * Use incReviewsCount() instead.
     * 
     * @param integer $value
     * @return \PostModel
     */
    public function setReviewsCount($value) {
        $this->reviewsCount = $value;
        return $this;
    }
    
    /**
     * Increases post reviews count by one.
     * Should be called in PostController::viewAction() for example.
     * 
     * @return int
     */
    public function incReviewsCount(){
        if(!$this->getId()){
            return 0;
        }
        $visited = Util::getItem($_SESSION, 'visited', array());
        if(!isset($_SESSION['visited'])){
            $_SESSION['visited'] = array();
        }
        $today = date('Y-m-d');
        foreach ($visited as $date => $posts) {
            if($date != $today){
                unset($_SESSION['visited'][$date]);
            }
        }
        if(!isset($_SESSION['visited'][$today])){
            $_SESSION['visited'][$today] = array();
        }
        
        $visit = Util::getItem($_SESSION['visited'][$today], $this->getId(), false);
        
        if(!$visit){
            $this->getReviewsCount();
            $this->reviewsCount++;
            $this->updateMeta('reviews_count', $this->reviewsCount);
//            update_post_meta($this->getId(), 'reviews_count', $this->reviewsCount);
            $_SESSION['visited'][$today][$this->getId()] = true;
        }
        
        return $this->reviewsCount;
    }
    
    /**
     * Checks if post has been already visited today.
     * Works in couple with incReviewsCount() only
     * @return type
     */
    public function isVisited(){
        $visited = Util::getItem($_SESSION, 'visited', array());
        if(!isset($_SESSION['visited'])){
            $_SESSION['visited'] = array();
        }
        $today = date('Y-m-d');
        if(!isset($_SESSION['visited'][$today])){
            $_SESSION['visited'][$today] = array();
        }
        
        $visit = Util::getItem($_SESSION['visited'][$today], $this->getId(), false);
        return $visit;
    }
    
    /**
     * Get original WP_Post object if the one is preserved
     * 
     * @return WP_Post
     */
    public function getWpPost() {
        return $this->wpPost;
    }

    /**
     * Set original WP_Post object to be preserved
     * 
     * @param WP_Post $wpPost
     * @return \PostModel
     */
    public function setWpPost($wpPost) {
        $this->wpPost = $wpPost;
        return $this;
    }
    
    /**
     * Get post href. Utilizes get_permalink().
     * 
     * @return string
     */
    public function getHref(){
        return get_permalink($this->getId());
    }

    /**
     * Get href to the next WP post
     * 
     * @param boolean $in_same_cat
     * @return string
     */
    public function getHrefNext($in_same_cat = true){
        $post = get_next_post($in_same_cat);
        return $post && $post->ID ? get_permalink($post->ID):null;
    }
    
    /**
     * Get href to the previous WP post
     * 
     * @param boolean $in_same_cat
     * @return string
     */
    public function getHrefPrev($in_same_cat = true){
        $post = get_previous_post($in_same_cat);
        return $post && $post->ID ? get_permalink($post->ID):null;
    }
    
    /**
     * DbRecordInterface method, returns corresponding DB Table ID column name
     * 
     * @return string
     */
    public static function getDbIdColumn() {
        return 'ID';
    }

    /**
     * DbRecordInterface method, returns corresponding DB Table name
     * 
     * @return string
     */
    public static function getDbTable() {
        global $wpdb;
        return $wpdb->posts;
    }

    /**
     * Unpacks db record while fetching model from DB 
     * 
     * @param stdObject $wpRecord
     * @return PostModel
     */
    public static function unpackDbRecord( $wpRecord){
        $obj = new self();

        $obj->setId($wpRecord->ID);
        $obj->setUserId($wpRecord->post_author);
        $obj->setParentId($wpRecord->post_parent);
        $obj->setGuid($wpRecord->guid);
        $obj->setType($wpRecord->post_type);
        $obj->setSlug($wpRecord->post_name);
        $obj->setTitle($wpRecord->post_title);
        $obj->setContent($wpRecord->post_content);
        $obj->setContentFiltered($wpRecord->post_content_filtered);
        $obj->setExcerpt($wpRecord->post_excerpt);
        $obj->setStatus($wpRecord->post_status);        
        $obj->setPingStatus($wpRecord->ping_status);
        $obj->setPinged($wpRecord->pinged);
        $obj->setToPing($wpRecord->to_ping);
        $obj->setPassword($wpRecord->post_password);
        $obj->setDtCreated(DateHelper::dbStrToDatetime($wpRecord->post_date));
        $obj->setDtModified(DateHelper::dbStrToDatetime($wpRecord->post_modified));
        $obj->setMenuOrder($wpRecord->menu_order);
        $obj->setMimeType($wpRecord->post_mime_type);
        $obj->setCommentStatus($wpRecord->comment_status);
        $obj->setCommentCount($wpRecord->comment_count);
        
        $obj->setWpPost($wpRecord);
        
        self::$postsCacheById[$obj->getId()] = $obj;
        self::$postsCacheBySlug[$obj->getSlug()] = $obj->getId();
        
        return $obj;
    }

    /**
     * Packs model into assoc array before commiting to DB
     * 
     * @param boolean $forUpdate
     * @return array
     */
    public function packDbRecord($forUpdate = true){
        $dbRecord = array();
        if($forUpdate){
            $dbRecord['ID'] = $this->getId();
        }
        if(!empty($this->password)){
            $dbRecord['post_password'] = $this->getPassword();
        }
        $dbRecord['post_author'] = $this->getUserId();
        $dbRecord['post_parent'] = $this->getParentId();
        $dbRecord['post_type'] = $this->getType();
        $dbRecord['post_name'] = $this->getSlug();
        $dbRecord['post_title'] = $this->getTitle();
        $dbRecord['post_content'] = $this->getContent(false);
        $dbRecord['post_excerpt'] = $this->getExcerpt(false);
        $dbRecord['post_status'] = $this->getStatus();
        $dbRecord['post_date'] = DateHelper::datetimeToDbStr($this->getDtCreated());
        $dbRecord['post_date_gmt'] = DateHelper::datetimeToDbStr($this->getDtCreatedGMT());
        $dbRecord['ping_status'] = $this->getPingStatus();
        $dbRecord['to_ping'] = $this->getToPing();
        $dbRecord['pinged'] = $this->getPinged();
        $dbRecord['menu_order'] = $this->getMenuOrder();
        $dbRecord['comment_status'] = $this->getCommentStatus();
        
        return $dbRecord;
    }

    /**
     * Inserts new model to DB, returns autogenerated ID
     * 
     * @return integer
     */
    public function insert(){
        $this->setDtCreated(new Zend_Date());
//        $this->setDtCreatedGMT(new Zend_Date());
        $dbRecord = $this->packDbRecord(false);
        $id = wp_insert_post($dbRecord);
        $this->setId($id);
        return $id;
    }
    
    /**
     * Update db record
     * 
     * @return int|WP_Error The value 0 or WP_Error on failure. The post ID on success.
     */
    public function update(){
        $this->setDtModified(new Zend_Date());
        $dbRecord = $this->packDbRecord(true);
        unset($dbRecord['post_created']);
        unset($dbRecord['post_created_gmt']);
        return wp_update_post($dbRecord);
    }
    
    /**
     * Delete record form DB
     * 
     * @param bool $force_delete Whether to bypass trash and force deletion. Defaults to false.
     * @return mixed False on failure
     */
    public function delete($forceDelete = 0){
        return self::deleteById($this->getId(), $forceDelete);
    }
    
    /**
     * Deletes post with the specified $post from db table
     *
     * @param integer $postId
     * @param bool $force_delete Whether to bypass trash and force deletion. Defaults to false.
     * @return mixed False on failure
     */
    public static function deleteById($postId = 0, $forceDelete = 0) {
        $item = Util::getItem(self::$postsCacheById, $postId);
        if($item){
            unset(self::$postsCacheBySlug[$item->getSlug()]);
            unset(self::$postsCacheById[$postId]);
        }
        return wp_delete_post( $postId, $forceDelete );
    }

    /**
     * Select model from DB by ID
     * 
     * @param integer $id
     * @return PostModel 
     */
    public static function selectById($id = 0, $useCache = true){
        if($useCache && $id){
            $item = Util::getItem(self::$postsCacheById, $id);
            if($item){
                return $item;
            }
        }
        $wpRecord = get_post($id);
        return $wpRecord?self::unpackDbRecord($wpRecord):null;
    }


    /**
     * Select model from DB by slug
     * 
     * @param integer $id
     * @return PostModel 
     */
    public static function selectBySlug($slug, $postType = 'ANY', $useCache = true){
        if($useCache){
            $id = Util::getItem(self::$postsCacheBySlug, $slug);
            $item = Util::getItem(self::$postsCacheById, $id);
            if($item){
                return $item;
            }
        }
        $args = array('name'=>$slug);
        if($postType){
            $args['post_type'] = $postType;
            $args['post_status'] = 'publish';
        }
        $posts = self::selectPosts($args);
        return count($posts)?reset($posts):null;
    }

    /**
     * Selects post of specified post type by title.
     * The use of this function is not recommended as WP 
     * 
     * @global type $wpdb
     * @param string $title
     * @param string $postType
     * @return PostModel
     */
    public static function selectByTitle($title, $postType = 'ANY'){
        global $wpdb;
        $sql = $postType == 'ANY' ? 
            WpDbHelper::prepare("
                SELECT * FROM $wpdb->posts
                WHERE post_title = %s AND post_status = 'publish'" , $title
            ):
            WpDbHelper::prepare("
                SELECT * FROM $wpdb->posts
                WHERE post_title = %s AND post_type = %s AND post_status = 'publish'" , $title, $postType
            );
        
        $posts = self::selectSql($sql);
        return count($posts)?reset($posts):null;
    }

    /**
     * Get PostQueryModel object to create a query.
     * Call ->select() to fetch queried models;
     * The count of found rows can be found by calling postsFound() aftermath.
     * @param boolean $gloaba set to true if you need import from $wp_query
     * 
     * @return PostQueryModel 
     */
    public static function query($globalImport = false){
        $query = new PostQueryModel($globalImport);
        return $query;
    }

    /**
     * Select models using WP_Query syntax.
     * The count of found rows can be found by calling postsFound() aftermath.
     * 
     * @param array $wpPostsQueryArgs
     * @return array(PostModel)
     */
    public static function selectPosts($wpPostsQueryArgs = array()){
        
        global $wp_query;
        
        $posts = array();
        
        if(empty($wpPostsQueryArgs)){
            if(!self::$wpQuery){
                self::$wpQuery = $wp_query;
//                Util::print_r($wp_query);
            }
        }else{
            self::$wpQuery = new WP_Query($wpPostsQueryArgs);
        }
        
//        self::$wpQuery = empty($wpPostsQueryArgs)? $wp_query : new WP_Query($wpPostsQueryArgs);
        $posts = array();
        self::$postsFound=self::$wpQuery->found_posts;
        while(self::$wpQuery->have_posts()){
            $dbRecord = self::$wpQuery->next_post();
            $posts[] = self::unpackDbRecord($dbRecord);
            
        }
        
        return $posts;
//        $dbRecords = self::$wpQuery->get_posts();
//        
//        foreach ($dbRecords as $dbRecord) {
//            $posts[] = self::unpackDbRecord($dbRecord);
//        }
        
        
        return $posts;
        
    }
    
    /**
     * Select models using SQL query.
     * Should start with 'SELECT * FROM {$wpdb->posts}'
     * The count of found rows can be found by calling postsFound() aftermath.
     * 
     * @global type $wpdb
     * @param string $sql
     * @return array(PostModel)
     */
    public static function selectSql($sql){
        global $wpdb;
        $posts = array();
        $dbRecords = $wpdb->get_results($sql);
        foreach ($dbRecords as $dbRecord) {
            $posts[] = self::unpackDbRecord($dbRecord);
        }
        self::$postsFound = count($dbRecords)?$wpdb->get_var('SELECT FOUND_ROWS()'):0;
        
        return $posts;
    }

    /**
     * Get associated $wp_query if set
     * 
     * @return WP_Query
     */
    public static function getWpQuery(){
        return self::$wpQuery;
    }
    
    /**
     * Get number of posts found using last mass fetch from DB
     * 
     * @return integer
     */
    public static function postsFound(){
        return (int)max(self::$wpQuery->found_posts, self::$postsFound);
    }

    /**
     * Get post meta single key-value pair or all key-values
     * 
     * @param int $postId Post ID.
     * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
     * @param bool $single Whether to return a single value.
     * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
     */
    public static function getPostMeta($postId, $key = '', $single = true){
        $meta = get_post_meta($postId, $key, $single);
        if(!$key && $single && $meta && is_array($meta)){
            $m = array();
            foreach($meta as $k => $values){
                $m[$k]= is_array($values)?reset($values):$values;
            }
            return $m;
        }
        return $meta;
    }
    
    /**
     * Update post meta value for the specified key in the DB
     * 
     * @param integer $postId
     * @param string $key
     * @param string $value
     * @param string $oldValue
     * @return bool False on failure, true if success.
     */
    public static function updatePostMeta($postId, $key, $value, $oldValue = ''){
        return update_post_meta($postId, $key, $value, $oldValue);
    }
    
    /**
     * Delete post meta value
     * 
     * @param integer $postId
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function deletePostMeta($postId, $key, $value = ''){
        return delete_post_meta($postId, $key, $value);
    }
    
    /**
     * Get post meta single key-value pair or all key-values
     * 
     * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
     * @param bool $single Whether to return a single value.
     * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
     */
    public function getMeta($key = '', $single = true) {
        $k = $single?$key:$key.'_arr';
        if(!isset($this->meta[$k])){
            $this->meta[$k] = self::getPostMeta($this->getId(), $key, $single);
        }
        return $this->meta[$k];
    }

    /**
     * Update post meta value for the specified key in the DB
     * If value is empty then delete it
     * 
     * @param string $key
     * @param string $value
     * @param string $oldValue
     * @return bool False on failure, true if success.
     */
    public function updateOrDeleteMeta($key, $value='', $oldValue = '') {
        if($value){
            return $this->updateMeta($key, $value, $oldValue);
        }else{
            return $this->deleteMeta($key, $oldValue);
        }
    }
    
    /**
     * Update post meta value for the specified key in the DB
     * 
     * @param string $key
     * @param string $value
     * @param string $oldValue
     * @return bool False on failure, true if success.
     */
    public function updateMeta($key, $value, $oldValue = '') {
        if($oldValue){
            unset($this->meta[$key]);
            unset($this->meta[$key.'_arr']);
        }else{
            $this->meta[$key] = $value;
        }
        return self::updatePostMeta($this->getId(), $key, $value, $oldValue);
    }
    
    /**
     * Delete post meta value
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function deleteMeta($key, $value = ''){
        unset($this->meta[$key]);
        unset($this->meta[$key.'_arr']);
        return self::deletePostMeta($this->getId(), $key, $value);
    }

    /**
     * Get post terms. Should be set first by setTerms() or load by loadTerms() or queryTerms()
     * If taxonomy not set returns
     * 
     * @param string|array $taxonomy
     * @return array(string|TermModel)
     */
    public function getTerms($taxonomy = '') {
        return $taxonomy?Util::getItem($this->terms, $taxonomy):$this->terms;
    }

    /**
     * Set post terms
     * 
     * @param array(sring|WP_Term|TermModel) $terms
     * @param string $taxonomy taxonomy
     * @return \PostModel
     */
    public function setTerms($terms, $taxonomy = null) {
        if($taxonomy){
            $this->terms[$taxonomy] = $terms;
        }else{
            $this->terms = $terms;
        }
        return $this;
    }
    
    /**
     * Select terms for the specified postId and taxonomy
     * 
     * @param integer $postId
     * @param string|array(string) $taxonomy
     * @param array|TermQueryModel $args
     * @return type
     */
    public static function selectTerms($postId, $taxonomy = 'post_tag', $args = array()){
        if($args instanceof TermQueryModel){
            $args = $args->getVars();
        }
        $terms = wp_get_post_terms($postId, $taxonomy, $args);
        if(in_array(Util::getItem($args, 'fields', 'names'), array('all', 'all_with_object_id'))){
            $dbRecords = $terms;
            $terms = array();
            foreach($dbRecords as $dbRecord){
                $term = TermModel::unpackDbRecord($dbRecord);
                if($term){
                    $terms[]=$term;
                }
            }
        }
        return $terms;
    }
    
    /**
     * Load terms for this post and taxonomy.
     * Utilizes selectTerms
     * 
     * @param integer $postId
     * @param string|array(string) $taxonomy
     * @param array|TermQueryModel $args
     * @return type
     */
    public function loadTerms($taxonomies = '', $args = array('fields'=>'names')){
        if($taxonomies){
            if(is_string($taxonomies) && strpos($taxonomies, ',')){
                $taxonomies = preg_split('%\s*,\s*%', $taxonomies);
            }
            if(is_array($taxonomies)){
                foreach ($taxonomies as $t){
                    $this->terms[$t] = self::selectTerms($this->getId(), $t, $args);
                }
            }else{
                $this->terms[$taxonomies] = self::selectTerms($this->getId(), $taxonomies, $args);
            }
        }else{
            $taxonomies = $this->getTaxonomies();
            foreach ($taxonomies as $t){
                $this->terms[$t] = self::selectTerms($this->getId(), $t, $args);
            }
        }
        return $this->terms;
    }
    
    /**
     * Update set of post's terms in DB
     * @param array(string|int|WP_Term|TermModel) $terms if ommited $this->getTerms($taxonomy) is taken
     * @param string $taxonomy if ommited $terms should be like array('post_tag' => ... , 'category' => ... )
     * @param boolean $append append or replace
     */
    public function updateTerms($terms = null, $taxonomy = null, $append = false){
//        echo "updateTerms\n";
        if(!$terms){
//            echo "getting terms\n";
            $terms = $this->getTerms($taxonomy);
            Util::print_r($terms);
        }
        if(!$taxonomy){
//            echo "no taxonomy set\n";
            foreach ($terms as $taxonomy=>$trms){
//                echo "setting by taxonomy: $taxonomy \n";
                $this->updateTerms($trms, $taxonomy, $append);
            }
        }else{
//            echo "taxonomy set: $taxonomy \n";
            $trms = $terms;
            if(is_array($terms) && count($terms)){
//                echo "is array\n";
                if(is_object(reset($terms))) {
//                    echo "is object\n";
                    $trms = array();
                    foreach($terms as $key=>$value){
//                        Util::print_r($value);
                        $trms[$key]= is_taxonomy_hierarchical($taxonomy)?$value->term_id:$value->name;
                    }
                }
            }
//            echo "wp_set_post_terms \nid: ".$this->getId();
//            Util::print_r($trms);
            wp_set_post_terms($this->getId(), $trms, $taxonomy, $append);
        }
    }
    
    /**
     * Get PostTermQueryModel object to query post terms.
     * Call ->select() at the end to load terms into this post
     * 
     * @param string|array(string) $taxonomies
     * @return PostTermQueryModel
     */
    public function queryTerms($taxonomies = null){
        return PostTermQueryModel::query($this, $taxonomies);
    }
    
    /**
     * Get taxonomy identifiers associatied with this post type
     * @return array(string)
     */
    public function getTaxonomies(){
        $taxonomies = get_taxonomies(array(), 'objects');
        $res = array();
        foreach($taxonomies as $name => $taxonomy){
            if(in_array($this->getType(), $taxonomy->object_type)){
                $res[]=$name;
            }
        }
        
        return $res;
    }
    
    /**
     * Get post comments. Should set first by setComments() or load by loadComments() or queryComments()
     * 
     * @return array(CommentModel)
     */
    public function getComments() {
        return $this->comments;
    }

    /**
     * Set post comments
     * 
     * @param array(CommentModel) $comments
     * @return \PostModel
     */
    public function setComments($comments) {
        $this->comments = $comments;
        return $this;
    }

    /**
     * Load post comments into the post object
     * 
     * @param array $args WP_Comment_Query args
     * @return integer count of comments loaded
     */
    public function loadComments($args = array()){
        $args['post_id'] = $this->getId();
        $defaults = array(
            'order' => 'ASC',
            'orderby' => 'comment_ID'
        );
        $args = array_merge($defaults, $args);
        $this->comments = CommentModel::selectComments($args);
        return count($this->comments);
    }
    
    /**
     * Get CommentQueryModel object to query this post comments.
     * Call ->select() at the end to load comments into this model.
     * 
     * @return type
     */
    public function queryComments(){
        return CommentQueryModel::query($this)
                ->order_ASC()
                ->orderBy('comment_ID');
    }
    
    public function getAttachments($type){
        $rawAttacments = get_attached_media($type, $this->getId());
        $attachments = array();
        foreach($rawAttacments as $id => $raw){
            $attachments[$id] = PostModel::unpackDbRecord($raw);
        }
        return $attachments;
    }
    
    /**
     * Get attachment url
     * @return string
     */
    public function getAttachmentUrl(){
        return wp_get_attachment_url($this->getId());
    }
    
    /**
     * Get image data in case this post is an attachment image.
     * Should be set by setImageData or loaded by loadImageData().
     * loadImageData can be used instead
     * 
     * @return mixed
     */
    public function getImageData() {
        return $this->imageData;
    }

    /**
     * Set image data in case this post is an attachment image.
     * Use within the model only.
     * 
     * @param array $imageData
     * @return \PostModel
     */
    public function setImageData($imageData) {
        $this->imageData = $imageData;
        return $this;
    }

    /**
     * Loads image data if this post is an attachment
     * 
     * @param string $size thumbnail|medium|large|full
     * @return type
     */
    public function loadImageData($size = ''){
        if($this->getType() == 'attachment'){
            $sizes = array();
            if($size){
                $sizes[$size] = wp_get_attachment_image_src( $this->getId(), 'icon' == $size?'thumbnail':$size, 'icon' == $size );
            }else{
                if($this->isAttachmentImage()){
                    foreach(array('thumbnail', 'medium', 'large', 'full') as $size){
                        $d = wp_get_attachment_image_src( $this->getId(), $size );
                        if($d){
                            $sizes[$size] = $d;
                        }
                    }
                }else{
                    $sizes['thumbnail'] = wp_get_attachment_image_src( $this->getId(), 'thumbnail', true );
                }
//                $sizes['icon'] = wp_get_attachment_image_src( $this->getId(), "thumbnail", true);
            }
            
            foreach($sizes as $size => $data){
                $this->imageData[$size] = array(
                    'url' => $data[0],
                    'width' => $data[1],
                    'height' => $data[2],
                );
            }
        }
        
        return $size?Util::getItem($this->imageData, $size):$this->imageData;
    }
    
    /**
     * Get post thumbnail id (thumbnail is an attachment post associated with this post)
     * 
     * @return integer
     */
    public function getThumbnailId(){
        if(!$this->thumbnailId){
            $this->thumbnailId = get_post_thumbnail_id($this->getId());
        }
        
        return $this->thumbnailId?$this->thumbnailId:0;
    }
    
    /**
     * Get thumbnail image HTML code (<img src="..."/>)
     * of the specified size and with HTML attributes
     * 
     * @param string $size thumbnail|post-thumbnail|medium|large|full|<custom>
     * @param array[key]=value $attrs
     * @return string(html)
     */
    public function getThumbnailImage($size = 'post-thumbnail', $attrs = array()){
        return get_the_post_thumbnail($this->getId(), $size, $attrs);
    }
    
    /**
     * Get thumbnail image HTML code (<img src="..."/>)
     * of the specified size and with HTML attributes
     * 
     * @param array[key]=value $attrs
     * @return string(html)
     */
    public function getThumbnailImage_Medium($attrs = array()){
        return $this->getThumbnailImage('medium', $attrs);
    }

    /**
     * Get thumbnail image HTML code (<img src="..."/>)
     * of the specified size and with HTML attributes
     * 
     * @param array[key]=value $attrs
     * @return string(html)
     */
    public function getThumbnailImage_Large($attrs = array()){
        return $this->getThumbnailImage('large', $attrs);
    }

    /**
     * Get thumbnail image HTML code (<img src="..."/>)
     * of the specified size and with HTML attributes
     * 
     * @param array[key]=value $attrs
     * @return string(html)
     */
    public function getThumbnailImage_Full($attrs = array()){
        return $this->getThumbnailImage('full', $attrs);
    }

    /**
     * Get thumbnail image data (url, width, height, resized)
     * of the specified size
     * 
     * @param string $size thumbnail|post-thumbnail|medium|large|full|<custom>
     * @return array[key]=value
     */
    public function getThumbnailData($size = 'thumbnail'){
        $attId = $this->getThumbnailId();
        if(!$attId){
            return null;
        }
        $image = wp_get_attachment_image_src($attId, $size);
        list($url, $width, $height, $resized) = $image;
        return array(
            'url' => $url,
            'width' => $width,
            'height' => $height,
            'resized' => $resized,
        );
        //thumbnail, medium, large or full
    }
    
    /**
     * Get thumbnail image data (url, width, height, resized)
     * of the specified size
     * 
     * @param string $size thumbnail|post-thumbnail|medium|large|full|<custom>
     * @return array[key]=value
     */
    public function getThumbnailData_Thumbnail(){
        return $this->getThumbnailData('thumbnail');
    }

    /**
     * Get thumbnail image data (url, width, height, resized)
     * of the specified size
     * 
     * @param string $size thumbnail|post-thumbnail|medium|large|full|<custom>
     * @return array[key]=value
     */
    public function getThumbnailData_Medium(){
        return $this->getThumbnailData('medium');
    }

    /**
     * Get thumbnail image data (url, width, height, resized)
     * of the specified size
     * 
     * @param string $size thumbnail|post-thumbnail|medium|large|full|<custom>
     * @return array[key]=value
     */
    public function getThumbnailData_Large(){
        return $this->getThumbnailData('large');
    }

    /**
     * Get thumbnail image data (url, width, height, resized)
     * of the specified size
     * 
     * @param string $size thumbnail|post-thumbnail|medium|large|full|<custom>
     * @return array[key]=value
     */
    public function getThumbnailData_Full(){
        return $this->getThumbnailData('full');
    }

    /**
     * Populates this post for old school WP use.
     * Defines global variables $post, $authordata, $wp_the_query, etc.
     * 
     * @global WP_Post $post
     * @global WP_User $authordata
     * @global WP_Query $wp_the_query
     */
    public function populateWpGlobals(){
        global $post, $authordata, $wp_the_query;
//        $authordata = get_userdata( $this->getUserId() );
        $post = $this->getWpPost();
        setup_postdata($post);
        $comments = $this->getComments()?$this->getComments():array();
        foreach($comments as $comment){
            $wp_the_query->comments[] = $comment->getWpComment();
        }
        $wp_the_query->comment_count = $this->getCommentCount();
    }
    
    public static function setJsonMetaFields($metaFields) {
        self::$jsonMetaFields = $metaFields;
    }
    
    public static function addJsonMetaField($fieldName){
        if(false === array_search($fieldName, self::$jsonMetaFields)){
            self::$jsonMetaFields[]=$fieldName;
        }
    }
    
    public static function removeJsonMetaField($fieldName){
        $i = array_search($fieldName, self::$jsonMetaFields);
        if(false !== $i){
            self::$jsonMetaFields = array_splice(self::$jsonMetaFields, $i, 1);
        }
    }

    /**
     * Packs this post into assoc array for JSON representation.
     * Used for API Output
     * 
     * @return array
     */
    public function packJsonItem() {
        $jsonItem = array();
        $jsonItem['id'] = $this->getId();
        $jsonItem['post_author'] = $this->getUserId();
        $jsonItem['post_parent'] = $this->getParentId();
        $jsonItem['post_type'] = $this->getType();
        $jsonItem['post_name'] = $this->getSlug();
        $jsonItem['post_title'] = $this->getTitle();
        $jsonItem['post_content'] = $this->getContent();
        $jsonItem['post_excerpt'] = $this->getExcerpt();
        $jsonItem['post_status'] = $this->getStatus();
        $jsonItem['post_date'] = DateHelper::datetimeToDbStr($this->getDtCreated());
        $jsonItem['post_date_gmt'] = DateHelper::datetimeToDbStr($this->getDtCreatedGMT());
        $jsonItem['ping_status'] = $this->getPingStatus();
        $jsonItem['to_ping'] = $this->getToPing();
        $jsonItem['pinged'] = $this->getPinged();
        $jsonItem['menu_order'] = $this->getMenuOrder();
        $jsonItem['comment_status'] = $this->getCommentStatus();
        $jsonItem['comment_count'] = $this->getCommentCount();
        $jsonItem['reviews_count'] = $this->getReviewsCount();
        $jsonItem['post_mime_type'] = $this->getMimeType();
        $jsonItem['terms'] = $this->getTerms();
        $jsonItem['href'] = $this->getHref();
        if('attachment' == $this->getType()){
            if(empty($this->imageData)){
                $this->loadImageData();
            }
            $jsonItem['image'] = $this->imageData;
        }
        $thumbId = $this->getThumbnailId();
        $jsonItem['thumbnail_id'] = $thumbId;
        if($thumbId){
            $thumb = array(
                'thumbnail' => $this->getThumbnailData_Thumbnail(),
                'medium' => $this->getThumbnailData_Medium(),
                'large' => $this->getThumbnailData_Large(),
                'full' => $this->getThumbnailData_Full(),
            );
            $jsonItem['thumbnail'] = $thumb;
        }
        $meta = array();
        foreach(self::$jsonMetaFields as $field){
            $meta[$field] = $this->getMeta($field);
        }
        if($meta){
            $jsonItem['meta'] = $meta;
        }
        
        return $jsonItem;
    }

    /**
     * Packs into intermediate structure for wpp-BRX-SearchEngine 
     * Indexed fields, their content and weights are set here
     * For custom post type this data could be adjusted on special hook
     * 
     * @return array
     */
    public function packLuceneDoc() {
        $item[LuceneHelper::getIdField()] = array('keyword', 'pk_'.$this->getId());
        $item['post_type'] = array('keyword', $this->getType());
        $item['title'] = array('unstored', $this->getTitle(), 2);
        $item['content'] = array('unstored', wp_strip_all_tags($this->getContent()), 0.5);
        $item['user_id'] = array('keyword', 'user_'.$this->getUserId());
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $taxonomy){
            $this->loadTerms($taxonomy);
        }
        $t = $this->getTerms();
        foreach($t as $taxonomy=>$terms){
            if(count($terms)){
                $item[$taxonomy] = array('unstored', join(', ', $terms));
            }
        }
        return $item;
    }

    /**
     * Get validation errors after unpacking from request input
     * Should be set by validateInput
     * 
     * @return array[field]='Error Text'
     */
    public function getValidationErrors() {
        return array();
    }

    /**
     * Unpacks request input.
     * Used by REST Controllers.
     * 
     * @param array $input
     */
    public function unpackInput($input = array()) {
        if(empty($input)){
            $input = InputHelper::getParams();
        }
        $input = array_merge($this->packJsonItem(), $input);

        $this->setId(Util::getItem($input, 'id', 0));
        $this->setUserId(Util::getItem($input, 'post_author'));
        $this->setParentId(Util::getItem($input, 'post_parent'));
        $this->setGuid(Util::getItem($input, 'guid'));
        $this->setType(Util::getItem($input, 'post_type'));
        $this->setSlug(Util::getItem($input, 'post_name'));
        $this->setTitle(Util::getItem($input, 'post_title'));
        $this->setContent(Util::getItem($input, 'post_content'));
//        $this->setContentFiltered(Util::getItem($input, 'post_content_filtered'));
        $this->setExcerpt(Util::getItem($input, 'post_excerpt'));
        $this->setStatus(Util::getItem($input, 'post_status'));        
        $this->setPingStatus(Util::getItem($input, 'ping_status'));
        $this->setPinged(Util::getItem($input, 'pinged'));
        $this->setToPing(Util::getItem($input, 'to_ping'));
        $this->setPassword(Util::getItem($input, 'post_password'));
//        $this->setDtCreated(DateHelper::jsonStrToDatetime(Util::getItem($input, 'post_date')));
//        $this->setDtCreatedGMT(DateHelper::jsonStrToDatetime(Util::getItem($input, 'post_date_gmt')));
        $this->setDtModified(DateHelper::jsonStrToDatetime(Util::getItem($input, 'post_modified')));
//        $this->setDtModifiedGMT(DateHelper::jsonStrToDatetime(Util::getItem($input, 'post_modified_gmt')));
        $this->setMenuOrder(Util::getItem($input, 'menu_order'));
//        $this->setMimeType(Util::getItem($input, 'post_mime_type'));
        $this->setCommentStatus(Util::getItem($input, 'comment_status'));
//        $this->setCommentCount(Util::getItem($input, 'comment_count'));
        return $this;
    }

    /**
     * Validates input and sets $validationErrors
     * 
     * @param array $input
     * @param string $action (create|update)
     * @return boolean is input valid
     */
    public function validateInput($input = array(), $action = 'create') {
        $valid = true; //apply_filters('PostModel.validateInput', true, $input, $action);
        return $valid;
    }

    /**
     * Flushes cache used for selectById() and selectBySlug
     */
    public static function flushCache(){
        self::$postsCacheById = array();
        self::$postsCacheBySlug = array();
    }
    
    /**
     * Get post by $id from cache.
     * It gets to cache once it was unpacked by unpackDbRecord()
     * 
     * @param type $id
     * @return type
     */
    public static function getPostsCacheById($id = 0){
        if($id){
            return Util::getItem(self::$postsCacheById, $id);
        }
        return self::$postsCacheById;
    }

    /**
     * Get post by $slug from cache.
     * It gets to cache once it was unpacked by unpackDbRecord()
     * 
     * @param type $id
     * @return type
     */
    public static function getPostsCacheBySlug($slug = ''){
        if($slug){
            $id = Util::getItem(self::$postsCacheBySlug, $slug);
            return $id?self::getPostsCacheById($id):null;
        }
        
        $ret = array();
        
        foreach (self::$postsCacheBySlug as $slug => $id){
            $item = $id?self::getPostsCacheById($id):null;
            if($item){
                $ret[$slug]=$id;
            }
        }
        
        return $ret;
    }
 }
