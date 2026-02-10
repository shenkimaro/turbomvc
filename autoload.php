<?php
/**
 * Esse arquivo deve ser incluido na raiz do projeto
 */

if(!class_exists('AutoLoader')) {
	require __DIR__.'/AutoLoader.php5';
}

if(!function_exists('getCallerDirectory')){
	function getCallerDirectory() {
		$line = getCallerFile();
		$fileParts = explode(DIRECTORY_SEPARATOR, $line['file']);
		$last = count($fileParts) - 1;
		return $fileParts[$last - 1];
	}
}

if(!function_exists('getFullCallerDirectory')){
	function getFullCallerDirectory() {
		$last = getCallerFile();
		return dirname($last['file']);
	}
}

if(!function_exists('getCallerFile')){
	function getCallerFile() {
		$backTrace = debug_backtrace();
		$line = '';
		foreach ($backTrace as $trace) {
			$args = $trace['args'] ?? [];
			foreach ($args as $value) {
				if (strpos($value, 'autoload')) {
					return $trace;
				}
			}
		}
		return $line;
	}
}

AutoLoader::init();

if (!function_exists('findProjectJson')) {
	function findProjectJson($startDir) {
		$dir = $startDir;
		// Primeiro procura subindo na hierarquia
		for ($i = 0; $i < 10; $i++) {
			$path = $dir . DIRECTORY_SEPARATOR . 'turbo.json';
			if (is_file($path)) {
				return $path;
			}
			$parent = dirname($dir);
			if ($parent === $dir) {
				break;
			}
			$dir = $parent;
		}
		
		// Se não encontrou, procura em diretórios adjacentes (siblings)
		// Útil quando autoload é chamado via library/ mas o projeto está em strictosensu/
		$parent = dirname($startDir);
		if (is_dir($parent)) {
			$siblings = @scandir($parent);
			if ($siblings !== false) {
				foreach ($siblings as $sibling) {
					if ($sibling === '.' || $sibling === '..') {
						continue;
					}
					$siblingPath = $parent . DIRECTORY_SEPARATOR . $sibling;
					if (is_dir($siblingPath)) {
						$jsonPath = $siblingPath . DIRECTORY_SEPARATOR . 'turbo.json';
						if (is_file($jsonPath)) {
							return $jsonPath;
						}
					}
				}
			}
		}
		
		return null;
	}
}

if (!function_exists('getProjectBaseFromJson')) {
	function getProjectBaseFromJson($jsonPath) {
		$contents = file_get_contents($jsonPath);
		$data = json_decode($contents, true);
		if (!is_array($data)) {
			return null;
		}
		$extra = isset($data['extra']) ? $data['extra'] : [];
		$relative = isset($extra['project-path']) ? $extra['project-path'] : '.';
		$base = realpath(dirname($jsonPath) . DIRECTORY_SEPARATOR . $relative);
		if ($base === false) {
			$base = dirname($jsonPath) . DIRECTORY_SEPARATOR . $relative;
		}
		return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}
}

$callerDir = getFullCallerDirectory();
$projectJson = $callerDir ? findProjectJson($callerDir) : null;
if ($projectJson) {
	$baseDir = getProjectBaseFromJson($projectJson);
	if ($baseDir) {
		$namespace = basename(rtrim($baseDir, DIRECTORY_SEPARATOR)) . '\\';
		// Debug: descomentar para ver o que está sendo registrado
		error_log("TURBO DEBUG: Registering namespace '$namespace' -> '$baseDir' from json '$projectJson'");
		AutoLoader::registerForNamespace($namespace, $baseDir);
	} else {
		AutoLoader::registerForNamespace(getCallerDirectory(), $callerDir);
	}
} else {
	error_log("TURBO DEBUG: No project.json found for caller dir: " . ($callerDir ?? 'NULL'));
	AutoLoader::registerForNamespace(getCallerDirectory(), $callerDir);
}

