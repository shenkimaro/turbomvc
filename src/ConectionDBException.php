<?php

class ConectionDBException extends Exception {

	public function printException() {		
		$r = rand(1, 3);
		$img = "dc{$r}.jpg";
		$fn = 'exception/connectionerror.html';
		$content = file_get_contents($fn, FILE_USE_INCLUDE_PATH);
		$content = str_replace('[default.imagem_banco;noerr]', $img, $content);
		die($content);
	}

}
