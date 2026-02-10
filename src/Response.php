<?php


/**
 *
 * @author ibanez
 */
class Response {
	public static function notFound() {
		header("HTTP/1.0 404 Not Found");
		echo "Nada encontrado.\n";
		die();
	}
}
