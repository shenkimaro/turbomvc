<?php

use library\ueg\Util;

/**
 * PHP REST Client
 * https://github.com/tcdent/php-restclient
 * (c) 2013 Travis Dent <tcdent@gmail.com>
 */
class RestClientException extends Exception {
	
}

class RestClient implements Iterator, ArrayAccess {

	const FORMAT_JSON = 'json';
	const FORMAT_XML = 'xml';

	public $options;
	public $handle; // cURL resource handle.
	// Populated after execution:
	public $response; // Response body.
	public $headers; // Parsed reponse header object.
	public $info; // Response info object.
	public $error; // Response error string.
	public $format;
	public $parameter;
	public $parameterXml;
	public $parameterJson;
	public $timeOut;
	// Populated as-needed.
	public $decoded_response; // Decoded response body.
	private $url;
	private $curlopt;
	private $clientDebug; // Clone da última execução para debug

	public function __construct($options = array()) {
		$default_options = array(
			'headers' => array(),
			'parameters' => array(),
			'curl_options' => array(),
			'user_agent' => "PHP RestClient/0.1.2",
			'base_url' => NULL,
			'format' => NULL,
			'format_regex' => "/(\w+)\/(\w+)(;[.+])?/",
			'decoders' => array(
				'json' => 'json_decode',
				'php' => 'unserialize'
			),
			'username' => NULL,
			'password' => NULL
		);

		$this->options = array_merge($default_options, $options);
		if (array_key_exists('decoders', $options))
			$this->options['decoders'] = array_merge(
					$default_options['decoders'], $options['decoders']);
	}

	public function set_option($key, $value) {
		$this->options[$key] = $value;
	}

	/**
	 * Time in miliseconds
	 * @param int $value
	 */
	public function setTimeOut(int $value) {
		$this->timeOut = $value;
	}

	public function register_decoder($format, $method) {
		// Decoder callbacks must adhere to the following pattern:
		//   array my_decoder(string $data)
		$this->options['decoders'][$format] = $method;
	}

	// Iterable methods:
	public function rewind(): void {
		$this->decode_response();
		reset($this->decoded_response);
	}

	public function current(): mixed {
		return current($this->decoded_response);
	}

	public function key(): mixed {
		return key($this->decoded_response);
	}

	public function next(): void {
		next($this->decoded_response);
	}

	public function valid(): bool {
		return is_array($this->decoded_response)
				&& (key($this->decoded_response) !== NULL);
	}

	// ArrayAccess methods:
	public function offsetExists($key): bool {
		$this->decode_response();
		return is_array($this->decoded_response) ?
				isset($this->decoded_response[$key]) : isset($this->decoded_response->{$key});
	}

	public function offsetGet($key): mixed {
		$this->decode_response();
		if (!$this->offsetExists($key))
			return NULL;

		return is_array($this->decoded_response) ?
				$this->decoded_response[$key] : $this->decoded_response->{$key};
	}

	public function offsetSet($key, $value): void {
		throw new RestClientException("Decoded response data is immutable.");
	}

	public function offsetUnset($key): void {
		throw new RestClientException("Decoded response data is immutable.");
	}

	// Request methods:
	public function get($url, $parameters = array(), $headers = array()) {
		return $this->execute($url, 'GET', $parameters, $headers);
	}

	public function post($url, $parameters = array(), $headers = array()) {
		return $this->execute($url, 'POST', $parameters, $headers);
	}

	private function isCurlEnabled() {
		if (!function_exists('curl_version')) {
			throw new Exception("A extensão cURL NÃO está ativada no servidor.");
		}
	}

	public function put($url, $parameters = array(), $headers = array()) {
		return $this->execute($url, 'PUT', $parameters, $headers);
	}

	public function delete($url, $parameters = array(), $headers = array()) {
		return $this->execute($url, 'DELETE', $parameters, $headers);
	}

	public function setParameterQuery($parameter) {
		$this->parameter = $parameter;
	}

	public function setFormat($format) {
		$this->format = $format;
	}

	public function setParameterXML($parameterXml) {
		$this->setFormat(self::FORMAT_XML);
		$this->parameterXml = $parameterXml;
	}

	public function setParameterJSON($parameterJson) {
		if (is_array($parameterJson)) {
			$parameterJson = json_encode($parameterJson);
		}
		$this->setFormat(self::FORMAT_JSON);
		$this->parameterJson = $parameterJson;
	}

	public function execute($url, $method = 'GET', $parameters = array(), $headers = array()) {
		$this->isCurlEnabled();
		$client = clone $this;
		$client->url = $url;
		$client->handle = curl_init();
		$curlopt = array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_USERAGENT => $client->options['user_agent']
		);

		if ($client->options['username'] && $client->options['password']) {
			$curlopt[CURLOPT_USERPWD] = sprintf("%s:%s", $client->options['username'], $client->options['password']);
		}

		if (count($client->options['headers']) || count($headers)) {
			$curlopt[CURLOPT_HTTPHEADER] = array();
			$headers = array_merge($client->options['headers'], $headers);
			foreach ($headers as $key => $value) {
				$curlopt[CURLOPT_HTTPHEADER][] = sprintf("%s:%s", $key, $value);
			}
		}

		if ($this->timeOut > 0) {
			$curlopt[CURLOPT_TIMEOUT_MS] = $this->timeOut;
		}

		$parameters = array_merge($client->options['parameters'], $parameters);

		if ($client->options['format']) {
			$client->url .= '.' . $client->options['format'];
		}

		if (in_array(strtoupper($method), ['POST', 'PUT', 'DELETE'])) {
			$curlopt[CURLOPT_POST] = TRUE;
			$curlopt[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
			if ($client->format == self::FORMAT_XML) {
				$curlopt[CURLOPT_POSTFIELDS] = $client->parameterXml;
			} elseif ($client->format == self::FORMAT_JSON) {
				$curlopt[CURLOPT_POSTFIELDS] = $client->parameterJson;
			} else {
				// Usar parâmetros setados via setParameterQuery ou passados via post()
				if (is_array($client->parameter)) {
					$parameters = array_merge($parameters, $client->parameter);
				}
				$curlopt[CURLOPT_POSTFIELDS] = $client->format_query($parameters);
			}
		} elseif (count($parameters)) {
			$client->url .= strpos($client->url, '?') ? '&' : '?';
			$client->url .= $client->format_query($parameters);
		}

		if ($client->options['base_url']) {
			if ($client->url[0] != '/' || mb_substr($client->options['base_url'], -1) != '/')
				$client->url = '/' . $client->url;
			$client->url = $client->options['base_url'] . $client->url;
		}
		$curlopt[CURLOPT_URL] = $client->url;

		if ($client->options['curl_options']) {
			// array_merge would reset our numeric keys.
			foreach ($client->options['curl_options'] as $key => $value) {
				$curlopt[$key] = $value;
			}
		}
		curl_setopt($client->handle, CURLINFO_HEADER_OUT, true);
		curl_setopt_array($client->handle, $curlopt);
		$curReturn = curl_exec($client->handle);
		$info = (object) curl_getinfo($client->handle);
		$client->curlopt = $curlopt;
		$client->parse_response($curReturn, $info);
		$client->info = $info;
		$client->error = curl_error($client->handle);

		// Transferir as opções curl para o objeto que será retornado
		
		// Armazenar o clone para debug posterior
		$this->clientDebug = $client;

		curl_close($client->handle);
		return $client;
	}

	public function format_query($parameters, $primary = '=', $secondary = '&') {
		$query = "";
		foreach ($parameters as $key => $value) {
			$pair = array(urlencode($key), urlencode($value));
			$query .= implode($primary, $pair) . $secondary;
		}
		return rtrim($query, $secondary);
	}

	public function parse_response($response, $info = null) {
		$hasResponseHeaders = isset($this->curlopt[CURLOPT_HEADER]) && $this->curlopt[CURLOPT_HEADER];

		// Se headers vieram no payload e header_size está definido, separar com precisão
		if ($hasResponseHeaders && $info !== null && isset($info->header_size) && $info->header_size > 0) {
			$rawHeaders = substr($response, 0, $info->header_size);
			$body = substr($response, $info->header_size);
			$this->response = trim($body);
			$this->headers = (object) $this->parseHeaderBlocks($rawHeaders);
			return;
		}

		// Caso não haja headers no payload, considere a resposta inteira como body
		$this->response = trim($response);
		$this->headers = (object) [];
	}

	private function explodeResponseParts($response): array {
		$responseParts = [];
		$responseLines = explode("\n", $response);
		$cont = 0;
		foreach ($responseLines as $line) {
			if (trim($line) == '' && count($responseParts) > 0) {
				++$cont;
			}
			if (isset($responseParts[$cont])) {
				$responseParts[$cont] .= "\n" . $line;
			} else {
				$responseParts[$cont] = $line;
			}
		}
		return $responseParts;
	}

	private function getLastPart(array &$responseParts) {
		$last = count($responseParts) - 1;
		if ($last < 0) {
			return '';
		}
		$responseGeral = isset($responseParts[$last]) ? $responseParts[$last] : '';
		if (trim($responseGeral) != '') {
			return $responseGeral;
		}
		array_pop($responseParts);
		return $this->getLastPart($responseParts);
	}

	private function parseHeaderBlocks(string $rawHeaders): array {
		$headers = [];
		// Divide múltiplos blocos (redirects/100-continue) por linha em branco dupla
		$blocks = preg_split("/\r?\n\r?\n/", trim($rawHeaders));
		foreach ($blocks as $block) {
			$lines = explode("\n", $block);
			foreach ($lines as $line) {
				$line = trim($line);
				if ($line === '') {
					continue;
				}
				// Ignorar linha de status (HTTP/1.x) ou pseudo cabeçalho HTTP/2
				if (stripos($line, 'HTTP/') === 0) {
					continue;
				}
				if (strpos($line, ':') === false) {
					continue;
				}
				list($key, $value) = explode(':', $line, 2);
				$key = trim(strtolower(str_replace('-', '_', $key)));
				$value = trim($value);
				if ($key === '') {
					continue;
				}
				if (!isset($headers[$key])) {
					$headers[$key] = $value;
				} elseif (is_array($headers[$key])) {
					$headers[$key][] = $value;
				} else {
					$headers[$key] = array($headers[$key], $value);
				}
			}
		}
		return $headers;
	}

	private function mergeHeaders(array $base, array $extra): array {
		foreach ($extra as $key => $value) {
			if (!isset($base[$key])) {
				$base[$key] = $value;
				continue;
			}
			if (is_array($base[$key])) {
				$base[$key][] = $value;
				continue;
			}
			$base[$key] = [$base[$key], $value];
		}
		return $base;
	}

	public function get_response_format() {
		if (!$this->response)
			throw new RestClientException("A response must exist before it can be decoded.");

		// User-defined format. 
		if (!empty($this->options['format']))
			return $this->options['format'];

		// Extract format from response content-type header. 
		if (!empty($this->headers->content_type))
			if (preg_match($this->options['format_regex'], $this->headers->content_type, $matches))
				return $matches[2];

		throw new RestClientException(
						"Response format could not be determined.");
	}

	public function decode_response() {
		if (empty($this->decoded_response)) {
			$format = $this->get_response_format();
			if (!array_key_exists($format, $this->options['decoders']))
				throw new RestClientException("'$format' is not a supported " .
								"format, register a decoder to handle this response.");

			$this->decoded_response = call_user_func(
					$this->options['decoders'][$format], $this->response);
		}

		return $this->decoded_response;
	}

	/**
	 * Gera comando curl após a execução da requisição (com todas as opções aplicadas)
	 * Útil para debug e reprodução de requisições em linha de comando
	 * @param bool $formatted Se true, retorna com quebras de linha para melhor legibilidade
	 * @return string Comando curl formatado
	 */
	public function getLastCurlCommand($formatted = false) {
		$cmd = ['curl'];

		// Se foi clonado em execute(), usar dados do clone. Caso contrário, usar dados locais
		$debugClient = $this->clientDebug ?? $this;
		
		// Validação da URL
		if (empty($debugClient->url)) {
			throw new RestClientException("URL não foi definida. Execute a requisição antes de chamar getLastCurlCommand()");
		}

		// URL (sempre escapada)
		$cmd[] = escapeshellarg($debugClient->url);

		// Usar as opções curl que foram realmente aplicadas na execução
		$curlOptions = $debugClient->curlopt ?? [];

		// Processar todas as opções CURL que foram usadas
		foreach ($curlOptions as $option => $value) {
			// Ignorar opções sem valor ou valores vazios
			if ($value === null || $value === false || (is_string($value) && trim($value) === '')) {
				continue;
			}

			switch ($option) {
				case CURLOPT_POST:
					// Se POST é true e não há CUSTOMREQUEST definido
					if ($value && !isset($curlOptions[CURLOPT_CUSTOMREQUEST])) {
						// curl assume POST por padrão com -d, então não precisa de -X POST
					}
					break;

				case CURLOPT_CUSTOMREQUEST:
					if ($value !== 'GET') {
						$cmd[] = '-X';
						$cmd[] = $value;
					}
					break;

				case CURLOPT_HTTPHEADER:
					if (is_array($value) && !empty($value)) {
						foreach ($value as $header) {
							if (!empty($header)) {
								$cmd[] = '-H';
								$cmd[] = escapeshellarg($header);
							}
						}
					}
					break;

				case CURLOPT_POSTFIELDS:
					if (!empty($value)) {
						$cmd[] = '-d';
						// Para dados grandes, considerar usar --data-binary ou arquivo
						if (strlen($value) > 500) {
							$cmd[] = escapeshellarg(substr($value, 0, 500) . '...[truncado para legibilidade]');
						} else {
							$cmd[] = escapeshellarg($value);
						}
					}
					break;

				case CURLOPT_USERAGENT:
					if (!empty($value)) {
						$cmd[] = '-A';
						$cmd[] = escapeshellarg($value);
					}
					break;

				case CURLOPT_TIMEOUT:
					if ($value > 0) {
						$cmd[] = '--max-time';
						$cmd[] = (int)$value;
					}
					break;

				case CURLOPT_TIMEOUT_MS:
					if ($value > 0) {
						$cmd[] = '--max-time';
						$cmd[] = round($value / 1000, 2);
					}
					break;

				case CURLOPT_CONNECTTIMEOUT:
					if ($value > 0) {
						$cmd[] = '--connect-timeout';
						$cmd[] = (int)$value;
					}
					break;

				case CURLOPT_USERPWD:
					if (!empty($value)) {
						$cmd[] = '-u';
						// Esconder senha parcialmente no log
						if (strpos($value, ':') !== false) {
							list($user, $pass) = explode(':', $value, 2);
							$maskedPass = substr($pass, 0, 3) . str_repeat('*', max(0, strlen($pass) - 3));
							$cmd[] = escapeshellarg($user . ':' . $maskedPass);
						} else {
							$cmd[] = escapeshellarg($value);
						}
					}
					break;

				case CURLOPT_FOLLOWLOCATION:
					if ($value) {
						$cmd[] = '-L';
					}
					break;

				case CURLOPT_SSL_VERIFYPEER:
					if (!$value) {
						$cmd[] = '-k';
					}
					break;

				case CURLOPT_SSL_VERIFYHOST:
					if (!$value) {
						$cmd[] = '--insecure';
					}
					break;

				case CURLOPT_VERBOSE:
					if ($value) {
						$cmd[] = '-v';
					}
					break;

				case CURLOPT_HEADER:
					if ($value) {
						$cmd[] = '-i';
					}
					break;

				case CURLOPT_NOBODY:
					if ($value) {
						$cmd[] = '-I';
					}
					break;

				case CURLOPT_REFERER:
					if (!empty($value)) {
						$cmd[] = '--referer';
						$cmd[] = escapeshellarg($value);
					}
					break;

				case CURLOPT_COOKIE:
					if (!empty($value)) {
						$cmd[] = '--cookie';
						$cmd[] = escapeshellarg($value);
					}
					break;

				case CURLOPT_COOKIEFILE:
					if (!empty($value)) {
						$cmd[] = '--cookie-jar';
						$cmd[] = escapeshellarg($value);
					}
					break;

				case CURLOPT_PROXY:
					if (!empty($value)) {
						$cmd[] = '--proxy';
						$cmd[] = escapeshellarg($value);
					}
					break;

				case CURLOPT_PROXYUSERPWD:
					if (!empty($value)) {
						$cmd[] = '--proxy-user';
						$cmd[] = escapeshellarg($value);
					}
					break;
			}
		}

		// Formatar saída
		if ($formatted) {
			// Quebras de linha a cada 80 caracteres para melhor legibilidade
			$result = "curl \\\n";
			for ($i = 1; $i < count($cmd); $i++) {
				$result .= "  " . $cmd[$i];
				if ($i < count($cmd) - 1) {
					$result .= " \\\n";
				} else {
					$result .= "\n";
				}
			}
			return $result;
		}

		return implode(' ', $cmd);
	}

	/**
	 * Debug: retorna informações sobre as opções curl configuradas
	 * @return array
	 */
	public function getCurlOptionsDebug() {
		return [
			'curl_options' => $this->options['curl_options'] ?? [],
			'format' => $this->format ?? 'none',
			'parameter' => $this->parameter ?? 'none',
			'parameterXml' => $this->parameterXml ?? 'none',
			'parameterJson' => $this->parameterJson ?? 'none',
			'url' => $this->url ?? 'none'
		];
	}
}
