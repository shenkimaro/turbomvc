<?php

/**
 * Utilitario de criptografia
 *
 * @author ibanez
 */
set_time_limit(3600);
ini_set("memory_limit", "256M");
class Crypt {

	const HASH_WHIRLPOOL = 'whirlpool';
	const HASH_MD4 = 'md4';
	const HASH_MD5 = 'md5';
	const HASH_SHA256 = 'sha256';
	const HASH_SHA512 = 'sha512';

	public static $tag = 'ZLz128';
	public static $abc = 'abcdefghijklmnopqrstuvwxyz0123456789=+/ABCDEFGHIJKLMNOPQRSTUVWXYZ';

	public static function getPublickey($res) {
		$keyArray = openssl_pkey_get_details($res);
		$pubKey = $keyArray["key"];
		return $pubKey;
	}

	/**
	 * Assina um texto retornando a assinatura e o texto
	 * @param string $cleartext
	 * @param string $private_key
	 * @return string
	 */
	public static function sign($cleartext, $private_key) {
		$msg_hash = self::hash($cleartext, self::HASH_WHIRLPOOL);
		openssl_private_encrypt($msg_hash, $sig, $private_key);
		$signed_data = $sig . "----ASSINATURA:----" . $cleartext;
		return $signed_data;
	}

	/**
	 * Retorna um texto previamente assinado com Crypt::sign()
	 * @param type $signedData
	 * @param type $publicKey
	 * @return string
	 */
	public static function getSignText($signedData, $publicKey) {
		list($old_sig, $plain_data) = explode("----ASSINATURA:----", $signedData);
		openssl_public_decrypt($old_sig, $decrypted_sig, $publicKey);
		$data_hash = self::hash($plain_data, self::HASH_WHIRLPOOL);
		if ($decrypted_sig == $data_hash && strlen($data_hash) > 0) {
			return $plain_data;
		}
		return "ERRO -- Assinatura quebrada";
	}

	/**
	 * Realiza funcao de hash use uma constante da classe Crypt
	 * @param string $data
	 * @param string $algoritmo
	 * @return string
	 */
	public static function hash($data, $algoritmo, $binary = false) {
		$r = hash($algoritmo, $data, $binary);
		return $r;
	}

	/**
	 * Lista possiveis algoritmos nativos de hash
	 * @param string $data
	 */
	public static function listaAlgoritmosResumo($data) {
		foreach (hash_algos() as $v) {
			$r = hash($v, $data, false);
			printf("%-12s %3d %s\n", $v, strlen($r), $r);
			echo "<br>";
		}
	}

	/**
	 * Cuidado, caso a informação que deseja encriptar seja persistente, informe um valor para o salt
	 * @param string $message
	 * @param string $password
	 * @param bool $limit
	 * @param string $salt Caso o dado seja persistente informe um valor para o salt
	 * @return string
	 * @throws Exception
	 */
	public static function encrypt($message, $password, $limit = true, $salt = '') {
		if ($limit && strlen($message) > 256) {
			throw new Exception("O valor foi perdido.");
		}
		$iv = random_bytes(16);
		$key = self::getKey($password, $salt);
		$result = self::assinar(openssl_encrypt($message, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv), $key);
		return bin2hex($iv) . bin2hex($result);
	}

	/**
	 * Usada para encriptar dados para banco de dados
	 * @param string $message
	 * @param string $password
	 * @param string $salt
	 * @return string
	 */
	public static function encryptPermanently($message, $password, $salt) {
		return self::encrypt($message, $password, false, $salt);
	}

	public static function decrypt($hash, $password, $salt = '') {
		$iv = hex2bin(substr($hash, 0, 32));
		$hashValue = substr($hash, 32);
		if (strlen($hashValue) % 2 != 0) { //precisa ter uma quantidade par
			throw new Exception("O dado foi corrompido na comunicação.");
		}
		$data = hex2bin($hashValue);
		$key = self::getKey($password, $salt);
		if (!self::verify($data, $key)) {
			throw new Exception("O dado foi perdido na comunicação.");
		}
		return openssl_decrypt(mb_substr($data, 64, null, '8bit'), 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv);
	}

	public static function decryptSimple($password, $text) {
		$lastPassLetter = 0;
		// this is the final decrypted string
		$decrypted = '';

		// let's start...
		for ($i = 0; $i < strlen($text); $i++) {

			// next letter from the string to decrypt
			$letter = $text[$i];

			// get the next letter from the password
			$passwordLetter = $password[$lastPassLetter];  
			// get the decrypted letter according to the password
			$temp = self::getInvertedLetterFromAlphabetForLetter($passwordLetter, $letter);
			// concat the response
			$decrypted .= $temp;

			// if our password is too short, 
			// let's start again from the first letter  
			if ($lastPassLetter == strlen($password) - 1) {
				$lastPassLetter = 0;
			} else {
				$lastPassLetter++;
			}
		} // return the decrypted string and converted 
		// from base64 to plain text 
		return base64_decode($decrypted);
	}

	private static function getInvertedLetterFromAlphabetForLetter($letter, $letterToChange) {
		$abc = self::$abc;
		$posLetter = strpos($abc, $letter);		
		if ($posLetter === false) {
			throw new Exception('Password letter ' . $letter . ' not allowed.');
		}
		$part1 = substr($abc, $posLetter);
		$part2 = substr($abc, 0, $posLetter);

		$newABC = $part1 . $part2;		

		$posLetterToChange = strpos($newABC, $letterToChange);
		
		if ($posLetterToChange === false) {
			throw new Exception('Password letter ' . $letter . ' not allowed.');
		}

		$letterAccordingToAbc = $abc[$posLetterToChange];
		return "$letterAccordingToAbc";
	}

	private static function assinar($message, $key) {
		return hash_hmac('sha256', $message, $key) . $message;
	}

	private static function verify($bundle, $key) {
		return hash_equals(
			hash_hmac('sha256', mb_substr($bundle, 64, null, '8bit'), $key),
			mb_substr($bundle, 0, 64, '8bit')
		);
	}

	private static function getKey($password, $salt = '', $keysize = 16) {
		if ($salt == '') {
			$salt = date('W') . 'library' . date('W.Y'); //salt muda 1 vez por semana
		}
		return hash_pbkdf2('sha256', $password, $salt, 100000, $keysize, true);
	}

}
