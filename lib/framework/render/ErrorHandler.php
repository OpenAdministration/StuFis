<?php

namespace framework\render;

use Exception;

/**
 * implement error handler
 *
 * @category          framework
 * @author            michael gnehr
 * @author            Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since             07.05.2018
 * @copyright         Copyright Referat IT (C) 2018 - All rights reserved
 */
class ErrorHandler extends Renderer{
    /**
     * contains default error messages
     *
     * @var array
     */
    private static $default = [
        403 => [
            'headline' => 'Zugriff verweigert',
            'json' => 'Access denied.',
            'msg' => 'Sie sind nicht berechtigt auf diesen Inhalt zuzugreifen.',
            'additional' => 'Ihr momentaner Benutzerstatus verfügt nicht über die benötigten Berechtigungen, um auf diese Seite zuzugreifen. Melden Sie sich mit einem Nutzer an, der über entsprechende Zugriffe verfügt.'
        ],
        404 => [
            'headline' => 'Uuups...',
            'json' => 'Not found.',
            'msg' => 'Da ist wohl etwas schief gegangen. Die angeforderte Seite konnte nicht gefunden werden.',
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
        ],
        500 => [
            'headline' => 'Interner Server Fehler',
            'json' => 'internal server error',
            'msg' => "Ein Server Fehler ist aufgetreten. Bitte kontaktiere den Administrator",
            'additional' => "<a href='mailto:ref-it@tu-ilmenau.de'>ref-it@tu-ilmenau.de</a>",
        ],
    ];
    
    // member variables --------------------------
    /**
     * @var array $routeInfo current route information, if called in router
     */
    private $routeInfo;
    
    /**
     * class constructor
     *
     * @param array $routeInfo contains controller -> 'error' mostly, action -> error|http code, and optional msg
     */
    public function __construct(array $routeInfo){
        $this->routeInfo = $routeInfo;
        if ($this->routeInfo['controller'] !== 'error'){
            $this->routeInfo = [
                'method' => $_SERVER['REQUEST_METHOD'],
                'controller' => 'error',
                'action' => '404',
            ];
        }
    }
    
    // -------------------------------------------
    
    public static function _errorExit($msg): void
    {
        //prepare message
        $stack = debug_backtrace(null, 3);
        [$msg_html, $msg_raw] = self::_wrapStackTrace($msg, $stack);


        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            $msg = $msg_raw;
        }else{
            $msg = $msg_html;
        }
        // log message
        self::_errorLog($msg_raw);
        // echo message
        self::_renderError($msg);
        exit(-1);
    }

    private static function _wrapStackTrace(string $msg, array $stackTrace) : array {
        if(!DEV){
            return [$msg, $msg];
        }
        $re = '/(\.php$|\.phtml$|\/(.)+\/)/';
        $line0 = ($stackTrace[0]['line'] ?? ' ?? ');
        $file0 = (isset($stackTrace[0]['file']) ? preg_replace($re, '', $stackTrace[0]['file']) : ' ?? ');
        $line1 = ($stackTrace[1]['line'] ?? ' ?? ');
        $file1 = (isset($stackTrace[1]['file']) ? preg_replace($re, '', $stackTrace[1]['file']) : ' ?? ');

        $msg_html =  "<i>$file0 [$line0]:</i>&emsp;<b><pre>$msg</b></pre>" . PHP_EOL .
            "(Called in: <i>$file1 [$line1]</i>)<p></p>";
        $msg_raw = "$file0 [$line0]:  $msg" . PHP_EOL .
            "(Called in: $file1 [$line1])";

        return [$msg_html, $msg_raw];

    }

    public static function _renderException(Exception $e, $preMsg = '', $htmlCode = 500): void
    {
        $msg = "";
        if(!empty($preMsg)){
            $msg = $preMsg . " ";
        }
        $msg .= $e->getMessage();

        $stack = $e->getTrace();

        [$msg_html,] = self::_wrapStackTrace($msg, $stack);

        $info = [
            'code' => $htmlCode,
            'msg' => $msg_html,
            'headline' => "Interner Fehler"
        ];


        if (DEV){
            $devInfo = [
                'headline' => "Interner Fehler (" . get_class($e) . ")",
                'additional' => $e->getTraceAsString(),
            ];
            $info = $devInfo + $info;
        }

        self::_render($info);
    }
    
    public static function _errorLog($msg, $caller = ''): void
    {
        error_log(date_create()->format('Y-m-d H:i:s') . "\t" .
            ($caller ? $caller . "\t" : '') .
            strip_tags(str_replace('<br>', "\n\t", $msg)));
    }
    
    /**
     * render html error page | json error
     *
     * @param string $msg
     * @param int    $htmlCode
     */
    public static function _renderError(string $msg, $htmlCode = 403): void
    {
        $info = ['code' => $htmlCode];
        if ($msg !== null){
            $info['msg'] = $msg;
        }
        self::_render($info);
    }
    
    // -------------------------------------------
    
    /**
     * detect request method and calls render function
     * static version
     *
     * @param array $info
     *        could be routerInfo: [
     *        'controller' => 'error' mostly | 'route:controller',
     *        'action' => error|http code|route:action,
     *        'msg' => 'message' [optional]
     *        or parts of error information: [
     *        'code' => 418,
     *        'headline' => 'little joke',
     *        'json' => "I'm a teapot",
     *        'msg' => "I'm a teapot. Please use a coffee pot.",
     *        'additional' => [
     *        'headline' => 'More Information',
     *        'msg' => 'For additional information google "<b>http error code 418</b>".'
     *        ]
     *        ]
     */
    public static function _render(array $info) : void{
        $info = self::_parseInfo($info);
        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            self::_renderJson($info);
        }else{
            self::_renderErrorPage($info);
        }
    }
    
    /**
     * handle route information and incomplete error information and set defaults
     *
     * @param array $info
     *
     * @return array
     */
    private static function _parseInfo(array $info): array
    {
        if (isset($info['controller'], $info['action']) && $info['controller'] === 'error' && is_numeric($info['action'])){
            $info['code'] = (int)$info['action'];
        }
        if (!isset($info['code'])){
            $info['code'] = 404;
            if (isset($info['controller'], $info['action']) && !isset($info['msg']) && $_SERVER['REQUEST_METHOD'] !== 'POST'){
                $info['msg'] = 'Route: ' . $info['controller'] . ' . ' . $info['action'] . ' konnte nicht gefunden werden.';
            }
        }
        if (!isset($info['method'])) {
            $info['method'] = $_SERVER['REQUEST_METHOD'];
        }
        
        if (!isset($info['headline'])){
            $info['headline'] = self::$default[$info['code']]['headline'] ?? '';
        }
        if (!isset($info['json'])){
            $info['json'] = self::$default[$info['code']]['json'] ?? 'Unknown Error.';
        }
        if (isset($info['controller'], self::$default[$info['code']]['additional']) && !isset($info['additional']) && $info['controller'] === 'error'){
            $info['additional'] = self::$default[$info['code']]['additional'];
        }
        if (!isset($info['image']) && isset(self::$default[$info['code']]['image'])){
            $info['image'] = self::$default[$info['code']]['image'];
        }
        //disable image with false
        if (array_key_exists('image', $info) && !$info['image']){
            unset($info['image']);
        }
        if (!isset($info['msg'])
            && $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset(self::$default[$info['code']]['json'])
        ){
            $info['msg'] = self::$default[$info['code']]['json'];
        }else if (!isset($info['msg'])
            && isset(self::$default[$info['code']]['msg'])
        ){
            $info['msg'] = self::$default[$info['code']]['msg'];
        }
        if (!isset($info['msg'])){
            $info['msg'] = 'Unknown Error.';
        }
        return $info;
    }
    
    // -----------------------------------------------------
    
    /**
     * render json error page
     *
     * @param array $info error info, see _render function for more information
     */
    public static function _renderJson(array $info): void
    {
        //create return message
        $out = ['success' => false, 'msg' => $info['msg'], 'status' => $info['code']];
        //set html response code
        self::_setErrorCode($info);
        //echo resonse
        JsonController::print_json($out);
        die();
    }
    
    /**
     * set html code response header
     *
     * @param integer|array $info error info, see _render function for more information
     */
    private static function _setErrorCode($info): void
    {
        if (is_int($info['code'])){
            $code = $info['code'];
            http_response_code($code);
        }else if (is_numeric($info)){
            http_response_code($info);
        }
    }
    
    /**
     * render json error page
     *
     * @param array $param error info, see _render function for more information
     */
    public static function _renderErrorPage(array $param): void
    {
        $routeInfo = ['controller' => 'error'];
        // set html response code
        self::_setErrorCode($param);
        HTMLPageRenderer::dieWithErrorPage($param);
        /*// include header
        include dirname(__FILE__, 2)."/template/header.tpl";
        //include error template
        include dirname(__FILE__, 2)."/template/error.phtml";
        // include footer
        include dirname(__FILE__, 2)."/template/footer.tpl";*/
    }
    
    /**
     * detect request method and calls render function
     * may set routeInfo
     * dynamic version
     *
     * @param array $info contains controller -> 'error' mostly, action -> error|http code, and optional msg
     */
    public function render($info = null):void
    {
        if ($info === null) {
            $info = $this->routeInfo;
        }
        self::_render($info);
    }
}