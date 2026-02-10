<?php

class LbrSession {	

	public static function addKey(string $key, $val, string $system = '') {
		if ($system != '') {
			$_SESSION[$system][$key] = $val;
		} else {
			$_SESSION[$key] = $val;
		}
	}

	/**
	 * Remove uma key do REQUEST
	 * @param type $key
	 */
	public static function removeKey(string $key, string $system = '') {
		if ($system != '') {
			if (isset($_SESSION[$system][$key])) {
				unset($_SESSION[$system][$key]);
			}
		} else {
			if (isset($_SESSION[$key])) {
				unset($_SESSION[$key]);
			}
		}
	}

	public static function getKey(string $key, string $system = '') {
		if ($system != '') {
			return isset($_SESSION[$system][$key]) ? $_SESSION[$system][$key] : NULL;
		}
		return isset($_SESSION[$key]) ? $_SESSION[$key] : NULL;
	}

}
