<?php
namespace library\ueg;

require_once 'HeaderFactory.php';

/**
 * Classe Header com suporte para Apache e Swoole
 * Utiliza o HeaderFactory para alternar entre implementações
 * Mantém compatibilidade total com código existente
 */
class Header {

	private const Authorization = 'Authorization';
	
	/** @var HeaderInterface|null Instância cached do header */
	private static $instance = null;

	/**
	 * Obtém o token Bearer do cabeçalho Authorization
	 * @return string|null Token Bearer ou null se não encontrado
	 */
	public static function getAuthorizationBearer(): ?string {
		return self::getInstance()->getAuthorizationBearer();
	}
	
	/**
	 * Obtém o valor completo do cabeçalho Authorization
	 * @return string|null Valor do Authorization ou null se não encontrado
	 */
	public static function getAuthorization(): ?string {
		return self::getInstance()->getAuthorization();
	}

	/**
	 * Obtém um cabeçalho específico por chave
	 * @param string $key Nome do cabeçalho
	 * @return string|null Valor do cabeçalho ou null se não encontrado
	 */
	private static function get($key): ?string {
		return self::getInstance()->get($key);
	}
	
	/**
	 * Obtém a instância do Header baseada na configuração atual
	 * @return HeaderInterface
	 */
	private static function getInstance(): HeaderInterface {
		if (self::$instance === null) {
			self::$instance = HeaderFactory::createAuto();
		}
		return self::$instance;
	}
	
	/**
	 * Define manualmente uma instância específica do Header
	 * Útil para testes ou configuração específica
	 * @param HeaderInterface $instance
	 */
	public static function setInstance(HeaderInterface $instance): void {
		self::$instance = $instance;
	}
	
	/**
	 * Reseta a instância, forçando recriação na próxima chamada
	 */
	public static function resetInstance(): void {
		self::$instance = null;
	}
	
	/**
	 * Configura objetos Swoole para uso global
	 * @param mixed $request Objeto request do Swoole
	 * @param mixed $response Objeto response do Swoole
	 */
	public static function configureSwoole($request, $response): void {
		HeaderFactory::setSwooleObjects($request, $response);
		self::resetInstance(); // Força recriação com nova configuração
	}
	
	/**
	 * Configura o tipo padrão de implementação
	 * @param string $type 'traditional' ou 'swoole'
	 */
	public static function setDefaultType(string $type): void {
		HeaderFactory::setDefaultType($type);
		self::resetInstance(); // Força recriação com novo tipo
	}

}
