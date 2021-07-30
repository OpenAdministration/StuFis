<?php


namespace framework\auth;


use framework\render\ErrorHandler;
use framework\Singleton;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use phpCAS;

class AuthCasHandler extends Singleton implements AuthHandler
{

    private static $HOST;
    private static $PORT;
    private static $PATH;
    private static $CAS_VERSION;
    private static $CERT_FILE;

    private static $ADMINGROUP;
    private static $AUTH_REALM;

    private static $DEBUG_CAS;

    private static $attributes;

    /**
     * @param mixed ...$pars
     * @return AuthHandler
     */
    public static function getInstance(...$pars): AuthHandler
    {
        return parent::getInstance(...$pars);
    }

    protected function __construct()
    {
        if(self::$DEBUG_CAS){
            $logger = new Logger('phpCAS');
            $logger->pushHandler(new RotatingFileHandler(SYSBASE . '/runtime/logs/cas.log'));
            phpCAS::setLogger($logger);
            if(DEV){
                phpCAS::setVerbose(true);
            }
        }
        phpCAS::client(self::$CAS_VERSION, self::$HOST, (int) self::$PORT, self::$PATH);
        phpCAS::setFixedServiceURL(FULL_APP_PATH);
        if(empty(self::$CERT_FILE)){
            phpCAS::setNoCasServerValidation();
        }else{
            phpCAS::setCasServerCACert(self::$CERT_FILE);
        }
    }

    final protected static function static__set($name, $value): void
    {
        if (property_exists(__CLASS__, $name)) {
            self::$$name = $value;
        } else {
            die("$name ist keine Variable in " . __CLASS__);
        }
    }

    /**
     * @inheritDoc
     */
    public function requireAuth(): void
    {
        if(!phpCAS::isAuthenticated() && !phpCAS::forceAuthentication()) {
            $this->reportPermissionDenied('Zugriff verweigert', 'forceAuth');
        }
    }

    /**
     * @inheritDoc
     */
    public function requireGroup($groups): void
    {
        if(!$this->hasGroup($groups)){
            $this->reportPermissionDenied("Fehlende Gruppenberechtigung", $groups);
        }
    }

    /**
     * @inheritDoc
     */
    public function hasGroup($groups, $delimiter = ","): bool
    {
        $this->requireAuth();

        if($this->isAdmin()){
            return true;
        }
        $attrGroups = $this->getAttributes()['groups'];
        if (is_string($groups)){
            $groups = explode($delimiter, $groups);
            $realm = self::$AUTH_REALM;
            array_walk($groups, static function (&$val, $key) use ($realm) {
                 $val = $realm . '-' . $val;
            });
        }
        $hasGroups = array_intersect($groups, $attrGroups);

        return count($hasGroups) > 1;
    }

    /**
     * @inheritDoc
     */
    public function getLogoutURL(): string
    {
        return phpCAS::getServerLogoutURL();
    }

    /**
     * @inheritDoc
     */
    public function logout(): void
    {
        phpCAS::logout();
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        $this->requireAuth();
        if(!isset(self::$attributes)){
            self::$attributes = phpCAS::getAttributes();
            self::$attributes['groups'] ??= [];
            self::$attributes['groups'] = (array) self::$attributes['groups'];
            self::$attributes['gremien'] ??= [];
            self::$attributes['gremien'] = (array) self::$attributes['gremien'];
        }
        return self::$attributes;
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): ?string
    {
        $this->requireAuth();
        return phpCAS::getUser();
    }

    /**
     * @inheritDoc
     */
    public function getUserFullName(): string
    {
        return $this->getAttributes()['name'] ?? phpCAS::getUser();
    }

    /**
     * @inheritDoc
     */
    public function getUserMail(): string
    {
        return $this->getAttributes()['email'];
    }

    /**
     * @inheritDoc
     */
    public function isAdmin(): bool
    {
        return in_array(self::$AUTH_REALM . '-' . self::$ADMINGROUP, $this->getAttributes()['groups'], true);
    }

    /**
     * @inheritDoc
     */
    public function hasGremium(string $gremien, string $delimiter = ','): bool
    {
        if($this->isAdmin()){
            return true;
        }
        $attrGremien = self::$attributes['gremien'] ?? [];
        $gremienArray = explode($delimiter, $gremien);
        $hasGremien = array_intersect($gremienArray, $attrGremien);

        return count($hasGremien) > 1;
    }

    public function reportPermissionDenied(string $errorMsg, string $debug): void
    {
        ErrorHandler::handleError(403, $errorMsg, $debug);
    }
}