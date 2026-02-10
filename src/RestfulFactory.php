<?php

require_once 'RestfulOutputInterface.php';
require_once 'RestfulTraditional.php';
require_once 'RestfulSwoole.php';

/**
 * Factory para criar instâncias do Restful apropriadas
 * Permite alternar entre implementação tradicional e Swoole
 */
class RestfulFactory {
    
    const TYPE_TRADITIONAL = 'traditional';
    const TYPE_SWOOLE = 'swoole';
    
    private static $defaultType = self::TYPE_TRADITIONAL;
    private static $swooleResponse = null;
    
    /**
     * Define o tipo padrão de implementação
     * @param string $type Tipo da implementação (traditional ou swoole)
     */
    public static function setDefaultType($type) {
        if (!in_array($type, [self::TYPE_TRADITIONAL, self::TYPE_SWOOLE])) {
            throw new InvalidArgumentException("Tipo '{$type}' não é válido. Use 'traditional' ou 'swoole'");
        }
        self::$defaultType = $type;
    }
    
    /**
     * Define o objeto response do Swoole para uso global
     * @param mixed $response Objeto response do Swoole
     */
    public static function setSwooleResponse($response) {
        self::$swooleResponse = $response;
    }
    
    /**
     * Cria uma instância do Restful baseada na configuração
     * @param string|null $type Tipo específico ou usa o padrão
     * @param mixed|null $swooleResponse Response específico para Swoole
     * @return RestfulOutputInterface
     */
    public static function create($type = null, $swooleResponse = null) {
        $type = $type ?? self::$defaultType;
        
        switch ($type) {
            case self::TYPE_TRADITIONAL:
                return new RestfulTraditional();
                
            case self::TYPE_SWOOLE:
                $restful = new RestfulSwoole();
                $response = $swooleResponse ?? self::$swooleResponse;
                if ($response) {
                    $restful->setResponse($response);
                }
                return $restful;
                
            default:
                throw new InvalidArgumentException("Tipo '{$type}' não é válido");
        }
    }
    
    /**
     * Cria instância tradicional
     * @return RestfulTraditional
     */
    public static function createTraditional() {
        return new RestfulTraditional();
    }
    
    /**
     * Cria instância para Swoole
     * @param mixed|null $response Objeto response do Swoole
     * @return RestfulSwoole
     */
    public static function createSwoole($response = null) {
        $restful = new RestfulSwoole();
        $responseObj = $response ?? self::$swooleResponse;
        if ($responseObj) {
            $restful->setResponse($responseObj);
        }
        return $restful;
    }
    
    /**
     * Detecta automaticamente o ambiente e retorna a implementação apropriada
     * @param mixed|null $swooleResponse Response do Swoole se disponível
     * @return RestfulOutputInterface
     */
    public static function createAuto($swooleResponse = null) {
        // Se foi passado um response do Swoole, usa implementação Swoole
        if ($swooleResponse !== null) {
            return self::createSwoole($swooleResponse);
        }
        
        // Se há response global configurado, usa Swoole
        if (self::$swooleResponse !== null) {
            return self::createSwoole();
        }
        
        // Tenta detectar se está rodando no Swoole verificando variáveis globais
        if ((defined('SWOOLE_VERSION') || class_exists('Swoole\Http\Response')) && php_sapi_name() === 'cli') {
            return self::createSwoole();
        }
        
        // Por padrão, usa implementação tradicional
        return self::createTraditional();
    }
}