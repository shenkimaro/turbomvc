<?php

/**
 * Exemplo de uso da classe Restful atualizada com factory
 * Demonstra diferentes formas de usar a classe, mantendo compatibilidade total
 */

require_once 'Restful.php5';

// ===========================
// USO TRADICIONAL (MANTÉM COMPATIBILIDADE)
// ===========================

// Forma antiga - ainda funciona exatamente igual
$restful_old = new Restful();
$restful_old->printREST(['mensagem' => 'Uso tradicional funciona'], Restful::STATUS_OK);

// ===========================
// NOVO USO COM FACTORY - MAIS SIMPLES
// ===========================

// Forma 1: Usando factory com configuração padrão
$restful = Restful::create();
$restful->printREST(['mensagem' => 'Usando factory padrão'], Restful::STATUS_OK);

// Forma 2: Especificando tipo explicitamente
$restfulTraditional = Restful::create('traditional');
$restfulSwoole = Restful::create('swoole', $swooleResponse);

// Forma 3: Métodos específicos
$restfulTraditional = Restful::createTraditional();
$restfulSwoole = Restful::createSwoole($swooleResponse);

// Forma 4: Detecção automática
$restful = Restful::createAuto($swooleResponse);

// ===========================
// CONFIGURAÇÃO GLOBAL
// ===========================

// Define tipo padrão para toda aplicação
Restful::setDefaultType('traditional'); // ou 'swoole'

// Para Swoole, define response global
Restful::setSwooleResponse($swooleResponse);

// ===========================
// EXEMPLO EM CONTROLLER
// ===========================

class ExemploController {
    
    public function listarUsuarios() {
        // Cria instância usando factory - automático e simples
        $restful = Restful::create();
        
        try {
            // Sua lógica aqui...
            $usuarios = [
                ['id' => 1, 'nome' => 'João'],
                ['id' => 2, 'nome' => 'Maria']
            ];
            
            // Funciona igual, tanto para tradicional quanto Swoole
            $restful->printREST($usuarios, Restful::STATUS_OK);
            
        } catch (Exception $e) {
            $restful->printREST(['erro' => $e->getMessage()], Restful::STATUS_ERRO_INTERNO_SERVIDOR);
        }
    }
    
    public function criarUsuario() {
        // Pode usar qualquer forma de criação
        $restful = Restful::createAuto();
        
        // Todos os métodos da classe original funcionam igual
        $dados = $restful->getREQUEST();
        $metodo = $restful->getMethod();
        
        // Lógica de criação...
        $restful->printREST(['id' => 123, 'criado' => true], Restful::STATUS_CRIADO);
    }
}

// ===========================
// MIGRAÇÃO GRADUAL
// ===========================

/*
ANTES:
$restful = new Restful();

DEPOIS (escolha uma):
$restful = Restful::create();              // Simples
$restful = Restful::createAuto();          // Auto-detecção
$restful = Restful::createTraditional();   // Explícito tradicional
$restful = new Restful();                  // Ainda funciona!
*/

// ===========================
// CONFIGURAÇÃO NO INDEX.PHP
// ===========================

/*
// Para ambiente tradicional (Apache/Nginx):
Restful::setDefaultType('traditional');

// Para ambiente Swoole:
Restful::setDefaultType('swoole');

// Se usando Swoole, configurar response global:
$http = new Swoole\Http\Server("127.0.0.1", 9501);
$http->on("request", function ($request, $response) {
    // Define response global
    Restful::setSwooleResponse($response);
    
    // Agora todos os controllers podem usar:
    $restful = Restful::create(); // Automaticamente será Swoole
    
    // Sua lógica de roteamento aqui...
});
$http->start();
*/