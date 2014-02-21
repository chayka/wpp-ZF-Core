<?php

require_once 'Zend/Application.php';
//require_once 'wp-admin/includes/theme.php';

add_action('parse_request', array('ZF_Query', 'parseRequest'));

//Template fallback
//add_action("template_redirect", array('ZF_Query', 'themeRedirect'));
//add_filter('archive_template', array('ZF_Query', 'archiveTemplate'), 1, 2);
//add_filter('author_template', array('ZF_Query', 'authorTemplate'), 1, 2);
//add_filter('category_template', array('ZF_Query', 'categoryTemplate'), 1, 2);
//add_filter('tag_template', array('ZF_Query', 'tagTemplate'), 1, 2);
//add_filter('taxonomy_template', array('ZF_Query', 'taxonomyTemplate'), 1, 2);
add_filter('page_template', array('ZF_Query', 'pageTemplate'), 1, 2);
add_filter('single_template', array('ZF_Query', 'singleTemplate'), 1, 2);


class ZF_Query extends WP_Query {

//    public $req_uri_zf = '';


    protected static $applications = array();
    protected static $routes = array();
    protected static $forbiddenRoutes = array();
    protected static $widgets = array();
    
    protected static $_post = null;
    
    public static function registerApplication($id, $path, $routes, $env = ''){
        if(!$env){
            $env = OptionHelper::getOption('environment', 'production');
            //Util::isDevelopment()?'development':'production';
        }
        self::$applications[$id] = array(
            'path' => $path,
            'environment' => $env,
            'routes' => $routes,
        );
        
        foreach($routes as $route){
            self::$routes[$route] = $id;
        }
    
//        foreach($widgets as $widget){
//            self::$widgets[$widget] = $id;
//        }
    }
    
    public static function forbidRoute($mask, $id = null){
        if($id){
            self::$forbiddenRoutes[$id] = $mask;
        }else{
            self::$forbiddenRoutes[] = $mask;
        }
    }
    
    public static function forbidRoutes($routes){
        foreach($routes as $id=>$mask){
            if(is_numeric($id)){
                self::forbidRoute($mask);
            }else{
                self::forbidRoute($mask, $id);
            }
        }
    }
    
    public static function isForbiddenRoute($requestUri){
        $requestUri = preg_replace('%^\/(api|widget)?\/?%', '', $requestUri);
        foreach(self::$forbiddenRoutes as $mask){
            if(preg_match($mask, $requestUri)){
//                die('forbidden');
                return true;
            }
        }
        return false;
    }
    
    public static function parseRequest(){
        BlockadeHelper::inspectUri($_SERVER['REQUEST_URI']);

        if(isset($request->query_vars['error'])){
            unset($request->query_vars['error']);
        }
        
        parse_str($_SERVER['QUERY_STRING'], $params);
        $isForbidden = self::isForbiddenRoute($_SERVER['REQUEST_URI']);
        $isZF = isset(self::$routes['index']) 
                && (empty($_SERVER['REQUEST_URI']) || '/'==$_SERVER['REQUEST_URI'])
                || preg_match('%^\/((api|widget)\/)?('.  join('|', array_keys(self::$routes)).')(\/|\z)%',$_SERVER['REQUEST_URI'], $m)
                || $isForbidden;
        $isAPI = Util::getItem($m, 1, false);
                
        if(self::isForbiddenRoute($_SERVER['REQUEST_URI'])){
            $isZF = true;
        }
        
        if ($isZF) {
            if($isAPI){
                $uri = $isForbidden ? '/not-found-404/':preg_replace('%^\/(api|widget)%', '', $_SERVER['REQUEST_URI']);
                die(ZF_Query::processRequest($uri));
            }
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
        if(self::isForbiddenRoute($uri)){
            WpHelper::setNotFound(true);
            return '';
        }
        $tmpUri = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = $uri;
        if(!$appId){
            $route = preg_match('%^\/([^\/]+)%', $uri, $m)?$m[1]:'index';
            $appId = Util::getItem(self::$routes, $route);
        }
        if($appId){
            $appInfo = Util::getItem(self::$applications, $appId);
            
//            Util::print_r($appInfo);
            $app = Util::getItem($appInfo, 'application');
            
            $appPath = Util::getItem($appInfo, 'path');

//            $appEnv = getenv($appId.'_APPLICATION_ENV') ? 
//                getenv($appId.'_APPLICATION_ENV') : 
            $appEnv =  Util::getItem($appInfo, 'environment', 'production');

            if(!$app){
                
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
//                $config
                $appPath . '/configs/application.ini'
            );
            
//            Util::print_r($application);

//            self::$applications[$appId]['application'] = $application;

            global $wp_the_query;
            $the_q = $wp_the_query;
            try{
                $front = Util::getFront();
                $front->resetInstance();
                $front->setParam('displayExceptions', true);
                $front->returnResponse(true);
                $application->bootstrap()->getBootStrap()->run();
                $r = $front->dispatch().'';
                $wp_the_query = $the_q;
                unset($application);
                unset($front);
                //$r = $application->bootstrap()->getBootStrap()->run();
            }catch(Exception $e){
//                Util::print_r($e->getTraceAsString());
                return $e->getMessage();
            }
            $_SERVER['REQUEST_URI'] = $tmpUri;
            
            return $r;        
        }
        
        return '';
        
    }

    
    public function copyFrom(WP_Query $wp_query) {
        $vars = get_object_vars($wp_query);
        foreach ($vars as $name => $value) {
            $this->$name = $value;
        }
    }
    
    public static function setPost($post){
        if(!($post instanceof PostModel)){
            $post = PostModel::unpackDbRecord($post);
        }
        return self::$_post = $post;
    }
    
    /**
     * 
     * @return PostModel
     */
    public static function getPost(){
        if(!self::$_post){
            self::$_post = new PostModel();
            self::$_post->setStatus('publish');
            self::$_post->setCommentStatus('closed');
            self::$_post->setCommentCount(0);
            self::$_post->setPingStatus('closed');
        }
        return self::$post;
    }

    public static function setPostId($val){
        self::getPost()->setId($val);
    }
    
    public static function getPostId(){
        return self::getPost()->getId();
    }
    
    public static function setPostAuthor($val){
        self::getPost()->setUserId($val);
    }
    
    public static function getPostAuthor(){
        return self::getPost()->getUserId();
    }
    
    public static function setPostDate($val){
        self::getPost()->setDtCreated($val);
    }
    
    public static function getPostDate(){
        return self::getPost()->getDtCreated();
    }
    
    public static function setPostContent($val){
        self::getPost()->setContent($val);
    }
    
    public static function getPostContent(){
        return self::getPost()->getContent();
    }
    
    public static function setPostTitle($val){
        self::getPost()->setTitle($val);
    }
    
    public static function getPostTitle(){
        return self::getPost()->getTitle();
    }
    
    public static function setPostExcerpt($val){
        self::getPost()->setExcerpt($val);
    }
    
    public static function getPostExcerpt(){
        return self::getPost()->getExcerpt();
    }
    
    public static function setPostType($val){
        self::getPost()->setType($val);
    }
    
    public static function getPostType(){
        return self::getPost()->getType();
    }
    
    public static function setPostStatus($val){
        self::getPost()->setStatus($val);
    }
    
    public static function getPostStatus(){
        return self::getPost()->getStatus();
    }
    
    public static function setCommentStatus($val){
        self::getPost()->setCommentStatus($val);
    }
    
    public static function getCommentStatus(){
        return self::getPost()->getCommentStatus();
    }
    
    public function &get_posts() {
        global $wp_the_query;
        global $wp_query;
        
        $this->request = '';
        $zf_response = self::processRequest();
        if(WpHelper::getNotFound()){
            $zf_response = self::processRequest('/not-found-404/');
        }
        $posts = WpHelper::getPosts();
        if($posts){
            $wpq = WpHelper::getQuery();
            if($wpq){
                if($wpq instanceof WP_Query){
                    $this->copyFrom($wpq);
                }  elseif (is_array($wpq)) {
                    $this->query_vars = $this->fill_query_vars($wpq);
//                Util::print_r($wpq);
                }
            }
            
//            Util::print_r($this->query_vars);
            global $post;
            $post = reset($posts);
            $this->posts = $posts;
            $this->post = $post;
            $this->post_count = count($this->posts);
            $this->current_post = -1;

            $this->is_single = 0;
            $this->is_page = 0;
            $this->is_404 = WpHelper::getNotFound();
            $this->is_search = WpHelper::getIsSearch();
            $this->is_archive = WpHelper::getIsArchive();
            $this->is_home = 0;
//            Util::print_r($posts);
        }else{
//            echo $zf_response;
            $post_zf = array(
                "ID" => WpHelper::getPostId(),
                "post_author" => WpHelper::getPostAuthor(),
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
                "post_type" => WpHelper::getPostType(),
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

            global $post;
            $post = (object) $post_zf;
            
            $this->post = $post;
            if(!WpHelper::getNotFound()){
                $this->is_single = 1;
                $this->posts = array($post);
                $this->post_count = count($this->posts);
                $this->queried_object = $post;
                $this->queried_object_id = $post->ID;
            }else{
                $this->is_single = 0;
                $this->posts = array();
//                $this->post = null;
                $this->post_count = 0;
                $this->queried_object = null;
                $this->queried_object_id = 0;
            }
            $this->current_post = -1;

            $this->is_search = WpHelper::getIsSearch();
            $this->is_page = 0;
            $this->is_404 = WpHelper::getNotFound();
            $this->is_archive = WpHelper::getIsArchive();
            $this->is_home = 0;
            $this->comment = null;
            $this->comments = array();
            $this->comment_count = 0;
            $wp_the_query = $this;
        }
        

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
            $dir = realpath(ZF_CORE_PATH);
            foreach(self::$applications as $id => $app){
                $path = $app['path'].'/views/wordpress/';
                if (file_exists($path . $template_name)) {
                    $located = $path . $template_name;
                    break;
                }
            }
//            if (file_exists($dir . '/' . $template_name)) {
//                $located = $dir . '/' . $template_name;
//                break;
            if ($located) {
                break;
            } else if (file_exists(STYLESHEETPATH . '/' . $template_name)) {
                $located = STYLESHEETPATH . '/' . $template_name;
                break;
            } else if (file_exists(TEMPLATEPATH . '/' . $template_name)) {
                $located = TEMPLATEPATH . '/' . $template_name;
                break;
            }  
        }

        if ($load && '' != $located){
            load_template($located, $require_once);
        }
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
    public function getTemplatePart($slug, $name = null) {

        $templates = array();
        if (isset($name)){
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";

        self::locateTemplate($templates, true, false);
    }

    public static function archiveTemplate($template){
//        echo 'archive_template = '.$template;
	$post_type = get_query_var('post_type');
        
//        echo "pt: $post_type";
        $templates = array();

        if ($post_type) {
            $templates[] = "archive-{$post_type}.php";
        }
        $templates[] = 'archive.php';
        return self::locateTemplate($templates);
    }
    
    public static function authorTemplate($template){
//        echo 'author_template = '.$template;
	$author = get_queried_object();

	$templates = array();

	$templates[] = "author-{$author->user_nicename}.php";
	$templates[] = "author-{$author->ID}.php";
	$templates[] = 'author.php';
        return self::locateTemplate($templates);
    }
    public static function categoryTemplate($template){
//        echo 'category_template = '.$template;
	$category = get_queried_object();

	$templates = array();

	$templates[] = "category-{$category->slug}.php";
	$templates[] = "category-{$category->term_id}.php";
	$templates[] = 'category.php';
        return self::locateTemplate($templates);
    }
    public static function tagTemplate($template){
//        echo 'tag_template = '.$template;
	$tag = get_queried_object();

	$templates = array();

	$templates[] = "tag-{$tag->slug}.php";
	$templates[] = "tag-{$tag->term_id}.php";
	$templates[] = 'tag.php';
        return self::locateTemplate($templates);
    }
    public static function taxonomyTemplate($template){
//        echo 'taxonomy_template = '.$template;
        $term = get_queried_object();
	$taxonomy = $term->taxonomy;

	$templates = array();

	$templates[] = "taxonomy-$taxonomy-{$term->slug}.php";
	$templates[] = "taxonomy-$taxonomy.php";
	$templates[] = 'taxonomy.php';
        return self::locateTemplate($templates);
    }
    public static function pageTemplate($template){
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

        return  self::locateTemplate($templates);
    }

    public static function singleTemplate($template){
//        echo 'single_template = '.$template;
	$object = get_queried_object();

	$templates = array();
//        Log::dir($object, 'get_queried_object');
        if($object){
            if(!empty($object->page_template)){
                $templates[] = $object->page_template;
            }
            if(!empty($object->_wp_page_template)){
                $templates[] = $object->_wp_page_template;
            }
            $templates[] = "single-{$object->post_type}.php";
        }
	$templates[] = "single.php";
        return  self::locateTemplate($templates);
    }
}

//require_once 'widgets-ZF-Core.php';
