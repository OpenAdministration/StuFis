<?php

namespace framework\render;

use Exception;
use framework\render\html\SmartyFactory;
use ReflectionClass;

class ErrorHandler extends Renderer{

    public const E400_BAD_REQUEST = [
        'code' => 400,
        'headline' => 'Fehlerhafte Anfrage',
        'msg' => 'Etwas ist schief gelaufen, bitte kontaktiere den Systemadmin',
    ];
    public const E401_UNAUTHORISED = [
        'code' => 401,
        'headline' => 'Nicht Autorisiert',
        'msg' => 'Du bist nicht berechtigt auf diesen Inhalt zuzugreifen.',
    ];
    public const E402_PAYMENT_REQUIRED = [
        'code' => 403,
        'headline' => 'Zahlung benötigt',
        'msg' => 'Es wird eine Zahlung benötigt.',

    ];
    public const E403_FORBIDDEN = [
        'code' => 403,
        'headline' => 'Zugriff verweigert',
        'msg' => 'Sie sind nicht berechtigt auf diesen Inhalt zuzugreifen.',
    ];
    public const E404_NOT_FOUND = [
        'code' => 404,
        'headline' => 'Seite nicht gefunden',
        'msg' => 'Da ist wohl etwas schief gegangen. Die angeforderte Seite konnte nicht gefunden werden.',
    ];

    public const E415_UNSUPORTED_MEDIATYPE = [
        'code' => 415,
        'headline' => 'Nicht unterstützer Medientyp',
        'msg' => 'Dieser Medientyp wird nicht unterstützt.',
    ];

    public const E500_INTERNAL_SERVER_ERROR = [
        'code' => 500,
        'headline' => 'Interner Server Fehler',
        'msg' => "Ein Server Fehler ist aufgetreten. Bitte kontaktiere den Administrator",
    ];

    public const UNKOWN_ERROR_CODE = [
        'code' => 418,
        'headline' => 'Dieser Fehler Code ist unbekannt',
        'msg' => 'Unbekannter Fehler',
    ];

    private $errorInformation;


    public static function handleException(Exception $e, string $additionalInformation = '', string $debugInfo = '', int $htmlCode = 500) : void
    {
        $stackTrace = $e->getTrace();
        $eh = new self(self::getDefaultErrorInfo($htmlCode), $stackTrace, $additionalInformation, $debugInfo);
        HTMLPageRenderer::showErrorAndDie($eh);
    }

    public static function handleError(int $htmlCode = 500, string $message = '', string $debugInfo = '') : void{
        $eh = new self(self::getDefaultErrorInfo($htmlCode), debug_backtrace(), $message, $debugInfo);
        HTMLPageRenderer::showErrorAndDie($eh);
    }

    public static function handleErrorRoute(array $routeInfo) : void{
        if ($routeInfo['controller'] === 'error'){
            $htmlCode = (int) $routeInfo['action'];
            $eh = new self(self::getDefaultErrorInfo($htmlCode), debug_backtrace(), $routeInfo['path']);
            HTMLPageRenderer::showErrorAndDie($eh);
        }
        $eh = new self(self::UNKOWN_ERROR_CODE, debug_backtrace());
        HTMLPageRenderer::showErrorAndDie($eh);
    }

    public function __construct(array $errorInformation, array $stackTrace = null, string $additionalInfo = '', string $debugInfo = ''){
        $stackTrace = $stackTrace ?? debug_backtrace(); // set default if null
        $errorInformation['trace'] = $this->cleanStackTrace($stackTrace);
        $errorInformation['debug'] = $debugInfo;
        $this->errorInformation = $errorInformation;
    }

    private static function getDefaultErrorInfo(int $htmlCode) : array
    {
        $reflectClass = new ReflectionClass(__CLASS__);
        $constantArray = $reflectClass->getConstants();
        $filteredConstants = array_filter($constantArray, static function ($val, $key) use ($htmlCode){
            return strpos($key,"E" . $htmlCode) === 0;
        }, ARRAY_FILTER_USE_BOTH);
        if(count($filteredConstants) === 1){
            return array_values($filteredConstants)[0];
        }
        //throw another Error ^^'
        return self::UNKOWN_ERROR_CODE;
    }



    public function render():void
    {
        $smarty = SmartyFactory::make();
        $smarty->assign('code', $this->errorInformation['code'] ?? 500);
        $smarty->assign('headline', $this->errorInformation['headline'] ?? '');
        $smarty->assign('msg', $this->errorInformation['msg'] ?? '');
        $smarty->assign('additional', $this->errorInformation['additional'] ?? '');
        $smarty->assign('trace', $this->errorInformation['trace'] ?? '');
        $smarty->assign('debug', $this->errorInformation['debug'] ?? '');
        $smarty->assign('telegramIssueLink', TG_ISSUE_LINK);
        $smarty->assign('githubIssueLink', GIT_ISSUE_LINK);

        $smarty->display('error.tpl');

    }

    public function renderJson() : void
    {
        JsonController::print_json(
            [
                'success' => true,
                'status' => $this->errorInformation['code'],
                'msg' => $this->errorInformation['msg'],
                'type' => 'modal',
                'subtype' => 'server-error',
                'headline' => $this->errorInformation['headline']
            ]
        );
    }

    private function cleanStackTrace(array $stackTrace) : array
    {
        foreach ($stackTrace as &$item){
            $item['file'] = str_replace(SYSBASE, '', $item['file']);
        }
        return $stackTrace;
    }

    /**
     * @deprecated ?
     * @param $msg
     * @param string $caller
     */
    private function log($msg, $caller = ''): void
    {
        error_log(date_create()->format('Y-m-d H:i:s') . "\t" .
            ($caller ? $caller . "\t" : '') .
            strip_tags(str_replace('<br>', "\n\t", $msg)));
    }


}