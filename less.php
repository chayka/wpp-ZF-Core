<?php

wp_register_script( 'less', ZF_CORE_URL.($minimize?'res/js/vendors/less-1.3.1.min.js':'res/js/vendors/less-1.3.3.js'));
wp_register_style( 'less-styles', ZF_CORE_URL.'res/css/styles.less?ver=1.0');

add_filter('style_loader_tag', 'less_style_loader_tag', 1, 2);

function less_style_loader_tag($tag, $handle){
    
    global $wp_styles;
    
    $style = Util::getItem($wp_styles->registered, $handle);
    
    if($style && strpos($style->src, '.less')){
        wp_enqueue_script('less');
        return sprintf('<link rel="stylesheet/less" type="text/css" href="%s">', $style->src);
    }
    
    return $tag;
    
}