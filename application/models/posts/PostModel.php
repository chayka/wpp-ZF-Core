<?php

require_once 'application/helpers/WpDbHelper.php';
require_once 'application/helpers/JsonHelper.php';
require_once 'application/models/posts/CommentModel.php';
//require_once 'application/helpers/LuceneHelper.php';

class PostModel implements DbRecordInterface, JsonReadyInterface, InputReadyInterface /*, LuceneReadyInterface*/{

    /**
     * Post Id
     *
     * @var integer
     */
    
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

    protected $dtCreated;
    protected $dtCreatedGMT;
    protected $dtModified;
    protected $dtModifiedGMT;
    
    protected $wpPost;
    
    protected static $postsCacheById = array();
    protected static $postsCacheBySlug = array();

    /**
     * PostModel constructor
     *
     * @param integer $id
     */
    public function __construct() {
        $this->init();
    }

    public function init(){
//        echo "UserModel::init";
        $this->setId(0);
        $this->setDtCreated(new Zend_Date());
//        $this->setDtCreatedGMT(new Zend_Date());
        $this->setStatus('draft');
        $this->setCommentStatus('open');
        $this->setPingStatus('closed');
        
    }
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }

    public function getParentId() {
        return $this->parentId;
    }

    public function setParentId($parentId) {
        $this->parentId = $parentId;
    }

    public function getGuid() {
        return $this->guid;
    }

    public function setGuid($guid) {
        $this->guid = $guid;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getSlug() {
        return $this->slug;
    }

    public function setSlug($slug) {
        $this->slug = $slug;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getContent($wpautop = true) {
        return $wpautop?wpautop($this->content):$this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function getContentFiltered() {
        return $this->contentFiltered;
    }

    public function setContentFiltered($contentFiltered) {
        $this->contentFiltered = $contentFiltered;
    }

    public function getExcerpt() {
        return $this->excerpt;
    }

    public function setExcerpt($excerpt) {
        $this->excerpt = $excerpt;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getPingStatus() {
        return $this->pingStatus;
    }

    public function setPingStatus($pingStatus) {
        $this->pingStatus = $pingStatus;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getToPing() {
        return $this->toPing;
    }

    public function setToPing($toPing) {
        $this->toPing = $toPing;
    }

    public function getPinged() {
        return $this->pinged;
    }

    public function setPinged($pinged) {
        $this->pinged = $pinged;
    }

    public function getMenuOrder() {
        return $this->menuOrder;
    }

    public function setMenuOrder($menuOrder) {
        $this->menuOrder = $menuOrder;
    }

    public function getMimeType() {
        return $this->mimeType;
    }

    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;
    }

    public function getCommentStatus() {
        return $this->commentStatus;
    }

    public function setCommentStatus($commentStatus) {
        $this->commentStatus = $commentStatus;
    }

    public function getCommentCount() {
        return $this->commentCount;
    }

    public function setCommentCount($commentCount) {
        $this->commentCount = $commentCount;
    }
 
    public function getComments() {
        return $this->comments;
    }

    public function setComments($comments) {
        $this->comments = $comments;
    }

    public function getTerms($taxonomy = '') {
        return $taxonomy?Util::getItem($this->terms, $taxonomy):$this->terms;
    }

    public function setTerms($terms) {
        $this->terms = $terms;
    }
    
    public function getMeta($key = '') {
        return $key?Util::getItem($this->meta, $key):$this->meta;
    }

    public function setMeta($meta) {
        $this->meta = $meta;
    }

    public function getImageData() {
        return $this->imageData;
    }

    public function setImageData($imageData) {
        $this->imageData = $imageData;
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

    public function getDtModified() {
        return $this->dtModified;
    }

    public function setDtModified($dtModified) {
      $this->dtModified = $dtModified;
    }

    public function getDtModifiedGMT() {
        return new Zend_Date(get_gmt_from_date(DateHelper::datetimeToDbStr($this->getDtModified())));
//        return $this->dtModifiedGMT;
    }
    
    public function getReviewsCount() {
        if(!$this->reviewsCount){
            $this->reviewsCount = get_post_meta($this->getId(), 'reviews_count', true);
        }
        return $this->reviewsCount?$this->reviewsCount:0;
    }
    
    public function setReviewsCount($value) {
        $this->reviewsCount = $value;
    }
    
    public function incReviewsCount(){
        if(!$this->getId()){
            return 0;
        }
        $visited = Util::getItem($_SESSION, 'visited', array());
        $today = date('Y-m-d');
        foreach ($visited as $date => $posts) {
            if($date != $today){
                unset($_SESSION['visited'][$date]);
            }
        }
        if(!$_SESSION['visited'][$today]){
            $_SESSION['visited'][$today] = array();
        }
        
        $visit = Util::getItem($_SESSION['visited'][$today], $this->getId(), false);
        
        if(!$visit){
            $this->getReviewsCount();
            $this->reviewsCount++;
            update_post_meta($this->getId(), 'reviews_count', $this->reviewsCount);
            $_SESSION['visited'][$today][$this->getId()] = true;
        }
        
        return $this->reviewsCount;
    }
    
//    public function setDtModifiedGMT($dtModifiedGMT) {
//        $this->dtModifiedGMT = $dtModifiedGMT;
//    }

    public function getWpPost() {
        return $this->wpPost;
    }

    public function setWpPost($wpPost) {
        $this->wpPost = $wpPost;
    }
    
    public static function getDbIdColumn() {
        return 'ID';
    }

    public static function getDbTable() {
        global $wpdb;
        return $wpdb->posts;
    }

    public static function unpackDbRecord( $wpRecord){
//        Util::print_r($wpRecord);
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
//        $obj->setDtCreatedGMT(DateHelper::dbStrToDatetime($wpRecord->post_date_gmt));
        $obj->setDtModified(DateHelper::dbStrToDatetime($wpRecord->post_modified));
//        $obj->setDtModifiedGMT(DateHelper::dbStrToDatetime($wpRecord->post_modified_gmt));
        $obj->setMenuOrder($wpRecord->menu_order);
        $obj->setMimeType($wpRecord->post_mime_type);
        $obj->setCommentStatus($wpRecord->comment_status);
        $obj->setCommentCount($wpRecord->comment_count);
        
        $obj->setWpPost($wpRecord);
        
        self::$postsCacheById[$obj->getId()] = $obj;
        self::$postsCacheBySlug[$obj->getSlug()] = $obj->getId();
        
        return $obj;
    }

    public function unpackMeta($meta){
        $this->meta = $meta;
//        print_r($meta);
//        $this->setNickname($meta['nickname'][0]);
//        $this->setFirstName($meta['first_name'][0]);
//        $this->setLastName($meta['last_name'][0]);
//        $this->setDescription($meta['description'][0]);
//        $this->setRichEditing($meta['rich_editing'][0]);        
    }
    

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
        $dbRecord['post_content'] = $this->getContent();
        $dbRecord['post_excerpt'] = $this->getExcerpt();
//        $dbRecord['description'] = $this->getDescription();
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

    public function insert(){
        $this->setDtCreated(new Zend_Date());
//        $this->setDtCreatedGMT(new Zend_Date());
        $dbRecord = $this->packDbRecord(false);
        $id = wp_insert_post($dbRecord);
        $this->setId($id);
        return $id;
    }
    
    public function update(){
        $this->setDtModified(new Zend_Date());
        $dbRecord = $this->packDbRecord(true);
        unset($dbRecord['post_created']);
        unset($dbRecord['post_created_gmt']);
        return wp_update_post($dbRecord);
    }
    
    public function delete($forceDelete = 0){
        return self::deleteById($this->getId(), $forceDelete);
    }
    
    /**
     * Deletes user with the specified $userId from db table
     *
     * @param integer $postId
     * @return boolean
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
     *
     * @param integer $id
     * @return PostModel 
     */
    public static function selectById($id, $useCache = true){
        if($useCache){
            $item = Util::getItem(self::$postsCacheById, $id);
            if($item){
                return $item;
            }
        }
        $wpRecord = get_post($id);
        return $wpRecord?self::unpackDbRecord($wpRecord):null;
    }


    public static function selectBySlug($slug, $postType = '', $useCache = true){
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
        print_r($args);
        $posts = self::selectPosts($args);
        return count($posts)?reset($posts):null;
    }

    public static function selectPosts($wpPostsQueryArgs){
        $posts = array();
        self::$wpQuery = new WP_Query($wpPostsQueryArgs);
//        $dbRecords = get_posts($wpPostsQueryArgs);
        $dbRecords = self::$wpQuery->get_posts($wpPostsQueryArgs);
        foreach ($dbRecords as $dbRecord) {
            $posts[] = self::unpackDbRecord($dbRecord);
        }
        self::$postsFound=self::$wpQuery->found_posts;
        return $posts;
    }

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

    public static function getWpQuery(){
        return self::$wpQuery;
    }
    
    public static function postsFound(){
//        $wpFound = empty(self::$wpQuery->found_posts)? 0:self::$wpQuery->found_posts;
//        $sqlFound = empty(self::$postsFound)? 0:self::$postsFound;
        return (int)max(self::$wpQuery->found_posts, self::$postsFound);
    }

    public static function selectMeta($post_id, $key, $single){
        return get_post_meta($post_id, $key, $single);
    }
    
    public function loadMeta($key = null, $single = false){
        $meta = self::selectMeta($this->getId(), $key, $single);
        $this->unpackMeta($meta, $key);
    }
    
    public static function selectTerms($postId, $taxonomy = 'post_tag', $args = array()){
        return wp_get_post_terms($postId, $taxonomy, $args);
    }
    
    public function loadTerms($taxonomy = '', $args = array('fields'=>'names')){
        if($taxonomy){
            $this->terms[$taxonomy] = self::selectTerms($this->getId(), $taxonomy, $args);
        }else{
            $taxonomies = $this->getTaxonomies();
            foreach ($taxonomies as $t){
                $this->terms[$t] = self::selectTerms($this->getId(), $t, $args);
            }
        }
        return $this->terms;
    }
    
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
    
    public function getTaxQuery($count = 3, $relation = 'OR'){
        $terms = wp_get_object_terms($this->getId(), array(
            'profession', 'equipment', 'work'
        ), array('orderby'=>'count', 'order' => 'DESC'));
        $taxed = array();
        foreach($terms as $i=>$term){
            if($i>=$count){
                break;
            }
            $taxed[$term->taxonomy][]=$term;
        }
        $tax_query = array('relation' => $relation);
        foreach ($taxed as $taxonomy => $ts) {
            $tax = array(
                'taxonomy' => $taxonomy,
                'field' => 'id',
                'terms' => array()                
            );
            foreach ($ts as $term) {
                $tax['terms'][]=$term->term_id; 
            }
            $tax_query[]=$tax;
        }
        
        return $tax_query;
    }
    
    public function loadComments($args = array()){
        $args['post_id'] = $this->getId();
        $defaults = array(
            'order' => 'ASC',
            'orderby' => 'comment_ID'
        );
        $args = array_merge($defaults, $args);
        $this->comments = CommentModel::selectComments($args);
    }
    
    public function loadImageData($size = ''){
        if($this->getType() == 'attachment'){
            $sizes = array();
            if($size){
                $sizes[$size] = wp_get_attachment_image_src( $this->getId(), 'icon' == $size?'thumbnail':$size, 'icon' == $size );
            }else{
                foreach(array('thumbnail', 'medium', 'large', 'full') as $size){
                    $sizes[$size] = wp_get_attachment_image_src( $this->getId(), $size );
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
    }
    
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
//        if($this->getMeta()){
//            $jsonItem['meta'] = $this->getMeta();
//        }
        
        return $jsonItem;
    }

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

    public function getValidationErrors() {
        return array();
    }

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
        
    }

    public function validateInput($input = array(), $action = 'create') {
        return true;
    }

    public static function flushCache(){
        self::$postsCacheById = array();
        self::$postsCacheBySlug = array();
    }
    
    public static function getPostsCacheById($id = 0){
        if($id){
            return Util::getItem(self::$postsCacheById, $id);
        }
        return self::$postsCacheById;
    }

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
