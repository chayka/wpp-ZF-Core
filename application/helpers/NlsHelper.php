<?php

require_once "Zend/Translate.php";
require_once "Zend/Locale.php";

class NlsHelper {

    protected static $instance;
    protected static $lang;
    protected static $nlsDir;
    protected static $nlsScriptsDir;
    protected static $nlsJsUrl;
    protected static $nlsJsDir;
    

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Zend_Translate('array', array(
                'yes'=>'yes',
            ), self::getLang());
        }
        return self::$instance;
    }

    public static function setLang($lang) {
        self::$lang = $lang;
    }
    
    public static function getLang() {
        if (!self::$lang) {
            $locale = new Zend_Locale('en-us');
            try {
                $locale = new Zend_Locale(Zend_Locale::BROWSER);
            } catch (Zend_Locale_Exception $e) {
                $locale = new Zend_Locale('en-us');
            }
            self::$lang = $locale->getLanguage();
        }
        return self::$lang;
    }
    
    public static function setCurrentPlugin($path = ''){
        if(!$path){
            $bt = debug_backtrace();
//            Util::print_r($bt);
            $file = '';
            do{
                $item = current($bt);
                if(!next($bt) ){
                    break;
                }
                $file = Util::getItem($item, 'file');
//                echo __FILE__." $file<br/>";
            }while($file && $file == __FILE__);
            $path = $file;
        }
        $dir = WpHelper::getRootDir($path);
        self::$nlsDir = $dir.'/nls/' ;
        self::$nlsJsDir = $dir.'/res/js/nls/';
        $url = WpHelper::getRootDir(plugin_dir_url($dir.'/dummy.php'));
        self::$nlsJsUrl = preg_replace('%^[\w\d]+\:\/\/[\w\d\.]+%', '',$url.'/res/js/nls/');
//        Util::print_r(array(self::$nlsDir, self::$nlsJsDir, self::$nlsJsUrl));
    }
    
    public static function setNlsDir($dir){
        self::$nlsDir = $dir;
    }
    
    public static function getNlsDir(){
        return self::$nlsDir;
    }

    public static function load($module, $nlsDir = null) {
        self::setCurrentPlugin();
        $nlsDir = $nlsDir?$nlsDir:self::getNlsDir();
        $srcLang = $nlsDir . self::getLang() . '/' . $module . '.' .self::getLang() .'.php';
        $srcDefault = $nlsDir . '/_default/' . $module . '._default.php';
        $src = file_exists($srcLang) ? $srcLang : $srcDefault;
        self::getInstance()->addTranslation($src, self::getLang());
    }

    public static function updateScriptPath(Zend_View $view) {
        self::setCurrentPlugin();
        $nlsScriptsDir = self::getNlsDir() . self::getLang().'/views';
        if (is_dir($nlsScriptsDir)) {
            $view->addScriptPath($nlsScriptsDir);
        }
    }
    
    public static function registerScriptNls($handle, $script, $deps = array(), $ver = false, $in_footer = false){
        self::setCurrentPlugin();
        $nlsScript = FsHelper::setExtensionPrefix($script, self::getLang());
//        echo "$nlsScript<br/>";
        if(!file_exists(self::$nlsJsDir.$nlsScript)){
            $nlsScript = FsHelper::setExtensionPrefix($script, '_default');
//            echo "$nlsScript<br/>";
            if(!file_exists(self::$nlsJsDir.$nlsScript)){
                $nlsScript = $script;
    //            echo "$nlsScript<br/>";
                if(!file_exists(self::$nlsJsDir.$nlsScript)){
                    return;
                }
            }
        }
        $src = self::$nlsJsUrl . $nlsScript;
        if(!in_array('nls', $deps)){
            $deps[]='nls';
        }
        
        wp_register_script($handle, $src, $deps, $ver, $in_footer);
    }

    public static function _($value) {
        if(func_num_args()>1){
            $args = func_get_args();
            $args[0] = self::getInstance()->_($value);
            return call_user_func_array('sprintf', $args);
        }
        return self::getInstance()->_($value);
    }
    
    public static function __($value){
        if(func_num_args()>1){
            $args = func_get_args();
            $args[0] = self::getInstance()->_($value);
            echo call_user_func_array('sprintf', $args);
            return ;
        }
        echo self::getInstance()->_($value);
        return;
    }

}