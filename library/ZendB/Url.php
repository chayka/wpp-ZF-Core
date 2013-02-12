<?php

class Url {

    public static function parse($url) {
        $enc = mb_detect_encoding($url, 'UTF-8,CP1251');
        $enc = $enc ? $enc : 'UTF-8';
        $url = mb_convert_encoding($url, 'CP1251', $enc);
        $parsed = parse_url($url);
        foreach ($parsed as $key => $val) {
            $parsed[$key] = mb_convert_encoding($val, 'UTF-8', 'CP1251');
        }
        return $parsed;
    }

    /**
     * Adds 'http://' to url if needed
     * @var string url
     * @return string url
     */
    public static function makeParseble($url) {
        if ($url == "http:///") {
            
        }
        if ($url == "http://" || empty($url)) {
            return '';
        }

        $url_p = @parse_url($url);
        if (!empty($url_p)) {
            if (!isset($url_p['scheme'])) {
                $url = 'http://' . $url;
                $url_p = parse_url($url);
            }
            if (empty($url_p['host'])) {
                
            }
        }
        return $url;
    }

    /**
     * Switches between  http://my.com <=> http://www.my.com
     * @var string url
     * @return string url
     * 
     * ������� ���������� �������������� ���
     * ��� http://my.com => http://www.my.com
     * ��� http://www.my.com => http://my.com
     */
    public static function alternate($url) {
        self::makeParseble($url);
        $www = strpos($url, 'http://www.');
        $aurl = '';
        if ($www === false) {
            $aurl = str_replace('http://', 'http://www.', $url);
        } else {
            $aurl = str_replace('http://www.', 'http://', $url);
        }
        return $aurl;
    }

    /**
     * Adds 'www.' to url if needed
     * @var string url
     * @return string url
     * 
     * ������� ���������� ������������ ���
     * ��� http://my.com => http://www.my.com
     */
    public static function makeCanonical($url) {
        self::makeParseble($url);
        if (preg_match('%http://\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}%', $url))
            return $url;
        if (preg_match('%http://[\w\d]*(\z|/)%', $url))
            return $url;
        $www = strpos($url, 'http://www.');
        $aurl = '';
        if ($www === false) {
            $aurl = str_replace('http://', 'http://www.', $url);
        } else {
            $aurl = $url;
        }
        return $aurl;
    }

    /**
     * Opposite to url_parse, combines url out of piecees stored in associative array
     * @var array[string](string) url parsed by url_parse()
     * @return string url
     */
    public static function unparse($url_p) {
        $url = '';
        if (isset($url_p['scheme'])) {
            $url.=$url_p['scheme'] . '://';
        } elseif (isset($url_p['host'])) {
            $url.='http://';
        }
        if (isset($url_p['user'])) {
            $url.=$url_p['user'];
            if (isset($url_p['pass'])) {
                $url.=':' . $url_p['pass'];
            }
            $url.='@';
        }
        if (isset($url_p['host'])) {
            $url.=$url_p['host'];
        }
        if (isset($url_p['path'])) {
            $url.=$url_p['path'];
        } else {
            $url.='/';
        }
        if (isset($url_p['query'])) {
            $url.='?' . $url_p['query'];
        }
        if (isset($url_p['fragment'])) {
            $url.='#' . $url_p['fragment'];
        }
        return $url;
    }

    /**
     * Sorts url arguments
     * @var string url
     * @var string arg separator
     * @return string url
     */
    public static function sortArgs($url, $arg_separator='&') {
        $url = self::makeParseble($url);
        $url_p = parse_url($url);
        if (!empty($url_p['query'])) {
            $args = array();
            parse_str($url_p['query'], $args);
            ksort($args);
            $url_p['query'] = String::buildHttpQuery($args, '', $arg_separator);
        }
        if (!empty($url_p['path'])) {
            $url_p['path'] = preg_replace_callback(
                    '%[^\/]+%', create_function(
                            // single quotes are essential here,
                            // or alternative escape all $ as \$
                            '$matches', 'return rawurlencode($matches[0]);'
                    ), $url_p['path']);
        }
        $url = self::unparse($url_p);
        return $url;
    }

    /**
     * Makes absolute url from current url and relative part
     * @var string relative part
     * @var string base url
     * @return string absolute url
     */
    public static function makeAbsolute($src, $host) {
        //        ������ ���������� ��������� ���� ��������������� �� ��������������
        //        ���� $src ��� �������� ���������� �� �� ������������ �� ���������������
        $src_p = @parse_url($src);
        if (!empty($src_p)) {
            if (isset($src_p['fragment'])) {
                $src = str_replace("#" . $src_p['fragment'], "", $src);
            }
            $abs = isset($src_p['scheme']);
            $www_domains = "www|by|ru|com|net|org|info|biz|ws|tv|us|uk|de|be|ca|jp|ms|gs|st|tc|vu|to|fm|kz|vg|nz|ph|dk|ro|as|za|il|lt|cc";
            $no_scheme = substr($src, strpos($src, '//') + 2);
            $no_path = substr($no_scheme, 0, strpos($no_scheme, '/'));
            $abs = $abs || preg_match("%\b\.($www_domains)\b%", $no_path, $m);

            if ($abs || $host == "") {
                $s = strpos($src, "http");
                if ($s) {
                    $src = substr($src, $s);
                }
                //        ����������� src
                if (!isset($src_p['scheme'])) {
                    $src = 'http://' . $src;
                }
                return $src;
            }
            /*             * *******************************************
              In the context of URI
              http://domain.com/e/z/f.htm
              http://domain.com/e/z/
              the partial URIs would expand as follows:
              g.htm        -        http://domain.com/e/z/g.htm
              /g.htm        -        http://domain.com/g.htm
              ./g.htm        -        http://domain.com/e/z/g.htm
              ./?g=h        -        http://domain.com/e/z/?g=h
              ../g.htm-        http://domain.com/e/g.htm
              //g.htm        -        http://g
             * ******************************************** */
            //        ����������� host
            $host_p = parse_url($host);
            if (!isset($host_p['scheme'])) {
                $host = 'http://' . $host;
            }
            $host_p = parse_url($host);

            //        /g.htm        -        http://domain.com/g.htm
            if (isset($src_p['path']) && substr($src_p['path'], 0, 1) == '/' && !isset($src_p['host'])) {
                $retval = $host_p['scheme'] . '://' . $host_p['host'] . $src;
            }

            //        ./g.htm        -        http://domain.com/e/z/g.htm
            elseif (substr($src, 0, 2) == './' && !isset($src_p['host'])) {
                while (!strncmp($src, './', 2)) {
                    $src = substr($src, 2);
                }
                if (isset($host_p['path'])) {
                    $host_p['path'] = preg_replace('%/[^/]*\z%', '', $host_p['path']);
                }
                if (!isset($host_p['path']) || $host_p['path'] == "/" || $host_p['path'] == "\\") {
                    $host_p['path'] = "";
                }
                $retval = $host_p['scheme'] . '://' . $host_p['host'] . $host_p['path'] . "/" . $src;
            }

            //        ../g.htm-        http://domain.com/e/g.htm
            elseif (substr($src, 0, 3) == '../' && !isset($src_p['host'])) {
                if (isset($host_p['path'])) {
                    $host_p['path'] = preg_replace('%/[^/]*\z%', '', $host_p['path']);
                }
                if (!isset($host_p['path']) || $host_p['path'] == "/" || $host_p['path'] == "\\") {
                    $host_p['path'] = "";
                }
                while (substr($src, 0, 3) == '../') {
                    $src = substr($src, 3);
                    $host_p['path'] = dirname($host_p['path']);
                    if ($host_p['path'] == "/" || $host_p['path'] == "\\")
                        $host_p['path'] = "";
                }
                $retval = $host_p['scheme'] . '://' . $host_p['host'] . $host_p['path'] . '/' . $src;
            }

            //        g.htm        -        http://domain.com/e/z/g.htm
            else {
                if (isset($host_p['path'])) {
                    $host_p['path'] = preg_replace('%/[^/]*\z%', '', $host_p['path']);
                }
                if (!isset($host_p['path']) || $host_p['path'] == "/" || $host_p['path'] == "\\") {
                    $host_p['path'] = "";
                }
                $host = $host_p['scheme'] . '://' . $host_p['host'] . $host_p['path'];
                if (!$src || strlen($src) && $src[0] != '/' && $src[0] != '?' && $src[0] != '#') {
                    $src = '/' . $src;
                }
                $retval = $host . $src;
            }
            $p_url = parse_url($retval);

            if (isset($p_url['fragment'])) {
                $retval = str_replace("#" . $p_url['fragment'], "", $retval);
            }
        } else {
            $retval = null;
        }
        return $retval;
    }

    /**
     * Normalizes url (sorts args and throws away #anchor)
     * @var string url
     * @return string normalized url
     */
    public static function normalize($url) {
        Log::info($url);
//    	$enc = mb_detect_encoding($url, 'UTF-8,CP1251');
//		if('CP1251'!=$enc){
//			$url = mb_convert_encoding($url, 'CP1251', $enc);
//		}
        $url = self::sortArgs($url, '&');
        // remove anchor
        $url = preg_replace('%#.*$%is', '', $url);
        return $url;
    }

    /**
     * Compute crc on normalized url
     * @var string url
     * @return integer crc
     */
    public static function crc($url) {
        $url = self::normalize($url);
        $url = preg_replace('%^(http(s)?://|www\.)+%is', '', $url);
        $crc = String::crc32($url);
        return $crc;
    }

    /**
     * Compute crc on url's host
     * @var string url
     * @return integer crc
     */
    public static function hostCrc($url) {
        $url = self::makeParseble($url);
        $parsedUrl = @parse_url($url);
        $hostCrc = 0;
        if (isset($parsedUrl['host'])) {
            $host = str_replace('www.', '', $parsedUrl['host']);
            $hostCrc = String::crc32($host);
        }

        return $hostCrc;
    }

    /**
     * Compute crc on url's host
     * @var string url
     * @return string url
     */
    public static function shortHost($url) {
        $url = self::makeParseble($url);
        $parsedUrl = @parse_url($url);
        $host = '';
        if (isset($parsedUrl['host'])) {
            $host = str_replace('www.', '', $parsedUrl['host']);
        }

        return $host;
    }

}
