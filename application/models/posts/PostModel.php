<?php

require_once 'application/helpers/WpDbHelper.php';
require_once 'application/helpers/JsonHelper.php';
require_once 'application/models/posts/CommentModel.php';
require_once 'application/models/posts/PostQueryModel.php';
require_once 'application/models/taxonomies/TermQueryModel.php';
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
    protected $thumbnailId;

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
        if(!$this->excerpt && $this->content){
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
            update_post_meta($this->getId(), 'reviews_count', $this->reviewsCount);
            $_SESSION['visited'][$today][$this->getId()] = true;
        }
        
        return $this->reviewsCount;
    }
    
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
    
//    public function setDtModifiedGMT($dtModifiedGMT) {
//        $this->dtModifiedGMT = $dtModifiedGMT;
//    }

    public function getThumbnailId(){
        if(!$this->thumbnailId){
            $this->thumbnailId = get_post_thumbnail_id($this->getId());
        }
        
        return $this->thumbnailId?$this->thumbnailId:0;
    }
    
    public function getThumbnailImage($size = 'post-thumbnail', $attrs = array()){
        return get_the_post_thumbnail($this->getId(), $size, $attr);
    }
    
    public function getThumbnailImageMedium($attrs = array()){
        return $this->getThumbnailImage('medium', $attrs);
    }

    public function getThumbnailImageLarge($attrs = array()){
        return $this->getThumbnailImage('large', $attrs);
    }

    public function getThumbnailImageFull($attrs = array()){
        return $this->getThumbnailImage('full', $attrs);
    }

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
    
    public function getThumbnailDataThumbnail(){
        return $this->getThumbnailData('thumbnail');
    }

    public function getThumbnailDataMedium(){
        return $this->getThumbnailData('medium');
    }

    public function getThumbnailDataLarge(){
        return $this->getThumbnailData('large');
    }

    public function getThumbnailDataFull(){
        return $this->getThumbnailData('full');
    }

    public function getWpPost() {
        return $this->wpPost;
    }

    public function setWpPost($wpPost) {
        $this->wpPost = $wpPost;
    }
    
    public function getHref(){
        return get_permalink($this->getId());
    }

    public function getHrefNext($in_same_cat = true){
//        $this->populateWpGlobals();
        $post = get_next_post($in_same_cat);
        return $post && $post->ID ? get_permalink($post->ID):null;
    }
    
    public function getHrefPrev($in_same_cat = true){
//        $this->populateWpGlobals();
        $post = get_previous_post($in_same_cat);
        return $post && $post->ID ? get_permalink($post->ID):null;
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

//    public function unpackMeta($meta){
//        $this->meta = $meta;
////        print_r($meta);
////        $this->setNickname($meta['nickname'][0]);
////        $this->setFirstName($meta['first_name'][0]);
////        $this->setLastName($meta['last_name'][0]);
////        $this->setDescription($meta['description'][0]);
////        $this->setRichEditing($meta['rich_editing'][0]);        
//    }
    

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

    public static function query(){
        $query = new PostQueryModel();
        return $query;
    }

    public static function selectPosts($wpPostsQueryArgs){
        
//        Util::print_r($wpPostsQueryArgs);
//        die('(@)');
        $posts = array();
        self::$wpQuery = new WP_Query($wpPostsQueryArgs);
//        $dbRecords = get_posts($wpPostsQueryArgs);
        $dbRecords = self::$wpQuery->get_posts($wpPostsQueryArgs);
        foreach ($dbRecords as $dbRecord) {
            $posts[] = self::unpackDbRecord($dbRecord);
        }
//        Util::print_r($posts);
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

    /**
     * 
     * @param int $post_id Post ID.
     * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
     * @param bool $single Whether to return a single value.
     * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
     */
    public static function getPostMeta($post_id, $key = '', $single = true){
        $meta = get_post_meta($post_id, $key, $single);
        if(!$key && $single){
            $m = array();
            foreach($meta as $k => $values){
                $m[$k]= is_array($values)?reset($values):$values;
            }
            
            return $meta;
        }
        return $meta;
    }
    
    public static function updatePostMeta($postId, $key, $value, $oldValue = ''){
        return update_post_meta($postId, $key, $value, $oldValue);
    }
    
    public function getMeta($key = '', $single = true) {
        return  self::getPostMeta($this->getId(), $key, $single);
    }

    public function updateMeta($key, $value, $oldValue = '') {
        self::updatePostMeta($this->getId(), $key, $value, $oldValue);
    }

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
    
    public function queryTerms($taxonomies = null){
        return PostTermQueryModel::query($this, $taxonomies);
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
    
    public function loadComments($args = array()){
        $args['post_id'] = $this->getId();
        $defaults = array(
            'order' => 'ASC',
            'orderby' => 'comment_ID'
        );
        $args = array_merge($defaults, $args);
        $this->comments = CommentModel::selectComments($args);
    }
    
    public function isAttachmentImage(){
        return preg_match('%^image%', $this->getMimeType());
    }
    
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
                'thumbnail' => $this->getThumbnailDataThumbnail(),
                'medium' => $this->getThumbnailDataMedium(),
                'large' => $this->getThumbnailDataLarge(),
                'full' => $this->getThumbnailDataFull(),
            );
            $jsonItem['thumbnail'] = $thumb;
        }
        if($this->getMeta()){
            $jsonItem['meta'] = $this->getMeta();
        }
        
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
