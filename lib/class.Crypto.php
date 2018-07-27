<?php
/**
 * class Crypto
 * framework class
 *
 * INTERTOPIA BASE FRAMEWORK
 * @package         intbf
 * @category        framework
 * @author 			Michael Gnehr
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved - do not copy without permission
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */

/**
 * class Crypto
 * framework class
 *
 * INTERTOPIA BASE FRAMEWORK
 * @package         intbf
 * @category        framework
 * @author 			Michael Gnehr
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved - do not copy without permission
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */
class Crypto
{
	/**
	 * private constructor, all member static
	 */
	private function __construct()
	{
	}
	
	// general ========================================================
	
	/**
	 * generates secure random hex string of length: 2*$length
	 * @param integer $length 0.5 string length
	 * @return NULL|string
	 */
	public static function generateRandomString($length) {
		if (!is_int($length)){
			throw new \Exception('Invalid argument type. Integer expected.');
			return null;
		}
		if (version_compare(PHP_VERSION, '7.0.0') >= 0 && function_exists('random_bytes')){
			return bin2hex(random_bytes($length));
		} else {
			return bin2hex(openssl_random_pseudo_bytes($length));
		}
	}
	
	/**
	 * pad string to minimum length of
	 * 
	 * encryption does not, and is not intended to, hide the length of the data being encrypted
	 * hide this before encryption
	 * @param string $string
	 * @param integer $length
	 * @return string
	 */
	public static function pad_string($string, $length = 128){
		$padlength = 0;
		if (mb_strlen($string) < $length){
			$padlength = $length - mb_strlen($string);
		}
		$exp = strlen(''.$length);
		$base = pow(10, $exp);
		$base += $padlength;
		$padstr = substr(self::generateRandomString(intval(floor($padlength/2)+1)), 0, $padlength);
		$string .= $padstr . '__padded__'.$base.'__';
		return $string;
	}
	
	/**
	 * unpad padded string
	 * restore padded string
	 *
	 * encryption does not, and is not intended to, hide the length of the data being encrypted
	 * hide this before encryption
	 * @param string $string
	 * @param integer $length
	 * @return string
	 */
	public static function unpad_string($string){
		if (preg_match('/__padded__\d\d+__$/', $string, $matches, PREG_OFFSET_CAPTURE)){
			$tmpout = substr($string, 0, $matches[0][1]);
			$padinfo = explode( '__', substr($string, $matches[0][1]));
			$triminfo = intval(substr($padinfo[2], 1));
			$string = substr($tmpout, 0, -$triminfo);
		}
		return $string;
	}
	
	// crypto =========================================================
	
	// without password -----------------------------------------------
	
	/**
	 * encrypt string with key
	 * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
	 * @param string $data
	 * @param string $keyAscii
	 * @return string encrypted string
	 */
	public static function encrypt_key ($data, $keyAscii){
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyAscii);
		$ciphertext = \Defuse\Crypto\Crypto::encrypt($data, $key);
		return $ciphertext;
	}
	
	/**
	 * decrypt string with secret key
	 * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
	 * @param string $ciphertext
	 * @param string $keyAscii
	 * @return string|false decrypted string | false if cipher was manipulated
	 */
	public static function decrypt_key ($ciphertext, $keyAscii){
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyAscii);
		try {
			$data = \Defuse\Crypto\Crypto::decrypt($ciphertext, $key);
			return $data;
		} catch (\Defuse\Crypto\WrongKeyOrModifiedCiphertextException $ex) {
			// An attack! Either the wrong key was loaded, or the ciphertext has
			// changed since it was created -- either corrupted in the database or
			// intentionally modified by Eve trying to carry out an attack.
			return false;
		}
	}
	
	// with password --------------------------------------------------
	
	/**
	 * encrypt string with key (key locked with password)
	 * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
	 * @param string $data
	 * @param string $keyAscii
	 * @param string $password
	 * @return string encrypted string
	 */
	public static function encrypt_key_by_pw ($data, $keyAscii, $password){
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = \Defuse\Crypto\KeyProtectedByPassword::loadFromAsciiSafeString($keyAscii);
		$key = $key->unlockKey($password);
		$ciphertext = \Defuse\Crypto\Crypto::encrypt($data, $key);
		return $ciphertext;
	}
	
	/**
	 * decrypt string with key (key locked with password)
	 * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
	 * @param string $ciphertext
	 * @param string $keyAscii
	 * @param string $password
	 * @return string|false decrypted string | false if cipher was manipulated
	 */
	public static function decrypt_key_by_pw ($ciphertext, $keyAscii, $password){
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = \Defuse\Crypto\KeyProtectedByPassword::loadFromAsciiSafeString($keyAscii);
		$key = $key->unlockKey($password);
		try {
			$data = \Defuse\Crypto\Crypto::decrypt($ciphertext, $key);
			return $data;
		} catch (\Defuse\Crypto\WrongKeyOrModifiedCiphertextException $ex) {
			// An attack! Either the wrong key was loaded, or the ciphertext has
			// changed since it was created -- either corrupted in the database or
			// intentionally modified by Eve trying to carry out an attack.
			return false;
		}
	}
	
	// key file helper =====================================================
	
	/**
	 * generate secret key and store it to file
	 * @param string $filename path to file
	 */
	public static function new_random_key_to_file($filename) {
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = \Defuse\Crypto\Key::createNewRandomKey();
		$pass_key = $key->saveToAsciiSafeString();
		
		//create file content
		$key_file_content = "<?php //* -------------------------------------------------------- *\n";
		$key_file_content .= "// Must include code to stop this file being accessed directly\n";
		$key_file_content .= "if(!defined('FINANRANTRAGUI_FW_SI')) die(); \n";
		$key_file_content .= "//* -------------------------------------------------------- *\n";
		$key_file_content .= '$KEY_SECRET = \''.$pass_key."';\n ?>";
		
		//create file
		$handle = fopen ($filename, 'w');
		fwrite ($handle, $key_file_content);
		fclose ($handle);
		chmod($filename, 0400);
	}
	
	/**
	 * generate secret key and store it to file
	 * @param string $filename path to file
	 * @param string $password
	 */
	public static function new_random_protected_key_to_file($filename, $password) {
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = \Defuse\Crypto\KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
		$pass_key = $key->saveToAsciiSafeString();
		
		//create file content
		$key_file_content = "<?php //* -------------------------------------------------------- *\n";
		$key_file_content .= "// Must include code to stop this file being accessed directly\n";
		$key_file_content .= "if(!defined('FINANRANTRAGUI_FW_SI')) die(); \n";
		$key_file_content .= "//* -------------------------------------------------------- *\n";
		$key_file_content .= '$KEY_SECRET = \''.$pass_key."';\n ?>";
		
		//create file
		$handle = fopen ($filename, 'w');
		fwrite ($handle, $key_file_content);
		fclose ($handle);
		chmod($filename, 0400);
	}
	
	/**
	 * read key secret from file
	 * @param string $filename
	 * @return string key
	 */
	public static function get_random_key_from_file($filename){
		if (file_exists($filename)){
			require($filename);
			$out = $KEY_SECRET;
			unset($KEY_SECRET);
			return $out;
		} else {
			return NULL;
		}
	}
	
	// HASHING ========================================================
	
	/**
	 * hashes password with best password algorithem and return data
	 * if argon2 is available this will be used, if not, bcrypt will be used
	 * @param string $password
	 * @return string
	 */
	public static function hashPassword($password){
		if (defined('PASSWORD_ARGON2I')){
			return self::hashPasswordArgon2($password);
		} else {
			return self::hashPasswordBcrypt($password);
		}
	}
	
	/**
	 * hashes password with argon2 algorithm
	 * @param string $password
	 * @return string
	 */
	public static function hashPasswordArgon2($password){
		return password_hash($password, PASSWORD_ARGON2I);
	}
	
	/**
	 * hashes password with bcrypt algorithm
	 * @param string $password
	 * @return string
	 */
	public static function hashPasswordBcrypt($password){
		$options = [
		    'cost' => 12,
		];
		return password_hash($password , PASSWORD_BCRYPT, $options);
	}
	
	/**
	 * check if provided password matches given hash value
	 * @param string $password
	 * @param string $hash
	 * @return bool
	 */
	public static function verifyPassword($password, $hash){
		return password_verify ($password, $hash);
	}
}
