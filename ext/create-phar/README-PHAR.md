# Criação de PHAR para pastas

Este projeto inclui scripts para converter qualquer pasta em um arquivo PHAR (PHP Archive), permitindo distribuir arquivos e dependências em um único arquivo.

## Vantagens do PHAR

- **Arquivo único**: Todos os arquivos em um só arquivo
- **Distribuição simplificada**: Facilita o deploy e distribuição
- **Performance**: Pode ser mais rápido que carregar muitos arquivos individuais
- **Compressão**: Reduz o tamanho total dos arquivos
- **Isolamento**: Evita conflitos de versões

## Pré-requisitos

1. Extensão PHP Phar habilitada
2. Configuração `phar.readonly` desabilitada

## Como criar o PHAR

### 1. Verificar configuração do PHP

```bash
# Verificar se a extensão Phar está carregada
php -m | grep -i phar

# Verificar se phar.readonly está desabilitado
php -i | grep phar.readonly
```

### 2. Executar o script de criação

#### Uso básico (pasta vendor):
```bash
# Cria vendor.phar da pasta vendor
php -d phar.readonly=0 create-phar.php

# Ou especificando explicitamente
php -d phar.readonly=0 create-phar.php vendor
```

#### Uso avançado (qualquer pasta):
```bash
# Criar PHAR de uma pasta específica
php -d phar.readonly=0 create-phar.php src src-lib.phar

# Usando caminho absoluto
php -d phar.readonly=0 create-phar.php /caminho/para/pasta

# Nome personalizado para o PHAR
php -d phar.readonly=0 create-phar.php vendor minha-vendor.phar
```

#### Sintaxe completa:
```bash
php -d phar.readonly=0 create-phar.php [pasta-origem] [arquivo-phar]
```

**Parâmetros:**
- `pasta-origem` (opcional): Pasta a ser empacotada (padrão: `vendor`)
- `arquivo-phar` (opcional): Nome do arquivo PHAR (padrão: `nome-da-pasta.phar`)

### 3. Testar o PHAR criado

```bash
php test-phar.php
```

## Estrutura de arquivos criados

- `create-phar.php` - Script principal para criar o PHAR
- `test-phar.php` - Script de teste para verificar o funcionamento
- `*.phar` - Arquivo(s) PHAR gerado(s) (criados após execução)

## Como usar o PHAR

### Para pasta vendor:
```php
// Antes (usando pasta vendor):
require __DIR__ . '/vendor/autoload.php';

// Depois (usando PHAR):
require __DIR__ . '/vendor.phar';
```

### Para outras pastas:
```php
// Para uma pasta src empacotada:
require __DIR__ . '/src.phar';

// Para biblioteca personalizada:
require __DIR__ . '/minha-lib.phar';
```

## Exemplos práticos

### 1. Empacotar dependências vendor
```bash
php -d phar.readonly=0 create-phar.php vendor vendor.phar
```

### 2. Empacotar código-fonte da aplicação
```bash
php -d phar.readonly=0 create-phar.php src app.phar
```

### 3. Empacotar biblioteca específica
```bash
php -d phar.readonly=0 create-phar.php library my-library.phar
```

### 4. Uso com caminhos absolutos
```bash
php -d phar.readonly=0 create-phar.php /var/www/projeto/modules modules.phar
```

## Funcionalidades do script

### Otimizações incluídas:
- Remove arquivos desnecessários (testes, documentação, etc.)
- Compressão automática (se zlib estiver disponível)
- Stub personalizado para autoloading
- Verificações de integridade

### Arquivos ignorados automaticamente:
- Diretórios de teste (`tests/`, `spec/`, etc.)
- Documentação (README, CHANGELOG, etc.)
- Arquivos de configuração de desenvolvimento
- Exemplos e demos
- Arquivos temporários

## Configuração do PHP (php.ini)

Para uso em produção, adicione ao `php.ini`:

```ini
; Habilitar extensão Phar
extension=phar

; Permitir criação de PHARs (apenas para desenvolvimento)
phar.readonly = Off

; Para produção, mantenha:
; phar.readonly = On
```

## Exemplo de integração

```php
<?php
// Detectar automaticamente se usar PHAR ou pasta vendor
if (file_exists(__DIR__ . '/vendor.phar')) {
    require __DIR__ . '/vendor.phar';
} else {
    require __DIR__ . '/vendor/autoload.php';
}

// Seu código continua igual...
use OpenTelemetry\API\Globals;
// ...
```

## Resolução de problemas

### Erro: "phar.readonly is enabled"
```bash
php -d phar.readonly=0 create-phar.php
```

### Erro: "Phar extension not loaded"
Habilite a extensão no php.ini:
```ini
extension=phar
```

### Erro de permissões
```bash
chmod +x create-phar.php test-phar.php
```

## Comparação de tamanhos

Após a criação, você verá algo como:

```
Pasta vendor original: ~15MB (3000+ arquivos)
PHAR comprimido: ~5MB (1 arquivo)
Redução: ~67%
```

## Notas importantes

1. **Desenvolvimento vs Produção**: Use PHAR principalmente para produção
2. **Debugging**: Pode ser mais difícil debugar código dentro de PHAR
3. **Updates**: Para atualizar dependências, recrie o PHAR
4. **Performance**: PHAR pode ser mais lento para carregamento inicial em algumas situações
5. **Memória**: O PHAR inteiro pode ser carregado na memória

## Automação

Para automatizar a criação do PHAR no seu processo de build:

### Script básico (vendor):
```bash
#!/bin/bash
# build-vendor.sh
composer install --no-dev --optimize-autoloader
php -d phar.readonly=0 create-phar.php vendor vendor.phar
php test-phar.php
```

### Script avançado (múltiplas pastas):
```bash
#!/bin/bash
# build-all.sh

# Dependências
composer install --no-dev --optimize-autoloader
php -d phar.readonly=0 create-phar.php vendor vendor.phar

# Código da aplicação
php -d phar.readonly=0 create-phar.php src app.phar

# Bibliotecas customizadas
php -d phar.readonly=0 create-phar.php lib lib.phar

# Testes
php test-phar.php

echo "PHARs criados com sucesso!"
```

### Integração com Composer:
```json
{
  "scripts": {
    "build-phar": [
      "composer install --no-dev --optimize-autoloader",
      "php -d phar.readonly=0 create-phar.php vendor vendor.phar"
    ],
    "build-app-phar": [
      "php -d phar.readonly=0 create-phar.php src app.phar"
    ]
  }
}
```

Executar com:
```bash
composer run build-phar
composer run build-app-phar
```