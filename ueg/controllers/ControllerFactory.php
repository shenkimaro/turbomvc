<?php

namespace library\ueg\controllers;

use library\ueg\controllers\SwooleController;
use library\ueg\controllers\ApiController;
use library\ueg\controllers\TraditionalController;

/**
 * Factory para criação de Controllers com diferentes implementações
 * 
 * Segue o mesmo padrão do RestfulFactory, permitindo:
 * - Modo tradicional (com templates)
 * - Modo API (sem templates, para JSON)
 * - Auto-detecção baseada em _TEMPLATE_MANAGER
 * 
 * @author Baseado no padrão RestfulFactory
 * @version 1.0
 */
class ControllerFactory {
    
    /**
     * Tipo padrão para novos controllers
     * @var string
     */
    private static $defaultType = 'traditional';
    
    /**
     * Response do Swoole para controllers Swoole
     * @var mixed
     */
    private static $swooleResponse = null;
    
    /**
     * Cria uma instância de controller baseada no contexto
     * 
     * @param string $controllerClass Nome da classe do controller
     * @param string $type Tipo: 'traditional', 'api', 'swoole', ou 'auto'
     * @param mixed $swooleResponse Response do Swoole (opcional)
     * @return mixed Instância do controller apropriado
     */
    public static function create($controllerClass = null, $type = null, $swooleResponse = null) {
        $type = $type ?: self::$defaultType;
        $swooleResponse = $swooleResponse ?: self::$swooleResponse;
        
        // Auto-detecção se não especificado
        if ($type === 'auto') {
            $type = self::detectEnvironment($swooleResponse);
        }
        
        // Se não passou classe, retorna Controller padrão
        if ($controllerClass === null) {
            $controllerClass = 'Controller';
        }
        
        // PRIORIDADE: Se tem response do Swoole, sempre usa SwooleController
        if ($swooleResponse !== null || $type === 'swoole') {
            return new SwooleController($controllerClass, $swooleResponse);
        }
        
        // Depois verifica se é modo API (apenas se não for Swoole)
        if (defined('_TEMPLATE_MANAGER') && _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW) {
            $type = 'api';
        }
        
        switch ($type) {
            case 'api':
                return new ApiController($controllerClass);
            default:
                return new TraditionalController($controllerClass);
        }
    }
    
    /**
     * Cria controller explicitamente no modo tradicional (com templates)
     */
    public static function createTraditional($controllerClass = null) {
        return new TraditionalController($controllerClass ?: 'Controller');
    }
    
    /**
     * Cria controller explicitamente no modo API (sem templates)
     */
    public static function createApi($controllerClass = null) {
        return new ApiController($controllerClass ?: 'Controller');
    }
    
    /**
     * Cria controller para Swoole
     */
    public static function createSwoole($controllerClass = null, $swooleResponse = null) {
        return new SwooleController(
            $controllerClass ?: 'Controller', 
            $swooleResponse ?: self::$swooleResponse
        );
    }
    
    /**
     * Auto-detecção do ambiente
     */
    public static function createAuto($controllerClass = null, $swooleResponse = null) {
        return self::create($controllerClass, 'auto', $swooleResponse);
    }
    
    /**
     * Define o tipo padrão para controllers
     */
    public static function setDefaultType($type) {
        self::$defaultType = $type;
    }
    
    /**
     * Define response global do Swoole
     */
    public static function setSwooleResponse($response) {
        self::$swooleResponse = $response;
    }
    
    /**
     * Detecta automaticamente o ambiente
     */
    private static function detectEnvironment($swooleResponse) {
        // Se tem response do Swoole, é Swoole
        if ($swooleResponse !== null) {
            return 'swoole';
        }
        
        // Se está configurado para JSON, é API
        if (defined('_TEMPLATE_MANAGER') && _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW) {
            return 'api';
        }
        
        // Padrão é tradicional
        return 'traditional';
    }
}