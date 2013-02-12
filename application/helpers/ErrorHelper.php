<?php

require_once 'application/helpers/JsonHelper.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ErrorHelper
 *
 * @author borismossounov
 */
class ErrorHelper {

    const CODE_MASS_ERRORS = "mass_errors";
    const CODE_AUTH_REQUIRED = 'auth_required';
    const CODE_PERMISSION_REQUIRED = 'permission_required';
    
    public static function error($message = '', $code = 1, $payload = null){
        JsonHelper::respond($payload, $code, $message);
    }
    
    public static function errors($errors, $payload = null){
        $count = count($errors);
        if($count){
            if(1 == $count){
                $key = key($errors);
                self::error($errors[$key], $key, $payload);
            }
            JsonHelper::respond($payload, self::CODE_MASS_ERRORS, $errors);
        }
        JsonHelper::respond($payload, 1, '');
    }

    
}

