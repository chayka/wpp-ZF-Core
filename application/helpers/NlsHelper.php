<?php

require_once "Zend/Translate.php";
require_once "Zend/Locale.php";

class NlsHelper {

    private static $instance;
    private static $lang;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Zend_Translate('array', PathHelper::getNlsDir() . '/_default/common.php', self::getLang());
        }
        return self::$instance;
    }

    public static function getLang() {
        if (!self::$lang) {
            $config = ConfigHelper::getInstance();
            $locale = new Zend_Locale('en-us');
            try {
                $locale = new Zend_Locale($config->locale->lang ? $config->locale->lang : Zend_Locale::BROWSER);
            } catch (Zend_Locale_Exception $e) {
                $locale = new Zend_Locale('en-us');
            }
            self::$lang = $locale->getLanguage();
        }
        return self::$lang;
    }

    public static function load($module) {
        $srcLang = PathHelper::getNlsDir() . '/' . self::getLang() . '/' . $module . '.php';
        $srcDefault = PathHelper::getNlsDir() . '/_default/' . $module . '.php';
        $src = file_exists($srcLang) ? $srcLang : $srcDefault;
        self::getInstance()->addTranslation($src, self::getLang());
    }

    public static function updateScriptPath(Zend_View $view) {
        $nlsScriptsDir = PathHelper::getCoreDir() . PathHelper::getNlsScriptsDir() . '/' . self::getLang();
        if (is_dir($nlsScriptsDir)) {
            $view->addScriptPath($nlsScriptsDir);
        }
    }

    public static function _($value) {
        return self::getInstance()->_($value);
    }

}