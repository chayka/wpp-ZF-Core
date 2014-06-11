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

$pluginDir = plugin_dir_path( __FILE__ );
defined('ZF_CORE_APPLICATION_PATH') 
    || define('ZF_CORE_APPLICATION_PATH', realpath($pluginDir . '/application'));
// Add /library directory to our include path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath($pluginDir . '/library'),
    ZF_CORE_APPLICATION_PATH,
    realpath($pluginDir),
    get_include_path(), 
)));

require_once 'library/ZendB/Util.php';
require_once 'application/classes/WpTheme.php';
require_once 'application/classes/WpSidebarWidget.php';
require_once 'application/helpers/LessHelper.php';
require_once 'application/helpers/OptionHelper.php';
require_once 'ZF-Query.php';

class ZF_Core extends WpPlugin{
    public static $zfCoreTree;
    
    public static $adminBar = false;
    
    public static $instance = null;
    
    const POST_TYPE_CONTENT_FRAGMENT = 'content-fragment';
    const TAXONOMY_CONTENT_FRAGMENT_TAG = 'content-fragment-tag';
    
    public static function init(){
        
//        self::registerActions();
//        self::registerFilters();
        LessHelper::setImportDir(ABSPATH);
        
        require_once 'application/helpers/NlsHelper.php';

        self::$instance = $plugin = new ZF_Core(__FILE__, array(
            'admin', 'autocomplete', 'upload',
            'post-model',
            'comment-model',
            'user-model',
            'social', 'zf-setup',
            'timezone',
            'options',
            'blockade',
            'not-found-404'
        ));
        
        $plugin->addSupport_ConsolePages();
        $plugin->addSupport_Metaboxes();
        $plugin->addSupport_PostProcessing();
        
        try {
            
            // Turn on autoloading, so we do not include each Zend Framework class
            require_once 'Zend/Loader/Autoloader.php';
            $autoloader = Zend_Loader_Autoloader::getInstance();
            spl_autoload_register(array('ZF_Core', 'autoloader'));

            // if everything went well, set a status flag
            define('WP_ZEND_LIBRARY', TRUE);

        } catch (Exception $e) {
            // try/catch works best in object mode (which we cannot use here), so not all errors will be caught
            echo '<span style="font-weight:bold;">WP Zend Library:</span> ' . nl2br($e);
        }
        
        require_once 'application/classes/WpPluginBootstrap.php';

        //require_once 'ZendB/Log.php';
        Log::setDir(ZF_CORE_PATH.'logs');
        
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
//        echo "zfCoreAutoloader(<b>$class</b>) \n";
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
            'name' => NlsHelper::_('Content fragment'), //'post type general name'
            'singular_name' => NlsHelper::_('Content fragment'), //'post type singular name'
            'add_new' => NlsHelper::_('Add fragment'), //'item'
            'add_new_item' => NlsHelper::_('Add fragment'),
            'edit_item' => NlsHelper::_('Edit fragment'),
            'new_item' => NlsHelper::_('New fragment'),
            'all_items' => NlsHelper::_('All fragments'),
            'view_item' => NlsHelper::_('View fragment'),
            'search_items' => NlsHelper::_('Search fragments'),
            'not_found' => NlsHelper::_('No fragments found'),
            'not_found_in_trash' => NlsHelper::_('No deleted fragments found'),
            'parent_item_colon' => 'Parent fragment:',
            'menu_name' => NlsHelper::_('Content fragments')
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
        self::getInstance()->addMetaBoxContentFragment();
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
            'content_fragment',
            'Advanced',
            '/metabox/content-fragment',
            'normal',
            'high',
            self::POST_TYPE_CONTENT_FRAGMENT
        );
        
    }
    
//    public static function renderMetaBoxContentFragment(){
//        echo ZF_Query::processRequest('/admin/content-fragment-metabox', 'ZF_CORE');
//    }
    
    public function savePost($postId, $post){
//        switch($post->post_type){
//            case self::POST_TYPE_CONTENT_FRAGMENT:
//                ZF_Query::processRequest('/admin/update-content-fragment/post_id/'.$postId, 'ZF_CORE');
//                break;
//        }
    }
    
    public static function registerTaxonomyContentFagmentTag(){
        $labels = array(
            'name' => NlsHelper::_('Fragment Tags'), //'taxonomy general name'),
            'singular_name' => NlsHelper::_('Fragment Tag'), //'taxonomy singular name'),
            'search_items' => NlsHelper::_('Search tags'),
            'all_items' => NlsHelper::_('All tags'),
            'edit_item' => NlsHelper::_('Edit'),
            'update_item' => NlsHelper::_('Update'),
            'add_new_item' => NlsHelper::_('Add tag'),
            'new_item_name' => NlsHelper::_('New tag name'),
            'menu_name' => NlsHelper::_('Fragment Tags'),
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
        $this->addAction('wp_footer', 'addUiFramework', 100);
        $this->addAction('admin_footer', 'addUiFramework_Console', 100);
//        add_action('wp_footer', array('ZF_Core', 'addJQueryWidgets'), 100);
        Util::sessionStart();
        if(empty($_SESSION['timezone'])){
//            add_action('wp_footer', array('ZF_Core', 'fixTimezone'));
            $this->addAction('wp_footer', 'fixTimezone');
        }
        
//        $this->addAction('admin_print_styles', 'addAdminStyles');
        
    }
    
    public function registerFilters(){
        $this->addFilter('wp_insert_post_data', 'autoSlug', 10, 1 );
        
    }
    
    public function registerResources($minimize = false){

        $isAdminPost = is_admin() && (strpos($_SERVER['REQUEST_URI'], 'post.php'));

        // backbone & underscore
        $this->registerScript( 'Underscore', ($minimize?'vendors/underscore.min.js':'vendors/underscore.js'), array('jquery'));
        $this->registerScript( 'Backbone', ($minimize?'vendors/backbone.min.js':'vendors/backbone.js'), array('jquery', ($isAdminPost?'underscore': 'Underscore')));
        $this->registerScript( 'nls', 'vendors/nls.js', array(($isAdminPost?'underscore': 'Underscore')));

        $this->registerScript( 'require', ($minimize?'vendors/require.min.js':'vendors/require.js'));

        // lesscss    
        $this->registerScript( 'less', ($minimize?'vendors/less-1.3.1.min.js':'vendors/less-1.3.3.js'));
        $this->registerStyle( 'less-styles', 'styles.less?ver=1.0');

        // dropkick.js
        $this->registerStyle( 'jquery-dropkick', 'vendors/jquery.dropkick-1.0.0.less', array());
        $this->registerScript( 'jquery-dropkick', 'vendors/jquery.dropkick-1.0.0.js', array('jquery'));

        // moment.js
        $lang = NlsHelper::getLang();
        $this->registerScript( 'moment-base', ($minimize?'vendors/moment/min/moment.min.js':'vendors/moment/moment.js'), array());
        $diskFile = ZF_CORE_PATH.'res/js/vendors/moment/lang/'.$lang.'.js';
        if($lang!='en' && file_exists($diskFile)){
            $this->registerScript( 'moment', ($minimize?'vendors/moment/min/lang/'.$lang.'.js':'vendors/moment/lang/'.$lang.'.js'), array('moment-base'));
        }else{
            $this->registerScript( 'moment', ($minimize?'vendors/moment/min/moment.min.js':'vendors/moment/moment.js'));
        }

        // touch-swipe
        $this->registerScript('jquery-touch-swipe', $minimize?'vendors/jquery.touchSwipe.min.js':'vendors/jquery.touchSwipe.js');
        
//        $this->registerScript( 'jquery-ui-templated', 'jquery.ui.templated.js', array('jquery-ui-core', 'jquery-ui-dialog','jquery-ui-widget', 'jquery-brx-utils', 'moment'));
        
        $this->registerScript( 'underscore-brx', 'underscore.brx.js', array(($isAdminPost?'underscore':'Underscore')));
        $this->registerScript( 'brx-parser', 'brx.Parser.js', array('underscore-brx', ($isAdminPost?'backbone':'Backbone')));
        $this->registerScript( 'backbone-brx', 'backbone.brx.js', array(($isAdminPost?'backbone':'Backbone'), 'underscore-brx', 'brx-parser', 'nls', 'moment', 'brx-ajax'));
        $this->registerScript( 'brx-ajax', 'brx.Ajax.js', array('underscore-brx'));
        $this->registerStyle( 'brx-modals', 'brx.Modals.less', array());
        $this->registerScript( 'brx-modals', 'brx.Modals.js', array('backbone-brx'));

        $this->registerScript( 'backbone-wp-models', 'backbone.wp.models.js', array('backbone-brx'));
        
        $this->registerScript( 'backbone-brx-pagination', 'brx.Pagination.view.js', array('backbone-brx'));

//        $this->registerScript( 'jquery-ajax-uploader', 'vendors/jquery.ajaxfileupload.js', array('jquery'));
//        $this->registerScript( 'jquery-ajax-iframe-uploader', 'vendors/jquery.iframe-post-form.js', array('jquery'));
        $this->registerScript( 'jquery-galleria', 'vendors/galleria/galleria-1.2.8.min.js', array('jquery'));
        $this->registerScript( 'jquery-masonry', 'vendors/jquery.masonry.min.js', array('jquery'));
        $this->registerScript( 'masonry', 'vendors/masonry.pkgd.min.js');

        $this->registerScript( 'jquery-brx-utils', 'jquery.brx.utils.js', array('jquery',  'nls'));
        $this->registerScript( 'jquery-brx-placeholder', 'jquery.brx.placeholder.js', array('jquery', 'jquery-ui-widget', 'underscore-brx'));
//        $this->registerStyle( 'jquery-brx-spinner', 'jquery.brx.spinner.css');
//        $this->registerScript( 'jquery-brx-spinner', 'jquery.brx.spinner.js', array('jquery-ui-templated'));
//        $this->registerScript( 'jquery-brx-modalBox', 'jquery.brx.modalBox.js', array('jquery-ui-dialog'));
//        $this->registerScript( 'jquery-brx-form', 'jquery.brx.form.js', array('jquery-ui-templated','jquery-brx-spinner', 'jquery-brx-placeholder', 'jquery-ui-autocomplete'));
//        $this->registerScript( 'jquery-brx-setupForm', 'jquery.brx.setupForm.js', array('jquery-brx-form'));

        $this->registerScript( 'backbone-brx-spinners', 'brx.spinners.view.js', array('backbone-brx'));
        $this->registerStyle( 'backbone-brx-spinners', 'brx.spinners.view.less');
        
        $this->registerStyle( 'backbone-brx-modals', 'brx.modals.view.less', array());
        $this->registerScript( 'backbone-brx-modals', 'brx.modals.view.js', array('jquery-ui-dialog', 'backbone-brx'));
        $this->registerStyle( 'backbone-brx-optionsForm', 'brx.OptionsForm.view.less');
        $this->registerScript( 'backbone-brx-optionsForm', 'brx.OptionsForm.view.js', array('backbone-brx'));
        $this->registerScript( 'backbone-brx-jobControl', 'brx.JobControl.view.js', array('backbone-brx', 'jquery-ui-progressbar', 'backbone-brx-spinners'));
        $this->registerStyle( 'backbone-brx-jobControl', 'brx.JobControl.view.less', array('backbone-brx-spinners'));
        $this->registerScript( 'backbone-brx-attachmentPicker', 'brx.AttachmentPicker.view.js', array('backbone-brx', 'backbone-brx-spinners'/*, 'jquery-ajax-iframe-uploader'*/));
        $this->registerStyle( 'backbone-brx-attachmentPicker', 'brx.AttachmentPicker.view.less', array('backbone-brx-spinners'));
        $this->registerStyle('backbone-brx-taxonomyPicker', 'brx.TaxonomyPicker.view.less');
        $this->registerScript('backbone-brx-taxonomyPicker', 'brx.TaxonomyPicker.view.js', array('jquery-brx-placeholder','backbone-brx'));
        $this->registerStyle('backbone-brx-ribbonSlider', 'brx.RibbonSlider.view.less');
        $this->registerScript('backbone-brx-ribbonSlider', 'brx.RibbonSlider.view.js', array('backbone-brx', 'jquery-touch-swipe'));
//        NlsHelper::registerScriptNls('backbone-brx-countDown-nls', 'brx.CountDown.view.js');
        $this->registerScript('backbone-brx-countDown', 'brx.CountDown.view.js', array('backbone-brx', 'moment'));
        $this->registerStyle('backbone-brx-countDown', 'brx.CountDown.view.less', array());

        $this->registerScript('google-youtube-loader', 'google.YouTube.ApiLoader.js', array('backbone-brx'));
        
        $this->registerStyle( 'admin-setupForm', 'bem-admin_setup_form.less');
        $this->registerScript( 'jquery-ui-datepicker-ru', 'jquery.ui.datepicker-ru.js');
//        $this->registerScript( 'jquery-ui-progressbar', 'jquery.ui.progressbar.js');
        $this->registerScript( 'bootstrap', ($minimize?'vendors/bootstrap.min.js':'vendors/bootstrap.js'), array('jquery'));
        $this->registerStyle( 'bootstrap', ($minimize?'bootstrap.min.css':'bootstrap.css'));
        $this->registerStyle( 'bootstrap-responsive', ($minimize?'bootstrap-responsive.min.css':'bootstrap-responsive.css'));


        $this->registerStyle( 'normalize', 'normalize.1.1.3.css');
        
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
        
        $this->registerStyle('brx-forms', 'brx.forms.less');
        $this->registerStyle('brx-wp-admin', 'brx.wp.admin.less');

    }

//    public function addAdminStyles(){
//        wp_enqueue_style('brx-forms');
//        wp_enqueue_style('brx-wp-admin');
//    }
    
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

    
    public function addUiFramework(){
        WidgetHelper::renderMultiSpinner();
        //TODO: remove jquery ui and effects
        wp_enqueue_style('jquery-ui');
        wp_enqueue_script('jquery-effects-fade');
        wp_enqueue_script('brx-parser');
        wp_enqueue_script('backbone-wp-models');
//        wp_enqueue_style('backbone-brx-modals');
//        wp_enqueue_script('backbone-brx-modals');
        wp_enqueue_style('brx-modals');
        wp_enqueue_script('brx-modals');
        wp_print_scripts();
        wp_print_styles(); 
        
        $options = array(
//            'uiFramework' =>is_admin()?'jQueryUI':get_site_option('ZfCore.uiFramework', '')
        );
                
        ?>
                    
        <script>
        jQuery(document).ready(function($) {
            $.declare('brx.options.ZfCore', <?php echo JsonHelper::encode($options)?>);
            if($.brx && $.brx.Parser){
                $.brx.Parser.parse();
            }
        });        
        </script>
                    
        <?php
    }
    
    public function addUiFramework_Console(){
        wp_enqueue_style('brx-forms');
        wp_enqueue_style('brx-wp-admin');
        $this->addUiFramework();
        ?>
                    
        <script>
        jQuery(document).ready(function($) {
            $.extend($.brx.Modals.buttonStyling, {
                'default': 'button button-large',
                'primary': 'button button-large button-primary'
            });
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
