<?php

require_once 'Zend/Application.php';
//require_once 'wp-admin/includes/theme.php';

add_action('parse_request', array('ZF_Query', 'parseRequest'));

//Template fallback
//add_action("template_redirect", array('ZF_Query', 'themeRedirect'));
add_filter('single_template', array('ZF_Query', 'singleTemplate'), 1, 2);


class ZF_Query extends WP_Query {

//    public $req_uri_zf = '';


    protected static $applications = array();
    protected static $routes = array();
    protected static $widgets = array();
    
    public static function registerApplication($id, $path, $routes, $widgets = array(), $env = 'production'){
        self::$applications[$id] = array(
            'path' => $path,
            'environment' => $env,
            'routes' => $routes,
        );
        
        foreach($routes as $route){
            self::$routes[$route] = $id;
        }
    
        foreach($widgets as $widget){
            self::$widgets[$widget] = $id;
        }
    }
    
    public static function parseRequest(){
        if(isset($request->query_vars['error'])){
            unset($request->query_vars['error']);
        }
        parse_str($_SERVER['QUERY_STRING'], $params);
        $isZF = /*empty($_SERVER['REQUEST_URI'])
            || '/'==$_SERVER['REQUEST_URI']
            || */ preg_match('%^\/((api|widget)\/)?('.  join('|', array_keys(self::$routes)).')(\/|\z)%',$_SERVER['REQUEST_URI'], $m);
//        $isAPI = preg_match('%^\/api|widget\/%',$_SERVER['REQUEST_URI']);
        $isAPI = Util::getItem($m, 1, false);
        
        if($isAPI){
            $uri = preg_replace('%^\/(api|widget)%', '', $_SERVER['REQUEST_URI']);
            die(ZF_Query::processRequest($uri));
        }
        
        if ($isZF || $isAPI/*isset($params[ZF_MARKER])*/) {
//            echo " ZF call detected ";
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
//            Util::print_r($q);
//            $zf_uri = preg_replace('%^\/do%', '', $_SERVER['REQUEST_URI']);
//            $q->req_uri_zf = $zf_uri;
            $wp_the_query = $wp_query = $q;
            
//            Util::print_r($wp_the_query);
        }
    }
    
    public static function processRequest($uri = '', $appId = null){
        if(!$uri){
            $uri = $_SERVER['REQUEST_URI'];
        }
        $tmpUri = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = $uri;
        if(!$appId){
            $route = preg_match('%^\/([^\/]*)%', $uri, $m)?$m[1]:'/';
            $appId = Util::getItem(self::$routes, $route);
        }
        if($appId){
            $appInfo = Util::getItem(self::$applications, $appId);
            
//            Util::print_r($appInfo);
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
            
//            Util::print_r($application);

            self::$applications[$appId]['application'] = $application;

            global $wp_the_query;
                $the_q = $wp_the_query;
                try{
                $front = Util::getFront();
                $front->resetInstance();
                //print_r($front);
                $front->setParam('displayExceptions', true);
                $front->returnResponse(true);
                $application->bootstrap()->getBootStrap()->setupRouting();
                $r = $front->dispatch();
                $wp_the_query = $the_q;
                //$r = $application->bootstrap()->getBootStrap()->run();
            }catch(Exception $e){
                return $e->getMessage();
            }
            $_SERVER['REQUEST_URI'] = $tmpUri;
            
            return $r;        
        }
        
        return '';
        
    }

//    public static function themeRedirect() {
//        global $wp;
//        $plugindir = dirname( __FILE__ );
//        Log::func();
//            Log::dir($wp, 'wp');
//
//        //A Specific Custom Post Type
//        if ($wp->query_vars["post_type"] == 'zf') {
//            $templatefilename = Util::getItem($wp->query_vars, 'page_template',  'single-zf.php');
//            if (file_exists(TEMPLATEPATH . '/' . $templatefilename)) {
//                $return_template = TEMPLATEPATH . '/' . $templatefilename;
//            } else {
//                $return_template = $plugindir . '/' . $templatefilename;
//            }
//
//            global $post, $wp_query;
////            echo $return_template;
//            if (have_posts()) {
//                include($return_template);
//                die();
//            } else {
//                $wp_query->is_404 = true;
//            }
//        }
//    }
//    
    
    function copyFrom(WP_Query $wp_query) {
        $vars = get_object_vars($wp_query);
        foreach ($vars as $name => $value) {
            $this->$name = $value;
        }
    }

    function &get_posts() {
        global $wp_the_query;
        global $wp_query;
        
        $this->request = '';
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
                "post_type" => 'zf',
                "post_mime_type" => "",
                "comment_count" => "0",
                "ancestors" => array(),
                "filter" => "",
                "page_template" => WpHelper::getPageTemplate(),
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
//        Util::print_r($this->posts);
        return $this->posts;
    }
    
    public static function theContent($content){
//        print_r($content);
//        remove_filter ('the_content','wpautop'); echo "!";
        return $content;
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
    public static function locateTemplate($template_names, $load = false, $require_once = true ) {
        $located = '';
        foreach ((array) $template_names as $template_name) {
            if (!$template_name)
                continue;
            $dir = realpath(__DIR__ );
            if (file_exists($dir . '/' . $template_name)) {
                $located = $dir . '/' . $template_name;
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
    function getTemplatePart($slug, $name = null) {

        $templates = array();
        if (isset($name)){
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";

        self::locateTemplate($templates, true, false);
    }

    public static function singleTemplate($template){
//        echo 'single_template = '.$template;
	$object = get_queried_object();

	$templates = array();
//        Log::dir($object, 'get_queried_object');
        if(!empty($object->page_template)){
            $templates[] = $object->page_template;
        }
        if(!empty($object->_wp_page_template)){
            $templates[] = $object->_wp_page_template;
        }
	$templates[] = "single-{$object->post_type}.php";
	$templates[] = "single.php";
        return  self::locateTemplate($templates);
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

