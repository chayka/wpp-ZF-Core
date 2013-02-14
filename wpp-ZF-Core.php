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
$zfCoreTree = array();

function zfCoreGoesFirst() {
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

function zfCoreClassTree($path){
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
            
            $map = array_merge($map, zfCoreClassTree("$path/$file"));
        }
        return $map;

    }
    return array();
}

function zfCoreAutoloader($class) {
    if(strpos($class, 'Helper')){
        include_once 'helpers'.PATH_SEPARATOR.$class.'.php'; 
    }else{
        global $zfCoreTree;
        if(empty($zfCoreTree)){
            $zfCoreTree = zfCoreClassTree(realpath(__DIR__ . '/application'));
        }
        if(isset($zfCoreTree[$class])){
            include_once $zfCoreTree[$class]; 
        }
    }
    
}

add_action("activated_plugin", "zfCoreGoesFirst");

try {

    // Add /library directory to our include path
    set_include_path(implode(PATH_SEPARATOR, array(
        get_include_path(), 
        realpath(__DIR__ . '/library'),
        realpath(__DIR__ . '/application'),
        )));

    // Turn on autoloading, so we do not include each Zend Framework class
    require_once 'Zend/Loader/Autoloader.php';
    $autoloader = Zend_Loader_Autoloader::getInstance();

    // Create registry object and setting it as the static instance in the Zend_Registry class
    $registry = new Zend_Registry();
    Zend_Registry::setInstance($registry);

    // Load configuration file and store the data in the registry
    $configuration = new Zend_Config_Ini(__DIR__ . '/application/configs/application.ini.php', 'development');
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

//    spl_autoload_register('zfCoreAutoloader');
    
} catch (Exception $e) {
    // try/catch works best in object mode (which we cannot use here), so not all errors will be caught
    echo '<span style="font-weight:bold;">WP Zend Library:</span> ' . nl2br($e);
}

require_once 'zf.php';
