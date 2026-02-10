<?php

class AutoLoader {


	public static $loader = [];

	/**
	 * Limpa todos os registros do AutoLoader (para hot reload)
	 */
	public static function clearRegistrations() {
		// Limpar array estático
		$oldCount = count(self::$loader);
		self::$loader = [];
		
		// NÃO limpar $GLOBALS['loader'] aqui - será atualizado pelo load.php
		// Apenas forçar uma "invalidação" para próximo acesso
		
		//"[AUTOLOADER] Registros internos limpos para hot reload (removidos: $oldCount)\n";
	}

	/**
	 * Inicializa o auloLoader
	 */
	public static function init() {
		// Evitar múltiplos registros durante hot reload
		static $initialized = false;
		
		if (!$initialized) {
			require __DIR__ . '/src/funcoes_de_compatibilidade.php';
			
			spl_autoload_register(function ($class) {

				// project-specific namespace prefix
				$prefix = 'library\\';

				// base directory for the namespace prefix
				$base_dir = __DIR__ . '/';

				// does the class use the namespace prefix?
				$len = strlen($prefix);
				if (strncmp($prefix, $class, $len) !== 0) {
					// no, move to the next registered autoloader
					return;
				}

				// get the relative class name
				$relative_class = substr($class, $len);
				// replace the namespace prefix with the base directory, replace namespace
				// separators with directory separators in the relative class name, append
				// with .php
				$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
				// if the file exists, require it
				if (file_exists($file)) {
					require $file;
				}
			});
			spl_autoload_register(array('AutoLoader', 'libraryAutoload'));
			
			$initialized = true;
		} 
		
		// Sempre tentar carregar env (com proteção contra redeclaração)
		self::loadEnv();
	}
	
	private static function loadEnv() {
		$filename = __DIR__ . '/../.library/config_db.php';
		if(!is_file($filename) && Util::isLocalIp()){
			die("Arquivo de configuração env não encontrado");			
		}
		if (!is_file($filename)) {
			return;
		}		
		
		// Verificar se já foi carregado para evitar redeclaração durante hot reload
		if (class_exists('EnvConfig', false)) {
			// Arquivo já foi carregado, pular para evitar redeclaração
			return;
		}
		
		require $filename;
	}

	/**
	 * Usado internamente para ser registrado no spl_autoload_register
	 * @param string $nomeDaClasse
	 * @return null
	 */
	private static function libraryAutoload($nomeDaClasse) {
		$pastas = array_merge(self::convertDirectory($GLOBALS['loader']['locais'] ?? []), $GLOBALS['loader']['dependencias'] ?? [], static::$loader, self::libraryDirectories());
		
		$iniDirectory = self::getIniDirectory();
		for ($index = 0; $index < count($pastas); $index++) {
			foreach ($iniDirectory as $iniDir) {
				$file = $iniDir . $pastas[$index] . '/' . $nomeDaClasse;	
				if (self::include($file)) {
					return;
				}
			}
		}
	}
	
	private static function debug($var) {
		if(!class_exists('Debug')){
			include __DIR__. '/src/Debug.php5';
		}
		Debug::tail($var);
	}

	private static function convertDirectory($pastas) {
		for ($index = 0; $index < count($pastas); $index++) {
			$caminhoSistema = [];
			if (defined('_SYSNAME')) {
				$caminhoSistema = explode(_SYSNAME, $pastas[$index]);
			}
			if (count($caminhoSistema) > 1) {
				$pastaAjustada = str_replace('.', '/', $caminhoSistema[1]); //faz a troca apenas nos pacotes internos do sistema
				$pastas[$index] = $caminhoSistema[0] . _SYSNAME . $pastaAjustada;
			} else {
				$pastas[$index] = str_replace('.', '/', $pastas[$index]);
			}
		}
		return $pastas;
	}

	private static function libraryDirectories() {
		return [
			__DIR__ . '/src/',
			__DIR__ . '/ueg/',
			__DIR__ . '/tinybutstrong/'
		];
	}

	private static function include($file) {
		$extArray = array('.php', '.php5');
		foreach ($extArray as $ext) {
			$finalFile = $file . $ext;
			if (file_exists($finalFile)) {
				include_once ($finalFile);
				return true;
			}
		}
		return false;
	}

	private static function getIniDirectory() {
		$iniDirectory = ['./', '/', ''];
		if (isset($GLOBALS['loader']['raiz']) && $GLOBALS['loader']['raiz'] != '') {
			array_unshift($iniDirectory, $GLOBALS['loader']['raiz'] . '/');
		}
		return $iniDirectory;
	}

	/**
	 * Register function to load namespaced classes.
	 * @param type $namespace format: 'namespace\\sub\\'
	 * @param type $baseDir format: 'directory/'
	 */
	public static function registerForNamespace($namespace, $baseDir = __DIR__) {
		spl_autoload_register(function ($class) use ($namespace, $baseDir) {
			$length = strlen($namespace);
			if (strncmp($namespace, $class, $length) !== 0) {
				return;
			}

			$relativeClass = substr($class, $length);
			$file = $baseDir . str_replace('\\', '/', $relativeClass);
			static::include($file);
		});
	}

}
