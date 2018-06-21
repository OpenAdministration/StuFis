<?php

/** 
 * @author michael
 * 
 */
class ErrorHandler
{
	/**
	 * contains default error messages
	 * @var array
	 */
	private static $default = [
		403 => [
			'headline' 	=> 'Zugriff verweigert',
			'json' 		=> 'Access denied.',
			'msg' 		=> 'Sie sind nicht berechtigt auf diesen Inhalt zuzugreifen.',
			'additional' => 'Ihr momentaner Benutzerstatus verfügt nicht über die benötigten Berechtigungen, um auf diese Seite zuzugreifen. Melden Sie sich mit einem Nutzer an, der über entsprechende Zugriffe verfügt.'
		],
		404 => [
			'headline'  => 'Uuups...',
			'json' 		=> 'Not found.',
			'msg' 		=> 'Da ist wohl etwas schief gegangen. Die angeforderte Seite konnte nicht gefunden werden.',
			'additional' => 'Möglicherweise sind Sie über einen veralteten Link auf diese Seite gestoßen. Informieren Sie doch bitte den entsprechenden Seitenbetreiber, so dass dieser entfernt, oder korrigiert werden kann.'
		],
		418 => [
			'headline' => 'Little Joke',
			'json' => "I'm a teapot",
			'msg' => "I'm a teapot. Please use a coffee pot.",
			'additional' => [
				'headline' => 'More Information',
				'msg' => 'For additional information google "<b>http error code 418</b>" please..'
			]
		]
	];
	
	// member variables --------------------------
	/**
	 * @var array $routeInfo current route information, if called in router
	 */
	private $routeInfo;
	
	/**
	 * class constructor
	 * @param array $routeInfo contains controller -> 'error' mostly, action -> error|http code, and optional msg
	 */
	function __construct($routeInfo)
	{
		$this->routeInfo = $routeInfo;
		if ($this->routeInfo['controller'] != 'error'){
			$routeInfo = [
				'method' => $_SERVER['REQUEST_METHOD'],
				'controller' => 'error',
				'action' => '404',
			];
		}
	}
	// -------------------------------------------
	
	public static function _errorLog($msg, $caller = ''){
		error_log(	date_create()->format('Y-m-d H:i:s')."\t".
					($caller?$caller."\t":'').
					strip_tags(str_replace('<br>', "\n\t", $msg)));
	}
	
	public static function _errorExit($msg){
		$re = '/(\.php$|\.phtml$|\/(.)+\/)/';
		//prepare message
		$stack = debug_backtrace(null, 3);
		$line0 = (isset($stack[0]['line'])?$stack[0]['line']:' ?? ');
		$file0 = (isset($stack[0]['file'])? preg_replace($re, '', $stack[0]['file']):' ?? ');
		$line1 = (isset($stack[1]['line'])?$stack[1]['line']:' ?? ');
		$file1 = (isset($stack[1]['file'])? preg_replace($re, '', $stack[1]['file']):' ?? ');
		//create message
		if ($_SERVER['REQUEST_METHOD'] == 'POST'){
			$msg = 	"$file0 [$line0]:  $msg\n".
			"(Called in: $file1 [$line1])";
		} else {
			$msg = 	"<i>$file0 [$line0]:</i>&emsp;<b><pre>$msg</b></pre>\n".
					"(Called in: <i>$file1 [$line1]</i>)<p></p>";
		}
		// log message
		self::_errorLog($msg);
		// echo message
		ErrorHandler::_renderError($msg);
		exit(-1);
	}
	
	/**
	 * render html error page | json error
	 * @param string $msg
	 * @param number $htmlCode
	 */
	public static function _renderError($msg, $htmlCode = 403){
		$info = ['code' => $htmlCode];
		if ($msg!==NULL){
			$info['msg']= $msg;
		}
		return self::_render($info);
	}
	
	// -------------------------------------------
	
	/**
	 * set html code response header
	 * @param integer|array $info error info, see _render function for more information
	 */
	private static function _setErrorCode($info){
		if (is_integer($info['code'])){
			$code = $info['code'];
			http_response_code ($code);
		} elseif (is_numeric($info)){
			http_response_code ($info);
		}
	}
	
	/**
	 * handle route information and incomplete error information and set defaults
	 * @param array $info
	 * @return array
	 */
	private static function _parseInfo($info){
		if (isset($info['controller']) && isset($info['action']) 
			&& $info['controller'] == 'error' && is_numeric($info['action'])){
			$info['code'] = intval($info['action'], 10);
		}
		if (!isset($info['code'])){
			$info['code'] = 404;
			if (isset($info['controller']) && isset($info['action']) && 
				!isset($info['msg']) && $_SERVER['REQUEST_METHOD'] != 'POST'){
				$info['msg'] = 'Route: '.$info['controller'].' . '.$info['action'].' konnte nicht gefunden werden.';
			}
		}
		if (!isset($info['method'])) $info['method'] = $_SERVER['REQUEST_METHOD'];
		
		if (!isset($info['headline'])){
			$info['headline'] = isset(self::$default[$info['code']]['headline'])? self::$default[$info['code']]['headline']: '';			
		}
		if (!isset($info['json'])){
			$info['json'] = isset(self::$default[$info['code']]['json'])? self::$default[$info['code']]['json']: 'Unknown Error.';
		}	
		if (!isset($info['additional']) && isset($info['controller']) && $info['controller'] == 'error' && isset(self::$default[$info['code']]['additional'])){
			$info['additional'] = self::$default[$info['code']]['additional'];	
		}
		if (!isset($info['image']) && isset(self::$default[$info['code']]['image'])){
			$info['image'] = self::$default[$info['code']]['image'];
		}
		//disable image with false
		if (array_key_exists('image', $info)&&!$info['image']){
			unset($info['image']);
		}
		if (!isset($info['msg'])
			&& $_SERVER['REQUEST_METHOD'] == 'POST'
			&& isset(self::$default[$info['code']]['json'])
			){
			$info['msg'] = self::$default[$info['code']]['json'];
		} elseif (!isset($info['msg']) 
			&& isset(self::$default[$info['code']]['msg'])
			){
			$info['msg'] = self::$default[$info['code']]['msg'];
		} if (!isset($info['msg'])){
			$info['msg'] = 'Unknown Error.';
		}
		return $info;
	}
	
	// -----------------------------------------------------
	
	/**
	 * detect request method and calls render function
	 * may set routeInfo
	 * dynamic version
	 * @param array $info contains controller -> 'error' mostly, action -> error|http code, and optional msg
	 */
	public function render($info = NULL){
		if ($info === NULL) $info = $this->routeInfo;
		self::_render($info);
	}
	
	/**
	 * detect request method and calls render function
	 * static version
	 * @param array $info
	 *    could be routerInfo: [
	 *    	'controller' => 'error' mostly | 'route:controller', 
	 *      'action' => error|http code|route:action,
	 *      'msg' => 'message' [optional]
	 *    or parts of error information: [
	 *    	'code' => 418,
	 *    	'headline' => 'little joke',
	 *      'json' => "I'm a teapot",
	 *      'msg' => "I'm a teapot. Please use a coffee pot.",
	 *      'additional' => [
	 *      	'headline' => 'More Information',
	 *      	'msg' => 'For additional information google "<b>http error code 418</b>".'
	 *      ]
	 *    ]
	 * @param boolean $default
	 */
	public static function _render($info){
		$info = self::_parseInfo($info);
		if ($_SERVER['REQUEST_METHOD'] == 'POST'){
			self::_renderJson($info);
		} else {
			self::_renderErrorPage($info);
		}
	}
	
	/**
	 * render json error page
	 * @param array $info error info, see _render function for more information
	 */
	public static function _renderJson($info){
		//create return message
		$out = ['success' => false, 'msg' => $info['info']];
		//set html response code
		self::_setErrorCode($info);
		//echo resonse
        require_once dirname(__FILE__) . '/class.JsonController.php';
		JsonController::print_json($out);
	}
	
	/**
	 * render json error page
	 * @param array $param error info, see _render function for more information
	 */
	public static function _renderErrorPage($param){
		//UI globals
		global $URIBASE, $ADMINGROUP, $subtype;
		$routeInfo = ['controller' => 'error'];
		// set html response code
		self::_setErrorCode($param);
		// include header
		include dirname(__FILE__, 2)."/template/header.tpl";
		//include error template
		include dirname(__FILE__, 2)."/template/error.phtml";
		// include footer
		include dirname(__FILE__, 2)."/template/footer.tpl";
	}
}

?>