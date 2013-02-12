<?php

require_once 'Zend/Json.php';

class XmlHelper {

	public static function parse($xmlStr){
		$json = Zend_Json::fromXml($xmlStr, false);
		$res = Zend_Json::decode($json);
		return $res;
	}
	
	public static function asArray($obj){
		return is_array($obj) && array_key_exists(0, $obj)?
			$obj:array($obj);
	}
}
