<?php

wp_register_script( 'less', ZF_CORE_URL.($minimize?'res/js/vendors/less-1.3.1.min.js':'res/js/vendors/less-1.3.3.js'));
wp_register_style( 'less-styles', ZF_CORE_URL.'res/css/styles.less?ver=1.0');
add_filter('style_loader_tag', array('LessHelper', 'styleLoaderTag'), 1, 2);

class LessHelper {
    
    protected static $instance = null;
    protected static $wrapperRendered = false;
    
    public static function getInstancePhp(){
        if(!self::$instance){
            require "library/lessc.inc.php";

            self::$instance = new lessc();
            $minimize = OptionHelper::getOption('minimizeMedia');
            self::$instance->setFormatter($minimize?'compressed':'classic');
        }
        return self::$instance;
    }
    
    public static function __callStatic($name, $arguments) {
        try{
            return call_user_func_array(array(self::getInstancePhp(), $name), $arguments);
        }catch(Exception $e){
            
        }
        return null;
    }
    
    /**
     * Check if input file or its dependancies were updated and rebuilds output if necessary
     * 
     * @param type $inputFile
     * @param type $outputFile 
     * @return string output file if ok
     */
    public static function smartCompilePhp($inputFile, $outputFile = null) {
        // load the cache
        $cacheFile = FileSystem::setExtension($inputFile, 'cache');
//        $cacheFile = $inputFile . ".cache";
        if(!$outputFile){
            $outputFile = FileSystem::setExtension($inputFile, 'css');
        }

        if (file_exists($cacheFile)) {
            $cache = unserialize(file_get_contents($cacheFile));
        } else {
            $cache = $inputFile;
        }

        try{
            $less = self::getInstancePhp();
            $less->flushParsedFiles();
            $newCache = $less->cachedCompile($cache);

            if (!is_array($cache) || $newCache["updated"] > $cache["updated"]) {
                file_put_contents($cacheFile, serialize($newCache));
                file_put_contents($outputFile, $newCache['compiled']);
                
            }
            return file_exists($outputFile)?$outputFile:null;
            
        }catch(Exception $e){
//            die($e->getMessage());
        }
        
        return null;
    }

    protected static function renderWrapperJs(){
        self::$wrapperRendered = true;
    ?>
    <script type="text/javascript">
        function renderLessCss(href, reload){
            reload = reload || false;
            if(window.less != undefined){
                var links = document.getElementsByTagName('link');
                var typePattern = /^text\/(x-)?less$/;

                less.sheets = [];

                for (var i = 0; i < links.length; i++) {
                    if (links[i].rel === 'stylesheet/less'
//                    || (links[i].rel.match(/stylesheet/) && (links[i].type.match(typePattern)))
                    && links[i].href.indexOf(href)>=0) {
                        less.sheets.push(links[i]);
                        break
                    }
                }
                less.refresh(false);
            }
        }
    </script>
    <?php    
    }
    
    public static function loadLessCssJs(){
        wp_enqueue_script('less');
        wp_print_scripts('less');
        if(!self::$wrapperRendered){
            self::renderWrapperJs();
        }
    }

    public static function styleLoaderTag($tag, $handle){

        global $wp_styles;

        $style = Util::getItem($wp_styles->registered, $handle);

        if($style && strpos($style->src, '.less')){
            $src = LessHelper::smartCompilePhp(ABSPATH.$style->src);
            if(!$src){
                self::loadLessCssJs();
            }
            return $src?
                    sprintf('<link rel="stylesheet" type="text/css" href="%s">', FsHelper::setExtension($style->src, 'css'))."\r":
                    sprintf('<link rel="stylesheet/less" type="text/css" href="%s"><script type="text/javascript">renderLessCss("%s");</script>', $style->src, $style->src)."\r";
        }
        
        return $tag;

    }
}