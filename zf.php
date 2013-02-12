<?php

define('ZF_MARKER', 'do');
add_action('parse_request', 'zf_query_controller');

function zf_query_controller($request) {
    
    $routes = array(
        'api',
        'user',
        'users',
        'question',
        'questions',
        'unanswered',
        'ask-question',
        'answer',
        'article',
        'articles',
        'album',
        'albums',
        'widget',
        'tag',
        'tags',
        'search',
        'lucene',
//        'equipment',
//        'equipments',
//        'profession',
//        'professions',
//        'work',
//        'works',
//        'location',
//        'locations',
//        'material',
//        'materials',
    );
    
//    print_r($request);
    if(isset($request->query_vars['error'])){
        unset($request->query_vars['error']);
    }
    parse_str($_SERVER['QUERY_STRING'], $params);
    $isZF = empty($_SERVER['REQUEST_URI'])
        || '/'==$_SERVER['REQUEST_URI']
        || preg_match('%^\/(do|'.  join('|', $routes).')(\/|\z)%',$_SERVER['REQUEST_URI']);
//        || preg_match('%^\/(do|'.  join('|', $routes).')\/|\z%',$_SERVER['REQUEST_URI']);
    $isAPI = preg_match('%^\/api|widget\/%',$_SERVER['REQUEST_URI']);
    if ($isZF || $isAPI/*isset($params[ZF_MARKER])*/) {
//    die('(!)');
//        die('<pre>'.print_r(array(
//            '$isZF'=>$isZF,
//            'empty' => empty($_SERVER['REQUEST_URI']),
//            '/' => '/'==$_SERVER['REQUEST_URI'],
//            '%^\/(do|'.  join('|', $routes).')\/|\z% '.$_SERVER['REQUEST_URI'] => preg_match('%^\/(do|'.  join('|', $routes).')\/|\z%',$_SERVER['REQUEST_URI']),
//            '$isAPI'=>$isAPI,
//            '$routes'=>$routes,
//            '$_SERVER'=>$_SERVER
//        ), true).'</pre>');
        $request->query_vars['pagename']='zf';
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        $args = array(
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
//            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
        );
        register_post_type('zf', $args);
        register_post_type('zf-api', $args);
        if($isAPI/*isset ($params['api'])*/){
            $GLOBALS['is_zf_api_call'] = true;            
        }
        global $wp_the_query, $wp_query, $wp_filter;
        remove_filter ('the_content','wpautop');
        $q = new ZF_Query();
        $q->copyFrom($wp_the_query);
//        $zf_uri = trim($params[ZF_MARKER], "/ ");
//        $q->req_uri_zf = empty($zf_uri) ? '/' : '/' . $zf_uri . '/';
        $zf_uri = preg_replace('%^\/do%', '', $_SERVER['REQUEST_URI']);
        $q->req_uri_zf = $zf_uri;
        $wp_the_query = $wp_query = $q;
    }elseif(preg_match('%^\/(\d+)\/%', $_SERVER['REQUEST_URI'], $matches)){
//        print_r($matches);
        $post = get_post($matches[1]);
//        print_r($post);
        
        $url = AnotherGuru::postPermalink($_SERVER['REQUEST_URI'], $post);
        header('HTTP/1.0 301 Moved Permanently', true, 301);
        header('Location: '.$url);
        
    }
}

class ZF_Query extends WP_Query {

    public $req_uri_original = '';
    public $req_uri_zf = '';

    function copyFrom(WP_Query $wp_query) {
        $vars = get_object_vars($wp_query);
        foreach ($vars as $name => $value) {
            $this->$name = $value;
        }
    }

    function &get_posts() {
        global $wp_the_query;
        global $wp_query;
//        parent::get_posts();
        $tmp = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = $this->req_uri_zf;
//        echo$this->req_uri_zf;
        try {
            $zf_response = require_once WPP_ANOTHERGURU_PATH . 'zf-app/public/index.php';
//            die($zf_response);
        }catch(Exception $e){
            echo '('.$e->getMessage().')';
        }
        $_SERVER['REQUEST_URI'] = $tmp;
//        remove_all_filters();
        $posts = WpHelper::getPosts();
        if($posts){
            global $post;
            $post = reset($posts);
            $this->posts = $posts;
            $this->post = $post;
            $this->post_count = count($this->posts);
            $this->current_post = -1;

            $this->is_single = count($post) == 1;
            $this->is_page = 0;
            $this->is_404 = 0;
            $this->is_archive = 0;
            $this->is_home = 0;
        }else{
//            echo $zf_response;
            $post_zf = array(
                "ID" => 1,
                "post_author" => 1,
                "post_date" => '',
                "post_date_gmt" => '',
                "post_content" => $zf_response,
                "post_title" => WpHelper::getPostTitle(),
                "post_excerpt" => WpHelper::getPostDescription(),
                "post_status" => "publish",
                "comment_status" => "closed",
                "ping_status" => "closed",
                "post_password" => "",
                "post_name" => "",
                "to_ping" => "",
                "pinged" => "",
                "post_modified" => "",
                "post_modified_gmt" => "",
                "post_content_filtered" => "",
                "post_parent" => 0,
                "guid" => "",
                "menu_order" => 1,
                "post_type" => $GLOBALS['is_zf_api_call']?'zf-api':'zf',
                "post_mime_type" => "",
                "comment_count" => "0",
                "ancestors" => array(),
                "filter" => "",
                "page_template" => "onecolumn-page.php",
                "nav_menu_id" => WpHelper::getNavMenuId(),
                "nav_menu" => WpHelper::getNavMenu(),
                "sidebar_id" => WpHelper::getSideBarId(),
                "sidebar_static" => WpHelper::getSideBarStatic()
            );
            $wp_the_query = $this;
            $wp_the_query->comment = null;
            $wp_the_query->comments = array();
            $wp_the_query->comment_count = 0;
            
            global $post;
            $post = (object) $post_zf;
            $this->posts = array($post);
            $this->post = $post;
            $this->post_count = count($this->posts);
            $this->current_post = -1;

            $this->is_single = 1;
            $this->is_page = 0;
            $this->is_404 = WpHelper::getNotFound();
            $this->is_archive = 0;
            $this->is_home = 0;
        }
        
        $this->queried_object = $post;

// эти 2 строки нужны, чтобы wordPress (особеннно в версии 3.x) не думал, что у него запросили нечто некорректное. 
        global $wp_filter;
        unset($wp_filter['template_redirect']);
        add_filter('the_content', array('ZF_Query', 'theContent'), 100);
        return $this->posts;
    }
    
    public static function theContent($content){
//        print_r($content);
//        remove_filter ('the_content','wpautop'); echo "!";
        return $content;
    }

}

class WP_Widget_ZF extends WP_Widget {
    
    protected static $args;

    public static function getArgs(){
        return self::$args;
    }
    
    public static function getTitle(){
        return self::$args['title'];
    }
    
    function __construct($id = 'zf_app_widget', $name = 'ZF App Response', 
        $widget_ops = array(
            'classname' => 'WP_Widget_ZF',
            'description' => "Zend Framework App response"
        )) {
        parent::__construct($id, $name, $widget_ops);
        $this->alt_option_name = $id;

        add_action('save_post', array(&$this, 'flush'));
        add_action('deleted_post', array(&$this, 'flush'));
        add_action('switch_theme', array(&$this, 'flush'));
    }

    function widget($args, $instance) {
        $cache = wp_cache_get($this->id_base, 'widget');

        if (!is_array($cache))
            $cache = array();

        if (!isset($args['widget_id']))
            $args['widget_id'] = $this->id;

        if (isset($cache[$args['widget_id']])) {
            echo $cache[$args['widget_id']];
            return;
        }

        ob_start();
        $output = '';
        self::$args = $instance;
        extract($args);

        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
        $request_uri = empty($instance['uri']) ? '/' : $instance['uri'];
        $tmp = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = $request_uri;
        try {
            echo include WPP_ANOTHERGURU_PATH . 'zf-app/public/index.php';
        } catch (Exception $e) {
            echo '(' . $e->getMessage() . ')';
        }
        $_SERVER['REQUEST_URI'] = $tmp;

//        echo $after_widget;
        // Reset the global $the_post as this query will have stomped on it
        wp_reset_postdata();

//		endif;

        $cache[$args['widget_id']] = ob_get_flush();
        wp_cache_set($this->id_base, $cache, 'widget');
    }

    function flush() {
        wp_cache_delete($this->id_base, 'widget');
    }
    
    public function generateUri($action, $params = array()){
        $uri = '/widget/'.$action.'/';
        $pieces = array();
        foreach($params as $key => $value){
            if($value){
                $pieces[] = $key.'/'.$value;
            }
        }
        $uri.=join('/', $pieces);
        return $uri;
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['uri'] = strip_tags($new_instance['uri']);
        $this->flush();

        $alloptions = wp_cache_get('alloptions', 'options');
        if (isset($alloptions[$this->id_base]))
            delete_option($this->id_base);

        return $instance;
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $uri = isset($instance['uri']) ? esc_attr($instance['uri']) : '/';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('uri'); ?>"><?php _e('ZF uri:'); ?></label>
            <input id="<?php echo $this->get_field_id('uri'); ?>" name="<?php echo $this->get_field_name('uri'); ?>" type="text" value="<?php echo $uri; ?>" /></p>

        <?php
    }

}

add_action( 'widgets_init', create_function( '', 'register_widget( "WP_Widget_ZF" );' ) );

class ZF_Widget_Articles extends WP_Widget_ZF{
    public $action = 'articles'; 
    public $modes = array(
        '0'=>'По умолчанию',
        'new'=>'Новые',
        'votes'=>'Популярные',
        'active'=>'Обсуждаемые'
    );
    
    public function __construct($id = 'zf_articles', $name = 'ZF: Статьи', $opts = array(
            'classname' => 'ZF_Widget_Articles',
            'description' => "Записи из раздела Статьи"
        )) {
        parent::__construct($id, $name, $opts);
    }
    function update($new_instance, $instance) {
        $instance['count'] = strip_tags($new_instance['count']);
        $instance['mode'] = strip_tags($new_instance['mode']);
        $params = array_intersect_key($new_instance, array_fill_keys(array(
            'count',
            'mode',
        ), null));
        $new_instance['uri'] = $this->generateUri($this->action, $params);
        
        return parent::update($new_instance, $instance);
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
//        $uri = isset($instance['uri']) ? esc_attr($instance['uri']) : '/';
        $count = isset($instance['count']) ? esc_attr($instance['count']) : '5';
        $mode = isset($instance['mode']) ? esc_attr($instance['mode']) : '0';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Count:'); ?></label>
            <input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('mode'); ?>"><?php _e('Mode:'); ?></label>
            <select id="<?php echo $this->get_field_id('mode'); ?>" name="<?php echo $this->get_field_name('mode'); ?>">
            <?php foreach($this->modes as $value=>$label):?>    
                <option value="<?php echo $value;?>" <?php if($value == $mode):?>selected="selected"<?php endif;?>><?php echo $label?></option>
            <?php endforeach;?>
            </select>
            </p>
        <?php
    }
}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_Articles" );' ) );

class ZF_Widget_Questions extends ZF_Widget_Articles{
    public $action = 'questions'; 
    public function __construct() {
        parent::__construct('zf_questions', 'ZF: Вопросы', array(
            'classname' => 'ZF_Widget_Questions',
            'description' => "Записи из раздела Вопросы"
        ));
    }
}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_Questions" );' ) );

class ZF_Widget_Profiles extends ZF_Widget_Articles{
    public $action = 'profiles'; 
    public $modes = array(
        '0'=>'По умолчанию',
        'new'=>'Новые',
        'top'=>'Топ',
        'consultants'=>'Консультанты',
        'contractors'=>'Подрядчики'
    );
    
    public function __construct() {
        parent::__construct('zf_profiles', 'ZF: Пользователи', array(
            'classname' => 'ZF_Widget_Profiles',
            'description' => "Пользователи сайта"
        ));
    }
//    function update($new_instance, $instance) {
//        $instance['count'] = strip_tags($new_instance['count']);
//        $instance['mode'] = strip_tags($new_instance['mode']);
//        $params = array_intersect_key($new_instance, array_fill_keys(array(
//            'count',
//            'mode',
//        ), null));
//        $new_instance['uri'] = $this->generateUri('articles', $params);
//        
//        return parent::update($new_instance, $instance);
//    }

}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_Profiles" );' ) );

class ZF_Widget_Tags extends WP_Widget_ZF{
    public $action = 'tags'; 
    public $taxonomies = array(
        '0'=>'Все',
        'post_tag'=>'Метки',
        'profession'=>'Профессии',
        'work'=>'Виды работ',
        'equipment'=>'Инструменты',
        'material'=>'Материалы'
    );
    
    public function __construct($id = 'zf_tags', $name = 'ZF: Метки', $opts = array(
            'classname' => 'ZF_Widget_Tags',
            'description' => "Метки"
        )) {
        parent::__construct($id, $name, $opts);
    }
    function update($new_instance, $instance) {
        $instance['count'] = strip_tags($new_instance['count']);
        $instance['taxonomy'] = strip_tags($new_instance['taxonomy']);
        $params = array_intersect_key($new_instance, array_fill_keys(array(
            'count',
            'taxonomy',
        ), null));
        $new_instance['uri'] = $this->generateUri($this->action, $params);
        
        return parent::update($new_instance, $instance);
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : 'Рубрики';
        $count = isset($instance['count']) ? esc_attr($instance['count']) : '5';
        $taxonomy = isset($instance['taxonomy']) ? esc_attr($instance['taxonomy']) : '0';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Count:'); ?></label>
            <input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e('Taxonomy:'); ?></label>
            <select id="<?php echo $this->get_field_id('mode'); ?>" name="<?php echo $this->get_field_name('mode'); ?>">
            <?php foreach($this->taxonomies as $value=>$label):?>    
                <option value="<?php echo $value;?>" <?php if($value == $taxonomy):?>selected="selected"<?php endif;?>><?php echo $label?></option>
            <?php endforeach;?>
            </select>
            </p>
        <?php
    }
}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_Tags" );' ) );

class ZF_Widget_Page extends WP_Widget_ZF{
    public $action = 'post';
    public $classes = '';
    public function __construct($id = 'zf_page', $name = 'ZF: Статическая страница', $opts = array(
            'classname' => 'ZF_Widget_Page',
            'description' => "Статическая страница"
        )) {
        parent::__construct($id, $name, $opts);
    }
    function update($new_instance, $instance) {
        $instance['id'] = strip_tags($new_instance['id']);
        $instance['slug'] = strip_tags($new_instance['slug']);
        $instance['classes'] = strip_tags($new_instance['classes']);
        $params = array_intersect_key($new_instance, array_fill_keys(array(
            'id',
            'slug',
            'classes',
        ), null));
        $new_instance['uri'] = $this->generateUri($this->action, $params);
        
        return parent::update($new_instance, $instance);
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $id = isset($instance['id']) ? esc_attr($instance['id']) : '';
        $slug = isset($instance['slug']) ? esc_attr($instance['slug']) : '';
        $classes = isset($instance['classes']) ? esc_attr($instance['classes']) : '';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('id'); ?>"><?php _e('Page ID:'); ?></label>
            <input id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" type="text" value="<?php echo $id; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('slug'); ?>"><?php _e('Slug:'); ?></label>
            <input id="<?php echo $this->get_field_id('slug'); ?>" name="<?php echo $this->get_field_name('slug'); ?>" type="text" value="<?php echo $slug; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('classes'); ?>"><?php _e('Html class:'); ?></label>
            <input id="<?php echo $this->get_field_id('classes'); ?>" name="<?php echo $this->get_field_name('classes'); ?>" type="text" value="<?php echo $classes; ?>" /></p>

        <?php
    }
}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_Page" );' ) );

class ZF_Widget_Banners extends WP_Widget_ZF{
    public $action = 'banners'; 
    public function __construct($id = 'zf_banners', $name = 'ZF: Баннеры', $opts = array(
            'classname' => 'ZF_Widget_Banners',
            'description' => "Баннеры"
        )) {
        parent::__construct($id, $name, $opts);
    }
    function update($new_instance, $instance) {
        $instance['ids'] = strip_tags($new_instance['ids']);
        $instance['count'] = strip_tags($new_instance['count']);
        $params = array_intersect_key($new_instance, array_fill_keys(array(
            'ids',
            'count',
        ), null));
        $new_instance['uri'] = $this->generateUri($this->action, $params);
        
        return parent::update($new_instance, $instance);
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $count = isset($instance['count']) ? esc_attr($instance['count']) : '2';
        $ids = isset($instance['ids']) ? esc_attr($instance['ids']) : '';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('ids'); ?>"><?php _e('Attachment IDs:'); ?></label>
            <input id="<?php echo $this->get_field_id('ids'); ?>" name="<?php echo $this->get_field_name('ids'); ?>" type="text" value="<?php echo $ids; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Count:'); ?></label>
            <input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></p>

        <?php
    }
}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_Banners" );' ) );

class ZF_Widget_SearchHistory extends WP_Widget_ZF{
    public $action = 'search-history'; 
    
    public function __construct($id = 'zf_search_history', $name = 'ZF: История поиска', $opts = array(
            'classname' => 'ZF_Widget_SearchHistory',
            'description' => "История поисковых запросов"
        )) {
        parent::__construct($id, $name, $opts);
    }
    function update($new_instance, $instance) {
        $instance['count'] = strip_tags($new_instance['count']);
        $params = array_intersect_key($new_instance, array_fill_keys(array(
            'count',
        ), null));
        $new_instance['uri'] = $this->generateUri($this->action, $params);
        
        return parent::update($new_instance, $instance);
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $count = isset($instance['count']) ? esc_attr($instance['count']) : '5';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Count:'); ?></label>
            <input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></p>
        <?php
    }
}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_SearchHistory" );' ) );

class ZF_Widget_ProfileMenu extends WP_Widget_ZF{
    public $action = 'profile-menu'; 
    
    public function __construct($id = 'zf_profile_menu', $name = 'ZF: Меню профиля', $opts = array(
            'classname' => 'ZF_Widget_ProfileMenu',
            'description' => "Меню профиля пользователя, отображается только для самого пользователя"
        )) {
        parent::__construct($id, $name, $opts);
    }
    function update($new_instance, $instance) {
        $new_instance['uri'] = $this->generateUri($this->action);
        
        return parent::update($new_instance, $instance);
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

        <?php
    }
}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_ProfileMenu" );' ) );

class ZF_Widget_Numbers extends WP_Widget_ZF{
    public $action = 'numbers';
    public $classes = '';
    public function __construct($id = 'zf_numbers', $name = 'ZF: Статистика', $opts = array(
            'classname' => 'ZF_Widget_Numbers',
            'description' => "Статистика по сайту"
        )) {
        parent::__construct($id, $name, $opts);
    }
    function update($new_instance, $instance) {
        $instance['classes'] = strip_tags($new_instance['classes']);
        $params = array_intersect_key($new_instance, array_fill_keys(array(
            'classes',
        ), null));
        $new_instance['uri'] = $this->generateUri($this->action, $params);
        
        return parent::update($new_instance, $instance);
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $classes = isset($instance['classes']) ? esc_attr($instance['classes']) : '';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('classes'); ?>"><?php _e('Html class:'); ?></label>
            <input id="<?php echo $this->get_field_id('classes'); ?>" name="<?php echo $this->get_field_name('classes'); ?>" type="text" value="<?php echo $classes; ?>" /></p>

        <?php
    }
}

add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_Numbers" );' ) );

