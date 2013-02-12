<?php

/*
  Plugin Name: AnotherGuru
  Plugin URI: http://anotherguru.me/
  Description: AnotherGuru Q&A, job mart functionality.
  Version: 0.1
  Author: Boris Mossounov
  Author URI: http://facebook.com/mossounov
  License: GPL2
 */
define( 'WPP_ANOTHERGURU_PATH', plugin_dir_path(__FILE__) );
define( 'WPP_ANOTHERGURU_URL', preg_replace('%^[\w\d]+\:\/\/[\w\d\.]+%', '',plugin_dir_url(__FILE__)) );
//define( 'WPP_ANOTHERGURU_URL', plugin_dir_url(__FILE__) );
define( 'WPP_AG_TEMPLATEPATH', WPP_ANOTHERGURU_PATH.'zf-app/application/views/wordpress' );

set_include_path(implode(PATH_SEPARATOR, array_unique(array(
    realpath(WPP_ANOTHERGURU_PATH.'zf-app/library'),
    get_include_path(),
))));

global $ag_db_version;
$ag_db_version = "1.0";

require_once 'zf.php';
require_once 'zf-app/application/helpers/LuceneHelper.php';
require_once 'zf-app/library/phpmorphy-0.3.7/src/common.php';

class AnotherGuru {
    const NLS_DOMAIN = "AnotherGuru";


    public static function dbInstall() {
        global $wpdb;
        global $ag_db_version;
        $installed_ver = get_option("ag_db_version");
        
//        $versionHistory = array('1.0', '1.1');
        $versionHistory = array('1.0');
        
        $queries = array();
        if(!$installed_ver){
            $cnt = file_get_contents(WPP_ANOTHERGURU_PATH.'sql/install.'.$ag_db_version.'.sql');
            $tmp = preg_split("%;\s*%m", $cnt);
            foreach($tmp as $query){
                $queries[] = str_replace('{prefix}', $wpdb->prefix, $query);
            }
        }elseif ($installed_ver != $ag_db_version){
            $found = false;
            foreach ($versionHistory as $ver){
                if($found){
                    $cnt = file_get_contents(WPP_ANOTHERGURU_PATH.'sql/update.'.$ver.'.sql');
                    $tmp = preg_split("%;\s*%m", $cnt);
                    foreach($tmp as $query){
                        $queries[] = str_replace('{prefix}', $wpdb->prefix, $query);
                    }
                }
                if(!$found && $ver==$installed_ver){
                    $found = true;
                }
            }
        }
        
        foreach($queries as $query){
            $wpdb->query($query);
        }
        
        add_option("ag_db_version", $ag_db_version);
    }

    public static function dbUpdate() {
        global $ag_db_version;
        if (get_site_option('ag_db_version') != $ag_db_version) {
            self::dbInstall();
        }
    }
    
    public static function dbTable($table){
        global $wpdb;
        return $wpdb->prefix.$table;
    }

    public static function isAdmin(){
        $userId = get_current_user_id();
        $user = get_user_by('id', $userId);
        $isAdmin = in_array('administrator', $user->roles);
        return $isAdmin;
    }
    
    public static function installPlugin() {
        self::registerCustomPostTypes();
    }

    public static function registerCustomPostTypes() {
        self::registerCustomPostTypeQuestion();
        self::registerCustomPostTypeAnswer();
//        self::registerCustomPostTypeContract();
//        self::registerCustomPostTypeBid();
        self::registerCustomPostTypeUserProfile();
        self::registerCustomPostTypePhotoAlbum();
        self::registerTaxonomies();
    }

    public static function registerCustomPostTypeQuestion() {
        $labels = array(
            'name' => _x('Questions', 'post type general name'),
            'singular_name' => _x('Question', 'post type singular name'),
            'add_new' => _x('Add New', 'question'),
            'add_new_item' => __('Add New Question'),
            'edit_item' => __('Edit Question'),
            'new_item' => __('New Question'),
            'all_items' => __('All Questions'),
            'view_item' => __('View Question'),
            'search_items' => __('Search Questions'),
            'not_found' => __('No Questions found'),
            'not_found_in_trash' => __('No questions found in Trash'),
            'parent_item_colon' => '',
            'menu_name' => __('Questions')
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'questions'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'taxonomies' => array('category', 'post_tag'),
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments',)
        );
        register_post_type('question', $args);
    }

    public static function manageQuestionColumns($cols) {
        $cols['votes'] = __('Голосов');
        $cols['answers'] = __('Ответов');
//        $cols['slug'] = __('Slug');
        return $cols;
    }

    public static function questionSortableColumns() {
        return array(
            'slug' => 'slug',
            'votes' => 'votes',
//            'url' => 'url',
//            'referrer' => 'referrer',
//            'host' => 'host'
        );
    }

    public static function registerCustomPostTypeAnswer() {
        $labels = array(
            'name' => _x('Answers', 'post type general name'),
            'singular_name' => _x('Answer', 'post type singular name'),
            'add_new' => _x('Add New', 'question'),
            'add_new_item' => __('Add New Answer'),
            'edit_item' => __('Edit Answer'),
            'new_item' => __('New Answer'),
            'all_items' => __('All Answers'),
            'view_item' => __('View Answer'),
            'search_items' => __('Search Answers'),
            'not_found' => __('No Answers found'),
            'not_found_in_trash' => __('No answers found in Trash'),
            'parent_item_colon' => '',
            'menu_name' => __('Answers')
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'exclude_from_search' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'answers'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
        );
        register_post_type('answer', $args);
    }

//    public static function registerCustomPostTypeContract() {
//        $labels = array(
//            'name' => _x('Contracts', 'post type general name'),
//            'singular_name' => _x('Contract', 'post type singular name'),
//            'add_new' => _x('Add New', 'question'),
//            'add_new_item' => __('Add New Contract'),
//            'edit_item' => __('Edit Contract'),
//            'new_item' => __('New Contract'),
//            'all_items' => __('All Contracts'),
//            'view_item' => __('View Contract'),
//            'search_items' => __('Search Contracts'),
//            'not_found' => __('No Contracts found'),
//            'not_found_in_trash' => __('No contracts found in Trash'),
//            'parent_item_colon' => '',
//            'menu_name' => __('Contracts')
//        );
//        $args = array(
//            'labels' => $labels,
//            'public' => true,
//            'publicly_queryable' => true,
//            'show_ui' => true,
//            'show_in_menu' => true,
//            'query_var' => true,
//            'rewrite' => array('slug' => 'contracts'),
//            'capability_type' => 'post',
//            'has_archive' => true,
//            'hierarchical' => false,
//            'menu_position' => 20,
//            'rewrite' => array('slug' => 'contracts'),
//            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
//        );
//        register_post_type('contract', $args);
//    }
//
//    public static function registerCustomPostTypeBid() {
//        $labels = array(
//            'name' => _x('Bids', 'post type general name'),
//            'singular_name' => _x('Bid', 'post type singular name'),
//            'add_new' => _x('Add New', 'question'),
//            'add_new_item' => __('Add New Bid'),
//            'edit_item' => __('Edit Bid'),
//            'new_item' => __('New Bid'),
//            'all_items' => __('All Bids'),
//            'view_item' => __('View Bid'),
//            'search_items' => __('Search Bids'),
//            'not_found' => __('No Bids found'),
//            'not_found_in_trash' => __('No bids found in Trash'),
//            'parent_item_colon' => '',
//            'menu_name' => __('Bids')
//        );
//        $args = array(
//            'labels' => $labels,
//            'public' => true,
//            'publicly_queryable' => true,
//            'show_ui' => true,
//            'show_in_menu' => true,
//            'query_var' => true,
//            'rewrite' => array('slug' => 'bids'),
//            'capability_type' => 'post',
//            'has_archive' => true,
//            'hierarchical' => false,
//            'menu_position' => 20,
//            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
//        );
//        register_post_type('bid', $args);
//    }

    public static function registerCustomPostTypeUserProfile() {
        $labels = array(
            'name' => _x('Профили пользователей', 'post type general name'),
            'singular_name' => _x('Профиль', 'post type singular name'),
            'add_new' => _x('Добавить новый', 'question'),
            'add_new_item' => __('Добавить профиль'),
            'edit_item' => __('Редактировать'),
            'new_item' => __('Новый профиль'),
            'all_items' => __('Все профили'),
            'view_item' => __('Просмотреть профиль'),
            'search_items' => __('Искать профиль'),
            'not_found' => __('Профили не найдены'),
            'not_found_in_trash' => __('В карзине профелей не найдено'),
            'parent_item_colon' => '',
            'menu_name' => __('Профили пользователей')
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'profiles'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'comments')
        );
        register_post_type('profile', $args);
    }

    public static function registerCustomPostTypePhotoAlbum() {
        $labels = array(
            'name' => _x('Фотоальбомы пользователей', 'post type general name'),
            'singular_name' => _x('Альбом', 'post type singular name'),
            'add_new' => _x('Добавить альбом', 'album'),
            'add_new_item' => __('Добавить альбом'),
            'edit_item' => __('Редактировать'),
            'new_item' => __('Новый альбом'),
            'all_items' => __('Все альбомы'),
            'view_item' => __('Просмотреть альбом'),
            'search_items' => __('Искать альбом'),
            'not_found' => __('Альбомы не найдены'),
            'not_found_in_trash' => __('В карзине альбомов не найдено'),
            'parent_item_colon' => '',
            'menu_name' => __('Альбомы')
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'albums'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => true,
            'menu_position' => 20,
            'supports' => array('title', 'author', 'comments')
        );
        register_post_type('album', $args);
    }

    public static function managePostCustomColumn($column, $post_id = 0) {
        global $post, $wpdb;
        print_r($pdData);
        if(!$post_id){
            $post_id = $post->ID;
        }
        $tablePostData = AnotherGuru::dbTable('ag_postdata');
        $pdData = $wpdb->get_row('SELECT * FROM '.$tablePostData.' WHERE post_id = '.$post_id);
        switch ($column) {
            case "votes":
                echo $pdData->votes_count;
                break;
            case "answers":
                echo $pdData->answers_count;
                break;
            default:
                echo $column;
        }
    }

    public static function registerTaxonomyProfession(){
        $labels = array(
            'name' => _x('Профессии', 'taxonomy general name'),
            'singular_name' => _x('Профессия', 'taxonomy singular name'),
            'search_items' => __('Найти профессию'),
            'all_items' => __('Все профессии'),
            'parent_item' => __('Родительская профессия'),
            'parent_item_colon' => __('Родительская профессия:'),
            'edit_item' => __('Редактировать профессию'),
            'update_item' => __('Обновить профессию'),
            'add_new_item' => __('Добавить новую профессию'),
            'new_item_name' => __('Название профессии'),
            'menu_name' => __('Профессии'),
        );

        register_taxonomy('profession', array('post', 'question', 'contract', 'profile'), array(
//            'hierarchical' => true,
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'profession'),
        ));
        
    }
    
    public static function registerTaxonomyWork(){
        $labels = array(
            'name' => _x('Виды работ', 'taxonomy general name'),
            'singular_name' => _x('Вид работ', 'taxonomy singular name'),
            'search_items' => __('Найти вид работ'),
            'all_items' => __('Все виды работ'),
            'parent_item' => __('Родительский вид работ'),
            'parent_item_colon' => __('Родительский вид работ:'),
            'edit_item' => __('Редактировать вид работ'),
            'update_item' => __('Обновить вид работ'),
            'add_new_item' => __('Добавить новый вид работ'),
            'new_item_name' => __('Название вида работ'),
            'menu_name' => __('Виды работ'),
        );

        register_taxonomy('work', array('post', 'question', 'contract', 'profile'), array(
//            'hierarchical' => true,
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'work'),
        ));
        
    }

    public static function registerTaxonomyEquipment(){
        $labels = array(
            'name' => _x('Инструменты / оборудование', 'taxonomy general name'),
            'singular_name' => _x('Инструмент / оборудование', 'taxonomy singular name'),
            'search_items' => __('Найти инструменты / оборудование'),
            'all_items' => __('Все инструменты и оборудование'),
            'parent_item' => __('Родитель'),
            'parent_item_colon' => __('Родитель:'),
            'edit_item' => __('Редактировать'),
            'update_item' => __('Обновить инструменты и оборудование'),
            'add_new_item' => __('Добавить инструменты и оборудование'),
            'new_item_name' => __('Название'),
            'menu_name' => __('Инструменты и оборудование'),
        );

        register_taxonomy('equipment', array('post', 'question', 'contract', 'profile'), array(
//            'hierarchical' => true,
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'equipment'),
        ));
    }
        
    public static function registerTaxonomyMaterial(){
        $labels = array(
            'name' => _x('Материалы', 'taxonomy general name'),
            'singular_name' => _x('материал', 'taxonomy singular name'),
            'search_items' => __('Найти материалы / оборудование'),
            'all_items' => __('Все материалы'),
            'parent_item' => __('Родитель'),
            'parent_item_colon' => __('Родитель:'),
            'edit_item' => __('Редактировать'),
            'update_item' => __('Обновить материал'),
            'add_new_item' => __('Добавить материал'),
            'new_item_name' => __('Название'),
            'menu_name' => __('Материалы'),
        );

        register_taxonomy('material', array('post', 'question', 'contract'), array(
//            'hierarchical' => true,
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'material'),
        ));
        
    }

    public static function registerTaxonomyLocation(){
        $labels = array(
            'name' => _x('Расположение', 'taxonomy general name'),
            'singular_name' => _x('расположение', 'taxonomy singular name'),
            'search_items' => __('Найти расположение'),
            'all_items' => __('Все расположения'),
            'parent_item' => __('Родитель'),
            'parent_item_colon' => __('Родитель:'),
            'edit_item' => __('Редактировать'),
            'update_item' => __('Обновить'),
            'add_new_item' => __('Добавить'),
            'new_item_name' => __('Название'),
            'menu_name' => __('Расположения'),
        );

        register_taxonomy('location', array('post', 'profile', 'contract'), array(
//            'hierarchical' => true,
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'location'),
        ));
    }

    public static function registerTaxonomies(){
  // Add new taxonomy, make it hierarchical (like categories)
        self::registerTaxonomyProfession();
        self::registerTaxonomyWork();
        self::registerTaxonomyEquipment();
        self::registerTaxonomyMaterial();
        self::registerTaxonomyLocation();
    }


    function unregisterPostType($post_type) {
        global $wp_post_types;
        if (isset($wp_post_types[$post_type])) {
            unset($wp_post_types[$post_type]);
            return true;
        }
        return false;
    }

    public static function uninstallPlugin() {
        
    }
    
   
    public static function hideActivationKey(){
        session_start();
        if(!empty($_GET['activationkey']) && !empty($_GET['login'])){
            $_SESSION['activationkey'] = $_GET['activationkey'];
            $_SESSION['activationlogin'] = $_GET['login'];
            $_SESSION['activationpopup'] = true;
            session_commit();
            $server = $_SERVER['SERVER_NAME'];
            header("Location: /", true);
            echo " ";
        }
    }
    
    public static function renderLoginForm(){
        if(!is_user_logged_in()){
            if(!empty($_SESSION['activationkey']) && !empty($_SESSION['activationlogin'])){
//        echo "<div style=\"background: red\">boomba</div>";
                $key = $_SESSION['activationkey'];
                $login = $_SESSION['activationlogin'];
                $popup = empty($_SESSION['activationpopup'])?'':'true';
                unset($_SESSION['activationpopup']);
                echo "<div widget=\"loginForm\" key=\"$key\" login=\"$login\" popup=\"$popup\" screen=\"changePassword\"></div>";
            }else{
//        echo "<div style=\"background: green\">boomba</div>";
                echo "<div widget=\"loginForm\"></div>";
            }
        }else{
            unset($_SESSION['activationkey']);
            unset($_SESSION['activationlogin']);
            unset($_SESSION['activationpopup']);
            echo "<div widget=\"loginForm\"></div>";
        }
    }
    
    public static function addLoginForm(){
        wp_enqueue_style('jquery-brx-loginForm');
        wp_enqueue_script('jquery-brx-loginForm');
        add_action('wp_footer', array('AnotherGuru', 'renderLoginForm'));
    }
    
    public static function addJQueryWidgets(){
        wp_enqueue_style('jquery-ui');
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-effects-fade');
        wp_enqueue_script('jquery-effects-drop');
        wp_enqueue_script('jquery-effects-blind');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-templated');
        wp_enqueue_script('jquery-brx-modalBox');
        wp_print_scripts();
        
        ?>
                    
        <script>
        jQuery(document).ready(function($) {
            // $() will work as an alias for jQuery() inside of this function
            $.ui.parseWidgets('<?php echo WPP_ANOTHERGURU_URL?>js/');
        });        
        </script>
                    
        <?php
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
    
    public static function autoSlug($post){
//        print_r($post);
//        if(preg_match('%[А-Яа-я]%', $post['post_name'])){
        if(!$post['post_name'] && $post['post_status']=='draft'){
            $post['post_name'] = self::slug($post['post_title']);
        }else{
            $post['post_name'] = self::slug(urldecode($post['post_name']));
        }
        return $post;
    }
    
    public static function getSamplePermalinkHtml($return, $id, $new_title, $new_slug){
//        echo "<pre>";
//        print_r(array(
//            'return' => $return,
//            'id' => $id,
//            'new_title' => $new_title,
//            'new_slug' => $new_slug
//        ));
//        echo "</pre>";
//        $return = preg_match('%<span id="editable-post-name"[^>]*>([^<]*)</span>%imUs', $return, $m)?str_replace($m[1], self::slug($new_title), $return):$return;
        return $return;
    }
    
    public static function enhanceUserQueryWithPD($query) {
        global $wpdb;
//        print_r($query);
        $tableUserData = AnotherGuru::dbTable('ag_userdata');
        $query->query_fields .= ', '.$tableUserData.'.*';
        $query->query_from .= " LEFT JOIN $tableUserData ON({$wpdb->users}.ID = $tableUserData.user_id) ";
        if('user_reputation'==$query->query_vars['orderby']){
            $query->query_orderby = 'ORDER BY '.$query->query_vars['orderby'].' '.$query->query_vars['order'];
        }
        
        if(isset($query->query_vars['flags'])){
            $query->query_where.=' AND '.$tableUserData.'.user_flags & '.$query->query_vars['flags'].' = '.$query->query_vars['flags'];
        }
        if($query->query_vars['account_type']){
            $query->query_where.=' AND '.$tableUserData.'.user_account_type = '.$query->query_vars['account_type'];
        }
//        print_r($query);
    }

    public static function posts($request, $query=null){
//        echo '<pre>'.print_r(array('request'=>$request, 'query'=>$query), 1).'</pre>';
        return $request;
    }
//    function query_vars($vars)
//    {   
//        $vars[] = 'my_where';       
//        return $vars;
//    }
    public static function postsClauses($clauses, $query=null){
        global $wpdb;
//        echo '<pre>'.print_r(array('clauses'=>$clauses, 'query'=>$query), 1).'</pre>';
        $userId = get_current_user_id();
        $tablePostData = self::dbTable('ag_postdata');
        $tableVotes = self::dbTable('ag_votes');
        if(isset($query->query_vars['post_type'])){
            switch($query->query_vars['post_type']){
                case 'question':
                case 'answer':
                case 'contract':
                case 'bid':
                case 'profile':
                    $clauses['join'] .= " LEFT JOIN $tablePostData AS pd ON(pd.post_id = {$wpdb->posts}.ID) ";
                    $clauses['fields'] .= ", pd.*";
                    if($userId){
                        $clauses['join'] .= "LEFT JOIN $tableVotes AS v ON(v.post_id = {$wpdb->posts}.ID AND v.user_id = $userId) ";
                        $clauses['fields'] .= ", v.vote_value";
                    }
                    if($query->query_vars['orderby']){
                        $order_by = preg_split('%,\s*%', $query->query_vars['orderby']);
                        $tmp = array();
                        foreach($order_by as $o){
                            if(in_array($o, array('answers_count', 'votes_count', 'reviews_count'))){
                                $tmp[]= 'pd.'.$o.' '.$query->query_vars['order'];
                            }
                        }
                        $tmp[]= $clauses['orderby'];
                        $clauses['orderby'] = join(', ', $tmp);
                    }
                    break;
                case 'page':
                case 'post':
                default:
                    break;
            }
        }
//        echo '<pre>'.print_r(array('clauses'=>$clauses, 'query'=>$query), 1).'</pre>';
        return $clauses;
    }
    
    public function savePost($postId, $post){
//        print_r(array('$postId'=>$postId, '$post'=>$post));
        global $wpdb;
        $tablePostData = self::dbTable('ag_postdata');
        if(null == $wpdb->get_row("SELECT * FROM $tablePostData WHERE post_id = $postId")){
            $wpdb->query("INSERT INTO $tablePostData(post_id) VALUES($postId)");
        }
        if('publish' == $post->post_status 
        && in_array($post->post_type, array('question', 'post'))){
//            print_r($post);
            $item[LuceneHelper::getIdField()] = array('keyword', 'pk_'.$post->ID);
            $item['post_type'] = array('keyword', $post->post_type);
            $item['title'] = array('unstored', $post->post_title);
            $item['content'] = array('unstored', wp_strip_all_tags($post->post_content));
            $item['user_id'] = array('keyword', 'user_'.$post->post_author);
            $taxonomies = get_taxonomies();
            $t = array();
            foreach ($taxonomies as $taxonomy){
                $t[$taxonomy] = wp_get_post_terms($postId, $taxonomy, array('fields'=>'names'));
            }
            foreach($t as $taxonomy=>$terms){
                if(count($terms)){
                    $item[$taxonomy] = join(', ', $terms);
                }
            }
            $doc = LuceneHelper::luceneDocFromArray($item);
//            print_r($doc);
            LuceneHelper::indexLuceneDoc($doc);
//        die('savePost');
        }
    }
    
    public static function deletePost($postId){
        global $wpdb;
        $tableActions = self::dbTable('ag_actions');
        $tableUserData = self::dbTable('ag_userdata');
        $tablePostData = self::dbTable('ag_postdata');
        $query = $wpdb->prepare("
            SELECT * FROM $tableActions 
            WHERE object_type = %d AND object_id = %d",
            1, $postId);
        $actions = $wpdb->get_results($query);
        foreach($actions as $action){
            $query = $wpdb->prepare("
                UPDATE $tableUserData
                SET user_reputation = user_reputation - (%d)
                WHERE user_id = %d",
                $action->score,
                $action->reputation_user_id);
            $wpdb->query($query);
        }
        $query = $wpdb->prepare("
            DELETE FROM $tableActions 
            WHERE object_type = %d AND object_id = %d",
            1, $postId);
        $wpdb->query($query);
        $query = $wpdb->prepare("
            DELETE FROM $tablePostData 
            WHERE post_id = %d",
            $postId);
        $wpdb->query($query);
        
        LuceneHelper::deleteById('pk_'.$postId);
    }
    
    public static function searchQuery($search, $query ){
        if($query->is_search){
            $term = $query->query_vars['s'];
            $page = $query->query_vars['paged'];
            $itemsPerPage = $query->query_vars['posts_per_page'];
            $postType = $query->query_vars['post_type'];
            print_r(array(
                $term,
                $page,
                $itemsPerPage,
                $postType
                
            ));
            LuceneHelper::getInstance();
            try{
                $str = sprintf('post_type: %s AND %s', $postType, $term);
                $q = LuceneHelper::parseQuery($str);
            }catch(Exception $e){
                die($e->getMessage());
            }
            LuceneHelper::setQuery($q);
            $hits = LuceneHelper::searchHits($q);
            $ids = array();
            foreach($hits as $hit){
                $ids[]=$hit->getDocument()->getFieldValue(LuceneHelper::getIdField());
            }
//            $ids = LuceneHelper::searchIds($q);
            print_r($ids);
//            $ids = LuceneHelper::searchIds($query);
            $ids = array_slice($ids, ($page - 1)*$itemsPerPage, $itemsPerPage);
            global $wpdb;
            $search = " AND $wpdb->posts.ID IN(".join(',', $ids).") AND (wp_posts.post_password = '') ";
            echo "<pre>";
            print_r(array($ids, $search, $query));
            echo "</pre>";
        }
        return $search;
    }
    
    public static function searchLimits($limits, $query){
        if($query->is_search){
//            $limits = '';
//            print_r(array($limits, $query));
        }
        return $limits;
    }
    
    public static function excerpt_more($more) {
           global $post;
            return ' <a class="more_link" href="'. get_permalink($post->ID) . '">читать дальше...</a>';
    }
    

    public static function postPermalink($permalink, $post, $leavename = false){
//        echo $permalink;
//        die($permalink);
        switch($post->post_type){
            case "post":
                return '/article/'.$post->ID.'/'.($leavename?'%postname%':$post->post_name);
            case "answer":
                return get_permalink($post->post_parent).'#answer-'.$post->ID;
            case "question":
                return '/question/'.$post->ID.'/'.($leavename?'%postname%':$post->post_name);
            default:
                return $permalink;
        }
    }
    
    public static function mediaUploadTabs($tabs){
        $isAdmin = self::isAdmin();
        if(!$isAdmin){
            unset($tabs['library']);
        }
//        $userId = get_current_user_id();
//        $user = get_user_by('id', $userId);
//        $isAdmin = $isAdmin?'true':'false';
//        echo sprintf("
//            <script>console.dir({tabs:%s, user:%s, isadmin: $isAdmin});</script>
//            ", json_encode($tabs), json_encode($user));
        return $tabs;
    }


    public static function commentPermalink($permalink, $comment){
        return preg_replace('%#[^#]+#%', '#', $permalink);
    }
    
    public static function termLink($link, $term, $taxonomy){
//        print_r(array($link, $term, $taxonomy));
        return sprintf('/tag/%s/%s', $taxonomy, urlencode($term->name));
    }
    
    /**
     * Retrieve the name of the highest priority template file that exists.
     *
     * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
     * inherit from a parent theme can just overload one file.
     *
     * @since 2.7.0
     *
     * @param string|array $template_names Template file(s) to search for, in order.
     * @param bool $load If true the template file will be loaded if it is found.
     * @param bool $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
     * @return string The template filename if one is located.
     */
    public static function locate_template($template_names, $load = false, $require_once = true ) {
        $located = '';
        foreach ((array) $template_names as $template_name) {
            if (!$template_name)
                continue;
            if (file_exists(WPP_AG_TEMPLATEPATH . '/' . $template_name)) {
                $located = WPP_AG_TEMPLATEPATH . '/' . $template_name;
                break;
            } else if (file_exists(STYLESHEETPATH . '/' . $template_name)) {
                $located = STYLESHEETPATH . '/' . $template_name;
                break;
            } else if (file_exists(TEMPLATEPATH . '/' . $template_name)) {
                $located = TEMPLATEPATH . '/' . $template_name;
                break;
            }  
        }

        if ($load && '' != $located)
            load_template($located, $require_once);

        return $located;
    }

    /**
     * Load a template part into a template
     *
     * Makes it easy for a theme to reuse sections of code in a easy to overload way
     * for child themes.
     *
     * Includes the named template part for a theme or if a name is specified then a
     * specialised part will be included. If the theme contains no {slug}.php file
     * then no template will be included.
     *
     * The template is included using require, not require_once, so you may include the
     * same template part multiple times.
     *
     * For the $name parameter, if the file is called "{slug}-special.php" then specify
     * "special".
     *
     * @uses locate_template()
     * @since 3.0.0
     * @uses do_action() Calls 'get_template_part_{$slug}' action.
     *
     * @param string $slug The slug name for the generic template.
     * @param string $name The name of the specialised template.
     */
    function get_template_part($slug, $name = null) {

        $templates = array();
        if (isset($name)){
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";

        self::locate_template($templates, true, false);
    }

    public static function archive_template($template){
//        echo 'archive_template = '.$template;
	$post_type = get_query_var('post_type');

        $templates = array();

        if ($post_type) {
            $templates[] = "archive-{$post_type}.php";
        }
        $templates[] = 'archive.php';
        return self::locate_template($templates);
    }
    
    public static function author_template($template){
//        echo 'author_template = '.$template;
	$author = get_queried_object();

	$templates = array();

	$templates[] = "author-{$author->user_nicename}.php";
	$templates[] = "author-{$author->ID}.php";
	$templates[] = 'author.php';
        return self::locate_template($templates);
    }
    public static function category_template($template){
//        echo 'category_template = '.$template;
	$category = get_queried_object();

	$templates = array();

	$templates[] = "category-{$category->slug}.php";
	$templates[] = "category-{$category->term_id}.php";
	$templates[] = 'category.php';
        return self::locate_template($templates);
    }
    public static function tag_template($template){
//        echo 'tag_template = '.$template;
	$tag = get_queried_object();

	$templates = array();

	$templates[] = "tag-{$tag->slug}.php";
	$templates[] = "tag-{$tag->term_id}.php";
	$templates[] = 'tag.php';
        return self::locate_template($templates);
    }
    public static function taxonomy_template($template){
//        echo 'taxonomy_template = '.$template;
        $term = get_queried_object();
	$taxonomy = $term->taxonomy;

	$templates = array();

	$templates[] = "taxonomy-$taxonomy-{$term->slug}.php";
	$templates[] = "taxonomy-$taxonomy.php";
	$templates[] = 'taxonomy.php';
        return self::locate_template($templates);
    }
    public static function page_template($template){
//        echo 'page_template = '.$template;
	$id = get_queried_object_id();
        $template = get_page_template_slug();
        $pagename = get_query_var('pagename');

        if (!$pagename && $id) {
            // If a static page is set as the front page, $pagename will not be set. Retrieve it from the queried object
            $post = get_queried_object();
            $pagename = $post->post_name;
        }

        $templates = array();
        if ($template && 0 === validate_file($template))
            $templates[] = $template;
        if ($pagename)
            $templates[] = "page-$pagename.php";
        if ($id)
            $templates[] = "page-$id.php";
        $templates[] = 'page.php';

        return  self::locate_template($templates);
    }

    public static function single_template($template){
//        echo 'single_template = '.$template;
	$object = get_queried_object();

	$templates = array();

	$templates[] = "single-{$object->post_type}.php";
	$templates[] = "single.php";
        return  self::locate_template($templates);
    }
    
    public static function wp_nav_menu_objects($items, $menu){
        $uri = $_SERVER['REQUEST_URI'];
        $x = strlen($uri)-1;
        $uri = '/' == $uri[$x]?substr($uri, 0, $x):$uri;
        foreach($items as $i=>$item){
            $url = $item->url;
            $x = strlen($url)-1;
            $url = '/' == $url[$x]?substr($url, 0, $x):$url;
            $cmp = strlen($url)?strpos($uri, $url):false;
            if($cmp!==false && $cmp == 0){
                $item->classes[]= $uri == $url?'current-menu-item':'current-menu-parent';
            }
        }
//        echo "<pre>";
//        print_r(array("wp_nav_menu_objects"=>array($items, $menu)));
//        echo "</pre>";
        return $items;
    }
    
    public static function wp_nav_menu_items($arg1, $arg2){
        echo "<pre>";
        print_r(array("wp_nav_menu_items"=>array($arg1, $arg2)));
        echo "</pre>";
    }
    
    public static function wp_nav_menu($arg1, $arg2){
        echo "<pre>";
        print_r(array("wp_nav_menu"=>array($arg1, $arg2)));
        echo "</pre>";
    }
    
    public static function excerpt_length(){
        return 20;
    }
    
}



//add_filter('comment_post_redirect', array('AnotherGuru', 'filterCommentPostRedirect'));
wp_register_script( 'jquery', WPP_ANOTHERGURU_URL.'js/jquery-1.8.3.min.js', array('jquery'));

wp_register_style( 'postroydom', WPP_ANOTHERGURU_URL.'css/postroydom.css');
wp_enqueue_style('postroydom');
wp_register_style( 'jquery-ui', WPP_ANOTHERGURU_URL.'css/jquery-ui-1.9.1.custom.css');
wp_register_script( 'jquery-ajax-uploader', WPP_ANOTHERGURU_URL.'js/jquery.ajaxfileupload.js', array('jquery'));
wp_register_script( 'jquery-ajax-iframe-uploader', WPP_ANOTHERGURU_URL.'js/jquery.iframe-post-form.js', array('jquery'));
wp_register_script( 'jquery-galleria', WPP_ANOTHERGURU_URL.'js/galleria/galleria-1.2.8.min.js', array('jquery'));

wp_register_script( 'jquery-brx-utils', WPP_ANOTHERGURU_URL.'js/jquery.brx.utils.js', array('jquery'));
wp_register_script( 'jquery-brx-placeholder', WPP_ANOTHERGURU_URL.'js/jquery.brx.placeholder.js', array('jquery', 'jquery-brx-utils'));
wp_register_script( 'jquery-ui-templated', WPP_ANOTHERGURU_URL.'js/jquery.ui.templated.js', array('jquery-ui-core', 'jquery-ui-dialog','jquery-ui-widget', 'jquery-brx-utils'));
wp_register_style( 'jquery-brx-spinner', WPP_ANOTHERGURU_URL.'js/jquery.brx.spinner.css');
wp_register_script( 'jquery-brx-spinner', WPP_ANOTHERGURU_URL.'js/jquery.brx.spinner.js', array('jquery-ui-templated'));
wp_register_script( 'jquery-brx-modalBox', WPP_ANOTHERGURU_URL.'js/jquery.brx.modalBox.js', array('jquery-ui-dialog'));

wp_register_script( 'jquery-brx-form', WPP_ANOTHERGURU_URL.'js/jquery.brx.form.js', array('jquery-ui-templated','jquery-brx-spinner', 'jquery-brx-placeholder', 'jquery-ui-autocomplete'));

wp_register_style( 'jquery-brx-loginForm', WPP_ANOTHERGURU_URL.'js/jquery.brx.loginForm.css', array('jquery-brx-spinner'));
wp_register_script( 'jquery-brx-loginForm', WPP_ANOTHERGURU_URL.'js/jquery.brx.loginForm.js', array('jquery-brx-form')); //array('jquery-brx-form','jquery-brx-spinner', 'jquery-brx-placeholder')

wp_register_style( 'jquery-brx-userForm', WPP_ANOTHERGURU_URL.'js/jquery.brx.userForm.css', array('jquery-brx-spinner'));
wp_register_script( 'jquery-brx-userForm', WPP_ANOTHERGURU_URL.'js/jquery.brx.userForm.js', array('jquery-brx-form', 'jquery-ui-datepicker'));

wp_register_style( 'jquery-brx-askForm', WPP_ANOTHERGURU_URL.'js/jquery.brx.askForm.css', array('jquery-brx-spinner'));
wp_register_script( 'jquery-brx-askForm', WPP_ANOTHERGURU_URL.'js/jquery.brx.askForm.js', array('jquery-brx-form'));

wp_register_style( 'jquery-brx-comments', WPP_ANOTHERGURU_URL.'js/jquery.brx.comments.css', array('jquery-brx-spinner'));
wp_register_script( 'jquery-brx-comments', WPP_ANOTHERGURU_URL.'js/jquery.brx.comments.js', array('jquery-brx-form'));

wp_register_style( 'jquery-brx-voter', WPP_ANOTHERGURU_URL.'js/jquery.brx.voter.css');
wp_register_script( 'jquery-brx-voter', WPP_ANOTHERGURU_URL.'js/jquery.brx.voter.js', array('jquery-ui-templated'));

wp_register_style( 'jquery-brx-answers', WPP_ANOTHERGURU_URL.'js/jquery.brx.answers.css', array('jquery-brx-spinner','jquery-brx-voter'));
wp_register_script( 'jquery-brx-answers', WPP_ANOTHERGURU_URL.'js/jquery.brx.answers.js', array('jquery-brx-form','jquery-brx-voter'));

//wp_register_style( 'jquery-brx-albums', WPP_ANOTHERGURU_URL.'js/jquery.brx.answers.css', array('jquery-brx-spinner','jquery-brx-voter'));
wp_register_script( 'jquery-brx-albums', WPP_ANOTHERGURU_URL.'js/jquery.brx.albums.js', array('jquery-brx-form','jquery-brx-voter', 'jquery-ajax-uploader', 'jquery-ajax-iframe-uploader', 'jquery-galleria'));

//wp_register_style( 'jquery-brx-searchHistory', WPP_ANOTHERGURU_URL.'js/jquery.brx.searchHistory.css'));
wp_register_script( 'jquery-brx-searchHistory', WPP_ANOTHERGURU_URL.'js/jquery.brx.searchHistory.js', array('jquery-ui-templated'));

register_activation_hook(__FILE__,array('AnotherGuru', 'dbInstall'));
add_action('plugins_loaded', array('AnotherGuru', 'dbUpdate'));

add_action('init', array('AnotherGuru', 'installPlugin'));
register_uninstall_hook(__FILE__, array('AnotherGuru', 'uninstallPlugin'));
//add_filter('manage_question_posts_columns', array('AnotherGuru', 'manageQuestionColumns'));
add_filter('manage_question_posts_columns', array('AnotherGuru', 'manageQuestionColumns'));
add_filter('manage_edit_question_sortable_columns', array('AnotherGuru', 'questionSortableColumns'));
add_action('manage_posts_custom_column', array('AnotherGuru', 'managePostCustomColumn'));
add_action('wp_footer', array('AnotherGuru', 'addJQueryWidgets'));
add_action('wp_head', array('AnotherGuru', 'addLoginForm'));
add_action('init', array('AnotherGuru', 'hideActivationKey'));
add_action('pre_user_query', array('AnotherGuru', 'enhanceUserQueryWithPD'));
add_action('save_post', array('AnotherGuru', 'savePost'), 10, 2);
add_action('delete_post', array('AnotherGuru', 'deletePost'), 10, 1);
add_filter('posts_search', array('AnotherGuru', 'searchQuery'), 10, 2);
add_filter('post_limits', array('AnotherGuru', 'searchLimits'), 10, 2);
add_filter('posts_request', array('AnotherGuru', 'posts'), 10, 2);
//add_filter('query_vars', array('AnotherGuru', 'query_vars'), 10, 1 );
add_filter('posts_clauses', array('AnotherGuru', 'postsClauses'), 10, 2);
add_filter('excerpt_more', array('AnotherGuru', 'excerpt_more'));
add_filter('wp_insert_post_data', array('AnotherGuru', 'autoSlug'), 10, 1 );

add_filter('get_sample_permalink_html', array('AnotherGuru', 'getSamplePermalinkHtml'), 1, 4);
add_filter('post_type_link', array('AnotherGuru', 'postPermalink'), 1, 3);
add_filter('post_link', array('AnotherGuru', 'postPermalink'), 1, 3);
add_filter('get_comment_link', array('AnotherGuru', 'commentPermalink'), 1, 2);
add_filter('term_link', array('AnotherGuru', 'termLink'), 1, 3);
add_filter('archive_template', array('AnotherGuru', 'archive_template'), 1, 2);
add_filter('author_template', array('AnotherGuru', 'author_template'), 1, 2);
add_filter('category_template', array('AnotherGuru', 'category_template'), 1, 2);
add_filter('tag_template', array('AnotherGuru', 'tag_template'), 1, 2);
add_filter('taxonomy_template', array('AnotherGuru', 'taxonomy_template'), 1, 2);
add_filter('page_template', array('AnotherGuru', 'page_template'), 1, 2);
add_filter('paged_template', array('AnotherGuru', 'paged_template'), 1, 2);
//add_filter('search_template', array('AnotherGuru', 'search_template'), 1, 2);
add_filter('single_template', array('AnotherGuru', 'single_template'), 1, 2);
add_filter('wp_nav_menu_objects', array('AnotherGuru', 'wp_nav_menu_objects'), 1, 2);
add_filter('media_upload_tabs', array('AnotherGuru', 'mediaUploadTabs'), 1, 1);
add_filter('excerpt_length', array('AnotherGuru', 'excerpt_length'), 1, 1);
//add_filter('wp_nav_menu_items', array('AnotherGuru', 'wp_nav_menu_items'), 1, 2);
//add_filter('wp_nav_menu', array('AnotherGuru', 'wp_nav_menu'), 1, 2);
    
register_sidebar(array(
    'id'=>'index-index',
    'name'=>__('Главная страница'),
));
register_sidebar(array(
    'id'=>'article-articles',
    'name'=>__('Статьи'),
));
register_sidebar(array(
    'id'=>'article-article',
    'name'=>__('Статья'),
));
register_sidebar(array(
    'id'=>'question-ask',
    'name'=>__('Задать вопрос'),
));
register_sidebar(array(
    'id'=>'question-questions',
    'name'=>__('Вопросы'),
));
register_sidebar(array(
    'id'=>'question-unanswered',
    'name'=>__('Вопросы без ответа'),
));
register_sidebar(array(
    'id'=>'question-view',
    'name'=>__('Вопрос'),
));
register_sidebar(array(
    'id'=>'user-users',
    'name'=>__('Пользователи'),
));
register_sidebar(array(
    'id'=>'user-profile',
    'name'=>__('Пользователь'),
));
register_sidebar(array(
    'id'=>'user-edit',
    'name'=>__('Анкета'),
));
register_sidebar(array(
    'id'=>'article-articles',
    'name'=>__('Статьи'),
));
register_sidebar(array(
    'id'=>'tag-tags',
    'name'=>__('Рубрики'),
));
register_sidebar(array(
    'id'=>'tag-tag',
    'name'=>__('Рубрика'),
));
register_sidebar(array(
    'id'=>'search',
    'name'=>__('Поиск'),
));
register_sidebar(array(
    'id'=>'list_top',
    'name'=>__('Записи - верх'),
    'before_widget' => '<div id="%1$s" class="bem-posts_list_widget %2$s">',
    'after_widget' => "</div>\n",
    'before_title' => '<h2 class="widgettitle">',
    'after_title' => "</h2>\n",
));
register_sidebar(array(
    'id'=>'list_middle',
    'name'=>__('Записи - середина'),
    'before_widget' => '<div id="%1$s" class="bem-posts_list_widget %2$s">',
    'after_widget' => "</div>\n",
));
register_sidebar(array(
    'id'=>'list_bottom',
    'name'=>__('Записи - низ'),
    'before_widget' => '<div id="%1$s" class="bem-posts_list_widget %2$s">',
    'after_widget' => "</div>\n",
));
    
    
    
    
    
    
//add_action('save_post', array('AnotherGuru', 'savePost'), 10, 2);