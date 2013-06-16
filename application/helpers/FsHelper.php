<?php

class FsHelper {

    public static function readFile($filename) {
        return file_exists($filename) && is_readable($filename) ?
                file_get_contents($filename) : '';
    }

    public static function saveFile($filename, $data, $flags=0, $context=null) {
        return file_exists($filename) && !is_writable($filename) ? 0 :
                file_put_contents($filename, $data, $flags, $context);
    }

    public static function append($filename, $data) {
        if (file_exists($filename) && !is_writable($filename)){
            return 0;
        }
        
        $fp = fopen($filename, 'ab');
        $n = fwrite($fp, $data);
        fclose($fp);

        return $n;
    }

    public static function compare($filename1, $filename2) {
        return filesize($filename1) == filesize($filename2)? 0 : strcmp(self::getFile($filename1), self::getFile($filename2));
    }

    public static function getExtension($filename) {
        return preg_match('%\.([\w\d]+)$%', $filename, $m)?$m[1]:pathinfo($filename, PATHINFO_EXTENSION);
    }

    public static function setExtension($filename, $ext) {
        if (strlen($ext) && $ext[0] != '.') {
            $ext = '.' . $ext;
        }

        return self::getExtension($filename)?preg_replace('%\.[\w\d]+$%', $ext, $filename):$filename.$ext;
    }
    
    public static function setExtensionPrefix($filename, $prefix){
        $ext = self::getExtension($filename);
        return self::setExtension($filename, $prefix.'.'.$ext);
    }

    public static function getFileServerParticular($filename) {
        $newFilename = self::setExtensionPrefix($filename, Util::serverName());
        return file_exists($newFilename) ? $newFilename : $filename;
    }

    public static function copy($src, $dst, $dstAttribs=0777) {
        if (is_file($src)) {
            return copy($src, $dst);
        } elseif (is_dir($src)) {
            if (!is_dir($dst) && !mkdir($dst, $dstAttribs)){
                return 0;
            }
            $src = preg_replace("%/$%", '', $src);
            $dst = preg_replace("%/$%", '', $dst);
            $d = dir($src);
            while ($file = $d->read()) {
                if ($file == "." || $file == "..") {
                    continue;
                }
                if (!self::copy("$src/$file", "$dst/$file", $dstAttribs)) {
                    $d->close();
                    return 0;
                };
            }
            $d->close();
        } else {
            return 0;
        }
        return 1;
    }

    public static function delete($path) {
        if (is_file($path)) {
            return unlink($path);
        } elseif (is_dir($path)) {
            $path = preg_replace("%/$%", '', $path);
            $d = dir($path);
            while ($file = $d->read()) {
                if ($file == "." || $file == "..") {
                    continue;
                }
                if (!self::delete("$path/$file")) {
                    $d->close();
                    return 0;
                }
            }
            $d->close();
            if (!rmdir($path)) {
                return 0;
            }
        }
        return 1;
    }

    public static function isDirEmpty($path)/* fvo */ {
        if (!is_dir($path)) {
            return 0;
        }
        $d = dir($path); //$img_set_folder
        $emptyDir = 1;
        while ($file = $d->read()) {
            if ($file != "." && $file != "..") {
                $emptyDir = 0;
                break;
            }
        }
        $d->close();
        return $emptyDir;
    }

    //TODO FileSystem::makeDir()	


}
