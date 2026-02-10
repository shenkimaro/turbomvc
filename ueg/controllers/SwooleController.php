<?php

namespace library\ueg\controllers;

use library\ueg\Controller;
use library\ueg\Debug;

/**
 * Controller otimizado para Swoole
 * 
 * Esta implementação herda do Controller original mas é otimizada
 * para trabalhar com Swoole HTTP Server, gerenciando responses
 * de forma adequada para o ambiente Swoole.
 * 
 * IMPORTANTE: Esta versão foi reimplementada para NÃO utilizar mergeTemplate(),
 * focando apenas na execução de métodos PHP e retorno de respostas JSON
 * através do RestfulFactory::createSwoole().
 * 
 * Características:
 * - listener() executa apenas métodos PHP, sem carregamento de templates
 * - loadAction() simplificado para Swoole, sem verificação de templates
 * - loadModule() otimizado, sem chamadas para indexTemplate()
 * - indexTemplate() retorna resposta JSON em vez de carregar templates
 * - Otimizado para performance em ambiente Swoole
 * 
 * @author Baseado no Controller original
 * @version 1.0
 */
class SwooleController extends \Controller {
    
    /**
     * Response do Swoole
     * @var mixed
     */
    private $swooleResponse;
    
    /**
     * Classe do controller original sendo wrapeada
     * @var string
     */
    private $wrappedControllerClass;
    
    /**
     * Instância do controller original
     * @var \Controller
     */
    private $wrappedController;
    
    /**
     * Construtor que aceita response do Swoole
     */
    public function __construct($controllerClass = null, $swooleResponse = null) {
        $this->swooleResponse = $swooleResponse;
        
        // IMPORTANTE: Sempre chama parent::__construct() primeiro para 
        // processar $_REQUEST e definir formVars corretamente
        parent::__construct();
        
        // Se passou uma classe específica, cria instância dela
        if ($controllerClass && $controllerClass !== 'Controller' && class_exists($controllerClass)) {
            $this->wrappedControllerClass = $controllerClass;
            $this->wrappedController = new $controllerClass();
            
            // Copia propriedades importantes
            $this->copyPropertiesFrom($this->wrappedController);
        }
    }
    
    /**
     * Copia propriedades do controller original
     */
    private function copyPropertiesFrom($controller) {
        $reflection = new \ReflectionObject($controller);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($controller);
            
            if (property_exists($this, $property->getName())) {
                $thisProperty = new \ReflectionProperty($this, $property->getName());
                $thisProperty->setAccessible(true);
                $thisProperty->setValue($this, $value);
            }
        }
    }
    
    /**
     * Getter para response do Swoole
     */
    public function getSwooleResponse() {
        return $this->swooleResponse;
    }
    
    /**
     * Setter para response do Swoole
     */
    public function setSwooleResponse($response) {
        $this->swooleResponse = $response;
    }
    
    /**
     * Sobrescreve indexTemplate para Swoole (sem uso de mergeTemplate)
     */
    #[\Override]
    protected function indexTemplate($var = '') {
        // SwooleController otimizado: não carrega templates
        // Em ambiente Swoole, o response é gerenciado pelos métodos específicos
        // que usam RestfulFactory::createSwoole() quando necessário
        
        // Se o método foi chamado explicitamente, pode retornar informação básica
        if ($this->swooleResponse) {
            $rest = \RestfulFactory::createSwoole($this->swooleResponse);
            $rest->printREST([
                'message' => 'SwooleController ativo',
                'status' => 'ok',
                'server' => 'Swoole',
                'timestamp' => date('Y-m-d H:i:s')
            ], \library\ueg\Restful::STATUS_OK);
            return;
        }
        
        // Fallback: se não tem response do Swoole, não faz nada
        return;
    }
    
    /**
     * Método index padrão para SwooleController (sem mergeTemplate)
     */
    public function index() {
        // SwooleController sempre retorna JSON usando Swoole response
        $rest = \RestfulFactory::createSwoole($this->swooleResponse);
        $rest->printREST([
            'message' => 'API Swoole ativa',
            'status' => 'online',
            'server' => 'Swoole',
            'timestamp' => date('Y-m-d H:i:s')
        ], \library\ueg\Restful::STATUS_OK);
        return;
    }
    
    /**
     * Sobrescreve start para tratamento de erros específico do Swoole
     */
    #[\Override]
    public function start() {
        try {
            $this->loadModule();
        } catch (\Throwable $t) {
            // Usar RestfulFactory com response do Swoole
            $rest = \RestfulFactory::createSwoole($this->swooleResponse);
			$causado = '';
			if(Util::isLocalIp()){
				$causado = ($t->getPrevious()) ? $t->getPrevious()->getMessage():'';
			}
            $rest->printREST(
                [
                    "mensagem" => $t->getMessage(),
                    "arquivo" => $t->getFile(),
                    "linha" => $t->getLine(),
                    "causado_por" => $causado,
                    "backtrace" => $t->getTraceAsString(),
                ],
                \library\ueg\Restful::STATUS_ERRO_INTERNO_SERVIDOR
            );
        }
    }
    
    /**
     * Sobrescreve loadModule para Swoole (sem uso de indexTemplate)
     */
    public function loadModule() {
        $mod = $this->getFormVars('module');
        $control = $this->getModuleByRequestSafe($mod);

        if (!($control != NULL && is_object($control))) {
            $rest = \RestfulFactory::createSwoole($this->swooleResponse);
            $rest->printREST(['error' => "Modulo não encontrado. Crie um modulo na pasta src. Nao se esqueca de, caso seja uma nova pasta, registre em load.php."], \library\ueg\Restful::STATUS_NAO_ENCONTRADO);
            return;
        }
        $r = new \ReflectionObject($control);
        $fields = $r->getProperties();

        foreach ($fields as $val) {
            $field = $val->getName();
            $p = $r->getProperty($field);
            $dc = $p->getDocComment();
            preg_match("#@Inject.*\n.+@var\\s+([A-Z].+)#", $dc, $a);

            if (isset($a[1]) && class_exists($a[1])) {
                $p->setAccessible(true);
                $p->setValue($control, new $a[1]());
            }
        }

        $this->call($control);
        $control->listener();
    }
    
    /**
     * Versão segura de getModuleByRequest para SwooleController
     */
    private function getModuleByRequestSafe($mod) {  
        $actMod = $this->getActionModules($mod);
        if (!is_array($actMod) && ($actMod) && class_exists($actMod)) {
           return new $actMod();
        }
        return self::getControlByRequest($mod);
    }
    
    /**
     * Sobrescreve loadAction para Swoole (sem uso de mergeTemplate)
     */
    private function loadAction($action) {
        if (method_exists($this, $action)) {
            // Executa o método - SwooleController otimizado não carrega templates
            $this->$action();
            return;
            
        } else {
            // Método não encontrado - retorna erro JSON usando Swoole
            $rest = \RestfulFactory::createSwoole($this->swooleResponse);
            $rest->printREST(['error' => "Ação '$action' não encontrada"], \library\ueg\Restful::STATUS_NAO_ENCONTRADO);
            return;
        }
    }
    
    /**
     * Sobrescreve listener para otimizar para Swoole (sem uso de mergeTemplate)
     */
    public function listener() {
        $opform = $this->getFormVars('op');
        $op = strtolower($opform);
        $actions = $this->getActions();
        $obj = $this;
        
        if (isset($actions[$op])) {
            if (method_exists($obj, $actions[$op])) {
                $func = $actions[$op];
                $this->$func(); 
                
                // Swoole otimizado: não carrega templates, apenas executa métodos PHP
                // O response já foi enviado pelo método executado se necessário
                
            } else {
                // Método não encontrado - retorna erro JSON usando Swoole
                $rest = \RestfulFactory::createSwoole($this->swooleResponse);
                $rest->printREST(['error' => 'Método não encontrado'], \library\ueg\Restful::STATUS_ERRO_INTERNO_SERVIDOR);
            }
        } elseif (is_string($opform) && $opform != '') {
            $this->loadAction($opform);
        } else {
            $this->loadAction('index');
        }
    }
}