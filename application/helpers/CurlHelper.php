<?php

class CurlHelper {

    /**
     * [sm_prepare_request] Prepares curl handle on given url
     * @param string url given URL
     * @param mixed request data to send
     * @param integer timeout in seconds
     * @return H_CURL curl handle
     */
    public static function prepareRequest($url, $params=array(), $timeout=60) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
    //    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if(!empty ($params)){
            curl_setopt($ch, CURLOPT_POST, 1);
            $request = String::buildHttpQuery($params, '', '&');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
//	    $fpVerbose = PathHelper::getLogVerboseFilename('localhost');
//	    curl_setopt ($ch, CURLOPT_VERBOSE, 1);
//	    curl_setopt ($ch, CURLOPT_STDERR, $fpVerbose);
//	    curl_setopt ($ch, CURLOPT_WRITEHEADER, $fpVerbose);

        return $ch;
    }
    
    public static function performRequest($ch){
        $res = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        return null == $json ? $res : $json;
    }

    /**
     * [sm_send_request] Sends request on given url
     * @param string url given URL
     * @param mixed request data to send
     * @param integer timeout in seconds
     * @return string response
     */
    public static function sendRequest($url, $params=array(), $timeout=60) {
        $ch = self::prepareRequest($url, $params, $timeout);
        return self::performRequest($ch);
    }
    
    public static function ping($url, $retry = 3, $timeout = 2) {
        $data = '';
        $try = 0;
        do {
            $try++;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            $data = curl_exec($ch);
            curl_close($ch);
        } while (empty($data) && $try <= $retry);

        return!empty($data);
    }

    public static function extractHttpHeader(&$str) {
        $response = array();

        $pattern = "%\A(.*\x0D\x0A)\x0D\x0A%imUsA";
        $answer = preg_match($pattern, $str, $m) ? $m[1] : '';

        $pattern = "%\A(.*)\x0D\x0A%imUs";
        if(preg_match($pattern, $answer, $m)){
            $response['SA'] = $m[1];

            $pattern = "%\s(\d{3})\s%";
            $response['Code'] = preg_match($pattern, $response['SA'], $m) ? $m[1] : '';
        }
        $pattern = "%^([^\x0D\x0A]*):(.*)$%imUs";
        preg_match_all($pattern, $answer, $m, PREG_SET_ORDER);
        $c = count($m);
        for ($i = 0; $i < $c; $i++) {
            $key = strtolower($m[$i][1]);
            $val = $m[$i][2];
            $response[$key] = $val;
        }
        $pattern = "%charset=(.*)$%iU";
        $charset = isset($response['content-type']) && preg_match($pattern, $response['content-type'], $m) ? $m[1] : '';
        if($charset){
            $response['charset'] = $charset;
        }
        $pattern = "%\A(.*\x0D\x0A)\x0D\x0A%imUsA";
//        $str = substr($str, strlen($answer) + 2);
        

        return $response;
    }

}
