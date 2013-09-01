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

    public static function setMeta($key, $value){
        self::$meta[$key]=$value; 
    }
    
    public static function getMeta($key, $default = ''){
        return Util::getItem(self::$meta, $key, $default);
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
    
    public static function getMetaKeywords($default){
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
        $keywords = array();
        if($terms){
            foreach($terms as $taxonomy=>$ts){
                $keywords = array_merge($keywords, $ts);
            }
        }
        $keywords = array_unique($keywords);
        self::setMetaKeywords(join(', ', $keywords));
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
    
    public static function renderMultiSpinner($populate='$.brx.multiSpinner'){
        $view = new Zend_View();
        $view->setScriptPath(ZF_CORE_PATH.'application/views/scripts');
        $view->assign('populate', $populate);
        echo $view->render('backbone/brx.MultiSpinner.phtml');
    }
    
}


