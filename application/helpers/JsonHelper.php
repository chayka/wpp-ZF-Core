<?php

interface JsonReadyInterface {

    /**
     * Returns assoc array to be packed into json payload
     * @return array($key=>$value);
     */
    public function packJsonItem();
}

class JsonHelper {

    public static function utf8encode($value) {
        $value = self::packObject($value);
        if (is_string($value)) {
            //	$value = mb_convert_encoding ($value, 'UTF-8', 'CP1251');
        } elseif (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = self::utf8encode($val);
            }
        }

        return $value;
    }

    public static function encode($value) {
        return json_encode(self::utf8encode($value));
    }

    public static function packObject($obj) {
        if ($obj instanceof JsonReadyInterface) {
            return $obj->packJsonItem();
        }

        return $obj;
    }

    public static function packResponse($payload = '', $code = 0, $message = '') {
        $response = array(
            'payload' => $payload,
            'code' => $code,
            'message' => $message
        );
        //	$response = self::utf8encode($response);
        return self::encode($response);
    }

    public static function respond($payload = '', $code = 0, $message = '') {
        return die(self::packResponse($payload, $code, $message));
    }
    
    public static function respondError($message = '', $code = 1, $payload = null){
        self::respond($payload, $code, $message);
    }
    
    public static function respondErrors($errors, $payload = null){
        if($errors instanceof WP_Error){
            $errors = self::packWpErrors($errors);
        }
        $count = count($errors);
        if($count){
            if(1 == $count){
                $key = key($errors);
                self::error($errors[$key], $key, $payload);
            }
            self::respond($payload, 'mass_errors', $errors);
        }
        self::respond($payload, 1, '');
    }
    
    public static function packWpErrors(WP_Error $errors){
        $codes = $errors->get_error_codes();
        $json = array();
        foreach ($codes as $code) {
            $json[$code] = $errors->get_error_message($code);
        }
        return $json;
    }

}