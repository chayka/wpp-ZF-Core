<?php

require_once 'Util.php';
require_once 'String.php';
require_once 'FileSystem.php';

class Log {
	
	const NEED_FUNC = 1;
	const NEED_ERROR = 2;
	const NEED_WARNING = 4;
	const NEED_INFO = 8;
	const NEED_DEBUG = 8;
	
	const INDENT_LENGTH = 4;
	
	/**
	 * @var integer Output indetation
	 */
	protected static $indent = 0;

	/**
	 * @var string Log filename
	 */
	protected static $logFn;
        protected static $logDir;

	/**
	 * @var integer log level
	 */
	protected static $logLevel = 255;
		
	/**
	 * @var integer log level
	 */
	protected static $signature;
        
        protected static $startTime;
        protected static $lastTimeCheck;
        
        public static function getStartTime(){
            if(empty(self::$startTime)){
                self::$startTime = new Zend_Date();
                self::$startTime->setFractionalPrecision(6);
            }
            return self::$startTime;
        }

        public static function getLastTimeCheck(){
            if(empty(self::$lastTimeCheck)){
                self::$lastTimeCheck = self::getStartTime();
            }
            return self::$lastTimeCheck;
        }

        public static function getElapsedTime(){
            $now = new Zend_Date();
            $now->setFractionalPrecision(6); 
            $elapsed = $now->sub(self::getStartTime());
            return $elapsed;
        }

        public static function getDeltaTime(){
            $now = new Zend_Date();
            $now->setFractionalPrecision(6); 
            $delta = $now->sub(self::getLastTimeCheck());
            self::$lastTimeCheck = new Zend_Date();
            self::$lastTimeCheck->setFractionalPrecision(6); 
            return $delta;
            
        }


        public static function fancyHeader($str){
		$strlen = strlen($str);
		if($strlen > 74){
			$str = substr($str, 0, 71).'...';
			$strlen = 74;
		}
		$res = '   ' . str_repeat('_', $strlen + 2) . "\r\n";
		$res.= '__/ ' . $str . ' \\' . str_repeat('_', 74 - $strlen) . "\r\n\r\n";
		return $res;
	}
	
	public static function fancyFooter($str){
		$strlen = strlen($str);
		if($strlen > 74){
			$str = substr($str, 0, 71).'...';
			$strlen = 74;
		}
		$res = str_repeat('_', 74 - $strlen) . str_repeat(' ', $strlen + 4) . '__' . "\r\n";
		$res.= str_repeat(' ', 74 - $strlen) . '\\ ' . $str . ' /' . "\r\n";
		$res.= str_repeat(' ', 75 - $strlen) . str_repeat('-', $strlen + 2) . "\r\n\r\n";
		return $res;
	}
	
	public static function getSignature(){
		if(empty(self::$signature)){
			$front = Zend_Controller_Front::getInstance();
			$action = $_SERVER['REQUEST_URI'];
			$server = $_SERVER['SERVER_NAME'];
			self::$signature = $server.$action.' at '.date('H:i:s');
		}
		return self::$signature;
	}
	
	/**
	 * Sets log FileName
	 * @param string fn
	 */
	public static function setDir($dir){
		self::$logDir = $dir;
	//	FileSystem::append(self::$logFn, self::fancyHeader(self::getSignature()));
	} 
	
	public static function getDir(){
		if(empty(self::$logDir)){
			self::$logDir = PathHelper::getLogDir();
		}
		return self::$logDir;
	}
	
	/**
	 * Sets log FileName
	 * @param string fn
	 */
	public static function setFn($fn){
		self::$logFn = $fn;
	//	FileSystem::append(self::$logFn, self::fancyHeader(self::getSignature()));
	} 
	
	public static function getFn(){
		if(empty(self::$logFn)){
			self::$logFn = self::getDir() . '/' . date('Ymd') . '.log';
//			FileSystem::append(self::$logFn, self::fancyHeader('Session at '.date('H:i:s')));
			FileSystem::append(self::$logFn, self::fancyHeader(self::getSignature()));
		//	register_shutdown_function(array('Log', 'shutDownHandler'));
		}
		return self::$logFn;
	}
	
	public static function shutDownHandler(){
		$str = self::fancyFooter(self::getSignature());
		FileSystem::append(Log::getFn(), $str);
	}
	
	public static function argsToString($args){
		foreach($args as $i => $arg){
			if(is_object($arg)){
				$args[$i] = '{Object}';
			}elseif(is_array($arg)){
				$args[$i] = '{Array}';
			}else{
				$args[$i] = "'".String::truncate($args[$i], 25)."'";
			}
		}
		
		return '('.implode(', ', $args).')';
	}
	
	public static function setLogLevel($level){
		self::$logLevel = $level;
	}
	
	protected static function put($str){
		$indent = str_repeat(' ', self::$indent * self::INDENT_LENGTH);
		$str = $indent . rtrim(str_replace("\n", "\n".$indent, $str)) . "\r\n"; 
		FileSystem::append(self::getFn(), $str);
	}
		
	public static function start(){
		if(self::$logLevel & self::NEED_FUNC){
			$trace = debug_backtrace();
			$str = Util::getItem($trace[1], 'class');
			$str.= Util::getItem($trace[1], 'type');
			$str.= Util::getItem($trace[1], 'function');
		//	$str.= '('. implode(', ', Util::getItem($trace[1], 'args')) .')';
			$str.= self::argsToString(Util::getItem($trace[1], 'args'));
			$str.= '[start]';
			self::put($str);
			self::$indent++;
		}
	}

	public static function stop(){
		if(self::$logLevel & self::NEED_FUNC){
			$trace = debug_backtrace();
			$str = Util::getItem($trace[1], 'class');
			$str.= Util::getItem($trace[1], 'type');
			$str.= Util::getItem($trace[1], 'function');
			$str.= '('.str_repeat('.', count(Util::getItem($trace[1], 'args'))).')';
			$str.= '[stop]';
			self::$indent--;
			self::put($str);
		}
	}

	public static function func($milestone=0){
		if(self::$logLevel & self::NEED_FUNC){
			$trace = debug_backtrace();
			$str = Util::getItem($trace[1], 'class');
			$str.= Util::getItem($trace[1], 'type');
			$str.= Util::getItem($trace[1], 'function');
		//	$str.= '('. implode(', ', Util::getItem($trace[1], 'args')) .')';
			$str.= self::argsToString(Util::getItem($trace[1], 'args'));
			if($milestone){
				$str .= '{milestone '.$milestone.'}';
			}
			self::put($str);
		}
	}
        
        public static function milestone($milestone = 0){
		if(self::$logLevel & self::NEED_FUNC){
			$trace = debug_backtrace();
			$str = Util::getItem($trace[1], 'class');
			$str.= Util::getItem($trace[1], 'type');
			$str.= Util::getItem($trace[1], 'function');
			$str .= ' {milestone '.$milestone.'}';
			$str .= ' elapsed: '.self::getElapsedTime()->toString('mm:ss.S');
			$str .= ' delta: '.self::getDeltaTime()->toString('mm:ss.S');
			self::put($str);
		}
            
        }
	
	public static function backtrace(){
		$trace = debug_backtrace();
		$str = "[backtrace]:\r\n";
		array_shift($trace);
		foreach($trace as $caller){
			$str.= Util::getItem($caller, 'class');
			$str.= Util::getItem($caller, 'type');
			$str.= Util::getItem($caller, 'function');
			$str.= self::argsToString(Util::getItem($caller, 'args'));
			$str.= "\r\n";
		}
		self::put($str);
		
	}
	
	public static function info($str){
		if(self::$logLevel & self::NEED_INFO){
			self::put('[info]: '.$str);
		}
	}

	public static function error($str){
		if(self::$logLevel & self::NEED_ERROR){
			self::put('[error]: '.$str);
		}
		FileSystem::append(PathHelper::getErrorsFile(), $str."\r\n");
	}

	public static function warning($str){
		if(self::$logLevel & self::NEED_WARNING){
			self::put('[warning]: '.$str);
		}
	}

	public static function debug($str){
		if(self::$logLevel & self::NEED_DEBUG){
			self::put('[debug]: '.$str);
		}
	}
	
	public static function dir($obj, $title = ""){
		$str = '[dir]: ';
		$trace = debug_backtrace();
		$str.= Util::getItem($trace[1], 'class');
		$str.= Util::getItem($trace[1], 'type');
		$str.= Util::getItem($trace[1], 'function');
		$str.= $title? ' '.$title . ' = ': ' ';
		$str .= print_r($obj, true);
		$str = str_replace("\n", "\r\n", $str);
		self::put($str);
	}

	public static function errorHandler($errno, $errstr, $errfile, $errline){
		$str = "($errfile:$errline)$errstr";
		switch ($errno) {
	  
			case E_ERROR:
			case E_CORE_ERROR  :
			case E_USER_ERROR:
			case E_COMPILE_ERROR:
			case E_RECOVERABLE_ERROR:
			case E_PARSE:
				self::error($str);
			    break;
			
			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
			case E_USER_WARNING:
				self::warning($str);
			    break;
			
			case E_NOTICE:
			case E_USER_NOTICE:
			case E_STRICT:
			default:
				self::info($str);
			    break;
		}		
		return false;
	}

	public static function exception(Exception $e){
		self::errorHandler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		self::info($e->getTraceAsString());
	}
	
	public static function handleErrors(){
		set_error_handler(array("Log", "errorHandler"));
	}

}

	

/*
   ___________________
__/ session at 345345 \_________________________________________________________

_________________________________________________________                     __
                                                         \ session at 345345 /
                                                          -------------------
*/