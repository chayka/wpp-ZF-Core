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
    
}


