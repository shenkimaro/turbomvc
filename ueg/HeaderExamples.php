<?php
namespace library\ueg;

require_once 'Header.php';

/**
 * Exemplo de uso da classe Header com suporte para Apache e Swoole
 */

// ===== USO PADRÃO (SEM ALTERAÇÕES NO CÓDIGO EXISTENTE) =====

// O código existente continua funcionando exatamente igual
$token = Header::getAuthorizationBearer();
$authorization = Header::getAuthorization();

echo "Token: " . ($token ?? 'null') . "\n";
echo "Authorization: " . ($authorization ?? 'null') . "\n";

// ===== CONFIGURAÇÃO PARA AMBIENTE SWOOLE =====

// Em um servidor Swoole, você configuraria assim:
/*
$server = new Swoole\Http\Server("0.0.0.0", 9501);

$server->on("request", function ($request, $response) {
    // Configura o Header para usar objetos Swoole
    Header::configureSwoole($request, $response);
    
    // Agora todas as chamadas usarão automaticamente Swoole
    $token = Header::getAuthorizationBearer();
    $authorization = Header::getAuthorization();
    
    // Resposta
    $response->header("Content-Type", "application/json");
    $response->end(json_encode([
        'token' => $token,
        'authorization' => $authorization
    ]));
});

$server->start();
*/

// ===== CONFIGURAÇÃO MANUAL DO TIPO =====

// Forçar uso da implementação tradicional
Header::setDefaultType('traditional');

// Forçar uso da implementação Swoole
// Header::setDefaultType('swoole');

// ===== USO DIRETO DO FACTORY (OPCIONAL) =====

// Criar instância específica sem alterar comportamento global
$headerTraditional = HeaderFactory::createTraditional();
$tokenTraditional = $headerTraditional->getAuthorizationBearer();

// Criar instância Swoole (necessita dos objetos request/response)
// $headerSwoole = HeaderFactory::createSwoole($request, $response);
// $tokenSwoole = $headerSwoole->getAuthorizationBearer();

// Detecção automática
$headerAuto = HeaderFactory::createAuto();
$tokenAuto = $headerAuto->getAuthorizationBearer();

echo "\nToken (Traditional): " . ($tokenTraditional ?? 'null') . "\n";
echo "Token (Auto): " . ($tokenAuto ?? 'null') . "\n";

// ===== EXEMPLO PARA SISTEMA HÍBRIDO =====

// Se você tem um sistema que roda tanto em Apache quanto Swoole
function handleRequest($request = null, $response = null) {
    if ($request && $response) {
        // Ambiente Swoole
        Header::configureSwoole($request, $response);
    } else {
        // Ambiente Apache - configuração padrão
        Header::setDefaultType('traditional');
    }
    
    // O resto do código é idêntico independente do ambiente
    $token = Header::getAuthorizationBearer();
    
    return [
        'token' => $token,
        'environment' => ($request && $response) ? 'swoole' : 'apache'
    ];
}

$result = handleRequest();
echo "\nResult: " . json_encode($result) . "\n";