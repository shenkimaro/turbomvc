<?php

/**
 * Factory para criar instâncias do Request apropriadas
 * Permite alternar entre implementação tradicional (Apache) e Swoole
 */
class RequestFactory {
    
    const TYPE_TRADITIONAL = 'traditional';
    const TYPE_SWOOLE = 'swoole';
    
    private static $defaultType = self::TYPE_TRADITIONAL;
    private static $swooleRequest = null;
    
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
     * Cria uma instância do Request baseada na configuração
     * @param string|null $type Tipo específico ou usa o padrão
     * @param mixed|null $swooleRequest Request específico para Swoole
     * @return RequestInterface
     */
    public static function create($type = null, $swooleRequest = null): RequestInterface {
        $type = $type ?? self::$defaultType;
        
        switch ($type) {
            case self::TYPE_TRADITIONAL:
                return new RequestTraditional();
                
            case self::TYPE_SWOOLE:
                $request = new RequestSwoole();
                $swooleRequestObj = $swooleRequest ?? self::$swooleRequest;
                
                if ($swooleRequestObj) {
                    $request->setRequest($swooleRequestObj);
                }
                return $request;
                
            default:
                throw new \InvalidArgumentException("Tipo '{$type}' não é válido");
        }
    }
    
    /**
     * Cria instância tradicional (Apache)
     * @return RequestTraditional
     */
    public static function createTraditional(): RequestTraditional {
        return new RequestTraditional();
    }
    
    /**
     * Cria instância para Swoole
     * @param mixed|null $request Objeto request do Swoole
     * @return RequestSwoole
     */
    public static function createSwoole($request = null): RequestSwoole {
        $requestObj = new RequestSwoole();
        
        $swooleRequestObj = $request ?? self::$swooleRequest;
        
        if ($swooleRequestObj) {
            $requestObj->setRequest($swooleRequestObj);
        }
        
        return $requestObj;
    }
    
    /**
     * Detecta automaticamente o ambiente e retorna a implementação apropriada
     * @param mixed|null $swooleRequest Request do Swoole se disponível
     * @return RequestInterface
     */
    public static function createAuto($swooleRequest = null): RequestInterface {
        // Se foi passado objeto do Swoole, usa implementação Swoole
        if ($swooleRequest !== null) {
            return self::createSwoole($swooleRequest);
        }
        
        // Se há objeto global configurado, usa Swoole
        if (self::$swooleRequest !== null) {
            return self::createSwoole();
        }
        
        // Tenta detectar se está rodando no Swoole verificando variáveis globais
        if ((defined('SWOOLE_VERSION') || class_exists('Swoole\\Http\\Request')) && php_sapi_name() === 'cli') {
            return self::createSwoole();
        }
        
        // Por padrão, usa implementação tradicional
        return self::createTraditional();
    }
}