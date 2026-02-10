<?php

/**
 * Factory para criar instâncias do Get apropriadas
 * Permite alternar entre implementação tradicional (Apache) e Swoole
 */
class GetFactory {
    
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
     * Cria uma instância do Get baseada na configuração
     * @param string|null $type Tipo específico ou usa o padrão
     * @param mixed|null $swooleRequest Request específico para Swoole
     * @return GetInterface
     */
    public static function create($type = null, $swooleRequest = null): GetInterface {
        $type = $type ?? self::$defaultType;
        
        switch ($type) {
            case self::TYPE_TRADITIONAL:
                return new GetTraditional();
                
            case self::TYPE_SWOOLE:
                $request = new GetSwoole();
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
     * @return GetTraditional
     */
    public static function createTraditional(): GetTraditional {
        return new GetTraditional();
    }
    
    /**
     * Cria instância para Swoole
     * @param mixed|null $request Objeto request do Swoole
     * @return GetSwoole
     */
    public static function createSwoole($request = null): GetSwoole {
        $requestObj = new GetSwoole();
        
        $swooleRequestObj = $request ?? self::$swooleRequest;
        
        if ($swooleRequestObj) {
            $requestObj->setRequest($swooleRequestObj);
        }
        
        return $requestObj;
    }
    
    /**
     * Detecta automaticamente o ambiente e retorna a implementação apropriada
     * @param mixed|null $swooleRequest Request do Swoole se disponível
     * @return GetInterface
     */
    public static function createAuto($swooleRequest = null): GetInterface {
        // Se foi passado objeto do Swoole, usa implementação Swoole
        if ($swooleRequest !== null) {
            return self::createSwoole($swooleRequest);
        }
        
        // Se há objeto global configurado, usa Swoole
        if (self::$swooleRequest !== null) {
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