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
        return AclHelper::isAdmin() || in_array(Util::serverName(), array('wordpress.brx', 'wordpress.bbx'));
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