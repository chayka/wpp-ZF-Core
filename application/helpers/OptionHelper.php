<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OptionHelper
 *
 * @author borismossounov
 */
class OptionHelper {

    public static function getOption($option, $default='', $reload = false){
        $key = 'ZF-Core.'.$option;
        return get_option($key, $default, !$reload);
    }
    
    public static function setOption($option, $value){
        $key = 'ZF-Core.'.$option;
        return update_option($key, $value);
    }
    
    public static function getSiteOption($option, $default='', $reload = false){
        $key = 'ZF-Core.'.$option;
        return get_site_option($key, $default, !$reload);
    }
    
    public static function setSiteOption($option, $value){
        $key = 'ZF-Core.'.$option;
        return update_site_option($key, $value);
    }
    
}
