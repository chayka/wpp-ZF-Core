<?php

class IniFile{

	public static function save($filename, $data, $processSections=true){
    //	��������� � ������� ������ �� �������������� �������
    //	������ ����� ���� ����-����-������
    	if(!is_array($data)||!count($data)){  
        	return 0;
        }
            
    	$fp=fopen($filename, "w");
		$num=0;        
        foreach($data as $key=>$value){
        	if(!is_numeric($key)){	//	������������� �� ������ ���� ��������
            	if(is_array($value)){	//	������
                	fwrite($fp,"[$key]\r\n");
                    foreach($value as $key2=>$value2){
                    	if(!is_numeric($key2)&&!is_array($value2)){
//		                	fwrite($fp, "$key2=\"".addslashes($value2)."\"\r\n");
		                	fwrite($fp, "$key2=\"".$value2."\"\r\n");
                            $num++;
                        }
                    }
                	fwrite($fp,"\r\n");
                }else{	//	����
//                    fwrite($fp, "$key=\"".addslashes($value)."\"\r\n");
                    fwrite($fp, "$key=\"".$value."\"\r\n");
                    $num++;
                }
            }
        }
        
        fclose($fp);
        
        return $num;
    }
    
    public static function load($filename, $section=1){
    //	��������� ������, ���������� ���-�� ����. ���������
    //	���� $section==0 ��������� ������ �� �������
    //	���� $section!=0 ��������� ������  �������
    //	���� � $section ������� ��������� �������������,
    //	�� ���������� ������ �������� �������������� (������ ��� ����)
    	if(!file_exists($filename)){
        	return 0;
        }
    	$data=parse_ini_file($filename, $section);
        if(!is_numeric($section)&&$section){
        	$data=$data[$section];
        }
        foreach($data as $k1=>$v1){
        	if(is_array($v1)){
        		foreach($v1 as $k2=>$v2){
        			$data[$k1][$k2]=stripslashes($v2);
//	                echo "{$data[$k1][$k2]} ";
        		}
        	}else{
                $data[$k1]=stripslashes($v1);
//                echo "{$data[$k1]} ";
        	}
        }
        return $data;
    }

    public static function loadConstants($filename, $section=0){
	//	�� �� �����, ������ ����������� ���������� ��������� �����������
    	$data=array();
        
    	if(!self::load($filename, $data, $section)){
        	return 0;
        }
        $data1d=array();

        foreach($data as $key=>$value){
        	if(is_array($value)){
            	$data1d=array_merge($data1d, $value);
            }else{
            	$data1d[$key]=$value;
            }
        }

        $num=0;
        foreach($data1d as $key2=>$value2){
        	$key2=strtoupper($key2);
        	if(!defined($key2)&&define($key2, $value2)){
            	$num++;
            }
        }

        return $num;
    }

    public static function makeConstants($data, $section=0){
	//	�� �� �����, ���������� �� ������� ��������� �����������
        

        $data1d=array();

    	if($section && isset($data[$section])){
        	$data1d=$data[$section];
        }else{
	        foreach($data as $key=>$value){
	            if(is_array($value)){
	                $data1d=array_merge($data1d, $value);
	            }else{
	                $data1d[$key]=$value;
	            }
	        }
        }
        $num=0;
        foreach($data1d as $key2=>$value2){
        	$key2=strtoupper($key2);
        	if(!defined($key2)&&define($key2, $value2)){
            	$num++;
            }
        }

        return $num;
    }

    public static function update($filename, $data, $processSections=true){
    //	�������� ������������ ��� ��������� ������ � ����
    	$oldData=array();
        self::load($filename, $oldData, $processSections);
        
        foreach($data as $key=>$value){
        	if(!is_numeric($key)){	//	������������� �� ������ ���� ��������
            	if(isset($oldData[$key])&&is_array($oldData[$key])&&is_array($value)){
                	$oldData[$key]=array_merge($oldData[$key], $value);
                }else{	//	������
                	$oldData[$key]=$value;
                }
            }
        }

        
    //  $data=array_merge_recursive($oldData, $data);
        
        return self::save($filename, $oldData, $processSections);
    }
	
}