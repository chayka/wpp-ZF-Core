<?php

class ZF_Core_TimezoneController extends Zend_Controller_Action{

    public function init(){
        Util::turnRendererOff();
    }
    
    public function indexAction(){
        $timezones = array( 
//            '-12'=>'Pacific/Kwajalein', 
//            '-11'=>'Pacific/Samoa', 
//            '-10'=>'Pacific/Honolulu', 
//            '-9'=>'America/Juneau', 
//            '-8'=>'America/Los_Angeles', 
//            '-7'=>'America/Denver', 
//            '-6'=>'America/Mexico_City', 
//            '-5'=>'America/New_York', 
//            '-4'=>'America/Caracas', 
//            '-3.5'=>'America/St_Johns', 
//            '-3'=>'America/Argentina/Buenos_Aires', 
//            '-2'=>'Atlantic/Azores',// no cities here so just picking an hour ahead 
//            '-1'=>'Atlantic/Azores', 
//            '0'=>'Europe/London', 
//            '1'=>'Europe/Paris', 
//            '2'=>'Europe/Helsinki', 
//            '3'=>'Europe/Moscow', 
//            '3.5'=>'Asia/Tehran', 
//            '4'=>'Asia/Baku', 
//            '4.5'=>'Asia/Kabul', 
//            '5'=>'Asia/Karachi', 
//            '5.5'=>'Asia/Calcutta', 
//            '6'=>'Asia/Colombo', 
//            '7'=>'Asia/Bangkok', 
//            '8'=>'Asia/Singapore', 
//            '9'=>'Asia/Tokyo', 
//            '9.5'=>'Australia/Darwin', 
//            '10'=>'Pacific/Guam', 
//            '11'=>'Asia/Magadan', 
//            '12'=>'Asia/Kamchatka' 
        ); 
        $offset = InputHelper::getParam('offset');
        $date1 = new Zend_Date();
        $date1->toString('d MMMM yyyy HH:mm');
        $timezones1=DateTimeZone::listIdentifiers();
        $timezone = '';
        foreach($timezones1 as $tz){
            date_default_timezone_set($tz);
            $d = new DateTime();
            $offsetTz = $d->getOffset()/3600;
            if($offset == $offsetTz){
                $timezone = $tz;
                break;
            }
//            echo $tz.' '.($d->getOffset()/3600).",\n";
        }
        $date2 = new Zend_Date();
        $date2->toString('d MMMM yyyy HH:mm');
        Util::print_r(array(
            'offset' => $offset,
            'timezone' => date_default_timezone_get(),
            'date' => $date1->toString('d MMMM yyyy HH:mm'),
            'local' => $date2->toString('d MMMM yyyy HH:mm'),
        ));
        Util::sessionStart();
        $_SESSION['timezone'] = $timezone;
    }
}
