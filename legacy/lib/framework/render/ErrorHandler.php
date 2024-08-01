<?php

namespace framework\render;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use ReflectionClass;
use function Laravel\Prompts\error;

class ErrorHandler extends Renderer
{
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

    public const E405_WRONG_METHOD = [
        'code' => 405,
        'headline' => 'Seite so nicht erreichbar',
        'msg' => 'So kann diese Seite nicht aufgerufen werden',
    ];

    public const E415_UNSUPORTED_MEDIATYPE = [
        'code' => 415,
        'headline' => 'Nicht unterstützer Medientyp',
        'msg' => 'Dieser Medientyp wird nicht unterstützt.',
    ];

    public const E500_INTERNAL_SERVER_ERROR = [
        'code' => 500,
        'headline' => 'Interner Server Fehler',
        'msg' => 'Ein Server Fehler ist aufgetreten. Bitte kontaktiere den Administrator',
    ];

    public const UNKOWN_ERROR_CODE = [
        'code' => 418,
        'headline' => 'Dieser Fehler Code ist unbekannt',
        'msg' => 'Unbekannter Fehler',
    ];

    private $errorInformation;

    /**
     * @param  string  $debugInfo
     */
    #[NoReturn]
    public static function handleException(Exception $e, string $additionalInformation = '', $debugInfo = '', int $htmlCode = 500): void
    {
        $stackTrace = $e->getTrace();
        $debugInfo .= $e->getMessage();
        $eh = new self(self::getDefaultErrorInfo($htmlCode), $stackTrace, $additionalInformation, $debugInfo);
        HTMLPageRenderer::showErrorAndDie($eh);
    }

    #[NoReturn]
    public static function handleErrorRoute(array $routeInfo): void
    {
        if ($routeInfo['controller'] === 'error') {
            $htmlCode = (int) $routeInfo['action'];
            $eh = new self(self::getDefaultErrorInfo($htmlCode), debug_backtrace(), $routeInfo['path']);
            HTMLPageRenderer::showErrorAndDie($eh);
        }
        $eh = new self(self::UNKOWN_ERROR_CODE, debug_backtrace());
        HTMLPageRenderer::showErrorAndDie($eh);
    }

    /**
     * ErrorHandler constructor.
     */
    public function __construct(array $errorInformation, ?array $stackTrace = null, string $additionalInfo = '', array|string $debugInfo = '')
    {
        parent::__construct();
        if (is_array($debugInfo)) {
            $debugInfo = var_export($debugInfo, true);
        }
        $stackTrace = $stackTrace ?? debug_backtrace(); // set default if null
        $stackTrace = $this->cleanStackTrace($stackTrace);
        $errorInformation['additional'] = $additionalInfo;
        $errorInformation['trace'] = $this->cleanStackTrace($stackTrace);
        $errorInformation['debug'] = $debugInfo;
        $this->errorInformation = $errorInformation;
    }

    private static function getDefaultErrorInfo(int $htmlCode): array
    {
        $reflectClass = new ReflectionClass(__CLASS__);
        $constantArray = $reflectClass->getConstants();
        $filteredConstants = array_filter($constantArray, static function ($val, $key) use ($htmlCode) {
            return str_starts_with($key, 'E'.$htmlCode);
        }, ARRAY_FILTER_USE_BOTH);
        if (count($filteredConstants) === 1) {
            return array_values($filteredConstants)[0];
        }

        // throw another Error ^^'
        return self::UNKOWN_ERROR_CODE;
    }

    public function render(): void
    {
        $code = $this->errorInformation['code'];
        $msg = $this->errorInformation['msg'];
        $debug = $this->errorInformation['debug'];

        error($debug);
        abort($code, $msg);
    }

    public function renderJson(): void
    {
        JsonController::print_json(
            [
                'success' => false,
                'status' => $this->errorInformation['code'],
                'msg' => $this->errorInformation['msg'].(PHP_EOL.$this->errorInformation['additional'] ?? '').(PHP_EOL.DEV ? $this->errorInformation['debug'] ?? '' : ''),
                'type' => 'modal',
                'subtype' => 'server-error',
                'headline' => $this->errorInformation['headline'],
            ]
        );
    }

    private function cleanStackTrace(array $stackTrace): array
    {
        foreach ($stackTrace as &$item) {
            $item['file'] = substr($item['file'], strrpos($item['file'], '/'));
            $item['file'] = str_replace('.php', '', $item['file']);
            if (isset($item['class'])) {
                $item['class'] = substr($item['class'], strrpos($item['class'], '\\'));
            }
        }

        return $stackTrace;
    }
}
