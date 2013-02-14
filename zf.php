<?php

require_once 'Zend/Application.php';

add_action('parse_request', array('ZF_Query', 'parseRequest'));

class ZF_Query extends WP_Query {

    public $req_uri_zf = '';
    
    protected static $applications = array();
    protected static $routes = array();
    
    public static function registerApplication($id, $path, $routes, $env = 'production'){
        self::$applications[$id] = array(
            'path' => $path,
            'environment' => $env,
            'routes' => $routes,
        );
        
        foreach($routes as $route){
            self::$routes[$route] = $id;
        }
    }
    
    public static function parseRequest(){
    
        if(isset($request->query_vars['error'])){
            unset($request->query_vars['error']);
        }
        parse_str($_SERVER['QUERY_STRING'], $params);
        $isZF = empty($_SERVER['REQUEST_URI'])
    //        || '/'==$_SERVER['REQUEST_URI']
            || preg_match('%^\/('.  join('|', self::$routes).')(\/|\z)%',$_SERVER['REQUEST_URI']);
        $isAPI = preg_match('%^\/api|widget\/%',$_SERVER['REQUEST_URI']);
        if ($isZF || $isAPI/*isset($params[ZF_MARKER])*/) {
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
            if($isAPI){
                $GLOBALS['is_zf_api_call'] = true;            
            }
            global $wp_the_query, $wp_query, $wp_filter;
            remove_filter ('the_content','wpautop');
            $q = new ZF_Query();
            $q->copyFrom($wp_the_query);
            $zf_uri = preg_replace('%^\/do%', '', $_SERVER['REQUEST_URI']);
            $q->req_uri_zf = $zf_uri;
            $wp_the_query = $wp_query = $q;
        }
    }
    
    public static function processRequest($uri = ''){
        if(!$uri){
            $uri = $_SERVER['REQUEST_URI'];
        }
        $tmpUri = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = $uri;
        $route = preg_match('%^\/([^\/]*)%', $uri, $m)?$m[1]:'/';
        
        $appId = Util::getItem(self::$routes, $route);
        if($appId){
            $appInfo = Util::getItem(self::$applications, $appId);
            
            $application = Util::getItem($appInfo, 'application');
            
            $appPath = Util::getItem($appInfo, 'path');

            $appEnv = getenv($appId.'_APPLICATION_ENV') ? 
                getenv($appId.'_APPLICATION_ENV') : 
                Util::getItem($appInfo, 'environment', 'production');

            if(!$application){
                
                // Define path to application directory
                defined($appId.'_APPLICATION_PATH')
                    || define($appId.'_APPLICATION_PATH', $appPath);

                // Define application environment
                defined($appId.'_APPLICATION_ENV')
                    || define($appId.'_APPLICATION_ENV', $appEnv);

                // Ensure library/ is on include_path
                set_include_path(implode(PATH_SEPARATOR, array_unique(array(
                    realpath($appPath.'/../'),
                    get_include_path(),
                ))));

                // Create application, bootstrap, and run
            }

            $application = new Zend_Application(
                $appEnv,
                $appPath . '/configs/application.ini'
            );

            self::$applications[$appId]['application'] = $application;

            global $wp_the_query;
            $the_q = $wp_the_query;
            $front = Util::getFront();
            $front->resetInstance();
            //print_r($front);
            $front->setParam('displayExceptions', true);
            $front->returnResponse(true);
            $application->bootstrap()->getBootStrap()->setupRouting();
            $r = $front->dispatch();
            $wp_the_query = $the_q;
            //$r = $application->bootstrap()->getBootStrap()->run();
            $_SERVER['REQUEST_URI'] = $tmpUri;
            return $r;        
        }
        
        return '';
        
    }

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
//        $tmp = $_SERVER['REQUEST_URI'];
//        $_SERVER['REQUEST_URI'] = $this->req_uri_zf;
//        echo$this->req_uri_zf;
        try {
//            $zf_response = require_once WPP_ANOTHERGURU_PATH . 'zf-app/public/index.php';
            $zf_response = self::processRequest();
//            die($zf_response);
        }catch(Exception $e){
            echo '('.$e->getMessage().')';
        }
//        $_SERVER['REQUEST_URI'] = $tmp;
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
        try {
//            echo include WPP_ANOTHERGURU_PATH . 'zf-app/public/index.php';
            echo ZF_Query::processRequest($request_uri);
        } catch (Exception $e) {
            $_SERVER['REQUEST_URI'] = $tmp;
            echo '(' . $e->getMessage() . ')';
        }

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

//add_action( 'widgets_init', create_function( '', 'register_widget( "ZF_Widget_Articles" );' ) );

