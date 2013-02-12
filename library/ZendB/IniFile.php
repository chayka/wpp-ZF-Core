<?php

class IniFile{

	public static function save($filename, $data, $processSections=true){
    //	сохран€ет в инифайл данные из ассоциативного массива
    //	массив может быть одно-двух-мерный
    	if(!is_array($data)||!count($data)){  
        	return 0;
        }
            
    	$fp=fopen($filename, "w");
		$num=0;        
        foreach($data as $key=>$value){
        	if(!is_numeric($key)){	//	идентификатор не должен быть числовым
            	if(is_array($value)){	//	секци€
                	fwrite($fp,"[$key]\r\n");
                    foreach($value as $key2=>$value2){
                    	if(!is_numeric($key2)&&!is_array($value2)){
//		                	fwrite($fp, "$key2=\"".addslashes($value2)."\"\r\n");
		                	fwrite($fp, "$key2=\"".$value2."\"\r\n");
                            $num++;
                        }
                    }
                	fwrite($fp,"\r\n");
                }else{	//	ключ
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
    //	загружает инишку, возвращает кол-во загр. элементов
    //	если $section==0 обработка секций не ведетс€
    //	если $section!=0 обработка секций  ведетс€
    //	если в $section указать строковый идентификатор,
    //	то загрузитс€ только значение идентификатора (секци€ или ключ)
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
	//	то же самое, только прочитанные переменные объ€вле€т константами
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
	//	то же самое, переменные из массива объ€вле€т константами
        

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
    //	замен€ет существующие или добавл€ет данные в файл
    	$oldData=array();
        self::load($filename, $oldData, $processSections);
        
        foreach($data as $key=>$value){
        	if(!is_numeric($key)){	//	идентификатор не должен быть числовым
            	if(isset($oldData[$key])&&is_array($oldData[$key])&&is_array($value)){
                	$oldData[$key]=array_merge($oldData[$key], $value);
                }else{	//	секци€
                	$oldData[$key]=$value;
                }
            }
        }

        
    //  $data=array_merge_recursive($oldData, $data);
        
        return self::save($filename, $oldData, $processSections);
    }
	
}