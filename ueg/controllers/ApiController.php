<?php

namespace library\ueg\controller;

use library\ueg\Controller;
use library\ueg\Restful;

/**
 * Controller para APIs que não utiliza templates
 * 
 * Esta implementação herda do Controller original mas sobrescreve
 * os métodos relacionados a templates para não fazer nada quando
 * estiver no modo JSON/API (_TEMPLATE_MANAGER == View::ENGINE_JSONVIEW)
 * 
 * @author Baseado no Controller original
 * @version 1.0
 */
class ApiController extends Controller {
    
    /**
     * Classe do controller original sendo wrapeada
     * @var string
     */
    private $wrappedControllerClass;
    
    /**
     * Instância do controller original
     * @var Controller
     */
    private $wrappedController;
    
    /**
     * Construtor que pode aceitar uma classe de controller específica
     */
    public function __construct($controllerClass = null) {
        // Se passou uma classe específica, cria instância dela
        if ($controllerClass && $controllerClass !== 'Controller' && class_exists($controllerClass)) {
            $this->wrappedControllerClass = $controllerClass;
            $this->wrappedController = new $controllerClass();
            
            // Copia propriedades importantes
            $this->copyPropertiesFrom($this->wrappedController);
        } else {
            // Comportamento padrão
            parent::__construct();
        }
    }
    
    /**
     * Copia propriedades do controller original
     */
    private function copyPropertiesFrom($controller) {
        $reflection = new ReflectionObject($controller);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($controller);
            
            if (property_exists($this, $property->getName())) {
                $thisProperty = new ReflectionProperty($this, $property->getName());
                $thisProperty->setAccessible(true);
                $thisProperty->setValue($this, $value);
            }
        }
    }
    
    /**
     * Sobrescreve indexTemplate para não fazer nada no modo API
     */
    protected function indexTemplate($var = '') {
        // No modo API, não carrega templates
        if (defined('_TEMPLATE_MANAGER') && _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW) {
            return;
        }
        
        // Se não for modo API, chama comportamento original
        parent::indexTemplate($var);
    }
    
    /**
     * Sobrescreve loadAction para pular templates no modo API
     */
    private function loadAction($action) {
        $templateFile = strtolower($action);
        
        if (method_exists($this, $action)) {
            // Executa o método
            $this->$action();
            
            // No modo API, não verifica nem carrega templates
            if (defined('_TEMPLATE_MANAGER') && _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW) {
                return;
            }
            
            // Se não for modo API, verifica e carrega template normalmente
            if (!$this->view->templateExists($templateFile)) {
                throw new Exception('No Template found to this Method: ' . $templateFile);
            }
            $this->view->mergeTemplate($templateFile);
            
        } elseif (defined('_TEMPLATE_MANAGER') && _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW) {
            // No modo API, se método não existe, retorna erro JSON
            $rest = \RestfulFactory::create();
            $rest->printREST(['error' => "Ação '$action' não encontrada"], \library\ueg\Restful::STATUS_NAO_ENCONTRADO);
            return;
            
        } elseif ($this->view->templateExists($templateFile)) {
            // Modo tradicional: carrega template se existe
            $this->view->mergeTemplate($templateFile);
            
        } else {
            // Fallback para template index
            $this->indexTemplate();
        }
    }
    
    /**
     * Sobrescreve listener para otimizar para APIs
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
            } else {
                $rest = \RestfulFactory::create();
                $rest->printREST(['error' => 'Método não encontrado'], \library\ueg\Restful::STATUS_ERRO_INTERNO_SERVIDOR);
            }
        } elseif (is_string($opform) && $opform != '') {
            $this->loadAction($opform);
        } else {
            $this->loadAction(self::getDefaultAction());
        }
    }
    
    /**
     * Sobrescreve loadModule para otimizar para APIs
     */
    public function loadModule() {
        $mod = $this->getFormVars('module');
        $this->reloadRequest();
        $control = $this->getModuleByRequestSafe($mod);

        if ($control != NULL && is_object($control)) {
            $r = new ReflectionObject($control);
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
        
        // No modo API, não processa templates
        if (defined('_TEMPLATE_MANAGER') && _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW) {
            return;
        }
        
        // Comportamento original para modo tradicional
        $op = $this->getFormVars('op');
        if (!isset($GLOBALS['files'])) {
            throw new Exception('Template folder not found');
        }
        $templateFile = $GLOBALS['files']['templates'] . "/$op";
        if (file_exists($templateFile . $GLOBALS['template']['extension'])) {
            $this->indexTemplate($templateFile);
        }
        $this->indexTemplate();
    }
    
    /**
     * Versão segura de getModuleByRequest que não acessa métodos privados
     */
    private function getModuleByRequestSafe($mod) {
        $actMod = $this->getActionModules($mod);
        if (!is_array($actMod) && ($actMod) && class_exists($actMod)) {
            return new $actMod();
        }
        return Controller::getControlByRequest($mod);
    }
    
    /**
     * Getter para acessar ação padrão
     */
    protected static function getDefaultAction() {
        return 'index'; // Valor padrão do Controller original
    }
    
    /**
     * Método index padrão para APIs
     * Retorna informação básica da API quando nenhuma ação específica é solicitada
     */
    public function index() {
        // Se está em modo API, retorna informação JSON
        if (defined('_TEMPLATE_MANAGER') && _TEMPLATE_MANAGER == View::ENGINE_JSONVIEW) {
            $rest = \RestfulFactory::create();
            $rest->printREST([
                'message' => 'API ativa',
                'status' => 'online',
                'timestamp' => date('Y-m-d H:i:s')
            ], \library\ueg\Restful::STATUS_OK);
            return;
        }
        
        // Se não está em modo API, chama comportamento padrão
        parent::indexTemplate();
    }
    
    /**
     * Delega acesso a propriedades para o controller wrapeado se existir
     */
    public function __get($property) {
        if ($this->wrappedController && property_exists($this->wrappedController, $property)) {
            return $this->wrappedController->$property;
        }
        
        return parent::__get($property);
    }
}