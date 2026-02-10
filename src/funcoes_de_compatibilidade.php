<?php


//
// mb_str_split só existe a partir do php 7.4
// copiado de https://www.php.net/manual/en/function.mb-str-split.php
// Polyfill PHP < 7.4 based on package "symfony/polyfill-mbstring":
//
if (!function_exists('mb_str_split') && (function_exists('mb_strlen') && function_exists('mb_substr'))) {

	function mb_str_split($string, $split_length = 1, $encoding = null) {
		if (null !== $string && !\is_scalar($string) && !(\is_object($string) && \method_exists($string, '__toString'))) {
			trigger_error('mb_str_split(): expects parameter 1 to be string, ' . \gettype($string) . ' given', E_USER_WARNING);
			return null;
		}
		if (null !== $split_length && !\is_bool($split_length) && !\is_numeric($split_length)) {
			trigger_error('mb_str_split(): expects parameter 2 to be int, ' . \gettype($split_length) . ' given', E_USER_WARNING);
			return null;
		}
		$split_length = (int) $split_length;
		if (1 > $split_length) {
			trigger_error('mb_str_split(): The length of each segment must be greater than zero', E_USER_WARNING);
			return false;
		}
		if (null === $encoding) {
			$encoding = mb_internal_encoding();
		} else {
			$encoding = (string) $encoding;
		}

		if (!in_array($encoding, mb_list_encodings(), true)) {
			static $aliases;
			if ($aliases === null) {
				$aliases = [];
				foreach (mb_list_encodings() as $encoding) {
					$encoding_aliases = mb_encoding_aliases($encoding);
					if ($encoding_aliases) {
						foreach ($encoding_aliases as $alias) {
							$aliases[] = $alias;
						}
					}
				}
			}
			if (!in_array($encoding, $aliases, true)) {
				trigger_error('mb_str_split(): Unknown encoding "' . $encoding . '"', E_USER_WARNING);
				return null;
			}
		}

		$result = [];
		$length = mb_strlen($string, $encoding);
		for ($i = 0; $i < $length; $i += $split_length) {
			$result[] = mb_substr($string, $i, $split_length, $encoding);
		}
		return $result;
	}
}

if(!function_exists('split')){
	function split($pattern, $text) {
		$pattern = str_replace("/", "\/", $pattern);
		return preg_split("/$pattern/", $text);
	}
}	

if(!function_exists('ereg_replace')){
	function ereg_replace($pattern, $text) {
		$pattern = str_replace("/", "\/", $pattern);
		return preg_replace("/$pattern/", $text);
	}
}

if(!function_exists('eregi')){
	function eregi($pattern, $text,&$matches = '') {
		$pattern = str_replace("/", "\/", $pattern);
		$result = preg_match("/$pattern/", $text, $matches);
		return $result;
	}
}

if(!function_exists('ereg')){
	function ereg($pattern, $text,&$matches = '') {
		$pattern = str_replace("/", "\/", $pattern);
		$result = preg_match("/$pattern/", $text, $matches);
		return $result;
	}
}

if (!function_exists('set_magic_quotes_runtime') ) {
	function set_magic_quotes_runtime($value) { 
		return true;		
	}
}

if (!function_exists('get_magic_quotes_runtime') ) {
    function get_magic_quotes_runtime() { 
		return false;		
	}
}

if(!function_exists('get_magic_quotes_gpc')){
	function get_magic_quotes_gpc(){
		return false;
	}
}