<?php
class Util {

    /**
     * Returns object's property or array's element by key
     * in case of absense returns default value
     * @var array|object data to extract element from
     * @var string key
     * @var mixed default value
     * @return mixed value
     */
    public static function getItem($data, $key, $defaultValue = "") {
        $value = $defaultValue;
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            if (isset($data[$key])) {
                $value = $data[$key];
            }
        }

        return $value;
    }

    /**
     * Makes associative array of rows from plain array of rows
     * using as a key value, taken from row[idCol]
     * @var array(row) plain array of rows
     * @var string id fieldname in row, value of it will be used as id
     * @return array[row[idCol]](row)
     */
    public static function makeAssoc($arr, $idCol) {
        $hashedArr = array();
        foreach ($arr as $row) {
            $hashedArr[$row[$idCol]] = $row;
        }
        return $hashedArr;
    }

    public static function Utf8Array($obj) {
        if (is_array($obj)) {
            foreach ($obj as $key => $val) {
                $obj[$key] = self::Utf8Array($val);
            }
        } elseif (is_string($obj)) {
            $obj = String::Cp12521toUtf8($obj);
        }

        return $obj;
    }

    /**
     * Get instance of the front controller
     * 
     * @return Zend_Controller_Front
     */
    public static function getFront(){
        return Zend_Controller_Front::getInstance();
    }
    
    public static function getParam($key, $default = ""){
        return self::getFront()->getRequest()->getParam($key, $default);
    }
    
    public static function turnRendererOff(){
        $front = self::getFront();
        $front->setParam('noViewRenderer', true);
        return true;
    }

    public static function turnRendererOn(){
        $front = self::getFront();
        $front->setParam('noViewRenderer', false);
        return false;
    }

    public static function httpRespondCode($code = 200){
        $message = '';
        $intCode = intval($code);
        switch($intCode){
            case 100:
                $message = '100 Continue'; break;
            case 101:
                $message = '101 Switching Protocols'; break;
            case 200:
                $message = '200 OK'; break;
            case 201:
                $message = '201 Created'; break;
            case 202:
                $message = '202 Accepted'; break;
            case 203:
                $message = '203 Non-Authoritative Information'; break;
            case 204:
                $message = '204 No Content'; break;
            case 205:
                $message = '205 Reset Content'; break;
            case 206:
                $message = '206 Partial Content'; break;
            case 300:
                $message = '300 Multiple Choices'; break;
            case 301:
                $message = '301 Moved Permanently'; break;
            case 300:
                $message = '300 Multiple Choices'; break;
            case 302:
                $message = '302 Found'; break;
            case 303:
                $message = '303 See Other'; break;
            case 304:
                $message = '304 Not Modified'; break;
            case 305:
                $message = '305 Use Proxy'; break;
            case 306:
                $message = '306 (Unused)'; break;
            case 307:
                $message = '307 Temporary Redirect'; break;
            case 400:
                $message = '400 Bad Request'; break;
            case 401:
                $message = '401 Unauthorized'; break;
            case 402:
                $message = '402 Payment Required'; break;
            case 403:
                $message = '403 Forbidden'; break;
            case 404:
                $message = '404 Not Found'; break;
            case 405:
                $message = '405 Method Not Allowed'; break;
            case 406:
                $message = '406 Not Acceptable'; break;
            case 407:
                $message = '407 Proxy Authentication Required'; break;
            case 408:
                $message = '408 Request Timeout'; break;
            case 409:
                $message = '409 Conflict'; break;
            case 410:
                $message = '410 Gone'; break;
            case 411:
                $message = '411 Length Required'; break;
            case 412:
                $message = '412 Precondition Failed'; break;
            case 413:
                $message = '413 Request Entity Too Large'; break;
            case 414:
                $message = '414 Request-URI Too Long'; break;
            case 415:
                $message = '415 Unsupported Media Type'; break;
            case 416:
                $message = '416 Requested Range Not Satisfiable'; break;
            case 417:
                $message = '417 Expectation Failed'; break;
            case 500:
                $message = '500 Internal Server Error'; break;
            case 501:
                $message = '501 Not Implemented'; break;
            case 502:
                $message = '502 Bad Gateway'; break;
            case 503:
                $message = '503 Service Unavailable'; break;
            case 504:
                $message = '504 Gateway Timeout'; break;
            case 505:
                $message = '505 HTTP Version Not Supported'; break;
        }
        
        $sapi_type = php_sapi_name();
        if (substr($sapi_type, 0, 3) == 'cgi'){
            $message = 'Status: '.$message;
        }else{
            $message = 'HTTP/1.1 '.$message;
        }
        
        header($message, true, $intCode);

    }
    
    /**
     *
     * @param type $doctype
     * @return Zend_View_Helper_Doctype 
     */
    public static function doctype($doctype = 'HTML4_LOOSE'){
        $doctypeHelper = new Zend_View_Helper_Doctype();
        return $doctypeHelper->doctype($doctype);
    }
    
    /**
     *
     * @param string $title
     * @param string $setType null|'SET'|'PREPEND'
     * @return Zend_View_Helper_HeadTitle 
     */
    public static function headTitle($title, $setType = null){
        $titleHelper = new Zend_View_Helper_HeadTitle();
        return $titleHelper->headTitle($title, $setType);
    }
    /**
     *
     * @return Zend_View_Helper_HeadLink 
     */
    public static function headLink(){
        $headLinkHelper = new Zend_View_Helper_HeadLink();
        return $headLinkHelper->setIndent(8)->setPostfix("\r\n");
    }
    
    /**
     *
     * @return Zend_View_Helper_HeadMeta 
     */
    public static function headMeta(){
        $headMetaHelper = new Zend_View_Helper_HeadMeta();
        return $headMetaHelper->headMeta();
    }
    
    /**
     *
     * @return Zend_View_Helper_HeadScript 
     */
    public static function headScript(){
        return new Zend_View_Helper_HeadScript();
    }
    
    /**
     *
     * @return Zend_View_Helper_HeadStyle 
     */
    public static function headStyle(){
        return new Zend_View_Helper_HeadStyle();
    }
    
    public static function appendCss($css, $media = 'screen'){
        return self::headLink()
                ->appendStylesheet(PathHelper::getCssDir().'/'.$css, $media);
    }

    public static function appendFavIcon($favicon = 'favicon.ico'){
        return self::headLink()->headLink(array('rel' => 'favicon',
                      'href' => PathHelper::getImgDir().'/'.$favicon),
                      'PREPEND');
    }
    
    public static function print_r($var){
        echo "<pre>\n";
        print_r($var);
        echo "</pre>";
        
        return 0;
    }
    
    public static function serverName(){
        return str_replace('www.', '', $_SERVER['SERVER_NAME']);
    }

    public static function isDevelopment(){
        return in_array(Util::serverName(), array('wordpress.brx', 'wordpress.bbx'));
    }

    public static function isProduction(){
        return !self::isDevelopment();
    }
    
    public static function sessionStart(){
        if(!session_id()){
            session_start();
        }
    }
}