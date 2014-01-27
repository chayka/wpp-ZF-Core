<?php

require_once 'WpPlugin.php';

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


