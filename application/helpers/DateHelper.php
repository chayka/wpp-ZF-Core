<?php

class DateHelper {
    const DB_DATETIME = 'yyyy-MM-dd HH:mm:ss';
    const DB_DATE = 'yyyy-MM-dd';
    const DB_TIME = 'HH:mm:ss';

    const JSON_DATETIME = 'yyyy-MM-ddTHH:mm:ss.000\'Z';
    const JSON_DATE = 'yyyy-MM-dd';//'dd.MM.yyyy';
    const JSON_TIME = 'HH:mm:ss';

    /* dateToStr */

    public static function dateToDbStr($zendDate) {
        return $zendDate->setTimezone('UTC')->toString(self::DB_DATE);
    }

    /* timeToStr */

    public static function timeToDbStr($zendDate) {
        return $zendDate->setTimezone('UTC')->toString(self::DB_TIME);
    }

    /* datetimeToStr */

    public static function datetimeToDbStr($zendDate) {
        return $zendDate->setTimezone('UTC')->toString(self::DB_DATETIME);
    }

    /* dateToObj */

    public static function dbStrToDate($strDate) {
        $zendDate = new Zend_Date();
        $zendDate->setTimezone('UTC');
        $zendDate->setDate($strDate, self::DB_DATE);
        $zendDate->setTime('00:00:00', self::DB_TIME);
        return $zendDate;
    }

    /* timeToObj */

    public static function dbStrToTime($strTime) {
        $zendDate = new Zend_Date();
        $zendDate->setTimezone('UTC');
//        $zendDate->setDate($strTime, self::DB_TIME);
        $zendDate->setTime($strTime, self::DB_TIME);
        return $zendDate;
    }

    /* datetimeToObj */

    public static function dbStrToDatetime($strDatetime) {
        $zendDate = new Zend_Date();
        $zendDate->setTimezone('UTC');
//        Util::print_r($zendDate);
//        phpinfo();      
//        die($strDatetime.'!!!');
        try{
        $zendDate->setDate($strDatetime, self::DB_DATETIME);
        $zendDate->setTime($strDatetime, self::DB_DATETIME);
        }catch(Exception $e){
//            Util::print_r($r);
//            die('111');
        }
        return $zendDate;
    }

    /* dateToStr */

    public static function dateToJsonStr($zendDate) {
        return $zendDate->toString(self::JSON_DATE);
    }

    /* timeToStr */

    public static function timeToJsonStr($zendDate) {
        return $zendDate->toString(self::JSON_TIME);
    }

    /* datetimeToStr */

    public static function datetimeToJsonStr($zendDate) {
        return $zendDate->toString(self::JSON_DATETIME);
    }

    /* dateToObj */

    public static function jsonStrToDate($strDate) {
        $zendDate = new Zend_Date($strDate, self::JSON_DATE);
        return $zendDate;
    }

    /* timeToObj */

    public static function jsonStrToTime($strTime) {
        $zendDate = new Zend_Date($strTime, self::JSON_TIME);
        return $zendDate;
    }

    /* datetimeToObj */

    public static function jsonStrToDatetime($strDatetime) {
        $zendDate = new Zend_Date($strDatetime, self::JSON_DATETIME);
        return $zendDate;
    }

    public static function jsonDatetimeToDbStr($strDatetime){
        $zendDate = self::jsonStrToDatetime($strDatetime);
        return self::datetimeToDbStr($zendDate);
    }
    
    public static function jsonDateToDbStr($strDate){
        $zendDate = self::jsonStrToDate($strDate);
        return self::dateToDbStr($zendDate);
    }
    
    public static function jsonTimeToDbStr($strTime){
        $zendDate = self::jsonStrToTime($strTime);
        return self::timeToDbStr($zendDate);
    }
    
    public static function dbDatetimeToJsonStr($strDatetime){
        $zendDate = self::dbStrToDatetime($strDatetime);
        return self::datetimeToJsonStr($zendDate);
    }
    
    public static function dbDateToJsonStr($strDate){
        $zendDate = self::dbStrToDate($strDate);
        return self::dateToJsonStr($zendDate);
    }
    
    public static function dbTimeToJsonStr($strTime){
        $zendDate = self::dbStrToTime($strTime);
        return self::timeToJsonStr($zendDate);
    }
    
    public static function difference(Zend_Date $date1, Zend_Date $date2 = null) {
        if(!$date2){
            $date2 = new Zend_Date();
        }
        if($date2->isEarlier($date1)){
            $tmp = $date2;
            $date2 = $date1;
            $date1 = $tmp;
        }
//        $date2->subTime($date1->getTime());

        $d1['year'] = date('Y', $date1->toValue());
        $d1['month'] = date('n', $date1->toValue());
        $d1['day'] = date('j', $date1->toValue());
        $d1['hour'] = date('G', $date1->toValue());
        $m1 = sscanf($date1->get(Zend_Date::MINUTE_SHORT), '%02d');
        $d1['minute'] = reset($m1);
        $s1 = sscanf($date1->get(Zend_Date::SECOND_SHORT), '%02d');
        $d1['second'] = reset($s1);

        $d2['year'] = date('Y', $date2->toValue());
        $d2['month'] = date('n', $date2->toValue());
        $d2['day'] = date('j', $date2->toValue());
        $d2['hour'] = date('G', $date2->toValue());
        $m2 = sscanf($date2->get(Zend_Date::MINUTE_SHORT), '%02d');
        $d2['minute'] = reset($m2);
        $s2 = sscanf($date2->get(Zend_Date::SECOND_SHORT), '%02d');
        $d2['second'] = reset($s2);
        
        $d2['second']-=$d1['second'];
        if($d2['second']<0){
            $d2['minute']--;
            $d2['second']+=60;
        }
        $d2['minute']-=$d1['minute'];
        if($d2['minute']<0){
            $d2['hour']--;
            $d2['minute']+=60;
        }
        $d2['hour']-=$d1['hour'];
        if($d2['hour']<0){
            $d2['day']--;
            $d2['hour']+=24;
        }
        $d2['day']-=$d1['day'];
        if($d2['day']<0){
            $d2['month']--;
            $d2['day']+=cal_days_in_month(CAL_GREGORIAN, $d2['month'], $d2['year']);
        }
        $d2['month']-=$d1['month'];
        if($d2['month']<0){
            $d2['year']--;
            $d2['month']+=12;
        }
        $d2['year']-=$d1['year'];

        return $d2;
    }
    
    public static function fixTimezone(Zend_Date $date){
        if($_SESSION['timezone']){
            $date->setTimezone($_SESSION['timezone']);
        }
        return $date;
    }

}
