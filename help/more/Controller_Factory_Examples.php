<?php

/**
 * Exemplo Prático: Como usar o Controller Factory Pattern
 * 
 * Este arquivo demonstra como usar a nova estrutura de controllers
 * mantendo compatibilidade total com código existente.
 */

// ===== EXEMPLO 1: Controller de API simples =====

class ExemploApiController extends Controller {
    
    public function listar() {
        // Lógica de negócio
        $dados = [
            ['id' => 1, 'nome' => 'João'],
            ['id' => 2, 'nome' => 'Maria']
        ];
        
        // Em modo API: retorna JSON automaticamente
        // Em modo tradicional: carrega template 'listar.html'
        
        $restful = Restful::create();
        $restful->printREST($dados, Restful::STATUS_OK);
    }
    
    public function criar() {
        $nome = $this->getFormVars('nome');
        
        if (empty($nome)) {
            $restful = Restful::create();
            $restful->printREST(['erro' => 'Nome obrigatório'], Restful::STATUS_BAD_REQUEST);
            return;
        }
        
        // Lógica de criação...
        $novoItem = ['id' => 3, 'nome' => $nome];
        
        $restful = Restful::create();
        $restful->printREST($novoItem, Restful::STATUS_CREATED);
    }
}

// ===== EXEMPLO 2: Uso em diferentes contextos =====

// 2.1 - Código antigo (continua funcionando)
echo "=== CÓDIGO ANTIGO ===\n";
$controller1 = new ExemploApiController();
// $controller1->listener(); // Funcionaria normalmente

// 2.2 - Novo código com auto-detecção
echo "=== NOVO CÓDIGO - AUTO DETECÇÃO ===\n";
$controller2 = ExemploApiController::create(); // Auto-detecção baseada em _TEMPLATE_MANAGER

// 2.3 - Forçar modo específico
echo "=== FORÇAR MODO ESPECÍFICO ===\n";
$controllerApi = ExemploApiController::createApi();           // Sempre modo API
$controllerTrad = ExemploApiController::createTraditional();  // Sempre modo tradicional

// ===== EXEMPLO 3: Configuração de ambiente =====

// 3.1 - Para APIs (JSON)
function exemploConfiguracaoApi() {
    echo "=== CONFIGURAÇÃO PARA API ===\n";
    
    // Define modo JSON
    define('_TEMPLATE_MANAGER', View::ENGINE_JSONVIEW);
    
    // Agora todos os create() usarão ApiController automaticamente
    $controller = ExemploApiController::create();
    
    echo "Usando: " . get_class($controller) . "\n";
    // Output esperado: "Usando: ApiController"
}

// 3.2 - Para aplicação web tradicional
function exemploConfiguracaoTradicional() {
    echo "=== CONFIGURAÇÃO TRADICIONAL ===\n";
    
    // Não define _TEMPLATE_MANAGER ou define como algo diferente
    if (defined('_TEMPLATE_MANAGER')) {
        // Já definido em outro lugar, força tradicional
        $controller = ExemploApiController::createTraditional();
    } else {
        // Auto-detecção funcionará como tradicional
        $controller = ExemploApiController::create();
    }
    
    echo "Usando: " . get_class($controller) . "\n";
    // Output esperado: "Usando: TraditionalController"
}

// 3.3 - Para Swoole
function exemploConfiguracaoSwoole() {
    echo "=== CONFIGURAÇÃO SWOOLE ===\n";
    
    // Simula resposta do Swoole
    $swooleResponse = new stdClass(); // Em caso real seria: new Swoole\Http\Response()
    
    $controller = ExemploApiController::createSwoole($swooleResponse);
    
    echo "Usando: " . get_class($controller) . "\n";
    // Output esperado: "Usando: SwooleController"
}

// ===== EXEMPLO 4: Configuração global =====

function exemploConfiguracaoGlobal() {
    echo "=== CONFIGURAÇÃO GLOBAL ===\n";
    
    // Define tipo padrão para toda aplicação
    ControllerFactory::setDefaultType('api');
    
    // Agora todos os create() sem parâmetros usarão modo API
    $controller1 = ExemploApiController::create();
    $controller2 = Controller::create();
    
    echo "Controller1: " . get_class($controller1) . "\n";
    echo "Controller2: " . get_class($controller2) . "\n";
    // Ambos serão ApiController
}

// ===== EXEMPLO 5: Controller customizado =====

class MeuControllerCustomizado extends Controller {
    
    public function metodoEspecial() {
        echo "Método especial executado!\n";
        
        // Em modo API: só executa o código
        // Em modo tradicional: + carrega template 'metodoespecial.html'
    }
    
    public function index() {
        echo "Index do controller customizado\n";
    }
}

function exemploControllerCustomizado() {
    echo "=== CONTROLLER CUSTOMIZADO ===\n";
    
    // Todas as variações funcionam
    $custom1 = new MeuControllerCustomizado();                    // Tradicional
    $custom2 = MeuControllerCustomizado::create();                // Auto-detecção
    $custom3 = MeuControllerCustomizado::createApi();             // Forçar API
    $custom4 = MeuControllerCustomizado::createTraditional();     // Forçar tradicional
    
    echo "Custom1: " . get_class($custom1) . "\n";
    echo "Custom2: " . get_class($custom2) . "\n";
    echo "Custom3: " . get_class($custom3) . "\n";
    echo "Custom4: " . get_class($custom4) . "\n";
}

// ===== EXEMPLO 6: Diferença prática entre modos =====

function exemploComparacao() {
    echo "=== COMPARAÇÃO ENTRE MODOS ===\n";
    
    // Simula _TEMPLATE_MANAGER
    define('_TEMPLATE_MANAGER', View::ENGINE_JSONVIEW);
    
    $apiController = ExemploApiController::createApi();
    $tradController = ExemploApiController::createTraditional();
    
    echo "API Controller:\n";
    echo "- Executa métodos PHP: ✅\n";
    echo "- Carrega templates HTML: ❌ (pulado)\n";
    echo "- Performance: ⚡ Alta\n";
    echo "- Ideal para: APIs REST, JSON\n\n";
    
    echo "Traditional Controller:\n";
    echo "- Executa métodos PHP: ✅\n";
    echo "- Carrega templates HTML: ✅\n";
    echo "- Performance: 📈 Normal\n";
    echo "- Ideal para: Aplicações web, HTML\n\n";
}

// ===== EXEMPLO 7: Migração gradual =====

function exemploMigracaoGradual() {
    echo "=== MIGRAÇÃO GRADUAL ===\n";
    
    // PASSO 1: Código existente (não muda)
    $old = new ExemploApiController();
    
    // PASSO 2: Substitui aos poucos por factory
    $new = ExemploApiController::create();
    
    // PASSO 3: Força modo quando necessário
    $api = ExemploApiController::createApi();
    
    echo "Todos funcionam! Zero breaking changes.\n";
}

// ===== EXECUTAR EXEMPLOS =====

if (!defined('_TEMPLATE_MANAGER')) {
    echo "Executando exemplos...\n\n";
    
    exemploConfiguracaoApi();
    echo "\n";
    
    exemploConfiguracaoTradicional();
    echo "\n";
    
    exemploConfiguracaoSwoole();
    echo "\n";
    
    exemploConfiguracaoGlobal();
    echo "\n";
    
    exemploControllerCustomizado();
    echo "\n";
    
    exemploComparacao();
    echo "\n";
    
    exemploMigracaoGradual();
    echo "\n";
    
    echo "Exemplos concluídos!\n";
}

?>