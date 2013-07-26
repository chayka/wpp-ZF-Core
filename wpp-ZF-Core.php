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

require_once 'library/ZendB/Util.php';
class ZF_Core{
    public static $zfCoreTree;
    
    public static $adminBar = false;
    
    const POST_TYPE_CONTENT_FRAGMENT = 'content-fragment';
    const TAXONOMY_CONTENT_FRAGMENT_TAG = 'content-fragment-tag';
    
    public static function initPlugin(){
        self::registerActions();
        self::registerFilters();

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

            
            self::registerResources($minimize = false);
            
//            self::registerCustomPostTypeContentFragment();
            
            require 'application/helpers/WpDbHelper.php';

        } catch (Exception $e) {
            // try/catch works best in object mode (which we cannot use here), so not all errors will be caught
            echo '<span style="font-weight:bold;">WP Zend Library:</span> ' . nl2br($e);
        }

        //require_once 'ZendB/Log.php';
        Log::setDir(ZF_CORE_PATH.'logs');
//        Log::start();
        require_once 'ZF-Query.php';

        ZF_Query::registerApplication('ZF_CORE', ZF_CORE_APPLICATION_PATH, array(
            'admin', 'autocomplete', 
            'post-model',
            'comment-model',
            'user-model',
            'social', 'zf-setup',
            'timezone',
            'options',
            'blockade',
        ));

        
    }
    
    public static function thisPluginGoesFirst() {
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
    
    public static function autoSlug($post){
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
        add_action('add_meta_boxes', array('ZF_Core', 'addMetaBoxContentFragment') );
        add_action('save_post', array('ZF_Core', 'savePost'), 10, 2);
    }
    
    public static function addMetaBoxContentFragment(){
        add_meta_box( 
            'content_fragment_metabox',
            'Advanced',
            array('ZF_Core', 'renderMetaBoxContentFragment'),
            null,
//            self::POST_TYPE_STAFF,
            'normal',
            'high'
        );
        
    }
    
    public static function renderMetaBoxContentFragment(){
        echo ZF_Query::processRequest('/admin/content-fragment-metabox', 'ZF_CORE');
    }
    
    public static function savePost($postId, $post){
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

    public static function registerActions(){
        add_action("activated_plugin", array("ZF_Core", "thisPluginGoesFirst"));
        add_action('admin_menu', array('ZF_Core', 'registerConsolePages'));
        add_action('wp_footer', array('ZF_Core', 'addJQueryWidgets'), 100);
        Util::sessionStart();
        if(empty($_SESSION['timezone'])){
            add_action('wp_footer', array('ZF_Core', 'fixTimezone'));
        }
        add_filter('wp_insert_post_data', array('ZF_Core', 'autoSlug'), 10, 1 );
        
    }
    
    public static function registerFilters(){
        
    }
    
    public static function registerResources($minimize = false){
//        wp_register_script( 'jquery', ZF_CORE_URL.($minimize?'res/js/vendors/jquery-1.8.2.min.js':'res/js/vendors/jquery-1.8.3.js'), array());
//        wp_enqueue_script('jquery');
        $isAdminPost = is_admin() && (strpos($_SERVER['REQUEST_URI'], 'post.php'));
        wp_register_script( 'Underscore', ZF_CORE_URL.($minimize?'res/js/vendors/underscore.min.js':'res/js/vendors/underscore.js'), array('jquery'));
        wp_register_script( 'Backbone', ZF_CORE_URL.($minimize?'res/js/vendors/backbone.min.js':'res/js/vendors/backbone.js'), array('jquery', ($isAdminPost?'underscore': 'Underscore')));
        wp_register_script( 'nls', ZF_CORE_URL.'res/js/vendors/nls.js', array(($isAdminPost?'underscore': 'Underscore')));

        wp_register_script( 'require', ZF_CORE_URL.($minimize?'res/js/vendors/require.min.js':'res/js/vendors/require.js'));
        wp_register_script( 'moment-base', ZF_CORE_URL.($minimize?'res/js/vendors/moment/min/moment.min.js':'res/js/vendors/moment/moment.js'), array());
        wp_register_script( 'moment-lang', ZF_CORE_URL.($minimize?'res/js/vendors/moment/min/lang/'.$lang.'.js':'res/js/vendors/moment/lang/'.$lang.'.js'), array());
        $lang = NlsHelper::getLang();
//        die($lang);
        $diskFile = ZF_CORE_PATH.'res/js/vendors/moment/lang/'.$lang.'.js';
//        die(file_exists($diskFile));
        if($lang!='en' && file_exists($diskFile)){
            wp_register_script( 'moment', ZF_CORE_URL.($minimize?'res/js/vendors/moment/min/lang/'.$lang.'.js':'res/js/vendors/moment/lang/'.$lang.'.js'), array('moment-base'));
        }else{
            wp_register_script( 'moment', ZF_CORE_URL.($minimize?'res/js/vendors/moment/min/moment.min.js':'res/js/vendors/moment/moment.js'));
        }
        wp_register_script( 'jquery-ui-templated', ZF_CORE_URL.'res/js/jquery.ui.templated.js', array('jquery-ui-core', 'jquery-ui-dialog','jquery-ui-widget', 'jquery-brx-utils', 'moment'));
        
        wp_register_script( 'backbone-brx', ZF_CORE_URL.'res/js/backbone.brx.js', array(($isAdminPost?'backbone':'Backbone'), 'nls', 'moment'));
//        wp_register_script( 'backbone-brx-model', ZF_CORE_URL.'res/js/backbone.brx.Model.js', array('Backbone', 'nls'));
//        wp_register_script( 'backbone-brx-view', ZF_CORE_URL.'res/js/backbone.brx.View.js', array('Backbone', 'nls', 'jquery-ui-templated', 'backbone-brx-model'));
//        wp_register_script( 'backbone-brx-form', ZF_CORE_URL.'res/js/backbone.brx.View.js', array('Backbone', 'nls', 'backbone-brx-view'));
        wp_register_script( 'backbone-wp-models', ZF_CORE_URL.'res/js/backbone.wp.models.js', array('backbone-brx'));
        wp_register_script( 'backbone-brx-pagination', ZF_CORE_URL.'res/js/backbone.brx.Pagination.view.js', array('backbone-brx'));

        wp_register_script( 'backbone-brx-spinners', ZF_CORE_URL.'res/js/brx.spinners.view.js', array('backbone-brx'));
        wp_register_style( 'backbone-brx-spinners', ZF_CORE_URL.'res/css/brx.spinners.view.less');
        
        wp_register_script( 'jquery-ajax-uploader', ZF_CORE_URL.'res/js/vendors/jquery.ajaxfileupload.js', array('jquery'));
        wp_register_script( 'jquery-ajax-iframe-uploader', ZF_CORE_URL.'res/js/vendors/jquery.iframe-post-form.js', array('jquery'));
        wp_register_script( 'jquery-galleria', ZF_CORE_URL.'res/js/vendors/galleria/galleria-1.2.8.min.js', array('jquery'));
        wp_register_script( 'jquery-masonry', ZF_CORE_URL.'res/js/vendors/jquery.masonry.min.js', array('jquery'));

        wp_register_script( 'jquery-brx-utils', ZF_CORE_URL.'res/js/jquery.brx.utils.js', array('jquery',  'nls'));
        wp_register_script( 'jquery-brx-placeholder', ZF_CORE_URL.'res/js/jquery.brx.placeholder.js', array('jquery', 'jquery-ui-templated', 'jquery-brx-utils'));
        wp_register_style( 'jquery-brx-spinner', ZF_CORE_URL.'res/js/jquery.brx.spinner.css');
        wp_register_script( 'jquery-brx-spinner', ZF_CORE_URL.'res/js/jquery.brx.spinner.js', array('jquery-ui-templated'));
        wp_register_script( 'jquery-brx-modalBox', ZF_CORE_URL.'res/js/jquery.brx.modalBox.js', array('jquery-ui-dialog'));
        wp_register_style( 'backbone-brx-modals', ZF_CORE_URL.'res/css/brx.modals.view.less', array());
        wp_register_script( 'backbone-brx-modals', ZF_CORE_URL.'res/js/brx.modals.view.js', array('jquery-ui-dialog', 'backbone-brx'));
        wp_register_script( 'jquery-brx-form', ZF_CORE_URL.'res/js/jquery.brx.form.js', array('jquery-ui-templated','jquery-brx-spinner', 'jquery-brx-placeholder', 'jquery-ui-autocomplete'));
        wp_register_script( 'jquery-brx-setupForm', ZF_CORE_URL.'res/js/jquery.brx.setupForm.js', array('jquery-brx-form'));
        wp_register_script( 'backbone-brx-optionsForm', ZF_CORE_URL.'res/js/brx.OptionsForm.view.js', array('backbone-brx'));
        wp_register_script( 'backbone-brx-jobControl', ZF_CORE_URL.'res/js/brx.JobControl.view.js', array('backbone-brx', 'jquery-ui-progressbar', 'backbone-brx-spinners'));
        wp_register_style( 'backbone-brx-jobControl', ZF_CORE_URL.'res/css/brx.JobControl.view.less', array('backbone-brx-spinners'));
        wp_register_style( 'admin-setupForm', ZF_CORE_URL.'res/css/bem-admin_setup_form.less');
        wp_register_script( 'jquery-ui-datepicker-ru', ZF_CORE_URL.'res/js/jquery.ui.datepicker-ru.js');
        wp_register_script( 'jquery-ui-progressbar', ZF_CORE_URL.'res/js/jquery.ui.progressbar.js');
        wp_register_script( 'bootstrap', ZF_CORE_URL.($minimize?'res/js/vendors/bootstrap.min.js':'res/js/vendors/bootstrap.js'), array('jquery'));
        wp_register_style( 'bootstrap', ZF_CORE_URL.($minimize?'res/css/bootstrap.min.css':'res/css/bootstrap.css'));
        wp_register_style( 'bootstrap-responsive', ZF_CORE_URL.($minimize?'res/css/bootstrap-responsive.min.css':'res/css/bootstrap-responsive.css'));


        wp_register_style( 'normalize', ZF_CORE_URL.'res/css/normalize.css');
        
        wp_register_script( 'modenizr', ZF_CORE_URL.'res/js/vendors/modernizr-2.6.2.min.js');

        wp_register_script( 'jquery-scroll', ZF_CORE_URL.'res/js/vendors/jquery.scroll.js', array('jquery'));

//        wp_register_script('', $src)
        
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
//          wp_register_style( 'jquery-ui-smoothness', ZF_CORE_URL.'res/css/jquery-ui-1.9.2.smoothness.css');
            wp_register_style( 'jquery-ui-'.$theme, self::getJQueryUIThemeCss($theme));
        }
        wp_register_style( 'jquery-ui', self::getJQueryUIThemeCss());
        
        
        
        require_once 'less.php';    

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
            ZF_CORE_URL.sprintf('res/css/jquery-ui/%s/%s', $theme, $themeCss);
        return $themeUrl;
    }

    public static function registerConsolePages() {
        add_menu_page('ZF Core', 'ZF Core', 'update_core', 'zf-core-admin', array('ZF_Core', 'renderConsolePageAdmin'), '', null); 
        add_submenu_page('zf-core-admin', 
                'jQueryUI theme', 'jQueryUI theme', 'update_core', 'zf-core-jqueryui-theme', 
                array('ZF_Core', 'renderConsolePageJQueryUIThemeSelect'), '', null); 
        add_submenu_page('zf-core-admin', 
                'phpinfo()', 'phpinfo()', 'update_core', 'zf-core-phpinfo', 
                array('ZF_Core', 'renderConsolePagePhpinfo'), '', null); 
        add_submenu_page('zf-core-admin', 
                'WP Hooks', 'WP Hooks', 'update_core', 'zf-core-wp-hooks', 
                array('ZF_Core', 'renderConsolePageWpHooks'), '', null); 
        add_submenu_page('zf-core-admin', 
                'E-mail', 'E-mail settings', 'update_core', 'zf-core-email', 
                array('ZF_Core', 'renderConsolePageEmailOptions'), '', null); 
        add_submenu_page('zf-core-admin', 
                'Blockade', 'Blockade', 'update_core', 'zf-core-blockade', 
                array('ZF_Core', 'renderConsolePageBlockadeOptions'), '', null); 
    }


    public static function renderConsolePageAdmin(){
       echo ZF_Query::processRequest('/admin/', 'ZF_CORE');	
    }

    public static function renderConsolePageJQueryUIThemeSelect(){
       echo ZF_Query::processRequest('/admin/jquery-ui-theme', 'ZF_CORE');	
    }

    public static function renderConsolePagePhpinfo(){
       echo ZF_Query::processRequest('/admin/phpinfo', 'ZF_CORE');	
    }

    public static function renderConsolePageWpHooks(){
       echo ZF_Query::processRequest('/admin/wp-hooks', 'ZF_CORE');	
    }

    public static function renderConsolePageEmailOptions(){
       echo ZF_Query::processRequest('/admin/email-options', 'ZF_CORE');	
    }
    
    public static function renderConsolePageBlockadeOptions(){
       echo ZF_Query::processRequest('/admin/blockade-options', 'ZF_CORE');	
    }
    
    public static function addJQueryWidgets(){
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
    
    public static function fixTimezone(){
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
    
    public static function showAdminBar($show = true){
        self::$adminBar = $show;
        add_filter('show_admin_bar', array('ZF_Core', 'isAdminBarShown'), 1, 1);
    }
    
    public static function hideAdminBar(){
        self::$adminBar = false;
        add_filter('show_admin_bar', array('ZF_Core', 'isAdminBarShown'), 1, 1);
    }
    
    public static function showAdminBarToAdminOnly(){
        self::$adminBar = 'admin';
        add_filter('show_admin_bar', array('ZF_Core', 'isAdminBarShown'), 1, 1);
    }
    
    public static function isAdminBarShown($show){
        if(self::$adminBar){
            if(self::$adminBar == 'admin'){
                return current_user_can('administrator') || is_admin();
            }
            
            return true;
        }
        
        return false;
    }
    
} 
    
ZF_Core::initPlugin();
