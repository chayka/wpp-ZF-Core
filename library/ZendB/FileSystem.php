<?php

class FileSystem {
    const TYPE_UNDEFINED = 0;
    const TYPE_FILE = 1;
    const TYPE_BZIP = 2;
    const TYPE_GZIP = 4;
    const TYPE_CRY = 8;

    const BZIP_DEFAULT_EXTENSION = '.bz2';
    const GZIP_DEFAULT_EXTENSION = '.gz';
    const CRY_DEFAULT_EXTENSION = '.cry';
    const CRY_KEY = 'akyahc';

#### ������ ������ � �������� ������� ##########################################		

    public static function getFile($filename) {
        //	���������� ���������� �����
        return file_exists($filename) && is_readable($filename) ?
                file_get_contents($filename) : '';
    }

    public static function putFile($filename, $data, $flags=0, $context=null) {
        //	��������� ���������� � ����
        if (file_exists($filename) && !is_writable($filename))
            return 0;

        return file_exists($filename) && !is_writable($filename) ? 0 :
                file_put_contents($filename, $data, $flags, $context);
    }

    public static function append($filename, $data) {
        //	��������� ���������� � ����
//    	echo "[$filename = ". realpath($filename). ":".realpath('./')."] ";
        if (file_exists($filename) && !is_writable($filename))
            return 0;

        $fp = fopen($filename, 'ab');
        $n = fwrite($fp, $data);
        fclose($fp);

        return $n;
    }

#### ������ ������ � ������� bz2 ###############################################		

    public static function getBz($filename) {
        //	���������� ���������� �����
        if (!file_exists($filename) || !is_readable($filename))
            return '';
        $data = '';
        $param = array('small' => 0);
        $fp = fopen($filename, 'rb');
        stream_filter_append($fp, 'bzip2.decompress', STREAM_FILTER_READ, $param);
        $rdata = '';
        while (strlen($rdata)) {
            $rdata = fread($fp, 1024);
            $data.=$rdata;
        }
        fclose($fp);

        return $data;
    }

    public static function putBz($filename, $data) {
        //	��������� ���������� � ����
        if (file_exists($filename) && !is_writable($filename))
            return 0;
        $n = 0;
        $param = array('blocks' => 9, 'work' => 0);
        $fp = fopen($filename, 'wb');
        stream_filter_append($fp, 'bzip2.compress', STREAM_FILTER_WRITE, $param);
        $n = fwrite($fp, $data);
        fclose($fp);

        return $n;
    }

#### ������ ������ � ������� gz ################################################		

    public static function getGz($filename) {
        //	���������� ���������� �����
        if (!file_exists($filename) || !is_readable($filename))
            return '';
        $data = '';
        $gz = gzopen($filename, 'rb');
        $rdata = 'fuck';
        while (strlen($rdata)) {
            $rdata = gzread($gz, 1024);
            $data.=$rdata;
        }
        gzclose($gz);

        return $data;
    }

    public static function putGz($filename, $data) {
        //	��������� ���������� � ����
        if (file_exists($filename) && !is_writable($filename))
            return 0;
        $gz = gzopen($filename, 'wb');
        $n = gzwrite($gz, $data);
        gzclose($gz);
        return $n;
    }

#### ������ ������ � ������� gz ################################################		

    public static function getCry($filename) {
        //	���������� ���������� �����
        if (!file_exists($filename) || !is_readable($filename))
            return '';
        $data = self::getFile($filename);
        $data = String::ksor($data, self::CRY_KEY);
        $data = gzuncompress($data);
        return $data;
    }

    public static function putCry($filename, $data) {
        //	��������� ���������� � ����
        if (file_exists($filename) && !is_writable($filename))
            return 0;
        $data = gzcompress($data, 9);
        $data = String::ksor($data, CRY_KEY);
        return self::putFile($filename, $data);
    }

#### ��������� ������ ##########################################################

    public static function compare($filename1, $filename2) {
        //	���������� ��� �����
        return strcmp(self::getFile($filename1), self::getFile($filename2));
    }

#### ������ ������ � ������ ������� ############################################

    public static function extension($filename) {
        //	���������� ���������� �����
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    public static function setExtension($filename, $ext) {
        //	������ ������������ ���������� ����� �� ��������
        if (strlen($ext) && substr($ext, 0, 1) != '.') {
            $ext = '.' . $ext;
        }

        return preg_replace('%\.[\w\d]+$%', $ext, $filename);
    }

    public static function getFileServerParticular($filename) {
        //	��������� � ���������� ������� ��� �������
        //	consts.conf => consts.se2.brx.conf
        $old_ext = self::extension($filename);
        $new_ext = Util::getItem($_SERVER, 'SERVER_NAME', 'localhost') . '.' . $old_ext;
        $new_filename = self::setExtension($filename, $new_ext);
        return file_exists($new_filename) ? $new_filename : $filename;
    }

    public static function setMarkerBeforeExtension($filename, $marker) {
        //	��������� � ���������� marker
        $old_ext = self::extension($filename);
        $new_ext = $marker . '.' . $old_ext;
        $new_filename = self::setExtension($filename, $new_ext);
        return $new_filename;
    }

    public static function getFileType($filename) {
        //	���������� ��� �����
        switch (self::extension($filename)) {
            case 'bz':
            case 'bz2':
            case 'bzip':
            case 'bzip2': {
                    return self::TYPE_BZIP;
                }
            case 'gz':
            case 'gzip': {
                    return self::TYPE_GZIP;
                }
            case 'cry': {
                    return self::TYPE_CRY;
                }
        }

        return self::TYPE_UNDEFINED;
    }

    public static function get($filename, $type=self::TYPE_UNDEFINED) {
        //	��������� � ���������� ������ �� �����, ���� ��� ����� �� ������,
        //	�������� ���������� ��� �� ����������
        if ($type == self::TYPE_UNDEFINED) {
            $type = self::getFileType($filename);
        }
        switch ($type) {
            case self::TYPE_BZIP: {
                    return self::getBz($filename);
                }
            case self::TYPE_GZIP: {
                    return self::getGz($filename);
                }
            case self::TYPE_CRY: {
                    return self::getCry($filename);
                }
        }

        return self::getFile($filename);
    }

    public static function put($filename, $data, $type=self::TYPE_UNDEFINED, $append_ext=1) {
        //	��������� ������ � ����, ���� ��� �� ������, �� �������� ���������� ��� 
        //	�� ����������, ���� ��� ������ $append_ext ����� ��������� ��������, �� 
        //	� $filename ����������� ���������� � ������ ������������� (.bz .gz ...)
        if ($type == self::TYPE_UNDEFINED) {
            $type = self::getFileType($filename);
        }
        switch ($type) {
            case self::TYPE_BZIP: {
                    if ($append_ext && !self::extension($filename)) {
                        $filename.=self::BZIP_DEFAULT_EXTENSION;
                    }
                    return self::putBz($filename, $data);
                }
            case self::TYPE_GZIP: {
                    if ($append_ext && !self::extension($filename)) {
                        $filename.=self::GZIP_DEFAULT_EXTENSION;
                    }
                    return self::putGz($filename, $data);
                }
            case self::TYPE_CRY: {
                    if ($append_ext && !self::extension($filename)) {
                        $filename.=self::CRY_DEFAULT_EXTENSION;
                    }
                    return self::putCry($filename, $data);
                }
        }

        return self::putFile($filename, $data);
    }

    public static function copy($src, $dst, $dst_attribs=0777) {
        //	������� �������� ���� ��� ���������� $src � $dst
        //  ���������� 1 � ������ ������ � 0 ��� ������
//		echo "$src, $dst - ";
        if (is_file($src)) {
            //	$src - ����
//	    	echo "����</br>";
            return copy($src, $dst);
        } elseif (is_dir($src)) {
            //	$src - �������
//	    	echo "�������</br>";
            if (!is_dir($dst) && !mkdir($dst, $dst_attribs))
                return 0;
            $src = preg_replace("%/$%", '', $src);
            $dst = preg_replace("%/$%", '', $dst);
            $d = dir($src);
            while ($file = $d->read()) {
                if ($file == "." || $file == "..") {
                    continue;
                }
                if (!self::copy("$src/$file", "$dst/$file", $dst_attribs)) {
                    $d->close();
                    return 0;
                };
            }
            $d->close();
        } else {
            //	$src - �� ����������
//	    	echo "�� ����������</br>";
            return 0;
        }
        return 1;
    }

    public static function delete($path) {
        //	������� ������� ���� ��� ���������� $path
        //	���������� 1 � ������ ������ � 0 ��� ������
        if (is_file($path)) {
            //	$path - ����
            return unlink($path);
        } elseif (is_dir($path)) {
            //	$path - �������
            $path = preg_replace("%/$%", '', $path);
            $d = dir($path);
            while ($file = $d->read()) {
                //	�������� ������������ � ������
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
                //	�������� ������� ��������
                return 0;
            }
        }
        return 1;
    }

    public static function isDirEmpty($path)/* fvo */ {
        //	���� ������� ������ ���������� 1
        //	���� ��������, �� ������� ��� ������ ������������ - 0
        if (!is_dir($path)) {
            return 0;
        }
        $d = dir($path); //$img_set_folder
        $empty_dir = 1;
        while ($file = $d->read()) {
            if ($file != "." && $file != "..") {
                $empty_dir = 0;
                break;
            }
        }
        $d->close();
        return $empty_dir;
    }

    //TODO FileSystem::makeDir()	

    function fs_upload($name, $path, $fn='') {
        //	��������� ���� $_FILES[$name] � ������� $path ��� ������ $fn
        //	���������� ��� ������
        return 0;
    }

}
