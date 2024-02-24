<?php

namespace framework\auth;

use framework\render\ErrorHandler;
use JetBrains\PhpStorm\NoReturn;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Utils;

class AuthSamlHandler extends AuthHandler
{
    private Auth $saml;

    protected function __construct()
    {
        $settings = [
            // If 'strict' is True, then the PHP Toolkit will reject unsigned
            // or unencrypted messages if it expects them to be signed or encrypted.
            // Also it will reject the messages if the SAML standard is not strictly
            // followed: Destination, NameId, Conditions ... are validated too.
            'strict' => $_ENV['SAML_STRICT'],
            // Enable debug mode (to print errors).
            'debug' => DEV,

            // Set a BaseURL to be used instead of try to guess
            // the BaseURL of the view that process the SAML Message.
            // Ex http://sp.example.com/
            //    http://example.com/sp/
            'baseurl' => $_ENV['URIBASE'],

            // Service Provider Data that we are deploying.
            'sp' => [
                // Identifier of the SP entity  (must be a URI)
                'entityId' => $_ENV['SAML_SP_ENTITY_ID'],
                // Specifies info about where and how the <AuthnResponse> message MUST be
                // returned to the requester, in this case our SP.
                'assertionConsumerService' => [
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => FULL_APP_PATH . 'auth/login',
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports this endpoint for the
                    // HTTP-POST binding only.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                // If you need to specify requested attributes, set a
                // attributeConsumingService. nameFormat, attributeValue and
                // friendlyName can be omitted
                /*'attributeConsumingService' => [
                    'serviceName' => 'SP test',
                    'serviceDescription' => 'Test Service',
                    'requestedAttributes' => [
                        [
                            'name' => 'username',
                            'isRequired' => false,
                            'nameFormat' => '',
                            'friendlyName' => '',
                            'attributeValue' => [],
                        ],
                    ],
                ],*/
                // Specifies info about where and how the <Logout Response> message MUST be
                // returned to the requester, in this case our SP.
                'singleLogoutService' => [
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => FULL_APP_PATH . 'auth/logout',
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                // Specifies the constraints on the name identifier to be used to
                // represent the requested subject.
                // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported.
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                // Usually x509cert and privateKey of the SP are provided by files placed at
                // the certs folder. But we can also provide them with the following parameters
                'x509cert' => $_ENV['SAML_SP_X509CERT'],
                'privateKey' => $_ENV['SAML_SP_PRIVATE_KEY'],
            ],

            // Identity Provider Data that we want connected with our SP.
            'idp' => [
                // Identifier of the IdP entity  (must be a URI)
                'entityId' => $_ENV['SAML_IDP_ENTITY_ID'],
                // SSO endpoint info of the IdP. (Authentication Request protocol)
                'singleSignOnService' => [
                    // URL Target of the IdP where the Authentication Request Message
                    // will be sent.
                    'url' => $_ENV['SAML_IDP_SSO'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                // SLO endpoint info of the IdP.
                'singleLogoutService' => [
                    // URL Location of the IdP where SLO Request will be sent.
                    'url' => $_ENV['SAML_IDP_SLO'],
                    // URL location of the IdP where the SP will send the SLO Response (ResponseLocation)
                    // if not set, url for the SLO Request will be used
                    'responseUrl' => $_ENV['SAML_IDP_SLO'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                // Public x509 certificate of the IdP
                'x509cert' => $_ENV['SAML_IDP_PUBCERT'],
                /*
                 *  Instead of use the whole x509cert you can use a fingerprint in order to
                 *  validate a SAMLResponse, but we don't recommend to use that
                 *  method on production since is exploitable by a collision attack.
                 *  (openssl x509 -noout -fingerprint -in "idp.crt" to generate it,
                 *   or add for example the -sha256 , -sha384 or -sha512 parameter)
                 *
                 *  If a fingerprint is provided, then the certFingerprintAlgorithm is required in order to
                 *  let the toolkit know which algorithm was used. Possible values: sha1, sha256, sha384 or sha512
                 *  'sha1' is the default value.
                 *
                 *  Notice that if you want to validate any SAML Message sent by the HTTP-Redirect binding, you
                 *  will need to provide the whole x509cert.
                 */
                // 'certFingerprint' => '',
                // 'certFingerprintAlgorithm' => 'sha1',

                /* In some scenarios the IdP uses different certificates for
                 * signing/encryption, or is under key rollover phase and
                 * more than one certificate is published on IdP metadata.
                 * In order to handle that the toolkit offers that parameter.
                 * (when used, 'x509cert' and 'certFingerprint' values are
                 * ignored).
                 */
                // 'x509certMulti' => array(
                //      'signing' => array(
                //          0 => '<cert1-string>',
                //      ),
                //      'encryption' => array(
                //          0 => '<cert2-string>',
                //      )
                // ),
            ],
            'security' => [


                // Indicates whether the <samlp:AuthnRequest> messages sent by this SP
                // will be signed.  [Metadata of the SP will offer this info]
                'authnRequestsSigned' => true,
                // Indicates whether the <samlp:logoutRequest> messages sent by this SP
                // will be signed.
                'logoutRequestSigned' => true,
            ],
        ];
        $this->saml = new Auth($settings);
    }

    public function requireAuth(): void
    {
        if (isset($_SESSION['samlUserdata'])) {
            return;
        }
        if (!$this->saml->isAuthenticated()) {
            // TODO: return url = current url
            $this->saml->login(forceAuthn: true);
        }
        /*
        if (!$this->hasGroup(self::$AUTHGROUP)) {
            $this->reportPermissionDenied('Eine der Gruppen ' . self::$AUTHGROUP . ' wird benötigt');
        }
        */
    }

    protected function getAttributes(): array
    {
        $this->requireAuth();
        return $_SESSION['samlUserdata'];
    }

    public function requireGroup(array|string $groups): void
    {
        if (!$this->hasGroup($groups)) {
            $this->reportPermissionDenied('Eine der Gruppen ' . $groups . ' wird benötigt');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasGroup(array|string $groups, string $delimiter = ','): bool
    {
        $authGroups = $this->getUserGroups();
        if ($this->isAdmin()) {
            return true;
        }
        if (is_array($groups)) {
            $groups = array_map('strtolower', $groups);
        } else {
            $groups = strtolower($groups);
            $groups = explode($delimiter, $groups);
        }
        if (count(array_intersect($groups, array_map('strtolower', $authGroups))) === 0) {
            return false;
        }
        return true;
    }

    public function getLogoutURL(): string
    {
        return FULL_APP_PATH . 'auth/logout';
    }

    public function login(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && isset($_POST['SAMLResponse'])) {
            // POST -> SAML Response Message
            $auth = $this->saml;
            $auth->processResponse();
            $errors = $auth->getErrors();

            if (!$this->saml->isAuthenticated() || !empty($errors)) {
                ErrorHandler::handleError(500, 'SAML Auth failed', $errors);
            }

            $_SESSION['samlUserdata'] = $auth->getAttributes();
            $_SESSION['IdPSessionIndex'] = $auth->getSessionIndex();
            if (isset($_POST['RelayState']) && Utils::getSelfURL() !== $_POST['RelayState']) {
                // TODO: to avoid 'Open Redirect' attacks, before execute the
                // redirection confirm the value of $_POST['RelayState'] is a trusted URL.
                $auth->redirectTo($_POST['RelayState']);
            }
        } else {
            // GET
            $this->saml->login();
        }
    }

    public function logout(): void
    {
        if (isset($_GET['SAMLResponse'])) {
            // process logout saml msg
            $this->saml->processSLO();
            $errors = $this->saml->getErrors();
            if (!empty($errors)) {
                ErrorHandler::handleError(500, 'SAML Logout gescheitert', $errors);
            }
        } else {
            // initiate logout
            $this->saml->logout(returnTo: FULL_APP_PATH . 'auth/logout', sessionIndex: $_SESSION['IdPSessionIndex']);
            exit();
        }
    }

    public function getSpMetaDataXML(): string
    {
        $settings = $this->saml->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);
        if (empty($errors)) {
            // header('Content-Type: text/xml');
            return $metadata;
        }
        ErrorHandler::handleError(500, 'SAML not correct configured', $errors);
    }
}
