<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HtmlHelper
 *
 * @author borismossounov
 */
class HtmlHelper {
    
    protected static $meta = array();
    protected static $mobileDetector = null;

    public static function setMeta($key, $value){
        self::$meta[$key]=$value; 
    }
    
    public static function getMeta($key, $default = ''){
        $value = Util::getItem(self::$meta, $key, $default); 
        return $value?$value:$default;
    }
    
    public static function setHeadTitle($title){
        return self::setMeta('head.title', $title);
    }
    
    public static function getHeadTitle($default = ''){
        return self::getMeta('head.title', $default);
    }
    
    public static function setMetaKeywords($value){
        return self::setMeta('meta.keywords', $value);
    }
    
    public static function getMetaKeywords($default = ''){
        return self::getMeta('meta.keywords', $default);
    }
    
    public static function setMetaDescription($value){
        return self::setMeta('meta.description', $value);
    }
    
    public static function getMetaDescription($default = ''){
        return self::getMeta('meta.description', $default);
    }
    
    /**
     * 
     * @param int|object|PostModel $post
     */
    public static function setPostMeta($post){
        if(is_object($post)){
            if(!($post instanceof PostModel)){
                $post = PostModel::unpackDbRecord($post);
            }
        }else{
            $post = PostModel::selectById($post);
        }
        
        self::setHeadTitle($post->getTitle());
        self::setMetaDescription($post->getExcerpt());
        $terms = $post->loadTerms();
        $pmkw = get_post_meta($post->getId(), 'keywords', true);
        if($pmkw){
            self::setMetaKeywords($pmkw);
        }else{
            $keywords = array();
            if($terms){
                foreach($terms as $taxonomy=>$ts){
                    $keywords = array_merge($keywords, $ts);
                }
            }
            $keywords = array_unique($keywords);
            self::setMetaKeywords(join(', ', $keywords));
        }
    }
    
    public static function hidden($condition = true){
        if($condition){
            echo 'style="display: none;"';
        }
    }
    
    public static function checked($condition = true){
        if($condition){
            echo 'checked="checked"';
        }
    }
    
    public static function disabled($condition = true){
        if($condition){
            echo 'disabled="disabled"';
        }
    }
    
    /**
     * 
     * @return Mobile_Detect
     */
    public static function MobileDetector(){
        require_once 'library/Mobile_Detect.php';
        if(!self::$mobileDetector){
            self::$mobileDetector = new Mobile_Detect;
        }
        
        return self::$mobileDetector;
    }
    
}


