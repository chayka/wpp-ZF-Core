<?php

/*
  Plugin Name: WP ZF Core
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
class ZF_Core{
    public static $zfCoreTree;
    
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
            
            require 'application/helpers/WpDbHelper.php';

        } catch (Exception $e) {
            // try/catch works best in object mode (which we cannot use here), so not all errors will be caught
            echo '<span style="font-weight:bold;">WP Zend Library:</span> ' . nl2br($e);
        }

        //require_once 'ZendB/Log.php';
        Log::setDir(ZF_CORE_PATH.'logs');
//        Log::start();
        require_once 'zf.php';

        ZF_Query::registerApplication('ZF_CORE', ZF_CORE_APPLICATION_PATH, array('admin', 'autocomplete'));

        
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
    //            print_r($zfCoreTree);
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

    public static function registerActions(){
        add_action("activated_plugin", array("ZF_Core", "thisPluginGoesFirst"));
        add_action('admin_menu', array('ZF_Core', 'registerConsolePages'));
        
    }
    
    public static function registerFilters(){
        
    }
    
    public static function registerResources($minimize = false){
        wp_register_script( 'jquery-ajax-uploader', ZF_CORE_URL.'res/js/vendors/jquery.ajaxfileupload.js', array('jquery'));
        wp_register_script( 'jquery-ajax-iframe-uploader', ZF_CORE_URL.'res/js/vendors/jquery.iframe-post-form.js', array('jquery'));
        wp_register_script( 'jquery-galleria', ZF_CORE_URL.'res/js/vendors/galleria/galleria-1.2.8.min.js', array('jquery'));

        wp_register_script( 'jquery-brx-utils', ZF_CORE_URL.'res/js/jquery.brx.utils.js', array('jquery'));
        wp_register_script( 'jquery-brx-placeholder', ZF_CORE_URL.'res/js/jquery.brx.placeholder.js', array('jquery', 'jquery-brx-utils'));
        wp_register_script( 'jquery-ui-templated', ZF_CORE_URL.'res/js/jquery.ui.templated.js', array('jquery-ui-core', 'jquery-ui-dialog','jquery-ui-widget', 'jquery-brx-utils'));
        wp_register_style( 'jquery-brx-spinner', ZF_CORE_URL.'res/js/jquery.brx.spinner.css');
        wp_register_script( 'jquery-brx-spinner', ZF_CORE_URL.'res/js/jquery.brx.spinner.js', array('jquery-ui-templated'));
        wp_register_script( 'jquery-brx-modalBox', ZF_CORE_URL.'res/js/jquery.brx.modalBox.js', array('jquery-ui-dialog'));
        wp_register_script( 'jquery-brx-form', ZF_CORE_URL.'res/js/jquery.brx.form.js', array('jquery-ui-templated','jquery-brx-spinner', 'jquery-brx-placeholder', 'jquery-ui-autocomplete'));
        wp_register_script( 'jquery-brx-setupForm', ZF_CORE_URL.'res/js/jquery.brx.setupForm.js', array('jquery-brx-form'));
        wp_register_style( 'admin-setupForm', ZF_CORE_URL.'res/css/bem-admin_setup_form.less');
        wp_register_script( 'jquery-ui-datepicker-ru', ZF_CORE_URL.'res/js/jquery.ui.datepicker-ru.js');
        wp_register_script( 'jquery-ui-progressbar', ZF_CORE_URL.'res/js/jquery.ui.progressbar.js');
        wp_register_script( 'bootstrap', ZF_CORE_URL.($minimize?'res/js/vendors/bootstrap.min.js':'res/js/vendors/bootstrap.js'), array('jquery'));
        wp_register_style( 'bootstrap', ZF_CORE_URL.($minimize?'res/css/bootstrap.min.css':'res/css/bootstrap.css'));
        wp_register_style( 'bootstrap-responsive', ZF_CORE_URL.($minimize?'res/css/bootstrap-responsive.min.css':'res/css/bootstrap-responsive.css'));
        wp_register_style( 'jquery-ui-smoothness', ZF_CORE_URL.'res/css/jquery-ui-1.9.2.smoothness.css');

        wp_register_style( 'normalize', ZF_CORE_URL.'res/css/normalize.css');
        
        wp_register_script( 'modenizr', ZF_CORE_URL.'res/js/vendors/modernizr-2.6.2.min.js');

        wp_register_script( 'jquery-scroll', ZF_CORE_URL.'res/js/vendors/jquery.scroll.js');

        require_once 'less.php';    

    }

    public static function registerConsolePages() {
        add_menu_page('ZF Core', 'ZF Core', 'update_core', 'zf-core-admin', array('ZF_Core', 'renderConsolePageAdmin'), '', null); 
    }


    public static function renderConsolePageAdmin(){
       echo ZF_Query::processRequest('/admin/', 'ZF_CORE');	
    }

} 
    
ZF_Core::initPlugin();
