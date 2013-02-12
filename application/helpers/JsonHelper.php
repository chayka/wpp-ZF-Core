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

}