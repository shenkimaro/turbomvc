<?php

/**
 * @package Framework
 *
 * @subpackage Debug
 * 
 * @filesource
 */

/**
 * Esta classe realiza servicos de debug 
 * 
 * 
 * @author Ibanez C. Almeida <ibanez.almeida@gmail.com>
 *
 * @version 2.0
 *
 */
class Debug {

	const DEBUG_FILE = 1;
	const DEBUG_APACHE = 2;
	const DEBUG_REDIS = 3;
	const DEBUG_ELASTIC = 4;

	/**
	 * Diz se a classe funciona ou nao
	 * 
	 * @var boolean
	 */
	private $status;

	/**
	 * Contem o caminho da pasta do arquivo de debug
	 * 
	 * @var string
	 */
	private $fileDebug;

	//************************************************************************************************************************\\

	/**
	 * Metodo construtor da classe Debug
	 *
	 * @author Ibanez C Almeida
	 * 
	 */
	function __construct() {
		$status = isset($GLOBALS['configDebug']['status']) ? $GLOBALS['configDebug']['status'] : false;
		$this->setStatusDebug($status);
		$this->fileDebug = "/var/www/logs/debugSys.log";
		if (defined('_DEBUG_FILE_PATH')) {
			$this->fileDebug = _DEBUG_FILE_PATH;
		}
	}

	//************************************************************************************************************************\\

	/**
	 * Seta o status da classe, ligado ou desligado
	 *
	 * @param boolean $file 0 para desligar a classe debug, 1 para 
	 * liga-la
	 *
	 * @return boolean verdadeiro se o debug funcionou, false em caso 
	 * contrario
	 *
	 * @author Ibanez C Almeida
	 * 
	 */
	public function setStatusDebug($status = 1) {
		$this->status = $status;
	}

	public function getStatusDebug() {
		return $this->status;
	}

	/**
	 * Retorna o tempo inicial da execução
	 * @return float
	 */
	public static function getStartExecutionTime() {
		return time();
	}

	/**
	 * Retorna o tempo decorrido da execução do codigo
	 * @param float $startTime
	 * @return float
	 */
	public static function getElapsedExecutionTime($startTime) {
		$script_end = time();
		$elapsedTime = round($script_end - $startTime, 5);
		return $elapsedTime;
	}

	//************************************************************************************************************************\\

	/**
	 * Seta o caminho para o arquivo de debug
	 *
	 * @param string $file Deve conter o caminho para o arquivo de debug, 
	 * somente a pasta onde deverar ficar
	 *
	 * @author Ibanez C Almeida
	 * 
	 * @version 1.0
	 */
	function setFileDebug($file) {
		$this->fileDebug = $file;
	}

	//************************************************************************************************************************\\

	/**
	 * Escreve a mensagem passada, para fins de acompanhamento da 
	 * codificacao
	 *
	 * @param string $var Deve conter a mensagem que aparecera no 
	 * log
	 *
	 * @param numerico output Não obrigatorio, 0 para tela do browser, 
	 * 1 para escrita em arquivo(previamente setado), 2 para email
	 *
	 * @return boolean verdadeiro se o debug funcionou, false em caso
	 * contrario
	 *
	 * @author Ibanez C Almeida
	 * 
	 */
	public function write($var, $output = 1, $info = '') {
		$message = "/=================================CHAMADA :=================================\ \n";
		$message .= print_r($var, TRUE);
		$message .= "\n \n" . $info;
		$message .= "\n/==================================FIM: ==================================\ \n\n";

		if ($output == 1) {
			$data = date('Y_m_d');
			if (strtoupper(mb_substr(PHP_OS, 0, 3)) === 'WIN') {
				$file = $_SERVER['DOCUMENT_ROOT'];
			}
			$file = $this->fileDebug;
			static::createDir($file);
			$fp = fopen($file, 'a');
			if (!$fp) {
				return;
			}

			fwrite($fp, $message);
			fclose($fp);
		} else if ($output == 0) {
			echo "<pre>";
			print_r($var);
			echo "</pre>";
		} else if ($output == 2) {
//			$this->enviarEmail($message);
		}

		return true;
	}

	public function writeNoLine($var, $info = '') {
		$message = ' INICIO :';
		$message .= print_r($var, TRUE);
		$message .= " " . $info;
		$message .= " FIM \n";

		$file = $this->fileDebug;
		static::createDir($file);
		$fp = fopen($file, 'a');
		if (!$fp) {
			return;
		}

		fwrite($fp, $message);
		fclose($fp);

		return true;
	}

	private function writeTemplate($var, $info = '') {
		$date = date('d-m-Y H:i:s');
		$message = "[$date]";
		$message .= "\n";
		$message .= 'Mensagem: ';
		$message .= print_r($var, TRUE);
		$message .= "\n";
		$message .= $info;
		$message .= "\n\n";
//		$this->writeFile($message);
		require __DIR__ . '/libs/opentelemetry/OpenTelemetry.php';
		OpenTelemetry::write($message);
		return true;
	}

	private function writeFile($message) {
		$file = $this->fileDebug;
		static::createDir($file);
		$fp = fopen($file, 'a');
		if (!$fp) {
			return;
		}

		fwrite($fp, $message);
		fclose($fp);
	}

	private static function createDir(string $file) {
		$directories = explode('/', $file);
		$dir = '';
		for ($index = 0; $index < count($directories) - 1; $index++) {
			if ($directories[$index] == '') {
				continue;
			}
			$dir .= '/' . $directories[$index];
		}
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
	}
	
	public static function log($variavel, $tipo = "print_r") {
		$date = date("d/m/Y H:i:s") . mb_substr((string) microtime(), 1, 8);
        echo "\n";
        switch ($tipo) {
            case "echo":
                echo($variavel);
                break;
            case "print_r":
                print_r($variavel);
                break;
            default:
                var_dump($variavel);
        }
        $stack = debug_backtrace();
        $call_info = @array_shift($stack);
        echo "\n";
        echo "[$date] Arquivo: {$call_info['file']} -> linha: ({$call_info['line']})";
        echo "\n";
	}

	public static function tail($variavel, $outPutFile = '', $outPut = Debug::DEBUG_APACHE) {
		$debug = new Debug();
		if ($outPutFile != '') {
			$debug->setFileDebug($outPutFile);
		}

		$stack = debug_backtrace();
		$call_info = array_shift($stack);
		$arquivo = $call_info['file'];
		$linha = $call_info['line'];
		$date = date('d-m-Y H:i:s:' . microtime());
		$message = '';
		if ($outPut != Debug::DEBUG_ELASTIC) {
			$traceSummary = self::traceSummary();
			$message = "\n Horario: $date";
			$message .= "\n Arquivo: $arquivo -> linha: ($linha)";
			$message .= "\n Stack: $traceSummary";
		} else {
			$message .= "Arquivo: $arquivo -> linha: ($linha) ";
		}
		switch ($outPut) {
			case Debug::DEBUG_FILE:
				$debug->write($variavel, 1, $message);
				break;
			case Debug::DEBUG_ELASTIC:
				$debug->writeTemplate($variavel, $message);
				break;
			case Debug::DEBUG_APACHE:
				$var = print_r($variavel, TRUE);
				$var .= "\n" . $message;
				$debug->writeLogApache($var);
				break;
			case Debug::DEBUG_REDIS:
				$variavel .= "\n" . $message;
				$debug->writeLogNoSql($variavel);
				break;

			default:
				break;
		}
	}

	private static function traceSummary(): string {
		$stack = debug_backtrace();
		$summary = '';
		foreach ($stack as $item) {
			$line = $item['line'] ?? 'não-tem-linha';
			$classe = $item['class'] ?? 'não-tem-classe';
			$file = $item['file'] ?? 'não-tem-arquivo';
			$function = $item['funcion'] ?? 'não-tem-função';

			if ($function == 'traceSummary' || ($function == 'tail' && $classe == 'Debug')) {
				continue;
			}
			if ($summary != '') {
				$summary .= "\n";
			}
			$summary .= "[{$file}] [{$line}] [{$function}] [{$classe}]";
		}
		return $summary;
	}

	public function trace(): array {
		return debug_backtrace();
	}

	private function writeLogApache($var) {
		error_log($var);
//        $stderr = fopen('php://stderr', 'w');
//        fwrite($stderr, "\n-----\n{$var}\n-----\n");
//        fclose($stderr);
	}

	private function writeLogNoSql($var) {
		$redis = new Redis();
		$redis->connect('127.0.0.1', 6380);
		$redis->lpush("log_debug_library", $var);
	}

	public function stop() {
		exit();
	}
}
