<?php

class Restful {

	const STATUS_OK = 200;
	const STATUS_CRIADO = 201;
	const STATUS_NO_CONTENT = 204;
	const STATUS_BAD_REQUEST = 400;
	const STATUS_SEM_AUTORIZACAO = 401;
	const STATUS_NAO_PERMITIDO = 403;
	const STATUS_NAO_ENCONTRADO = 404;
	const STATUS_METODO_NAO_PERMITIDO = 405;
	const STATUS_CONFLIT = 409;
	const STATUS_ERRO_INTERNO_SERVIDOR = 500;
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const OUTPUTMETHOD_XML = 'xml';
	const OUTPUTMETHOD_JSON = 'json';
	const REQUEST_OPTIONS = 'OPTIONS';

	protected $tipo_saida;

	function __construct() {
		$this->escolheTipoSaida();
	}

	/**
	 * Retorna os valores requisitados do cliente
	 * @return array[key] = value
	 */
	public function getREQUEST() {
		foreach ($_REQUEST as $key => $value) {
			$array[$key] = $value;
		}
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') === FALSE) {
				continue;
			}
			$key = str_replace('HTTP_', '', $key);
			$array[strtolower($key)] = $value;
		}
		return $array;
	}

	/**
	 * 
	 * @return array
	 */
	public function getRequestHeaders() {
		return apache_request_headers();
	}

	/**
	 *  Retorna o metodo requisitado pelo cliente
	 * @return string
	 */
	public function getMethod() {
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Usada para converter ISO para UTF
	 * @param string $item
	 */
	public static function formatUTF8(&$item) {
		if (!is_numeric($item)) {
			$item = utf8_encode($item);
		}
	}

	protected function addCorsHeaders() {
//		header("Access-Control-Allow-Origin: *");
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Headers: Authorization');
        header('Access-Control-Expose-Headers: Authorization');
		header('Access-Control-Allow-Headers: user-agent');
	}

	protected function returnHeadersWhenOptionsMethod() {
		if ($this->getRequestMethod() == self::REQUEST_OPTIONS) {
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
				// may also be using PUT, PATCH, HEAD etc
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			}

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
				header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
			}
			header("HTTP/1.1 " . Restful::STATUS_OK . " " . $this->requestStatus(Restful::STATUS_OK));
			die();
		}
	}

	protected function allowedOrigins() {
		$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
		$agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
		return (
				(strpos($origin, 'ueg.br') !== false) ||
				(strpos($agent, 'br.ueg.app') !== false) ||
				(strpos(strtolower($agent), 'iphone') !== false) ||
				(strpos(strtolower($agent), 'applewebkit') !== false) ||
				(Util::isLocalIp() || Util::isBeta())
				);
	}

	/**
	 *
	 * @param array $data
	 * @param integer $status
	 */
	public function printREST($data, $status = Restful::STATUS_OK) {
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
		if ($this->allowedOrigins()) {
			$this->addCorsHeaders();
			$this->returnHeadersWhenOptionsMethod();
		}
		header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));

		if ($this->tipo_saida == 'json') {
			header("Content-Type: application/{$this->tipo_saida};");
			if ($data instanceof DTO) {
				$data = $this->getProperties($data);
			}
			if ($data == null) {
				$data = [];
			}
			echo json_encode($data);
			die();
		}

		if ($this->tipo_saida == 'xml') {
			header("Content-Type: text/{$this->tipo_saida};charset=utf-8");
			echo $this->xml_encode($data);
			die();
		}
	}

	protected function getProperties(DTO $dto) {
		$r = new ReflectionObject($dto);
		$methods = $r->getMethods();
		$obj = [];
		foreach ($methods as $value) {
			$methodName = $value->getName();
			if ($methodName[0] == 's') {
				continue;
			}
			$field = $dto->getMethodTableField($methodName);
			if ($field == "") {
				continue;
			}
			$value = $dto->$methodName();
			$obj[$field] = $value;
		}
		return $obj;
	}

	public function setTipoSaida($tipoSaida) {
		$this->tipo_saida = $tipoSaida;
	}

	protected function xml_encode($data) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		if (isset($data[0])) {
			$xml .= '<registros>';
		}
		foreach ($data as $key => $value) {
			if (is_string($value)) {
				$xml .= "<$key>$value</$key>";
			} else if (is_array($value)) {
				$xml .= "<registro>";
				foreach ($value as $key1 => $value1) {
					if (is_string($value1)) {
						$xml .= "<$key1>$value1</$key1>";
					} elseif (is_array($value1)) {
						$xml .= "<$key1>";
						foreach ($value1 as $key2 => $value2) {
							$xml .= "<$key2>$value2</$key2>";
						}
						$xml .= "</$key1>";
					}
				}
				$xml .= "</registro>";
			}
		}
		if (isset($data[0])) {
			$xml .= '</registros>';
		}
		return $xml;
	}

	protected function requestStatus($code) {
		$status = array(
			self::STATUS_OK => 'OK',
			self::STATUS_CRIADO => 'Criado',
			self::STATUS_NO_CONTENT => 'No Content',
			self::STATUS_BAD_REQUEST => 'Bad Request',
			self::STATUS_SEM_AUTORIZACAO => 'Sem Autorizacao',
			self::STATUS_NAO_PERMITIDO => 'Nao Permitido',
			self::STATUS_NAO_ENCONTRADO => 'Nao Encontrado',
			self::STATUS_METODO_NAO_PERMITIDO => 'Metodo nao permitido',
			self::STATUS_ERRO_INTERNO_SERVIDOR => 'Erro Interno do Servidor',
		);
		return (isset($status[$code])) ? $status[$code] : $status[500];
	}

	protected function getRequestMethod() {
		return $_SERVER['REQUEST_METHOD'] ?? 'GET';
	}

	public function escolheTipoSaida() {
		if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] == '') {
			$this->setTipoSaida('json');
			return;
		}
		$content = $_SERVER['CONTENT_TYPE'];
		if (!(strpos($content, self::OUTPUTMETHOD_XML) === FALSE)) {
			$this->setTipoSaida('xml');
		} else if (strpos($content, self::OUTPUTMETHOD_JSON)) {
			$this->setTipoSaida('json');
		} else {
			$this->setTipoSaida('json');
		}
	}

	// ===========================
	// MÉTODOS ESTÁTICOS PARA USO DA FACTORY
	// ===========================

	/**
	 * Cria uma instância usando a factory com configuração padrão
	 * Uso: $restful = Restful::create();
	 * @param string|null $type Tipo específico ou usa o padrão
	 * @param mixed|null $swooleResponse Response específico para Swoole
	 * @return RestfulOutputInterface
	 */
	public static function create($type = null, $swooleResponse = null) {
		return RestfulFactory::create($type, $swooleResponse);
	}

	/**
	 * Cria instância tradicional
	 * Uso: $restful = Restful::createTraditional();
	 * @return RestfulTraditional
	 */
	public static function createTraditional() {
		return RestfulFactory::createTraditional();
	}

	/**
	 * Cria instância para Swoole
	 * Uso: $restful = Restful::createSwoole($response);
	 * @param mixed|null $response Objeto response do Swoole
	 * @return RestfulSwoole
	 */
	public static function createSwoole($response = null) {
		return RestfulFactory::createSwoole($response);
	}

	/**
	 * Detecta automaticamente o ambiente
	 * Uso: $restful = Restful::createAuto($swooleResponse);
	 * @param mixed|null $swooleResponse Response do Swoole se disponível
	 * @return RestfulOutputInterface
	 */
	public static function createAuto($swooleResponse = null) {
		return RestfulFactory::createAuto($swooleResponse);
	}

	/**
	 * Define o tipo padrão de implementação
	 * Uso: Restful::setDefaultType('swoole');
	 * @param string $type Tipo da implementação (traditional ou swoole)
	 */
	public static function setDefaultType($type) {
		RestfulFactory::setDefaultType($type);
	}

	/**
	 * Define o objeto response do Swoole para uso global
	 * Uso: Restful::setSwooleResponse($response);
	 * @param mixed $response Objeto response do Swoole
	 */
	public static function setSwooleResponse($response) {
		RestfulFactory::setSwooleResponse($response);
	}
}
