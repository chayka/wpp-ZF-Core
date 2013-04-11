<?php

wp_register_script( 'less', ZF_CORE_URL.($minimize?'res/js/vendors/less-1.3.1.min.js':'res/js/vendors/less-1.3.3.js'));
wp_register_style( 'less-styles', ZF_CORE_URL.'res/css/styles.less?ver=1.0');

add_filter('style_loader_tag', array('LessCss', 'styleLoaderTag'), 1, 2);

class LessCss{
    
    protected static $wrapperRendered = false;


    protected static function renderWrapper(){
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

    function styleLoaderTag($tag, $handle){

        global $wp_styles;

        $style = Util::getItem($wp_styles->registered, $handle);

        if($style && strpos($style->src, '.less')){
            if(!self::$wrapperRendered){
                self::renderWrapper();
            }
            wp_enqueue_script('less');
            return sprintf('<link rel="stylesheet/less" type="text/css" href="%s"><script type="text/javascript">renderLessCss("%s");</script>', $style->src, $style->src)."\r";
        }

        return $tag;

    }

}