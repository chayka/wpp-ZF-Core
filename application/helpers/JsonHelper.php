<?php

interface JsonReadyInterface {

    /**
     * Returns assoc array to be packed into json payload
     * @return array($key=>$value);
     */
    public function packJsonItem();
    
    /**
     * Set array of fields that should be exported to json
     */
//    public static function setJsonMetaFields($metaFields);
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
//        return json_encode($value);
    }

    public static function packObject($obj) {
        if ($obj instanceof JsonReadyInterface) {
            return $obj->packJsonItem();
        } elseif ($obj instanceof Zend_Date) {
            return DateHelper::datetimeToJsonStr($obj);
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
        if($code){
//            header("HTTP/1.0 400 Bad request", true, 400);
        }
        return die(self::packResponse($payload, $code, $message));
    }
    
    /**
     * 
     * @param Exception $e
     */
    public static function respondException( $e){
        Util::httpRespondCode(500);
        self::respond(array(
            'file'=>$e->getFile(),
            'line'=>$e->getLine(),
            'trace'=>$e->getTrace(),
        ), $e->getCode(), $e->getMessage());
    }
    
    public static function respondError($message = '', $code = 1, $payload = null, $httpResponseCode = 400){
        Util::httpRespondCode($httpResponseCode);
        self::respond($payload, $code, $message);
    }
    
    public static function respondErrors($errors, $payload = null, $httpResponseCode = 400){
        Util::httpRespondCode($httpResponseCode);
        if($errors instanceof WP_Error){
            $errors = self::packWpErrors($errors);
        }
        $count = count($errors);
        if($count){
            if(1 == $count){
                $key = key($errors);
                self::respondError($errors[$key], $key, $payload, $httpResponseCode);
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