<?php
namespace library\ueg;

require_once 'HeaderInterface.php';
require_once 'HeaderTraditional.php';
require_once 'HeaderSwoole.php';

/**
 * Factory para criar instâncias do Header apropriadas
 * Permite alternar entre implementação tradicional (Apache) e Swoole
 */
class HeaderFactory {
    
    const TYPE_TRADITIONAL = 'traditional';
    const TYPE_SWOOLE = 'swoole';
    
    private static $defaultType = self::TYPE_TRADITIONAL;
    private static $swooleRequest = null;
    private static $swooleResponse = null;
    
    /**
     * Define o tipo padrão de implementação
     * @param string $type Tipo da implementação (traditional ou swoole)
     */
    public static function setDefaultType($type): void {
        if (!in_array($type, [self::TYPE_TRADITIONAL, self::TYPE_SWOOLE])) {
            throw new \InvalidArgumentException("Tipo '{$type}' não é válido. Use 'traditional' ou 'swoole'");
        }
        self::$defaultType = $type;
    }
    
    /**
     * Define o objeto request do Swoole para uso global
     * @param mixed $request Objeto request do Swoole
     */
    public static function setSwooleRequest($request): void {
        self::$swooleRequest = $request;
    }
    
    /**
     * Define o objeto response do Swoole para uso global
     * @param mixed $response Objeto response do Swoole
     */
    public static function setSwooleResponse($response): void {
        self::$swooleResponse = $response;
    }
    
    /**
     * Define ambos os objetos do Swoole de uma vez
     * @param mixed $request Objeto request do Swoole
     * @param mixed $response Objeto response do Swoole
     */
    public static function setSwooleObjects($request, $response): void {
        self::setSwooleRequest($request);
        self::setSwooleResponse($response);
    }
    
    /**
     * Cria uma instância do Header baseada na configuração
     * @param string|null $type Tipo específico ou usa o padrão
     * @param mixed|null $swooleRequest Request específico para Swoole
     * @param mixed|null $swooleResponse Response específico para Swoole
     * @return HeaderInterface
     */
    public static function create($type = null, $swooleRequest = null, $swooleResponse = null): HeaderInterface {
        $type = $type ?? self::$defaultType;
        
        switch ($type) {
            case self::TYPE_TRADITIONAL:
                return new HeaderTraditional();
                
            case self::TYPE_SWOOLE:
                $header = new HeaderSwoole();
                $request = $swooleRequest ?? self::$swooleRequest;
                $response = $swooleResponse ?? self::$swooleResponse;
                
                if ($request) {
                    $header->setRequest($request);
                }
                if ($response) {
                    $header->setResponse($response);
                }
                return $header;
                
            default:
                throw new \InvalidArgumentException("Tipo '{$type}' não é válido");
        }
    }
    
    /**
     * Cria instância tradicional (Apache)
     * @return HeaderTraditional
     */
    public static function createTraditional(): HeaderTraditional {
        return new HeaderTraditional();
    }
    
    /**
     * Cria instância para Swoole
     * @param mixed|null $request Objeto request do Swoole
     * @param mixed|null $response Objeto response do Swoole
     * @return HeaderSwoole
     */
    public static function createSwoole($request = null, $response = null): HeaderSwoole {
        $header = new HeaderSwoole();
        
        $requestObj = $request ?? self::$swooleRequest;
        $responseObj = $response ?? self::$swooleResponse;
        
        if ($requestObj) {
            $header->setRequest($requestObj);
        }
        if ($responseObj) {
            $header->setResponse($responseObj);
        }
        
        return $header;
    }
    
    /**
     * Detecta automaticamente o ambiente e retorna a implementação apropriada
     * @param mixed|null $swooleRequest Request do Swoole se disponível
     * @param mixed|null $swooleResponse Response do Swoole se disponível
     * @return HeaderInterface
     */
    public static function createAuto($swooleRequest = null, $swooleResponse = null): HeaderInterface {
        // Se foram passados objetos do Swoole, usa implementação Swoole
        if ($swooleRequest !== null || $swooleResponse !== null) {
            return self::createSwoole($swooleRequest, $swooleResponse);
        }
        
        // Se há objetos globais configurados, usa Swoole
        if (self::$swooleRequest !== null || self::$swooleResponse !== null) {
            return self::createSwoole();
        }
        
        // Tenta detectar se está rodando no Swoole verificando variáveis globais
        if (defined('SWOOLE_VERSION') || class_exists('Swoole\\Http\\Request')) {
            return self::createSwoole();
        }
        
        // Por padrão, usa implementação tradicional
        return self::createTraditional();
    }
}