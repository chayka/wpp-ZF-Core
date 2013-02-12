<?php

class FormatHelper{
    /**
     * 1            1       1
     * 100          100     100
     * 1000         1k      1k
     * 1200         1.2k    1.2k
     * 1289         1.3k    1.29k
     * 12340        12k     12.3k
     * 123456       123k    123k
     * 1234567      1.2m    1.23m
     * 12345678     12m     12.3m
     * 
     * @param int $number
     * @param type $precision 
     */
    public static function simplifiedNumber($number, $precision = 2){
        $suffixes = array('k', 'M');
        $suffix = '';
        while($number >= 1000 && count($suffixes)){
            $suffix = array_shift($suffixes);
            $number /= 1000;    // 1.289 | 12.345 | 123.456
        }
        $num = $number;
        while ($precision){
            $num /= 10;
            $precision--;
            if($num < 1){
                break;
            }
        }
        
        $number = round($number, $precision);
        
        return $number.$suffix;
    }
    
    public static function timeAgo(Zend_Date $date, $precision = 2){
        $diff = DateHelper::difference($date);
        $str = '';
        foreach($diff as $key=>$val){
//                echo $val.' '.$key.'s ';
            if((int)$val > 0){
                $str .= $val.' '.$key.'s ';
                if($precision){
                    $precision--;
                    if(!$precision){
                        break;
                    }
                }
            }
        }
        return $str.'ago';
    }
    
}
