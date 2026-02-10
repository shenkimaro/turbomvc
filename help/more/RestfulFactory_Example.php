<?php

/**
 * Exemplo de uso do RestfulFactory
 * Demonstra como configurar e usar as diferentes implementações
 */

require_once 'RestfulFactory.php';

// ===========================
// CONFIGURAÇÃO INICIAL
// ===========================

// Exemplo 1: Configuração para uso tradicional (Apache/Nginx com PHP-FPM)
RestfulFactory::setDefaultType(RestfulFactory::TYPE_TRADITIONAL);

// Exemplo 2: Configuração para uso com Swoole
// RestfulFactory::setDefaultType(RestfulFactory::TYPE_SWOOLE);

// ===========================
// USO NO CÓDIGO DA APLICAÇÃO
// ===========================

// Modo 1: Usar a factory com configuração padrão
$restful = RestfulFactory::create();

// Modo 2: Especificar o tipo explicitamente
$restfulTraditional = RestfulFactory::create(RestfulFactory::TYPE_TRADITIONAL);
$restfulSwoole = RestfulFactory::create(RestfulFactory::TYPE_SWOOLE);

// Modo 3: Usar métodos específicos
$restfulTraditional = RestfulFactory::createTraditional();
$restfulSwoole = RestfulFactory::createSwoole($swooleResponse); // $swooleResponse vem do Swoole

// Modo 4: Detecção automática
$restful = RestfulFactory::createAuto($swooleResponse); // Passa response se disponível

// ===========================
// EXEMPLO DE USO EM CONTROLLER
// ===========================

class ExemploController {
    
    public function listarUsuarios() {
        // Cria instância usando factory
        $restful = RestfulFactory::create();
        
        try {
            // Lógica do controller...
            $usuarios = [
                ['id' => 1, 'nome' => 'João'],
                ['id' => 2, 'nome' => 'Maria']
            ];
            
            // Retorna resposta (funciona tanto para tradicional quanto Swoole)
            $restful->printREST($usuarios, Restful::STATUS_OK);
            
        } catch (Exception $e) {
            $restful->printREST(['erro' => $e->getMessage()], Restful::STATUS_ERRO_INTERNO_SERVIDOR);
        }
    }
}

// ===========================
// CONFIGURAÇÃO NO INDEX.PHP
// ===========================

/*
// Para ambiente tradicional (Apache/Nginx):
RestfulFactory::setDefaultType(RestfulFactory::TYPE_TRADITIONAL);

// Para ambiente Swoole:
RestfulFactory::setDefaultType(RestfulFactory::TYPE_SWOOLE);

// Se usando Swoole, configurar response global:
$http = new Swoole\Http\Server("127.0.0.1", 9501);
$http->on("request", function ($request, $response) {
    // Define response global para a factory
    RestfulFactory::setSwooleResponse($response);
    
    // Sua lógica de roteamento aqui...
    $controller = new ExemploController();
    $controller->listarUsuarios();
});
$http->start();
*/

// ===========================
// MIGRAÇÃO DE CÓDIGO EXISTENTE
// ===========================

/*
// Código antigo:
// $restful = new Restful();
// $restful->printREST($data);

// Código novo (compatível):
$restful = RestfulFactory::create();
$restful->printREST($data);

// Para desativar die() em ambiente tradicional (se necessário):
if ($restful instanceof RestfulTraditional) {
    $restful->setShouldExit(false);
}
*/