<?php
/**
 * FRAMEWORK MailHandler
 * Sends Messages via SMTP Protocol
 *
 * BASE FRAMEWORK
 * @package         intbf
 * @category        html clients
 * @author 			Michael Gnehr
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved - do not copy without permission
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

/**
 * class MailHandler
 * @author Michael Gnehr <michael@gnehr.de>
 * @since 20.03.2017
 * @package _framework
 */
class MailHandler extends Singleton
{
	// member variables ==============================================

	/**
	 *
	 * @var PHPMailer
	 */
	public $mail;

	/**
	 *
	 * @var boolean
	 */
	protected $initOk;

	/**
	 *
	 * @var array
	 */
	protected $templateVars;

	/**
	 *
	 * @var string
	 */
	protected $templateName;

	/**
	 *
	 * @var string
	 */
	protected $logoImagePath;

	/**
	 * global mail settings
	 * @var array
	 */
	private static $_settings;

	/**
	 * instance mail settings
	 * @var array
	 */
	protected $settings;

	// constructor ==============================================

	/**
	 * class constructor
	 */
	protected function __construct($settings = null) {
		$this->initOk = false;
		$this->templateVars = array();
		$this->templateName = '';
		$this->logoImagePath = '/www/img/stura_black.svg';
		$this->settings = ($settings) ? $settings : self::$_settings;
	}

	/**
	 * return instance of this class
	 * singleton class
	 * return same instance on every call
	 * @param bool $noPermCheck
	 * @return BasicAuthHandler
	 */
	public static function getInstance(...$pars){
		return parent::getInstance(...$pars);
	}
	
	final static protected function static__set($name, $value){
		if (property_exists(get_class(), $name))
			self::$$name = $value;
		else
			die("$name ist keine Variable in " . get_class());
	}

	// modify settings =========================================

	/**
	 * set global mail config
	 * @param array $settings
	 */
	public static function _setSettings($settings){
		if (self::$_settings == NULL && self::checkMailsettings($settings)){
			self::$_settings = $settings;
		}
	}

	/**
	 * set instance mail config
	 * @param array $settings
	 */
	public function setSettings($settings){
		if (self::checkMailsettings($settings)){
			$this->settings = $settings;
		}
	}

	/**
	 * update instance mail config - key value
	 * @param string $key
	 * @param string $value
	 */
	public function setConfigOption($key, $value){
		if (isset($this->settings[$key])){
			$this->settings[$key] = $value;
		}
	}

	/**
	 * set logo image path
	 * set empty string to disable
	 * default: '/../public/images/logo_wt.png'
	 *
	 * @param string $logoImagePath
	 */
	public function setLogoImagePath($logoImagePath = '/www/img/stura_black.svg')
	{
		$this->logoImagePath = $logoImagePath;
	}

	// crypto functions ======================================

	public static function encryptPassword($p = NULL){
		if ($p === NULL) $p = self::$_settings['MAIL_PASSWORD'];
		if (!$p) return '';
		$p = Crypto::pad_string($p);
		return Crypto::encrypt_by_key_pw($p, Crypto::get_key_from_file(SYSBASE.'/secret.php'), URIBASE);
	}

	private static function decryptPassword($p){
		if (!$p) return '';
		$p = Crypto::decrypt_by_key_pw($p, Crypto::get_key_from_file(SYSBASE.'/secret.php'), URIBASE);
		$p = Crypto::unpad_string($p);
		return $p;
	}

	// init ==================================================

	/**
	 * test if settings array has all required parameters
	 * @param array $settings
	 */
	public static function checkMailsettings($settings){
		if (!is_array($settings)) return false;
		if (!isset($settings['MAIL_PASSWORD']) 	|| $settings['MAIL_PASSWORD'] == '') return false;
		if (!isset($settings['SMTP_HOST']) 		|| $settings['SMTP_HOST'] == '') return false;
		if (!isset($settings['SMTP_USER']) 		|| $settings['SMTP_USER'] == '') return false;
		if (!isset($settings['SMTP_SECURE']) 	|| $settings['SMTP_SECURE'] == '') return false;
		if (!isset($settings['SMTP_PORT']) 		|| $settings['SMTP_PORT'] == '' ||
				(!strtolower($settings['SMTP_PORT']) == 'tls' &&
				 !strtolower($settings['SMTP_PORT']) == 'ssl')) return false;
		if (!isset($settings['MAIL_FROM']) 		|| $settings['MAIL_FROM'] == '') return false;
		if (!isset($settings['MAIL_FROM_ALIAS'])|| $settings['MAIL_FROM_ALIAS'] == '') return false;
		return true;
	}

	/**
	 * initialize phpmailer object
	 * @param $settings mail settings
	 * @param array $settings
	 */
	public function init() {
		$settings = $this->settings;
		if (!self::checkMailsettings($settings)){
			error_log("Mailsettings unvollständig");
			return false;
		}
		$this->mail = new PHPMailer\PHPMailer\PHPMailer;
		$this->mail->setLanguage('de', MAIL_LANGUAGE_PATH); //TODO set Language //from Session
		$this->mail->CharSet = 'UTF-8';

		$settings['SMTP_SECURE'] = ($settings['SMTP_SECURE'] == 'STARTTLS')? 'TLS' : $settings['SMTP_SECURE'];
		$settings['SMTP_SECURE'] = strtolower($settings['SMTP_SECURE']);

		$mail_pw = self::decryptPassword($settings['MAIL_PASSWORD']);

		$this->mail->isSMTP();								// Set mailer to use SMTP
		$this->mail->Host = $settings['SMTP_HOST'];			// Specify main and backup SMTP servers
		$this->mail->SMTPAuth = true;						// Enable SMTP authentication
		$this->mail->Username = $settings['SMTP_USER'];		// SMTP username
		$this->mail->Password = $mail_pw;					// SMTP password
		$this->mail->SMTPSecure = $settings['SMTP_SECURE'];	// Enable TLS encryption, 'tls' or `ssl` also accepted
		$this->mail->Port = $settings['SMTP_PORT'];			// TCP port to connect to

		$this->mail->setFrom($settings['MAIL_FROM'], $settings['MAIL_FROM_ALIAS']);

		if ($this->logoImagePath){
			$ext = '.png';
			if (pathinfo($this->logoImagePath, PATHINFO_EXTENSION)){
				$ext = '.'.pathinfo($this->logoImagePath, PATHINFO_EXTENSION);
			}
			$this->mail->AddEmbeddedImage(SYSBASE.$this->logoImagePath, "logoattach", "mailLogo".$ext);
		}

		$this->mail->isHTML(true);							// Set email format to HTML
		$this->initOk = true;
		return true;
	}

	/**
	 * set mail template by filename (without extension)
	 * mailtemplate have to exist in choosen template folder in mail directory
	 * @param string $template_name
	 */
	public function setTemplate ($template_name){
		$template_name = trim($template_name);
		if(is_string($template_name)) {
			$template_name = str_replace('..', '.', $template_name);
			$template_name = str_replace('..', '.', $template_name);
			$this->templateName = $template_name;
		}
	}

	/**
	 * bind variables to mail template
	 * format array with keys. Template variables will be named like keys
	 * @param array $set
	 * @throws Exception
	 */
	public function bindVariables($set){
		if (!is_array($set)){
			throw (new Exception("ERROR: MailHandler: BindVariables: Das gegebene Datenset ist kein Array. Benötigtes Format: array('key' => 'value')"));
			return false;
		}
		$this->templateVars = $set;
		return true;
	}

	/**
	 * set single variables for mail template
	 * @param string|number $key
	 * @param mixed $value
	 * @throws Exception
	 * @return boolean
	 */
	public function addTemplateVariable($key, $value){
		$key = trim('' . $key);
		if ($key === null || !is_string('' . $key) || $key = ''){
			throw (new Exception('ERROR: MailHandler: AddTemplateVariable: Ungültige Variable: $key'));
			return false;
		}
		$this->templateVars[$key] = $value;
	}

	/**
	 * renders template phtml file and return string
	 * @param string $file
	 * @param array $param
	 */
	private static function renderPHTML($file, $param = NULL){
		ob_start();
		include($file);
		$mail_content_html = ob_get_clean();
		return $mail_content_html;
	}

	/**
	 * renders template txt file and return string
	 * @param string $file
	 * @param array $param
	 */
	private static function renderTXT($file, $param = NULL){
		$text_replacers = array();
		$text_values = array();
		foreach ($param as $key => $value){
			$text_replacers[] = "%".$key."%";
			$text_values[] = $value;
		}
		$mail_content_text = str_replace(
			$text_replacers,
			$text_values,
			file_get_contents($file)
		);
		return $mail_content_text;
	}

	/**
	 * send mail with phpmailer
	 * load mailtemplate, bind variables, and send mail with them
	 * @param string $echo echo mail status messages
	 * @param string $toSessionMessage store mail statusmessages to session (messagesystem)
	 * @param string $suppressOKMsg if $toSesstionMessage isset, suppress messages on success
	 * @param string $showPhpmailError show phpmailer errormessages in echo/SessionMessage/error_log
	 */
	public function send($echo = false, $toSessionMessage = true, $suppressOKMsg = true, $showPhpmailError = false){
		if (!defined('TEMPLATE')) define('TEMPLATE', '.');
		if (!$this->initOk){
			if ($echo) {
				echo 'Mailinitialisierung fehlgeschlagen. Bitte Informieren Sie den Webseitenbetreiber über diesen Fehler.';
			}
			if ($toSessionMessage){
				$_SESSION['SILMPH']['MESSAGES'][] = array('Mailinitialisierung fehlgeschlagen. Bitte Informieren Sie den Webseitenbetreiber über diesen Fehler.', 'WARNING');
			}
			return false;
		} else if ($this->templateName == ''){
			if ($echo) {
				echo 'Kein Mail-Template gewählt. Bitte Informieren Sie den Webseitenbetreiber über diesen Fehler.';
			}
			if ($toSessionMessage){
				$_SESSION['SILMPH']['MESSAGES'][] = array('Kein Mail-Template gewählt. Bitte Informieren Sie den Webseitenbetreiber über diesen Fehler.', 'WARNING');
			}
			ob_start();
			debug_print_backtrace(0, 5);
			$error_trace = ob_get_clean();
			error_log("Kein Mail-Template gewählt. Stacktrace:\n" . print_r($error_trace, true));
			return false;
		} else if (!file_exists(dirname(__FILE__)."/../template/".TEMPLATE."/mail/".$this->templateName.".txt") &&
				   !file_exists(dirname(__FILE__)."/../template/".TEMPLATE."/mail/".$this->templateName.".phtml") &&
				   !file_exists(dirname(__FILE__)."/../template/".TEMPLATE."/mail/".$this->templateName.".txt.phtml")){
			if ($echo) {
				echo 'Mail-Template konnte nicht gefunden werden. Bitte Informieren Sie den Webseitenbetreiber über diesen Fehler.';
			}
			if ($toSessionMessage){
				$_SESSION['SILMPH']['MESSAGES'][] = array('Mail-Template konnte nicht gefunden werden. Bitte Informieren Sie den Webseitenbetreiber über diesen Fehler.', 'WARNING');
			}
			ob_start();
			debug_print_backtrace(0, 5);
			$error_trace = ob_get_clean();
			$emsg = "Mail-Template konnte nicht gefunden werden. TEMPLATE_NAME: ".$this->templateName." Stacktrace:\n" . print_r($error_trace, true);
			error_log($emsg);
			if ($showPhpmailError) echo '<pre>'.$emsg.'</pre>';
			return false;
		} else {
			//bind template
			if (file_exists(SYSBASE."/template/".TEMPLATE."/mail/".$this->templateName.".txt")){
				$this->mail->AltBody = self::renderTXT(SYSBASE."/template/".TEMPLATE."/mail/".$this->templateName.".txt", $this->templateVars);
			} elseif (file_exists(SYSBASE."/template/".TEMPLATE."/mail/".$this->templateName.".txt.phtml")){
				$this->mail->AltBody = self::renderPHTML(SYSBASE."/template/".TEMPLATE."/mail/".$this->templateName.".txt.phtml", $this->templateVars);
			}
			if (file_exists(SYSBASE."/template/".TEMPLATE."/mail/".$this->templateName.".phtml")){
				$this->mail->Body = self::renderPHTML(SYSBASE."/template/".TEMPLATE."/mail/".$this->templateName.".phtml", $this->templateVars);
			}
			try {
				if(!$this->mail->send()) {
					if ($echo) {
						echo 'Message could not be sent.'.(($showPhpmailError)? ' '.$this->mail->ErrorInfo : '');
					}
					if ($toSessionMessage){
						$_SESSION['SILMPH']['MESSAGES'][] = array('Die Nachricht konnte nicht gesendet werden.'.(($showPhpmailError)? ' '.$this->mail->ErrorInfo : ''), 'WARNING');
					}
					ob_start();
					debug_print_backtrace(0, 5);
					$error_trace = ob_get_clean();
					error_log("Mail konnte nicht gesendet werden werden. FEHLER: ".$this->mail->ErrorInfo." \nStacktrace:\n" . print_r($error_trace, true));
					return false;
				} else {
					if ($echo) {
						if (!$suppressOKMsg) echo 'Die E-Mail wurde erfolgreich verschickt.';
					}
					if ($toSessionMessage){
						if (!$suppressOKMsg) $_SESSION['SILMPH']['MESSAGES'][] = array('Die E-Mail wurde erfolgreich verschickt.', 'SUCCESS');
					}
					return true;
				}
			} catch (Exception $e) {
				error_log("Mail konnte nicht gesendet werden werden. ERROR: ".$e->getMessage()." \nStacktrace:\n" . print_r($error_trace, true));
				return false;
			}
		}
	}

	/**
	 * debug SMTP settings
	 * @param array $settings
	 * @param function $out function($message, $add_emptyline_suffix = false, $bold = false, $add_extra_tab_space = 0)
	 * @throws Exception
	 */
	public static function smtpdebug($out, $settings = NULL){
		$settings = ($settings)? $settings : self::$_settings;
		//get settings ----------------------------------
		$mail_pw = self::decryptPassword($settings['MAIL_PASSWORD']);
		$settings['SMTP_SECURE'] = ($settings['SMTP_SECURE'] == 'STARTTLS')? 'TLS' : $settings['SMTP_SECURE'];
		$settings['SMTP_SECURE'] = strtolower($settings['SMTP_SECURE']);

		$out('Current Settings:', 0, 1);
		foreach ($settings as $k => $v){
			$out($k.' -> '.$v, 0, 0, 1);
		}
		if (DEBUG > 0){
			$out('PW[Decrypted] -> '.$mail_pw, 1, 0, 1);
		} else {
			$out('PW[Decrypted] -> ****** NO DEBUG MODE ******', 1, 0, 1);
		}

		// ----------------------------------------------
		$out('Create SMTP Connection', 0, 1, 0);
		date_default_timezone_set('Etc/UTC');

		try {
			$smtp = new PHPMailer\PHPMailer\SMTP;
			$smtp->do_debug = PHPMailer\PHPMailer\SMTP::DEBUG_CONNECTION;

			//settimeout
			$out('Set Timeout', 0, 0, 0);
			set_time_limit(40); // set the time limit to 120 seconds
			$smtp->Timeout       =   30; // set the timeout (seconds)
			$smtp->Timelimit       =   10; // set the timelimit (seconds)
			$out('-> done', 1, 0, 1);

			//Connect to an SMTP server
			$out('Connect to '.(($settings['SMTP_SECURE'] == 'ssl')?'ssl://':'').$settings['SMTP_HOST'].':'.$settings['SMTP_PORT'].' ...', 0, 1, 0);
			ob_start();
			$ok = $smtp->connect((($settings['SMTP_SECURE'] == 'ssl')?'ssl://':'').$settings['SMTP_HOST'], $settings['SMTP_PORT']);
			$message = ob_get_clean();
			$out( htmlspecialchars($message) ,0, 0, 1);
			if (!$ok) {
				throw new Exception('-> failed');
			} else {
				$out('-> connected', 1, 0, 1);
			}

			//Say hello
			$out('say hello (own host: '.gethostname().')', 0, 1, 0);
			ob_start();
			$ok = $smtp->hello(gethostname());
			$message = ob_get_clean();
			$out( htmlspecialchars($message) ,0, 0, 1);
			if (!$ok) {
				throw new Exception('-> EHLO failed: ' . $smtp->getError()['error']);
			} else {
				$out('-> ok', 1, 0, 1);
			}

			//Get the list of ESMTP services the server offers
			$out('Get the list of ESMTP services the server offers...', 0, 1, 0);
			$e = $smtp->getServerExtList();
			foreach ($e as $k => $v){
				if (is_string($v)) {
					$out($k.' -> '.$v, 0, 0, 1);
				} elseif (is_array($v)){
					$out($k.' -> '.implode(', ' ,$v), 0, 0, 1);
				} elseif (is_bool($v)) {
					$out($k.' -> '.(($v)?'true':'false'), 0, 0, 1);
				} else {
					echo '<pre>'; var_dump($v); echo '</pre>';
				}
			}
			$out('');

			//If server can do TLS encryption, use it
			if ($settings['SMTP_SECURE'] == 'tls'){
				$out('USE TLS ---------', 0, 1 , 0);
				if (!is_array($e) || !array_key_exists('STARTTLS', $e) ){
					$out('NO STARTTLS IN RESPONSE - TRY ANYWAY...', 0, 1 , 1);
				}
			} else {
				$out('USE SSL ----------', 1, 1 , 0);
			}

			if ($settings['SMTP_SECURE'] == 'tls') {
				$out('STARTTLS...', 0, 0, 0);
				ob_start();
				$tlsok = $smtp->startTLS();
				$message = ob_get_clean();
				if ($message != ''){
					$out( htmlspecialchars($message) ,0, 0, 1);
				}
				if (!$tlsok) {
					throw new Exception('Failed to start encryption: ' . $smtp->getError()['error']);
				} else {
					$out('-> ok', 1, 0, 1);
				}

				//Repeat EHLO after STARTTLS
				$out('Repeat EHLO after STARTTLS', 0, 0, 0);
				ob_start();
				$ok = $smtp->hello(gethostname());
				$message = ob_get_clean();
				$out( htmlspecialchars($message) ,0, 0, 1);
				if (!$ok) {
					throw new Exception('EHLO (2) failed: ' . $smtp->getError()['error']);
				} else {
					$out('-> ok', 1, 0, 1);
				}

				//Get new capabilities list, which will usually now include AUTH if it didn't before
				$out('get capabilities again', 0, 0, 0);
				$e = $smtp->getServerExtList();
				foreach ($e as $k => $v){
					if (is_string($v)) {
						$out($k.' -> '.$v, 0, 0, 1);
					} elseif (is_array($v)){
						$out($k.' -> '.implode(', ' ,$v), 0, 0, 1);
					} elseif (is_bool($v)) {
						$out($k.' -> '.(($v)?'true':'false'), 0, 0, 1);
					} else {
						echo '<pre>'; var_dump($v); echo '</pre>';
					}
				}
				$out('');
			}

			//If server supports authentication, do it (even if no encryption)
			$out('Login Posible?', 0, 1, 0);
			if (is_array($e) && isset($e['AUTH'])) {
				$out('-> yes, includes AUTH', 0, 1, 1);
				$out('Login ...', 0, 1, 0);
				error_log('SMTPdebug Login: '.$settings['SMTP_USER'].' ...' );

				ob_start();
				$ok = $smtp->authenticate($settings['SMTP_USER'], $mail_pw);
				$message = ob_get_clean();
				$out( htmlspecialchars($message) ,0, 0, 1);
				if ($ok) {
					error_log('Connected -> OK');
					$out('-> Connected  ok', 1, 1, 1);
				} else {
					error_log('NOT Connected -> FAILURE');
					throw new Exception('Authentication failed: ' . $smtp->getError()['error']);
				}
			} else {
				$out('-> no, includes no AUTH', 1, 1, 1);
			}
		} catch (Exception $e) {
			$out($e->getMessage(), 0, 0, 1);
		}
		return true;
	}

	/**
	 * send mail
	 * @param array $mailOptions mail content
	 *  supported values
	 * 	[
	 * 		'to' => [],			(required)
	 * 		'cc' => [], 		(optional)
	 * 		'bcc' => [], 		(optional)
	 * 		'subject' => '',	(optional)
	 * 		'template' => '',	(required)
	 * 		'param' => [],		(optional)
	 *  ]
	 * @param boolean $echoError
	 * @return boolean
	 */
	public function easyMail($mailOptions, $echoError = true){
		$initOk = $this->init();
		if (!$initOk){
			if ($echoError) echo 'Error in mail settings';
			return false;
		}
		if (!isset($mailOptions['to']) || !is_array($mailOptions['to']) || count($mailOptions['to']) == 0 ){
			if ($echoError) echo '$mailOptions[\'to\'] has to be set, non empty and have to be an array';
			return false;
		}
		if (!isset($mailOptions['template']) || !is_string($mailOptions['template']) || !$mailOptions['template']){
			if ($echoError) echo '$mailOptions[\'template\'] has to be set, non empty and have to be a string';
			return false;
		}

		//mail to ------------
		foreach ($mailOptions['to'] as $to){
			$this->mail->addAddress($to);
		}
		if (isset($mailOptions['cc']))foreach ($mailOptions['cc'] as $to){
			$this->mail->addCC($to);
		}
		if (isset($mailOptions['bcc']))foreach ($mailOptions['bcc'] as $to){
			$this->mail->addBCC($to);
		}

		//mail subject ----------
		if (isset($mailOptions['subject'])){
			$this->mail->Subject = $mailOptions['subject'];
		}

		//$mailOptions params ----------
		$mailParam = [
			'time' 			=> date_create()->format('H:i d.m.Y'),
			'base_url' 		=> BASE_URL,
			'base_sub' 		=> URIBASE
		];
		if (isset($mailOptions['param']) && is_array($mailOptions['param'])){
			$mailParam = $mailParam + $mailOptions['param'];
		}
		$this->bindVariables($mailParam);

		//mail template ----------
		$this->setTemplate($mailOptions['template']);

		// send mail
		if($this->send($echoError, false, true, $echoError)){
			return true;
		} else {
			$emsg = 'Es konnte keine Mail versendet werden. Prüfen Sie bitte die Konfiguration. '.((isset($this->mail) && isset($this->mail->ErrorInfo))? $this->mail->ErrorInfo: '' );
			ErrorHandler::_errorLog($emsg);
			if ($echoError) echo $emsg;
			return false;
		}
	}
}

?>