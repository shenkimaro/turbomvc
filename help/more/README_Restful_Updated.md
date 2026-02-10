# Restful.php5 - Versão Atualizada com Factory

## Resumo das Melhorias

A classe `Restful.php5` foi atualizada para integrar a factory pattern, permitindo uso tanto em ambientes tradicionais (Apache/Nginx) quanto em Swoole, **mantendo 100% de compatibilidade** com código existente.

## ✅ Vantagens da Nova Versão

### 1. **Compatibilidade Total**
```php
// Código antigo continua funcionando exatamente igual
$restful = new Restful();
$restful->printREST($data);
```

### 2. **Novas Opções Simplificadas**
```php
// Novas formas mais simples e flexíveis
$restful = Restful::create();                    // Usa configuração padrão
$restful = Restful::createAuto($swooleResponse); // Auto-detecção
$restful = Restful::createTraditional();         // Explícito tradicional
$restful = Restful::createSwoole($response);     // Explícito Swoole
```

### 3. **Configuração Global**
```php
// Define o comportamento padrão para toda aplicação
Restful::setDefaultType('swoole');          // ou 'traditional'
Restful::setSwooleResponse($response);      // Response global para Swoole
```

## 🚀 Como Migrar

### Opção 1: Não fazer nada
Seu código existente continua funcionando sem alterações.

### Opção 2: Migração gradual
Substitua `new Restful()` por `Restful::create()` quando conveniente.

### Opção 3: Migração completa
Configure o tipo padrão no início da aplicação e use os novos métodos.

## 📝 Exemplos de Uso

### Em Controllers
```php
class UsuarioController {
    
    public function listar() {
        $restful = Restful::create(); // Simples e automático
        
        try {
            $usuarios = $this->usuarioService->listar();
            $restful->printREST($usuarios, Restful::STATUS_OK);
        } catch (Exception $e) {
            $restful->printREST(['erro' => $e->getMessage()], Restful::STATUS_ERRO_INTERNO_SERVIDOR);
        }
    }
}
```

### Configuração no index.php

#### Para ambiente tradicional:
```php
<?php
require_once 'Restful.php5';

// Define padrão como tradicional
Restful::setDefaultType('traditional');

// Agora todos os create() usarão implementação tradicional
$restful = Restful::create();
```

#### Para ambiente Swoole:
```php
<?php
require_once 'Restful.php5';

// Define padrão como Swoole
Restful::setDefaultType('swoole');

$http = new Swoole\Http\Server("127.0.0.1", 9501);
$http->on("request", function ($request, $response) {
    // Define response global
    Restful::setSwooleResponse($response);
    
    // Agora todos os create() usarão implementação Swoole automaticamente
    $controller = new UsuarioController();
    $controller->listar();
});
$http->start();
```

## 🔧 Métodos Disponíveis

### Métodos de Criação
- `Restful::create($type, $swooleResponse)` - Cria usando configuração padrão
- `Restful::createTraditional()` - Força uso tradicional
- `Restful::createSwoole($response)` - Força uso Swoole
- `Restful::createAuto($swooleResponse)` - Auto-detecção do ambiente

### Métodos de Configuração
- `Restful::setDefaultType($type)` - Define tipo padrão ('traditional' ou 'swoole')
- `Restful::setSwooleResponse($response)` - Define response global para Swoole

### Métodos Originais (inalterados)
Todos os métodos da classe original continuam funcionando:
- `printREST($data, $status)`
- `getREQUEST()`
- `getMethod()`
- `getRequestHeaders()`
- `setTipoSaida($tipo)`
- etc.

## 💡 Benefícios

1. **Zero Breaking Changes**: Código existente não precisa ser alterado
2. **Flexibilidade**: Suporte nativo a Swoole e tradicional
3. **Simplicidade**: Menos código para escrever
4. **Manutenibilidade**: Um ponto central de configuração
5. **Performance**: Otimizações específicas para cada ambiente

## 🎯 Recomendação

Para novos projetos, use `Restful::create()` após configurar o tipo padrão. Para projetos existentes, migre gradualmente conforme necessário.

A nova versão é uma evolução natural que adiciona funcionalidades sem quebrar nada existente.