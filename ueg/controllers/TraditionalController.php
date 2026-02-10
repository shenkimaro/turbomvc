<?php
namespace library\ueg\controllers;

use library\ueg\Controller;
/**
 * Controller tradicional que mantém comportamento original
 * 
 * Esta classe é um wrapper que garante compatibilidade total
 * com o Controller original, preservando todas as funcionalidades
 * de template existentes.
 * 
 * @author Baseado no Controller original
 * @version 1.0
 */
class TraditionalController extends Controller {
    
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
     * Mantém comportamento original do indexTemplate
     */
    protected function indexTemplate($var = '') {
        parent::indexTemplate($var);
    }
    
    /**
     * Mantém comportamento original do listener
     */
    public function listener() {
        parent::listener();
    }
    
    /**
     * Mantém comportamento original do loadModule
     */
    public function loadModule() {
        parent::loadModule();
    }
    
    /**
     * Mantém comportamento original do start
     */
    public function start() {
        parent::start();
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
    
    /**
     * Delega definição de propriedades para o controller wrapeado se existir
     */
    public function __set($property, $value) {
        if ($this->wrappedController && property_exists($this->wrappedController, $property)) {
            $this->wrappedController->$property = $value;
            return;
        }
        
        $this->$property = $value;
    }
}