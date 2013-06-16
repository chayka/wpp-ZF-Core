<?php

class String {

    public static function capitalize($str){
        return preg_replace('%\b(\w+)\b%e', 'ucwords(strtolower("$1"))', $str);
//        return ucwords(strtolower($str));
    }
    
    public static function Cp12521toUtf8($str) {
        $enc = mb_detect_encoding($str, 'UTF-8,CP1251');
        $enc = $enc ? $enc : 'UTF-8';
        $str = mb_convert_encoding($str, 'UTF-8', $enc);

        return $str;
    }

    public static function Utf8toCp1251($str) {
        $enc = mb_detect_encoding($str, 'UTF-8,CP1251');
        $enc = $enc ? $enc : 'UTF-8';
        $str = mb_convert_encoding($str, 'CP1251', $enc);

        return $str;
    }

    public static function truncate($str, $length, $trail='...') {
        $strLength = strlen($str);
        if ($strLength > $length) {
            $trailLength = strlen($trail);
            $str = substr($str, 0, $length - $trailLength) . $trail;
        }

        return $str;
    }

    public static function stripParamFromRequest($param, $str) {
        $request_uri = preg_replace(array('%' . $param . '=[^&]*&?%', '%&\z%', '%\?\z%'), '', $str);
        return $request_uri;
    }

    public static function find($needle, $haystack) {
        return strpos($haystack, $needle) !== false;
    }

    public static function dateFormat($str, $date_format, $date_trans) {
        $str = date($date_format, strtotime($str));
        if (!empty($date_trans))
            $str = str_replace(array_keys($date_trans), array_values($date_trans), $str);

        return $str;
    }

    public static function buildHttpQuery($formdata, $numeric_prefix='', $arg_separator='&') {
        if (function_exists('http_build_query')) {
            return http_build_query($formdata, $numeric_prefix, $arg_separator);
        } else {
            $parts = array();
            foreach ($formdata as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $key2 => $value2)
                        $parts[] = $key . urlencode('[' . $key2 . ']') . '=' . urlencode($value2);
                } else {
                    $parts[] = $key . '=' . urlencode($value);
                }
            }
            return implode($arg_separator, $parts);
        }
    }

    public static function pcreDtPattern($date_pattern, $strict=0) {
        $d_p_elems = array('.', 'd', 'm', 'Y', 'H', 'i', 's', 'c');
        $p_p_elems = array('\.', '(\d{2})', '(\d{2})', '(\d{4})', '(\d{2})', '(\d{2})', '(\d{2})', '(\d{6})');
        if ($strict) {
            $pcre_pattern = '%^' . str_replace($d_p_elems, $p_p_elems, $date_pattern) . '$%';
        } else {
            $pcre_pattern = preg_replace('%\W+%', '\D+', $date_pattern);
            $pcre_pattern = '%^\s*' . str_replace($d_p_elems, $p_p_elems, $pcre_pattern) . '\s*$%';
        }
        return $pcre_pattern;
    }

    public static function dateConvert($dt, $from, $to) {
        if (empty($dt)) {
            return '';
        } elseif ($dt == 'NULL') {
            return 'NULL';
        } elseif (!isset($from, $to)) {
            return $dt;
        }

        if (!is_string($dt))
            return date($to, $dt);

        $check_ptn = pcre_dt_pattern($to, 1);
        if (preg_match($check_ptn, $dt))
            return $dt;

        $check_ptn = pcre_dt_pattern($from);
        if (preg_match($check_ptn, $dt, $m)) {
//			preg_match_all("%\w%", $from, $n);
            preg_match_all("%m|d|Y|H|i|s|c%", $from, $n);
            $search = array();
            $replace = array();
            $def_elems = array('m' => date('m'), 'd' => date('d'), 'Y' => date('Y'),
                'H' => date('H'), 'i' => date('i'), 's' => date('s'), 'c' => '000000');
//			print_r($n[0]);
            foreach ($n[0] as $key => $item) {
                $search[] = $item;
                $replace[] = $m[$key + 1];
            }
            foreach ($def_elems as $key => $item) {
                if (!in_array($key, $search)) {
                    $search[] = $key;
                    $replace[] = $item;
                }
            }
            return str_replace($search, $replace, $to);
        }

        return $dt;
    }

    public static function mailEncode($string, $from, $to) {
        $string = convert_cyr_string($string, $from, $to);
        preg_match_all('/(\w*[\x20\x80-\xFF]+\w*)/', $string, $matches);
        foreach ($matches[1] as $value) {
            $replacement = preg_replace('/([\x80-\xFF])/e', '"=".strtoupper(dechex(ord("\1")))', $value);
            $replacement = str_replace(' ', '_', $replacement);
            $string = str_replace($value, '=?koi8-r?Q?' . $replacement . '?=', $string);
        }
        return $string;
    }

    public static function addHttp($str) {
        if (!empty($str) && !str_find('http://', $str))
            $str = 'http://' . $str;

        return $str;
    }

    public static function getFirstWords($str, $char_len) {
        if (strlen($str) > $char_len) {
            do {
                $last_space = strrpos($str, ' ');
                if ($last_space === false)
                    break;
                $str = substr($str, 0, $last_space);
            }while ($last_space > $char_len);
        }

        return $str;
    }

    public static function getLastWords($str, $char_len) {
        if (strlen($str) > $char_len) {
            do {
                $first_space = strpos($str, ' ');
                if ($first_space === false)
                    break;
                $str = substr($str, $first_space);
            }while (strlen($str) > $char_len);
        }

        return $str;
    }

    public static function stripTags($str) {
        $str = html_entity_decode($str);
        $str = preg_replace("%<(script|style)[^>]*>.*</\\1>%imUs", " ", $str);
        $nonspaceble = "\/?(b|i|u|font|span|strong|big|small|a)";
        $str = preg_replace("%<$nonspaceble(?![\w\xC0-\xFF])[^>]*>%imUs", "", $str);
        $str = preg_replace("%<\?.*\?>%imUs", " ", $str);
        $str = preg_replace("%<[^>]*>%imUs", " ", $str);
        $str = preg_replace("%\s+%ims", " ", $str);
        $str = trim($str);
        return $str; //	trim(strtr(strip_tags($str),$trans));
    }

    public static function stripScripts($str) {
        return preg_replace("%<(script)[^>]*>.*</\\1>%imUs", " ", $str);
    }

    public static function hex($str) {
        $slen = strlen($str);
        $ret = "";
        for ($i = 0; $i < $slen; $i++) {
//	        $c=sprintf('%x', chr($str[$i]));
            $c = sprintf('%02x', ord($str[$i]));
            $ret.=$c;
        }

        return $ret;
    }

    public static function unhex($str) {
        $slen = strlen($str);
        $ret = "";
        for ($i = 0; $i < $slen; $i+=2) {
            list($c) = sscanf(substr($str, $i, 2), '%x');
            $ret.=chr($c);
        }

        return $ret;
    }

    public static function ksor($str, $key) {
        $slen = strlen($str);
        $klen = strlen($key);
        $ret = "";
        for ($i = 0; $i < $slen; $i++) {
            $c = $str[$i] ^ $key[$i % $klen];
            $ret.=$c;
        }

        return $ret;
    }

    public static function glueOneDollar($str, $key) {
        $slen = strlen($str);
        $klen = strlen($key);
        //  $len=($slen>$klen)?$klen:$slen;
        $ret = "";
        for ($i = 0; $i < $slen; $i++) {
            $c = $str[$i] | $key[$i % $klen];
            $ret.=$c;
        }

        return $ret;
    }

    public static function crc32($str) {
        $res = sprintf('%u', crc32($str));
        return $res;
    }

    public static function getRandomString($len) {
        $str = strtoupper(chr(rand(ord("a"), ord("z"))) . substr(md5(date("U") . rand()), 0, $len - 1));
        return $len<=32?$str:$str.self::getRandomString($len-32);
    }

    public static function getFormattedSize($size) {
        $measures = array(
            0 => 'b',
            1 => 'kb',
            2 => 'Mb',
            3 => 'Gb',
            4 => 'Tb',
            5 => 'Pb',
        );
        $measure = 0;
        while ($size > 1024) {
            $size/=1024;
            $measure++;
        }

        return sprintf("%01.2f %s", $size, $measures[$measure]);
    }

}
