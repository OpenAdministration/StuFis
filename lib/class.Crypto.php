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
 * BASE FRAMEWORK
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
	 * write key asci save string to file
	 * @param string $filename
	 * @param string $text
	 * @param boolean $overwrite
	 */
	private static function key_to_file($filename, $text, $overwrite = false){
		if (overwrite && file_exists($filename) && is_file($filename) && fileperms($filename) != '0777' ){
			chmod($filename, 0777);
		}
		
		//create file content
		$key_file_content = "<?php //* -------------------------------------------------------- *\n";
		$key_file_content .= "// Must include code to stop this file being accessed directly\n";
		$key_file_content .= "if(!defined('INTBF')) die(); \n";
		$key_file_content .= "//* -------------------------------------------------------- *\n";
		$key_file_content .= '$KEY_SECRET = \''.$text."';\n ?>";
		
		//create file
		$handle = fopen ($filename, 'w');
		fwrite ($handle, $key_file_content);
		fclose ($handle);
		chmod($filename, 0400);
		unset($text);
	}
	
	// string padding =========================================================
	
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
	
	// defuse crypto =========================================================
	
	// without password -----------------------------------------------
	
	/**
	 * encrypt string with key - defuse
	 * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
	 * @param string $data
	 * @param string $keyAscii
	 * @return string encrypted string
	 */
	public static function encrypt_by_key ($data, $keyAscii){
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = Defuse\Crypto\Key::loadFromAsciiSafeString($keyAscii);
		$ciphertext = Defuse\Crypto\Crypto::encrypt($data, $key);
		return $ciphertext;
	}
	
	/**
	 * decrypt string with secret key - defuse
	 * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
	 * @param string $ciphertext
	 * @param string $keyAscii
	 * @return string|false decrypted string | false if cipher was manipulated
	 */
	public static function decrypt_by_key ($ciphertext, $keyAscii){
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = Defuse\Crypto\Key::loadFromAsciiSafeString($keyAscii);
		try {
			$data = Defuse\Crypto\Crypto::decrypt($ciphertext, $key);
			return $data;
		} catch (Defuse\Crypto\WrongKeyOrModifiedCiphertextException $ex) {
			// An attack! Either the wrong key was loaded, or the ciphertext has
			// changed since it was created -- either corrupted in the database or
			// intentionally modified by Eve trying to carry out an attack.
			return false;
		}
	}
	
	// with password --------------------------------------------------
	
	/**
	 * encrypt string with key (key locked with password) - defuse
	 * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
	 * @param string $data
	 * @param string $keyAscii
	 * @param string $password
	 * @return string encrypted string
	 */
	public static function encrypt_by_key_pw ($data, $keyAscii, $password){
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = Defuse\Crypto\KeyProtectedByPassword::loadFromAsciiSafeString($keyAscii);
		$key = $key->unlockKey($password);
		$ciphertext = Defuse\Crypto\Crypto::encrypt($data, $key);
		return $ciphertext;
	}
	
	/**
	 * decrypt string with key (key locked with password) - defuse
	 * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
	 * @param string $ciphertext
	 * @param string $keyAscii
	 * @param string $password
	 * @return string|false decrypted string | false if cipher was manipulated
	 */
	public static function decrypt_by_key_pw ($ciphertext, $keyAscii, $password){
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = Defuse\Crypto\KeyProtectedByPassword::loadFromAsciiSafeString($keyAscii);
		$key = $key->unlockKey($password);
		try {
			$data = Defuse\Crypto\Crypto::decrypt($ciphertext, $key);
			return $data;
		} catch (Defuse\Crypto\WrongKeyOrModifiedCiphertextException $ex) {
			// An attack! Either the wrong key was loaded, or the ciphertext has
			// changed since it was created -- either corrupted in the database or
			// intentionally modified by Eve trying to carry out an attack.
			return false;
		}
	}
	
	// key file helper ==================================================
	
	/**
	 * generate secret key and store it to file - defuse
	 * @param string $filename path to file
	 */
	public static function new_key_to_file($filename) {
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = Defuse\Crypto\Key::createNewRandomKey();
		$pass_key = $key->saveToAsciiSafeString();
		
		//create file
		self::key_to_file($filename, $key->saveToAsciiSafeString(), false);
	}
	
	/**
	 * generate secret key and store it to file
	 * @param string $filename path to file
	 * @param string $password
	 */
	public static function new_protected_key_to_file($filename, $password) {
		require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');
		$key = Defuse\Crypto\KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
		
		//create file
		self::key_to_file($filename, $key->saveToAsciiSafeString(), false);
	}
	
	/**
	 * read key secret from file
	 * @param string $filename
	 * @return string key
	 */
	public static function get_key_from_file($filename){
		$out = $KEY_SECRET = NULL;
		if (file_exists($filename)){
			require($filename);
			$out = $KEY_SECRET;
			unset($KEY_SECRET);
		}
		return $out;
	}
	
	// SSL ENCRYPTION ===================================================
	
	/**
	 * create openssl key pair - openssl
	 * RSA @ 4096
	 */
	public static function new_openssl_pair($private_filename, $public_filename, $force = false){
		//check files exists
		if (!file_exists($private_filename) || filesize($private_filename) == 0 || $force){
			$config = array(
				"digest_alg" => "sha512",
				"private_key_bits" => 4096,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
			);
			$res = openssl_pkey_new($config);
			openssl_pkey_export($res, $privKey);
			//create file
			self::key_to_file($private_filename, base64_encode($privKey), $force);
			$pubKey = openssl_pkey_get_details($res);
			$pubKey = $pubKey["key"];
			//create file
			self::key_to_file($public_filename, base64_encode($pubKey), $force);
		}
	}
	
	/**
	 * encrypt data by public openssl key - openssl
	 * could be decrypted with private key
	 * @param string $data
	 * @param string $keyAscii
	 * @return string
	 */
	public static function encrypt_public_openssl($data, $keyAscii){
		$keyAscii = base64_decode($keyAscii);
		openssl_public_encrypt($data, $encrypted, $keyAscii);
		return base64_encode($encrypted);
	}
	
	/**
	 * decrypt data by private openssl key - openssl
	 * @param string $ciphertext
	 * @param string $keyAscii
	 * @return string
	 */
	public static function decrypt_private_openssl($ciphertext, $keyAscii){
		$keyAscii = base64_decode($keyAscii);
		openssl_private_decrypt(base64_decode($ciphertext), $decrypted, $keyAscii);
		if ($decrypted === NULL) return NULL;
		return $decrypted;
	}
	
	/**
	 * encrypt data by private openssl key - openssl
	 * could be decrypted with public key
	 * may used for signing
	 * @param string $data
	 * @param string $keyAscii
	 * @return string
	 */
	public static function encrypt_private_openssl($data, $keyAscii){
		$keyAscii = base64_decode($keyAscii);
		openssl_private_encrypt($data, $encrypted, $keyAscii);
		return base64_encode($encrypted);
	}
	
	/**
	 * decrypt data by public openssl key - openssl
	 * @param string $ciphertext
	 * @param string $keyAscii
	 * @return string
	 */
	public static function decrypt_public_openssl($ciphertext, $keyAscii){
		$keyAscii = base64_decode($keyAscii);
		openssl_public_decrypt(base64_decode($ciphertext), $decrypted, $keyAscii);
		if ($decrypted === NULL) return NULL;
		return $decrypted;
	}
	
	// HASHING (passwords, etc) =========================================
	
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
