# Controller Factory Pattern - Documentação Completa

## Resumo das Melhorias

A classe `Controller.php5` foi atualizada para integrar o factory pattern, permitindo uso otimizado para APIs JSON e ambientes Swoole, **mantendo 100% de compatibilidade** com código existente.

## ✅ Vantagens da Nova Versão

### 1. **Compatibilidade Total**
```php
// Código antigo continua funcionando exatamente igual
$controller = new MinhaController();
$controller->listener();
```

### 2. **Novas Opções Otimizadas**
```php
// Novas formas otimizadas para diferentes contextos
$controller = Controller::create();                    // Auto-detecção
$controller = Controller::createApi();                 // Modo API (sem templates)
$controller = Controller::createTraditional();         // Modo tradicional (com templates)
$controller = Controller::createSwoole($response);     // Modo Swoole

// Ou usando classes específicas
$controller = MinhaController::create();
$controller = MinhaController::createApi();
```

### 3. **Auto-detecção Inteligente**
```php
// Se _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW, usa automaticamente modo API
$controller = Controller::create(); // Detecta automaticamente o modo adequado
```

## 🚀 Como Funciona

### Detecção Automática de Modo

1. **Modo API**: Detectado quando `_TEMPLATE_MANAGER == View::ENGINE_JSONVIEW`
   - Pula carregamento de templates
   - Otimizado para retornos JSON
   - Não processa HTML

2. **Modo Tradicional**: Comportamento padrão original
   - Carrega templates normalmente
   - Processa HTML
   - Compatibilidade total

3. **Modo Swoole**: Para Swoole HTTP Server
   - Otimizado para Swoole
   - Gerencia responses adequadamente

## 📁 Arquivos Criados

### 1. `ControllerFactory.php`
Factory principal que decide qual implementação usar:

```php
<?php
// Auto-detecção baseada no contexto
$controller = ControllerFactory::create('MinhaController');

// Configuração global
ControllerFactory::setDefaultType('api'); // ou 'traditional', 'swoole'
```

### 2. `ApiController.php`
Implementação otimizada para APIs:

```php
<?php
// Herda do Controller original mas pula templates quando em modo JSON
class ApiController extends Controller {
    // Sobrescreve métodos para pular templates
    protected function indexTemplate($var = '') {
        if (defined('_TEMPLATE_MANAGER') && _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW) {
            return; // Não faz nada
        }
        parent::indexTemplate($var);
    }
}
```

### 3. `TraditionalController.php`
Wrapper que mantém comportamento original 100%:

```php
<?php
// Garante compatibilidade total com código existente
class TraditionalController extends Controller {
    // Mantém todos os métodos originais
}
```

### 4. `SwooleController.php`
Implementação otimizada para Swoole (SEM mergeTemplate):

```php
<?php
// Otimizado para Swoole HTTP Server
// NOVA VERSÃO: Não utiliza mergeTemplate() para máxima performance
class SwooleController extends Controller {
    private $swooleResponse;
    
    // listener() - executa APENAS métodos PHP
    // loadAction() - simplificado para Swoole
    // loadModule() - otimizado, sem indexTemplate()
    // indexTemplate() - retorna JSON em vez de carregar templates
    // Todos os responses via RestfulFactory::createSwoole()
}
```
class SwooleController extends Controller {
    private $swooleResponse;
    // Métodos otimizados para Swoole
}
```

## 🔧 Exemplos de Uso

### Em Controllers Existentes

#### Exemplo 1: Controller de Usuário (API)
```php
<?php
class UsuarioController extends Controller {
    
    public function listar() {
        $usuarios = $this->usuarioService->listar();
        
        // Em modo API, só executa o método, sem template
        // Em modo tradicional, carrega template 'listar.html'
        return $usuarios;
    }
    
    public function criar() {
        $dados = $this->getFormVars();
        $usuario = $this->usuarioService->criar($dados);
        
        // Restful usado para retorno JSON em APIs
        $restful = Restful::create();
        $restful->printREST($usuario, Restful::STATUS_OK);
    }
}

// Uso:
$controller = UsuarioController::create(); // Auto-detecção
$controller->listener();
```

#### Exemplo 2: Migração Gradual
```php
<?php
// Código antigo (continua funcionando)
$controller = new UsuarioController();

// Novo código (otimizado)
$controller = UsuarioController::create();

// Ambos funcionam exatamente igual!
```

### Configuração no index.php

#### Para APIs JSON:
```php
<?php
// Define modo JSON
define('_TEMPLATE_MANAGER', View::ENGINE_JSONVIEW);

// Agora todos os create() usarão ApiController automaticamente
$controller = UsuarioController::create();
```

#### Para ambiente tradicional:
```php
<?php
// Não define _TEMPLATE_MANAGER ou define diferente
// ou
ControllerFactory::setDefaultType('traditional');

$controller = UsuarioController::create(); // Usará TraditionalController
```

#### Para ambiente Swoole:
```php
<?php
require_once 'ControllerFactory.php';

ControllerFactory::setDefaultType('swoole');

$http = new Swoole\Http\Server("127.0.0.1", 9501);
$http->on("request", function ($request, $response) {
    $controller = UsuarioController::createSwoole($response);
    $controller->listener();
});
$http->start();
```

## 🎯 Diferenças Entre Modos

### Modo Tradicional (Traditional)
```php
$controller = Controller::createTraditional();
```
- ✅ Carrega templates HTML
- ✅ Processa mergeTemplate()
- ✅ Comportamento original 100%
- ✅ Para aplicações web tradicionais

### Modo API (ApiController)
```php
$controller = Controller::createApi();
```
- ❌ **NÃO** carrega templates
- ❌ **NÃO** processa HTML
- ✅ Executa apenas métodos PHP
- ✅ Otimizado para retornos JSON
- ✅ Performance superior para APIs

### Modo Swoole (SwooleController)
```php
$controller = Controller::createSwoole($response);
```
- ✅ Otimizado para Swoole
- ✅ Gerencia response do Swoole
- ✅ Performance superior

## 🔄 Migração Gradual

### Passo 1: Não fazer nada
Código existente continua funcionando sem alterações.

### Passo 2: Configurar detecção automática
```php
// No início da aplicação
if ($isApiMode) {
    define('_TEMPLATE_MANAGER', View::ENGINE_JSONVIEW);
}
```

### Passo 3: Migrar para factory
```php
// De:
$controller = new MinhaController();

// Para:
$controller = MinhaController::create();
```

### Passo 4: Usar tipos específicos quando necessário
```php
// Para API explícita
$controller = MinhaController::createApi();

// Para tradicional explícito
$controller = MinhaController::createTraditional();
```

## 🎭 Comportamento nos Métodos

### loadAction() - Comparação

#### Modo Tradicional:
```php
private function loadAction($action) {
    if (method_exists($this, $action)) {
        $this->$action();                        // Executa método
        $this->view->mergeTemplate($action);     // ✅ Carrega template
    }
}
```

#### Modo API:
```php
private function loadAction($action) {
    if (method_exists($this, $action)) {
        $this->$action();                        // Executa método
        // ❌ NÃO carrega template em modo JSON
    }
}
```

### listener() - Comparação

#### Modo Tradicional:
```php
public function listener() {
    // ... lógica ...
    $this->$func();                              // Executa método
    $this->view->mergeTemplate($op);             // ✅ Carrega template
}
```

#### Modo API:
```php
public function listener() {
    // ... lógica ...
    $this->$func();                              // Executa método
    // ❌ NÃO carrega template se _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW
}
```

## 🚨 Pontos Importantes

### 1. **Zero Breaking Changes**
- Código existente não precisa ser alterado
- `new Controller()` continua funcionando
- Todos os métodos originais preservados

### 2. **Detecção Automática**
- `_TEMPLATE_MANAGER == View::ENGINE_JSONVIEW` → Modo API automático
- Senão → Modo tradicional

### 3. **Performance**
- Modo API: **Muito mais rápido** para retornos JSON
- Não processa templates desnecessários
- Não faz verificações de arquivos HTML

### 4. **Flexibilidade**
- Pode forçar modo específico quando necessário
- Permite configuração global ou por instância
- Suporte a controllers customizados

## 💡 Recomendação de Uso

### Para APIs novas:
```php
define('_TEMPLATE_MANAGER', View::ENGINE_JSONVIEW);
$controller = Controller::create(); // Auto-detecção → ApiController
```

### Para aplicações web tradicionais:
```php
$controller = Controller::create(); // Auto-detecção → TraditionalController
```

### Para migração gradual:
```php
// Substitua aos poucos:
// new MinhaController() → MinhaController::create()
```

### Para Swoole:
```php
$controller = Controller::createSwoole($response);
```

## ✨ Benefícios Finais

1. **Performance**: APIs JSON muito mais rápidas
2. **Flexibilidade**: Suporte a múltiplos ambientes
3. **Compatibilidade**: Zero breaking changes
4. **Manutenibilidade**: Código mais organizado
5. **Escalabilidade**: Pronto para Swoole
6. **Simplicidade**: Auto-detecção inteligente

A nova estrutura é uma evolução natural que adiciona funcionalidades sem quebrar nada existente, seguindo o mesmo padrão de sucesso do RestfulFactory.