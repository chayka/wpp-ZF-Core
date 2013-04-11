<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminController
 *
 * @author borismossounov
 */
class ZFCore_AdminController extends Zend_Controller_Action{

    public function init(){

    }
    
    public function indexAction(){
//        Util::turnRendererOff();
        wp_enqueue_style('zf-core-admin', ZF_CORE_URL.'res/css/bem-zf_core_admin.less');
//        echo "Hi, i'm admin area";
    }
    
    public function jqueryUiThemeAction(){
        $themeUrl = InputHelper::getParam('theme_url');
        $theme = InputHelper::getParam('theme');
        if($theme){
            OptionHelper::setOption('jQueryUI.theme', $theme);
            OptionHelper::setOption('jQueryUI.themeUrl', $themeUrl);
//            header('Location: '.$_SERVER['REQUEST_URI']);
        }

        $root = ZF_CORE_PATH.'res/css/jquery-ui/';
        $themes = scandir($root);
        foreach($themes as $key=>$theme){
            if($theme=='.' || $theme=='..' || !is_dir($root.$theme)){
                unset($themes[$key]);
            }
        }
        $themes = array_values($themes);
        $theme = OptionHelper::getOption('jQueryUI.theme', 'smoothness');
        $themeUrl = OptionHelper::getOption('jQueryUI.themeUrl');
  
        $this->view->themes = $themes;
        $this->view->theme = $theme;
        $this->view->themeUrl = $themeUrl;
        wp_enqueue_style('admin-jquery-ui-theme', ZF_CORE_URL.'res/css/bem-admin_jquery_ui_theme.less');
    }
    
    public function phpinfoAction(){
        
    }
    
//function ReflectionFunctionFactory($callback) {
//    if (is_array($callback)) {
//        // must be a class method
//        list($class, $method) = $callback;
//        return new ReflectionMethod($class, $method);
//    }
//
//    // class::method syntax
//    if (is_string($callback) && strpos($callback, "::") !== false) {
//        list($class, $method) = explode("::", $callback);
//        return new ReflectionMethod($class, $method);
//    }
//
//    // objects as functions (PHP 5.3+)
//    if (version_compare(PHP_VERSION, "5.3.0", ">=") && method_exists($callback, "__invoke")) {
//        return new ReflectionMethod($callback, "__invoke");
//    }
//
//    // assume it's a function
//    return new ReflectionFunction($callback);
//}
    public function wpHooksAction(){
        $tables = array();
        $columns = array();
        
        global $wp_filter;
        
        foreach($wp_filter as $tag=>$filters){
            $table = array();
            foreach ($filters as $priority=>$filterSet){
                foreach($filterSet as $func=>$implementation){
                    $function = $implementation['function'];
                    $callback = $function;
                    $ref = '';
                    if(is_array($function)){
                        list($cls, $method) = $function;
                        $delimiter = '::';
                        if(is_object($cls)){
                            $delimiter = '->';
                            $cls = get_class($cls);
                        }
                        $callback = sprintf('%s %s %s', $cls, $delimiter, $method);
                    }else{
//                        $r = $this->ReflectionFunctionFactory($function);
//                        $r = new ReflectionMethod("SearchEngine", 'addMetaBoxSearchOptions');
//                        $file = $r->getFileName();
//                        $startLine = $r->getStartLine();
//                        $ref = sprintf('%s (%d)', $file, $startLine);
                    }
                    $table[]=array(
                        'priority' => $priority,
                        'callback' => $callback,
                        'args' => $implementation['accepted_args'],
//                        'reflection' => $ref,
                    );
                }
            }
            usort($table, function($a, $b){
                $a = $a['priority'];
                $b = $b['priority'];
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            });
            $tag = urldecode($tag);
            $tables[$tag]=$table;
        }
        
        ksort($tables);
        
        $this->view->tables=$tables;
    }
    
}
