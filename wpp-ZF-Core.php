<?php

/*
  Plugin Name: wpp-ZF-Core
  Description: Integration of Zend Framework into Wordpress - this plugin makes the Zend Framework library available to Wordpress themes and plugins.
  Author: Boris Mossounov
  Version: 1.0

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

//////////////////////////////////////////////////////////////////////
// This section emulates the Zend Framework bootstrap file, without any application environment

require_once 'ZF-Query.php';
require_once 'library/ZendB/Util.php';
require_once 'application/helpers/WpPluginHelper.php';

class ZF_Core extends WpPlugin{
    public static $zfCoreTree;
    
    public static $adminBar = false;
    
    public static $instance = null;
    
    const POST_TYPE_CONTENT_FRAGMENT = 'content-fragment';
    const TAXONOMY_CONTENT_FRAGMENT_TAG = 'content-fragment-tag';
    
    public static function init(){
        
//        self::registerActions();
//        self::registerFilters();

        try {
            $pluginDir = plugin_dir_path( __FILE__ );
            defined('ZF_CORE_APPLICATION_PATH') 
                || define('ZF_CORE_APPLICATION_PATH', realpath($pluginDir . '/application'));
            defined('ZF_CORE_PATH') 
                || define('ZF_CORE_PATH', $pluginDir);
            defined('ZF_CORE_URL') 
                || define( 'ZF_CORE_URL', preg_replace('%^[\w\d]+\:\/\/[\w\d\.]+%', '',plugin_dir_url(__FILE__)) );
            // Add /library directory to our include path
//            die(ZF_CORE_URL);
            set_include_path(implode(PATH_SEPARATOR, array(
                get_include_path(), 
                realpath($pluginDir . '/library'),
                ZF_CORE_APPLICATION_PATH,
                realpath($pluginDir),
                )));
//            die( get_include_path());
            // Turn on autoloading, so we do not include each Zend Framework class
            require_once 'Zend/Loader/Autoloader.php';
            $autoloader = Zend_Loader_Autoloader::getInstance();
            spl_autoload_register(array('ZF_Core', 'autoloader'));

            FirebugHelper::getInstance();
            
            // Create registry object and setting it as the static instance in the Zend_Registry class
            $registry = new Zend_Registry();
            Zend_Registry::setInstance($registry);

            // Load configuration file and store the data in the registry
            $configuration = new Zend_Config_Ini($pluginDir . '/application/configs/application.ini', Util::isDevelopment()?'development':'production');
            Zend_Registry::set('configuration', $configuration);

            /*
             * We want to set the encoding to UTF-8, so we won't rely on the ViewRenderer action helper by default,
             * but will construct view object and deliver it to the ViewRenderer after setting some options.
             */
            $view = new Zend_View(array('encoding' => 'UTF-8'));
            $viewRendered = new Zend_Controller_Action_Helper_ViewRenderer($view);
            Zend_Controller_Action_HelperBroker::addHelper($viewRendered);

            // if everything went well, set a status flag
            define('WP_ZEND_LIBRARY', TRUE);

            require_once 'application/helpers/LessHelper.php';
            
//            self::registerResources($minimize = false);
            
//            self::registerCustomPostTypeContentFragment();
            
            require_once 'application/helpers/WpDbHelper.php';

        } catch (Exception $e) {
            // try/catch works best in object mode (which we cannot use here), so not all errors will be caught
            echo '<span style="font-weight:bold;">WP Zend Library:</span> ' . nl2br($e);
        }

        //require_once 'ZendB/Log.php';
        Log::setDir(ZF_CORE_PATH.'logs');
        LessHelper::setImportDir(ABSPATH);
//        LessHelper::addImportDir(ZF_CORE_PATH.'res/css');
//        Log::start();
//        require_once 'ZF-Query.php';
        self::$instance = $plugin = new ZF_Core(__FILE__, array(
            'admin', 'autocomplete', 'upload',
            'post-model',
            'comment-model',
            'user-model',
            'social', 'zf-setup',
            'timezone',
            'options',
            'blockade',
        ));
        
        $plugin->addSupport_ConsolePages();
        $plugin->addSupport_Metaboxes();
        $plugin->addSupport_PostProcessing();
        

//        ZF_Query::registerApplication('ZF_CORE', ZF_CORE_APPLICATION_PATH, array(
//            'admin', 'autocomplete', 'upload',
//            'post-model',
//            'comment-model',
//            'user-model',
//            'social', 'zf-setup',
//            'timezone',
//            'options',
//            'blockade',
//        ));

        
    }
    
    public function thisPluginGoesFirst() {
        // ensure path to this file is via main wp plugin path
        $wpPathToThisFile = preg_replace('/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR . "/$2", __FILE__);
        $thisPlugin = plugin_basename(trim($wpPathToThisFile));
        $activePlugins = get_option('active_plugins');
        $thisPluginKey = array_search($thisPlugin, $activePlugins);
        if ($thisPluginKey) { // if it's 0 it's the first plugin already, no need to continue
            array_splice($activePlugins, $thisPluginKey, 1);
            array_unshift($activePlugins, $thisPlugin);
            update_option('active_plugins', $activePlugins);
        }
    }

    public static function getClassTree($path){
        if (is_file($path)) {
            return preg_match('%([\w_]+)\.php%', $path, $matches)? 
                    array($matches[1] => $path): array();
        } elseif (is_dir($path)) {
            $path = preg_replace("%/$%", '', $path);
            $d = dir($path);

            $map = array();
            while ($file = $d->read()) {
                if ($file == "." || $file == "..") {
                    continue;
                }

                $map = array_merge($map, self::getClassTree("$path/$file"));
            }
            return $map;

        }
        return array();
    }

    public static function autoloader($class) {
    //    echo "zfCoreAutoloader($class)";
        if(false && strpos($class, 'Helper')){
            include_once 'application/helpers'.PATH_SEPARATOR.$class.'.php'; 
        }else{
//            global $zfCoreTree;
            if(empty(self::$zfCoreTree)){
                self::$zfCoreTree = self::getClassTree(realpath( ZF_CORE_APPLICATION_PATH));
                self::$zfCoreTree = array_merge(self::$zfCoreTree, self::getClassTree(realpath(ZF_CORE_PATH . '/library/ZendB')));
//                print_r(self::$zfCoreTree);die(realpath(ZF_CORE_PATH . '/library/ZendB'));
            }
            if(isset(self::$zfCoreTree[$class])){
                include_once self::$zfCoreTree[$class]; 
    //            echo " Class $class now ".(class_exists($class)?'exists':'does not exist');
            }
        }

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
    
    public function autoSlug($post){
        if(!$post['post_name'] && $post['post_status']=='draft'){
            $post['post_name'] = self::slug($post['post_title']);
        }else{
            $post['post_name'] = self::slug(urldecode($post['post_name']));
        }
        return $post;
    }    

    public static function registerCustomPostTypeContentFragment() {
        $labels = array(
            'name' => _x('Content fragment', 'post type general name'),
            'singular_name' => _x('Content fragment', 'post type singular name'),
            'add_new' => _x('Add fragment', 'item'),
            'add_new_item' => __('Add fragment'),
            'edit_item' => __('Edit fragment'),
            'new_item' => __('New fragment'),
            'all_items' => __('All fragments'),
            'view_item' => __('View fragment'),
            'search_items' => __('Search fragments'),
            'not_found' => __('No fragments found'),
            'not_found_in_trash' => __('No deleted fragments found'),
            'parent_item_colon' => 'Parent fragment:',
            'menu_name' => __('Content fragments')
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
//            'query_var' => true,
//            'rewrite' => array('slug' => 'fragment'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => true,
            'menu_position' => 20,
            'taxonomies' => array(
                self::TAXONOMY_CONTENT_FRAGMENT_TAG
            ),
            'supports' => array(
                'title', 
                'editor', 
//                'author', 
                'thumbnail', 
                'excerpt',
                'page-attributes'
//                'comments',
                )
        );
        register_post_type(self::POST_TYPE_CONTENT_FRAGMENT, $args);
        self::registerTaxonomyContentFagmentTag();
        self::getInstance()->addAction('add_meta_boxes', 'addMetaBoxContentFragment' );
//        add_action('add_meta_boxes', array('ZF_Core', 'addMetaBoxContentFragment') );
//        add_action('save_post', array('ZF_Core', 'savePost'), 10, 2);
    }
    
    public function addMetaBoxContentFragment(){
//        add_meta_box( 
//            'content_fragment_metabox',
//            'Advanced',
//            array('ZF_Core', 'renderMetaBoxContentFragment'),
//            self::POST_TYPE_CONTENT_FRAGMENT,
//            'normal',
//            'high'
//        );
        $this->addMetaBox( 
            'content_fragment_metabox',
            'Advanced',
            '/admin/content-fragment-metabox',
            'normal',
            'high',
            self::POST_TYPE_CONTENT_FRAGMENT
        );
        
    }
    
//    public static function renderMetaBoxContentFragment(){
//        echo ZF_Query::processRequest('/admin/content-fragment-metabox', 'ZF_CORE');
//    }
    
    public function savePost($postId, $post){
        switch($post->post_type){
            case self::POST_TYPE_CONTENT_FRAGMENT:
                ZF_Query::processRequest('/admin/update-content-fragment/post_id/'.$postId, 'ZF_CORE');
                break;
        }
    }
    
    public static function registerTaxonomyContentFagmentTag(){
        $labels = array(
            'name' => _x('Fragment Tags', 'taxonomy general name'),
            'singular_name' => _x('Fragment Tag', 'taxonomy singular name'),
            'search_items' => __('Search tags'),
            'all_items' => __('All tags'),
            'edit_item' => __('Edit'),
            'update_item' => __('Update'),
            'add_new_item' => __('Add tag'),
            'new_item_name' => __('New tag name'),
            'menu_name' => __('Fragment Tags'),
        );

        register_taxonomy(self::TAXONOMY_CONTENT_FRAGMENT_TAG, 
                array(
                    self::POST_TYPE_CONTENT_FRAGMENT,
                ), 
                array(
                    'hierarchical' => false,
                    'labels' => $labels,
                    'show_ui' => true,
                    'query_var' => true,
                    'show_admin_column' => true,
                    'rewrite' => array('slug' => self::TAXONOMY_CONTENT_FRAGMENT_TAG),
                ));
    }

    public function registerActions(){
        $this->addAction('activated_plugin', 'thisPluginGoesFirst');
//        add_action("activated_plugin", array("ZF_Core", "thisPluginGoesFirst"));
//        add_action('admin_menu', array('ZF_Core', 'registerConsolePages'));
        $this->addAction('wp_footer', 'addJQueryWidgets', 100);
//        add_action('wp_footer', array('ZF_Core', 'addJQueryWidgets'), 100);
        Util::sessionStart();
        if(empty($_SESSION['timezone'])){
//            add_action('wp_footer', array('ZF_Core', 'fixTimezone'));
            $this->addAction('wp_footer', 'fixTimezone');
        }
        
    }
    
    public function registerFilters(){
        $this->addFilter('wp_insert_post_data', 'autoSlug', 10, 1 );
        
    }
    
    public function registerResources($minimize = false){

        $isAdminPost = is_admin() && (strpos($_SERVER['REQUEST_URI'], 'post.php'));

        $this->registerScript( 'Underscore', ($minimize?'vendors/underscore.min.js':'vendors/underscore.js'), array('jquery'));
        $this->registerScript( 'Backbone', ($minimize?'vendors/backbone.min.js':'vendors/backbone.js'), array('jquery', ($isAdminPost?'underscore': 'Underscore')));
        $this->registerScript( 'nls', 'vendors/nls.js', array(($isAdminPost?'underscore': 'Underscore')));

        $this->registerScript( 'require', ($minimize?'vendors/require.min.js':'vendors/require.js'));

        // dropkick.js
        $this->registerStyle( 'jquery-dropkick', 'vendors/jquery.dropkick-1.0.0.less', array());
        $this->registerScript( 'jquery-dropkick', 'vendors/jquery.dropkick-1.0.0.js', array('jquery'));

        // moment.js
        $lang = NlsHelper::getLang();
        $this->registerScript( 'moment-base', ($minimize?'vendors/moment/min/moment.min.js':'vendors/moment/moment.js'), array());
//        $this->registerScript( 'moment-lang', ($minimize?'vendors/moment/min/lang/'.$lang.'.js':'vendors/moment/lang/'.$lang.'.js'), array());

        $diskFile = ZF_CORE_PATH.'res/js/vendors/moment/lang/'.$lang.'.js';

        if($lang!='en' && file_exists($diskFile)){
            $this->registerScript( 'moment', ($minimize?'vendors/moment/min/lang/'.$lang.'.js':'vendors/moment/lang/'.$lang.'.js'), array('moment-base'));
        }else{
            $this->registerScript( 'moment', ($minimize?'vendors/moment/min/moment.min.js':'vendors/moment/moment.js'));
        }
        
        
        $this->registerScript( 'jquery-ui-templated', 'jquery.ui.templated.js', array('jquery-ui-core', 'jquery-ui-dialog','jquery-ui-widget', 'jquery-brx-utils', 'moment'));
        
        $this->registerScript( 'underscore-brx', 'underscore.brx.js', array(($isAdminPost?'underscore':'Underscore')));
        $this->registerScript( 'backbone-brx', 'backbone.brx.js', array(($isAdminPost?'backbone':'Backbone'), 'underscore-brx', 'nls', 'moment'));

        $this->registerScript( 'backbone-wp-models', 'backbone.wp.models.js', array('backbone-brx'));
        $this->registerScript( 'backbone-brx-pagination', 'backbone.brx.Pagination.view.js', array('backbone-brx'));

        $this->registerScript( 'jquery-ajax-uploader', 'vendors/jquery.ajaxfileupload.js', array('jquery'));
        $this->registerScript( 'jquery-ajax-iframe-uploader', 'vendors/jquery.iframe-post-form.js', array('jquery'));
        $this->registerScript( 'jquery-galleria', 'vendors/galleria/galleria-1.2.8.min.js', array('jquery'));
        $this->registerScript( 'jquery-masonry', 'vendors/jquery.masonry.min.js', array('jquery'));

        $this->registerScript( 'jquery-brx-utils', 'jquery.brx.utils.js', array('jquery',  'nls'));
        $this->registerScript( 'jquery-brx-placeholder', 'jquery.brx.placeholder.js', array('jquery', 'jquery-ui-templated', 'jquery-brx-utils'));
        $this->registerStyle( 'jquery-brx-spinner', 'jquery.brx.spinner.css');
        $this->registerScript( 'jquery-brx-spinner', 'jquery.brx.spinner.js', array('jquery-ui-templated'));
        $this->registerScript( 'jquery-brx-modalBox', 'jquery.brx.modalBox.js', array('jquery-ui-dialog'));
        $this->registerScript( 'jquery-brx-form', 'jquery.brx.form.js', array('jquery-ui-templated','jquery-brx-spinner', 'jquery-brx-placeholder', 'jquery-ui-autocomplete'));
        $this->registerScript( 'jquery-brx-setupForm', 'jquery.brx.setupForm.js', array('jquery-brx-form'));

        $this->registerScript( 'backbone-brx-spinners', 'brx.spinners.view.js', array('backbone-brx'));
        $this->registerStyle( 'backbone-brx-spinners', 'brx.spinners.view.less');
        
        $this->registerStyle( 'backbone-brx-modals', 'brx.modals.view.less', array());
        $this->registerScript( 'backbone-brx-modals', 'brx.modals.view.js', array('jquery-ui-dialog', 'backbone-brx'));
        $this->registerScript( 'backbone-brx-optionsForm', 'brx.OptionsForm.view.js', array('backbone-brx'));
        $this->registerScript( 'backbone-brx-jobControl', 'brx.JobControl.view.js', array('backbone-brx', 'jquery-ui-progressbar', 'backbone-brx-spinners'));
        $this->registerStyle( 'backbone-brx-jobControl', 'brx.JobControl.view.less', array('backbone-brx-spinners'));
        $this->registerScript( 'backbone-brx-attachmentPicker', 'brx.AttachmentPicker.view.js', array('backbone-brx', 'backbone-brx-spinners', 'jquery-ajax-iframe-uploader'));
        $this->registerStyle( 'backbone-brx-attachmentPicker', 'brx.AttachmentPicker.view.less', array('backbone-brx-spinners'));
        $this->registerStyle('backbone-brx-taxonomyPicker', 'brx.TaxonomyPicker.view.less');
        $this->registerScript('backbone-brx-taxonomyPicker', 'brx.TaxonomyPicker.view.js', array('jquery-brx-placeholder','backbone-brx'));
        $this->registerStyle('backbone-brx-ribbonSlider', 'brx.RibbonSlider.view.less');
        $this->registerScript('backbone-brx-ribbonSlider', 'brx.RibbonSlider.view.js', array('backbone-brx'));

        $this->registerScript('google-youtube-loader', 'google.YouTube.ApiLoader.js', array('backbone-brx'));
        
        $this->registerStyle( 'admin-setupForm', 'bem-admin_setup_form.less');
        $this->registerScript( 'jquery-ui-datepicker-ru', 'jquery.ui.datepicker-ru.js');
        $this->registerScript( 'jquery-ui-progressbar', 'jquery.ui.progressbar.js');
        $this->registerScript( 'bootstrap', ($minimize?'vendors/bootstrap.min.js':'vendors/bootstrap.js'), array('jquery'));
        $this->registerStyle( 'bootstrap', ($minimize?'bootstrap.min.css':'bootstrap.css'));
        $this->registerStyle( 'bootstrap-responsive', ($minimize?'bootstrap-responsive.min.css':'bootstrap-responsive.css'));


        $this->registerStyle( 'normalize', 'normalize.css');
        
        $this->registerScript( 'modenizr', 'vendors/modernizr-2.6.2.min.js');

        $this->registerScript( 'jquery-scrolly', 'vendors/jquery.scrolly.js', array('jquery', 'underscore-brx'));

        $jQueryThemes = array(
            'black-tie',
            'blitzer',
            'cupertino',
            'dark-hive',
            'darkness',
            'dot-luv',
            'egg-plant',
            'excite-bike',
            'flick',
            'hot-sneaks',
            'humanity',
            'le-frog',
            'lightness',
            'mint-choc',
            'overcast',
            'pepper-grinder',
            'redmond',
            'smoothness',
            'south-street',
            'start',
            'sunny',
            'swanky-purse',
            'trontastic',
            'vader',
        );
        
        foreach($jQueryThemes as $theme){
            $this->registerStyle( 'jquery-ui-'.$theme, self::getJQueryUIThemeCss($theme));
        }
        $this->registerStyle( 'jquery-ui', self::getJQueryUIThemeCss());

    }

    public static function getJQueryUIThemeCss($theme = ''){
        if(!$theme){
            $theme = OptionHelper::getOption('jQueryUI.theme', 'smoothness');
        }
        $minimize = true;
        $themeCss = $minimize?
            'jquery-ui-1.9.2.custom.min.css':
            'jquery-ui-1.9.2.custom.css';
        $themeUrl = 'custom' == $theme? 
            '/wp-content/'.OptionHelper::getOption('jQueryUI.themeUrl'):
            sprintf('jquery-ui/%s/%s', $theme, $themeCss);
        return $themeUrl;
    }

    public function registerConsolePages() {
        $this->addConsolePage('ZF Core', 'ZF Core', 'update_core', 'zf-core-admin', '/admin/');

        $this->addConsoleSubPage('zf-core-admin', 
                'jQueryUI theme', 'jQueryUI theme', 'update_core', 'zf-core-jqueryui-theme', '/admin/jquery-ui-theme');
        
        $this->addConsoleSubPage('zf-core-admin', 
                'phpinfo()', 'phpinfo()', 'update_core', 'zf-core-phpinfo', 
                '/admin/phpinfo'); 

        $this->addConsoleSubPage('zf-core-admin', 
                'WP Hooks', 'WP Hooks', 'update_core', 'zf-core-wp-hooks', 
                '/admin/wp-hooks', '', null); 

        $this->addConsoleSubPage('zf-core-admin', 
                'E-mail', 'E-mail settings', 'update_core', 'zf-core-email', 
                '/admin/email-options', '', null); 

        $this->addConsoleSubPage('zf-core-admin', 
                'Blockade', 'Blockade', 'update_core', 'zf-core-blockade', 
                '/admin/blockade-options', '', null); 
    }

    
    public function addJQueryWidgets(){
        wp_enqueue_style('jquery-ui');
//        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-effects-fade');
        wp_enqueue_script('jquery-effects-drop');
        wp_enqueue_script('jquery-effects-blind');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-templated');
        wp_enqueue_script('jquery-brx-placeholder');
        wp_enqueue_script('jquery-brx-modalBox');
        wp_enqueue_style('jquery-brx-spinner');
        wp_enqueue_script('jquery-brx-spinner');
//        wp_enqueue_script('Backbone');
        wp_enqueue_script('backbone-wp-models');
        wp_enqueue_style('backbone-brx-modals');
        wp_enqueue_script('backbone-brx-modals');
        wp_print_scripts();
        wp_print_styles(); 
        
        $options = array(
            'uiFramework' =>is_admin()?'jQueryUI':get_site_option('ZfCore.uiFramework', '')
        );
                
        ?>
                    
        <div widget="generalSpinner"></div>
        <div widget="modalBox"></div>    
        <script>
        jQuery(document).ready(function($) {
            $.declare('brx.options.ZfCore', <?php echo JsonHelper::encode($options)?>);
            $.ui.parseWidgets('<?php echo ZF_CORE_URL?>res/js/');
            if($.brx && $.brx.parseBackboneViews){
                $.brx.parseBackboneViews();
            }
        });        
        </script>
                    
        <?php
    }
    
    public function fixTimezone(){
        if(1):?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
//                var visitortime = new Date();
//                var visitortimezone = "GMT " + -visitortime.getTimezoneOffset()/60;
                $.ajax({
                    type: "GET",
                    url: "/api/timezone/",
                    data: 'offset='+ (-(new Date()).getTimezoneOffset()/60),
                    success: function(){
//                        location.reload();
                    }
                });
            });
        </script>
        <?php endif;
    }
    
    /** deprecated **/
    public static function showAdminBar($show = true){
        self::$adminBar = $show;
        add_filter('show_admin_bar', array('ZF_Core', 'isAdminBarShown'), 1, 1);
    }
    
    /** deprecated **/
    public static function hideAdminBar(){
        self::$adminBar = false;
        add_filter('show_admin_bar', array('ZF_Core', 'isAdminBarShown'), 1, 1);
    }
    
    /** deprecated **/
    public static function showAdminBarToAdminOnly(){
        self::$adminBar = 'admin';
        add_filter('show_admin_bar', array('ZF_Core', 'isAdminBarShown'), 1, 1);
    }
    
    /** deprecated **/
    public static function isAdminBarShown($show){
        if(self::$adminBar){
            if(self::$adminBar == 'admin'){
                return current_user_can('administrator') || is_admin();
            }
            
            return true;
        }
        
        return false;
    }

    public function registerCustomPostTypes() {
        
    }

    public function registerSidebars() {
        
    }

    public function registerTaxonomies() {
        
    }

    public static function baseUrl() {
        return ZF_CORE_URL;
    }

    public static function blockStyles($block = true) {
        
    }

    public static function getInstance() {
        return self::$instance;
    }

} 
    
ZF_Core::init();
