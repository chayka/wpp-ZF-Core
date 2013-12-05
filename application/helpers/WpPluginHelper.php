<?php

abstract class WpPlugin{
//    protected $plugin
    protected $needStyles = true;
    
    protected $currentDbVersion = '1.0';
    
    protected $consolePageUris = array();
    
    protected $metaBoxUris = array();
    
    protected $baseUrl;
    
    protected $basePath;
    
    protected $appId;
    
    public function __construct($__file__, $routes = array()) {
        $this->basePath = plugin_dir_path( $__file__ );
        $this->baseUrl = preg_replace('%^[\w\d]+\:\/\/[\w\d\.]+%', '',plugin_dir_url($__file__));
        $this->appId = strtoupper($this->getClassName());
        defined($this->appId.'_PATH') 
            || define($this->appId.'_PATH', $this->basePath);
        defined($this->appId.'_URL') 
            || define( $this->appId.'_URL',  $this->baseUrl);
//        Util::print_r($this->basePath().'res/css');
        LessHelper::addImportDir($this->getBasePath().'res/css');
        $minimize = OptionHelper::getOption('minimizeMedia');
        $this->registerResources($minimize);
        $this->registerRoutes($routes);
        $this->registerCustomPostTypes();
        $this->registerTaxonomies();
        $this->registerSidebars();
        $this->registerActions();
        $this->registerFilters();
    }

    public static abstract function getInstance();

    public function getAppId(){
        return $this->appId;
    }

    public function getClassName(){
        return get_class($this);
    }
    
    public function getCallbackMethod($method){
        return array($this, $method);
    }
    
    public function getBasePath(){
        return $this->basePath;
    }
    
    public function getBaseUrl(){
        return $this->baseUrl;
    }
    
    public static abstract function baseUrl();
    
    /**
     * 
     * @param type $path
     * @return type
     */
    public function getUrl($path = ''){
        return $this->getBaseUrl().$path;
    }
    
    public function getUrlCss($relativeResCssPath){
        return $this->getUrl('res/css/'.$relativeResCssPath);
    }
    
    public function getUrlImg($relativeResImgPath){
        return $this->getUrl('res/img/'.$relativeResImgPath);
    }
    
    public function getUrlJs($relativeResJsPath){
        return $this->getUrl('res/js/'.$relativeResJsPath);
    }

    public function registerRoutes($routes){
        ZF_Query::registerApplication($this->getAppId(), $this->getBasePath().'application', $routes);
    }
    
    /**
     * DB Version history
     * @param array(string) $versionHistory
     */
    public function dbUpdate($versionHistory = array('1.0')){
        $this->currentDbVersion = end($versionHistory);
        reset($versionHistory);
        WpDbHelper::dbUpdate($this->currentDbVersion, strtolower($this->getClassName()).'_db_version', $this->getBasePath().'res/sql', $versionHistory);
    }

    abstract public static function init();
    
    abstract public function registerCustomPostTypes();
    
    abstract public function registerTaxonomies();
    
    abstract public function registerSidebars();
    
    public function registerSidebar($name, $id){
        register_sidebar(array('name' => $name, 'id'=>$id));
    }
    
    /**
     * You need to implement savePost(), deletePost() and trashedPost()
     * 
     * @param type $priority
     */
    public function addSupport_PostProcessing($priority = 100){
        $this->addAction('save_post', 'savePost', $priority, 2);
        $this->addAction('delete_post', 'deletePost', $priority, 2);
        $this->addAction('trashed_post', 'trashedPost', $priority, 2);
    }
    
    /**
     * 
     * @param integer $postId
     * @param WP_Post $post
     */
    public function savePost($postId, $post){
        
    }
    
    /**
     * 
     * @param integer $postId
     * @param WP_Post $post
     */
    public function deletePost($postId, $post){
        
    }
    
    /**
     * 
     * @param integer $postId
     * @param WP_Post $post
     */
    public function trashedPost($postId, $post){
        $this->deletePost($postId, $post);
    }

    /**
     * You need to implement postPermalink() and termLink() methods
     */
    public function addSupport_CustomPermalinks(){
        $this->addFilter('post_type_link', 'postPermalink', 1, 3);
        $this->addFilter('post_link', 'postPermalink', 1, 3);
        $this->addFilter('term_link', 'termLink', 1, 3);
        $this->addFilter('author_link', 'userLink', 1, 3);
        $this->addFilter('get_comment_link', 'commentPermalink', 1, 2);
    }
    
    /**
     * 
     * @param string    $permalink
     * @param WP_Post   $post
     * @param boolean   $leavename
     * @return string
     */
    public function postPermalink($permalink, $post, $leavename = false){
        switch($post->post_type){
            case 'post':
                return '/entry/'.$post->ID.'/'.($leavename?'%postname%':$post->post_name);
            default:
                return $permalink;
        }
        return $permalink;
    }
    
    /**
     * 
     * @param string $link
     * @param WP_Term $term
     * @param string $taxonomy
     * @return string
     */
    public function termLink($link, $term, $taxonomy){
        return $link;
    }

    /**
     * 
     * @param string $link
     * @param integer $userId
     * @param string $nicename
     * @return string
     */
    public function userLink($link, $userId, $nicename){
        return sprintf('/user/%s/', $nicename);
    }
    
    public function commentPermalink($permalink, $comment){
        return $permalink;
    }
//    abstract public static function enableSearch($query);
//    
//    abstract public static function luceneReadyPost($item, $post);
//    
//    abstract public static function searchResult($post);
//    
    abstract public static function blockStyles($block = true);
    
    abstract public function registerResources($minimize = false);
    
    public  function registerStyle($handle, $relativeResCssPath, $dependencies = array()){
        wp_register_style($handle, $this->getUrlCss($relativeResCssPath), $dependencies);
    }
    
    public function registerScript($handle, $relativeResJsPath, $dependencies = array()){
        wp_register_script($handle, $this->getUrlJs($relativeResJsPath), $dependencies);
    }
    
    public function registerScriptNls($handle, $relativeResJsPath, $dependencies = array()){
        NlsHelper::registerScriptNls($handle, $relativeResJsPath, $dependencies, null, null, $this->basePath);
    }
    
    abstract public function registerActions();
    
    public function addAction($action, $method, $priority = 10, $numberOfArguments = 1){
        return add_action($action, is_array($method)?$method:$this->getCallbackMethod($method), $priority, $numberOfArguments);
    }
    
    abstract public function registerFilters();
    
    public function addFilter($filter, $method, $priority = 10, $numberOfArguments = 1){
        return add_filter($filter, is_array($method)?$method:$this->getCallbackMethod($method), $priority, $numberOfArguments);
    }
    
    public function processRequest($requestUri){
        return ZF_Query::processRequest($requestUri, $this->getAppId());
    }
    
    public function renderRequest($requestUri){
        echo $this->processRequest($requestUri);
    }
    
    /**
     * You should implement registerConsolePages();
     */
    public function addSupport_ConsolePages(){
        $this->addAction('admin_menu', 'registerConsolePages');
    }
    
    /**
     * Override to add addConsolePage() calls
     */
    public function registerConsolePages(){
        
    }
    
    public function renderConsolePage(){
        $page = Util::getItem($_GET, 'page');
        $requestUri = Util::getItem($this->consolePageUris, $page);
        
        $this->renderRequest($requestUri);
    }

    public function addConsolePage($pageTitle, $menuTitle, $capapbility, $menuSlug, 
        $renderUri='', $relativeResImgIconUrl='', $position=null){

        $this->consolePageUris[$menuSlug] = $renderUri;

        add_menu_page($pageTitle, $menuTitle, $capapbility, $menuSlug, 
            $this->getCallbackMethod('renderConsolePage'), 
            $relativeResImgIconUrl?$this->getUrlImg($relativeResImgIconUrl):'', $position); 
    }

    public function addConsoleSubPage($parentSlug, $pageTitle, $menuTitle, 
            $capapbility, $menuSlug, $renderUri=''){

        $this->consolePageUris[$menuSlug] = $renderUri;
        
        add_submenu_page($parentSlug, $pageTitle, $menuTitle, $capapbility, $menuSlug, 
            $this->getCallbackMethod('renderConsolePage'));
    }
    
    /**
     * You should implement registerMetaBoxes();
     */
    public function addSupport_Metaboxes(){
        $this->addAction('add_meta_boxes', 'addMetaBoxes');
        $this->addAction('save_post', 'updateMetaBoxes', 50, 2);
        $this->registerMetaBoxes();
    }
    
    /**
     * Override to add addMetaBox() calls;
     */
    public function registerMetaBoxes(){
        
    }
    
    public function renderMetaBox($post, $box){
        $boxId = Util::getItem($box, 'id');
        $params = Util::getItem($this->metaBoxUris, $boxId, array());
        $requestUri = Util::getItem($params, 'renderUri');
        $this->renderRequest($requestUri);
    }

    /**
     * 
     * @param integer $postId
     * @param WP_Post $post
     */
    public function updateMetaBoxes($postId, $post){
        foreach($this->metaBoxUris as $id=>$uri){
            ZF_Query::processRequest('/metabox/update/'.$id, 'ZF_CORE');
        }
    }
    
    /**
     * Add Metabox
     * 
     * @param string $id
     * @param string $title
     * @param string $renderUri
     * @param string $context 'normal', 'advanced', or 'side'
     * @param string $priority 'high', 'core', 'default' or 'low'
     * @param string $screen post type
     */
    public function addMetaBox($id, $title, $renderUri, $context = 'advanced', $priority = 'default', $screen = null){
        
//        $this->metaBoxUris[$id] = $renderUri;
        $this->metaBoxUris[$id] = array(
            'title' => $title,
            'renderUri' => $renderUri,
            'context' => $context,
            'priority' => $priority,
            'screen' => $screen,
        );
        
    }
    
    public function addMetaBoxes(){
        foreach($this->metaBoxUris as $id => $params){
            $title = Util::getItem($params, 'title');
            $context = Util::getItem($params, 'context');
            $priority = Util::getItem($params, 'priority');
            $screens = Util::getItem($params, 'screen');
            if(is_array($screens)){
                foreach($screens as $screen){
                    add_meta_box($id, $title, $this->getCallbackMethod('renderMetaBox'), $screen, $context, $priority);
                }
            }else{
                add_meta_box($id, $title, $this->getCallbackMethod('renderMetaBox'), $screens, $context, $priority);
            }
        }
        
        wp_enqueue_style('brx-wp-admin');
    }

}

abstract class WpTheme extends WpPlugin{
    
    protected $excerptLength = 30;
    protected $excerptMore = '...';
    protected $adminBar = true;
    
    public function __construct($routes = array()) {
        $this->basePath = TEMPLATEPATH.'/';
        $this->baseUrl = preg_replace('%^[\w\d]+\:\/\/[\w\d\.]+%', '', get_bloginfo('template_url', 'display' )).'/';
        $this->appId = strtoupper($this->getClassName());
        defined($this->appId.'_PATH') 
            || define($this->appId.'_PATH', $this->basePath);
        defined($this->appId.'_URL') 
            || define( $this->appId.'_URL',  $this->baseUrl);
//        Util::print_r($this->basePath().'res/css');
        LessHelper::addImportDir($this->getBasePath().'res/css');
        $minimize = OptionHelper::getOption('minimizeMedia');
        $this->registerResources($minimize);
        $this->registerRoutes($routes);
        $this->registerCustomPostTypes();
        $this->registerTaxonomies();
        $this->registerSidebars();
        $this->registerActions();
        $this->registerFilters();
        $this->registerNavMenus();
        $this->addFilter('wp_nav_menu_objects', 'customizeNavMenuItems', 1, 2);
        $this->addFilter('show_admin_bar', 'isAdminBarShown', 1, 1);
    }
    
    abstract public function registerNavMenus();
    
    public function registerNavMenu($location, $description){
        register_nav_menu($location, $description);
    }
    
    public function customizeNavMenuItems($items, $args){
        return $items; 
    }

    public function addSupport_Thumbnails($width = 0, $height = 0, $crop = false){
        add_theme_support( 'post-thumbnails' );
        set_post_thumbnail_size($width, $height, $crop);
    }
    
    public function addSupport_Excerpt($length = 30, $more = '...'){
        $this->excerptLength = $length;
        $this->excerptMore = $more;
        $this->addFilter('excerpt_length', 'excerptLength', 1, 1);
        $this->addFilter('excerpt_more', 'excerptMore');
    }

    public function excerptLength(){
        return $this->excerptLength;
    }
    
    public function excerptMore(){
        return $this->excerptMore;
    }
    
    public function showAdminBar($show = true){
        $this->adminBar = $show;
    }
    
    public function hideAdminBar(){
        $this->adminBar = false;
    }
    
    public function showAdminBarToAdminOnly(){
        $this->adminBar = 'admin';
    }
    
    public function isAdminBarShown($show){
        if($this->adminBar){
            if($this->adminBar == 'admin'){
                return current_user_can('administrator') || is_admin();
            }
            
            return true;
        }
        
        return false;
    }
    
}

