<?php
/**
 * Script para criar um arquivo PHAR de uma pasta
 * Usage: php create-phar.php [pasta-origem] [arquivo-phar]
 * 
 * Exemplos:
 *   php create-phar.php vendor vendor.phar
 *   php create-phar.php /caminho/para/pasta minha-lib.phar
 *   php create-phar.php (usa vendor como padrão)
 */

declare(strict_types=1);

// Verificar se a extensão phar está habilitada
if (!extension_loaded('phar')) {
    die("A extensão PHP Phar não está habilitada.\n");
}

// Verificar se phar.readonly está desabilitado
if (ini_get('phar.readonly')) {
    die("phar.readonly está habilitado. Execute com: php -d phar.readonly=0 create-phar.php\n");
}

// Processar argumentos da linha de comando
$sourceDir = $argv[1] ?? 'vendor';
$pharFileName = $argv[2] ?? null;

// Se o sourceDir não é um caminho absoluto, torná-lo relativo ao diretório atual
if (!str_starts_with($sourceDir, '/')) {
    $sourceDir = __DIR__ . '/' . $sourceDir;
}

// Verificar se a pasta origem existe
if (!is_dir($sourceDir)) {
    die("Erro: A pasta '$sourceDir' não existe.\n");
}

// Definir nome do arquivo PHAR
if ($pharFileName === null) {
    $pharFileName = basename($sourceDir) . '.phar';
}

// Se o pharFileName não é um caminho absoluto, torná-lo relativo ao diretório atual
if (!str_starts_with($pharFileName, '/')) {
    $pharFile = __DIR__ . '/' . $pharFileName;
} else {
    $pharFile = $pharFileName;
}

// Remover PHAR existente se houver
if (file_exists($pharFile)) {
    unlink($pharFile);
    echo "PHAR anterior removido.\n";
}

try {
    echo "Criando PHAR: $pharFile\n";
    echo "Pasta origem: $sourceDir\n";
    
    $phar = new Phar($pharFile);
    
    // Começar buffering para melhor performance
    $phar->startBuffering();
    
    // Definir o stub (código que será executado quando o PHAR for incluído)
    $stub = <<<'STUB'
<?php
/**
 * Vendor dependencies PHAR
 * Generated automatically - do not edit
 */

// Registrar o autoloader do composer
require_once 'phar://' . __FILE__ . '/autoload.php';

// Definir constante indicando que estamos usando PHAR
if (!defined('VENDOR_PHAR_LOADED')) {
    define('VENDOR_PHAR_LOADED', true);
}

__HALT_COMPILER();
STUB;
    
    $phar->setStub($stub);
    
    // Adicionar todos os arquivos da pasta vendor
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    $fileCount = 0;
    foreach ($iterator as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);
        
        // Pular arquivos desnecessários
        if (shouldSkipFile($relativePath)) {
            continue;
        }
        
        $phar->addFile($filePath, $relativePath);
        $fileCount++;
        
        if ($fileCount % 100 === 0) {
            echo "Processados $fileCount arquivos...\n";
        }
    }
    
    // Finalizar buffering
    $phar->stopBuffering();
    
    // Comprimir o arquivo (opcional)
    if (extension_loaded('zlib')) {
        echo "Comprimindo PHAR...\n";
        $phar->compressFiles(Phar::GZ);
    }
    
    echo "PHAR criado com sucesso!\n";
    echo "Total de arquivos: $fileCount\n";
    echo "Tamanho do arquivo: " . formatBytes(filesize($pharFile)) . "\n";
    echo "Localização: $pharFile\n";
    
} catch (Exception $e) {
    die("Erro ao criar PHAR: " . $e->getMessage() . "\n");
}

/**
 * Determina se um arquivo deve ser ignorado no PHAR
 */
function shouldSkipFile(string $relativePath): bool {
    $skipPatterns = [
        // Arquivos de desenvolvimento
        '/\.git/',
        '/\.svn/',
        '/\.hg/',
        '/tests?\//',
        '/test\//',
        '/Tests?\//',
        '/Test\//',
        '/spec\//',
        '/Spec\//',
        
        // Arquivos de documentação
        '/README\./',
        '/CHANGELOG\./',
        '/HISTORY\./',
        '/UPGRADE\./',
        '/UPGRADING\./',
        '/NEWS\./',
        '/COPYING\./',
        '/LICENSE\./',
        '/AUTHORS\./',
        '/CONTRIBUTORS\./',
        '/\.md$/',
        '/\.txt$/',
        '/\.rst$/',
        
        // Arquivos de configuração de desenvolvimento
        '/phpunit\.xml/',
        '/phpunit\.xml\.dist/',
        '/\.phpunit\.result\.cache/',
        '/phpcs\.xml/',
        '/phpcs\.xml\.dist/',
        '/phpstan\.neon/',
        '/phpstan\.dist\.neon/',
        '/psalm\.xml/',
        '/psalm\.xml\.dist/',
        '/\.php_cs/',
        '/\.php-cs-fixer/',
        '/behat\.yml/',
        '/\.travis\.yml/',
        '/\.github\//',
        '/\.gitlab-ci\.yml/',
        '/codeception\.yml/',
        '/circle\.yml/',
        '/appveyor\.yml/',
        
        // Exemplos e demos
        '/examples?\//',
        '/Examples?\//',
        '/demo\//',
        '/Demo\//',
        '/docs?\//',
        '/Docs?\//',
        
        // Arquivos temporários
        '/\.DS_Store$/',
        '/Thumbs\.db$/',
        '/\.swp$/',
        '/\.tmp$/',
        '/~$/',
    ];
    
    foreach ($skipPatterns as $pattern) {
        if (preg_match($pattern, $relativePath)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Formatar bytes em formato legível
 */
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

echo "\nPara usar o PHAR:\n";
if (basename($sourceDir) === 'vendor') {
    echo "  Substitua: require __DIR__ . '/vendor/autoload.php';\n";
    echo "  Por:       require __DIR__ . '/" . basename($pharFile) . "';\n";
} else {
    echo "  Use: require __DIR__ . '/" . basename($pharFile) . "';\n";
}

echo "\nExemplos de uso deste script:\n";
echo "  php create-phar.php                           # Cria vendor.phar da pasta vendor\n";
echo "  php create-phar.php vendor                    # Mesmo que acima\n";
echo "  php create-phar.php vendor minha-vendor.phar  # Nome personalizado\n";
echo "  php create-phar.php src src-lib.phar          # Empacotar pasta src\n";
echo "  php create-phar.php /caminho/absoluto         # Pasta com caminho absoluto\n";