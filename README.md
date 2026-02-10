# TurboMVC Framework

Framework PHP leve e flexível para desenvolvimento de aplicações web, com suporte a ambientes tradicionais (Apache/Nginx) e modernos (Swoole).

## 📋 Características

- **MVC Pattern**: Arquitetura Model-View-Controller completa
- **Multi-Engine**: Suporte para Apache/Nginx e Swoole HTTP Server
- **RESTful API**: Criação simplificada de APIs REST
- **Database Abstraction**: Suporte para MySQL e PostgreSQL
- **Autoload Inteligente**: Sistema de autoload com suporte a namespaces e configuração via JSON
- **Data Patterns**: Implementação de DAO, TDG (Table Data Gateway) e DTO
- **Utilities**: Cache, Session, Email, PDF, Image, Crypto e muito mais

## 🚀 Instalação

### Via Include

```php
<?php
require_once '/path/to/turbomvc/autoload.php';
```

## ⚙️ Configuração

### Arquivo turbo.json

O TurboMVC utiliza um arquivo `turbo.json` para configuração de projetos, similar ao `composer.json`:

```json
{
  "name": "meu-projeto/app",
  "description": "Descrição do projeto",
  "extra": {
    "project-path": "."
  }
}
```

O autoload procura automaticamente o `turbo.json` na hierarquia de diretórios e registra o namespace baseado no nome da pasta do projeto.

**Estrutura de projeto recomendada:**

```
meu-projeto/
├── turbo.json
├── index.php
├── config/
├── controllers/
├── models/
├── views/
└── persistencia/
```

### Configuração de Banco de Dados

Crie um arquivo de configuração em `.library/config_db.php`:

```php
<?php
define('_ENGINE', 'pgsql'); // ou 'mysql'
define('_HOSTNAME', 'localhost');
define('_DATABASE', 'nome_do_banco');
define('_USERNAME', 'usuario');
define('_PASSWORD', 'senha');
define('_PORT', 5432); // ou 3306 para MySQL
```

## 📚 Componentes Principais

### Controller

```php
<?php

namespace meuapp\controllers;

use Controller;

class UsuarioController extends Controller {
    
    public function listar() {
        $usuarios = $this->usuarioService->listar();
        $this->view->assign('usuarios', $usuarios);
        $this->view->show('usuarios/lista.html');
    }
    
    public function criar() {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioService->criar($dados);
        $this->response->json($usuario, 201);
    }
}
```

### RESTful API

```php
<?php

use Restful;

class ApiController {
    
    public function usuarios() {
        $restful = Restful::create();
        
        try {
            $usuarios = $this->obterUsuarios();
            $restful->printREST($usuarios, Restful::STATUS_OK);
        } catch (Exception $e) {
            $restful->printREST(
                ['erro' => $e->getMessage()], 
                Restful::STATUS_ERRO_INTERNO_SERVIDOR
            );
        }
    }
}
```

### TDG (Table Data Gateway)

```php
<?php

namespace meuapp\persistencia;

use TDG;

class UsuarioTDG extends TDG {
    
    protected $tableName = 'usuarios';
    
    public function buscarPorEmail($email) {
        $this->setTable($this->tableName);
        $this->setWhere("email = :email");
        $this->setParam([':email' => $email]);
        return $this->selectOne();
    }
    
    public function inserir($dados) {
        $this->setTable($this->tableName);
        return $this->insert($dados);
    }
}
```

### DTO (Data Transfer Object)

```php
<?php

namespace meuapp\dto;

use DTO;

class UsuarioDTO extends DTO {
    
    public $id;
    public $nome;
    public $email;
    public $dt_cadastro;
    
    protected $required = ['nome', 'email'];
}
```

## 🎯 Padrões de Uso

### Modo Tradicional (Apache/Nginx)

```php
<?php
// index.php
require_once '../turbomvc/autoload.php';

$controller = new UsuarioController();
$controller->start();
```

### Modo Swoole (Assíncrono)

```php
<?php
require_once '../turbomvc/autoload.php';

use Swoole\Http\Server;
use Restful;

Restful::setDefaultType('swoole');

$server = new Server("0.0.0.0", 9501);

$server->on("request", function ($request, $response) {
    Restful::setSwooleResponse($response);
    
    $controller = UsuarioController::create('swoole', $response);
    $controller->handleRequest($request);
});

$server->start();
```

## 🛠️ Utilitários Disponíveis

- **Cache**: Sistema de cache em arquivo ou memória
- **Session**: Gerenciamento de sessões
- **Email/Mail**: Envio de emails (PHPMailer integrado)
- **PDF**: Geração de PDFs (FPDF/TFPDF)
- **Image**: Manipulação de imagens
- **Crypt**: Criptografia e hash
- **Log**: Sistema de logs
- **Validador**: Validação de dados
- **Conversor**: Conversão de formatos e encoding
- **Debug**: Ferramentas de debug
- **RestClient**: Cliente HTTP para consumir APIs

## 📖 Documentação Adicional

Consulte a pasta `help/` para documentação detalhada sobre:

- RESTful API patterns
- Controller Factory examples
- Integração com Swoole
- Exemplos avançados

## 🧪 Testes

```bash
cd tests/system01
php index.php
```

## 📁 Estrutura do Framework

```
turbomvc/
├── autoload.php           # Autoloader principal
├── AutoLoader.php5        # Classe de autoload
├── src/                   # Classes do framework
│   ├── Controller.php     # Controller base
│   ├── TDG.php           # Table Data Gateway
│   ├── DAO.php           # Data Access Object
│   ├── DTO.php           # Data Transfer Object
│   ├── View.php          # View engine
│   ├── Restful.php       # RESTful API
│   ├── Db.php            # Database abstraction
│   └── ...
├── ueg/                   # Extensões UEG
├── libs/                  # Bibliotecas externas
├── help/                  # Documentação
└── tests/                 # Testes de exemplo
```

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

## 👥 Autores

- **Ibanez C. Almeida** - Desenvolvimento principal

## 📄 Licença
Este projeto está licenciado sob a [Licença MIT](LICENSE).


