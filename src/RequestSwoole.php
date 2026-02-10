<?php

require_once 'RequestInterface.php';

/**
 * Implementação do Request para Swoole
 * Processa o objeto $request do Swoole para extrair módulo, ação e parâmetros
 */
class RequestSwoole implements RequestInterface {
    
    protected static $_REQ = [];
    private $swooleRequest;
    private $requestData = [];
    
    /**
     * Define o objeto request do Swoole
     * @param mixed $request Objeto request do Swoole
     */
    public function setRequest($request): void {
        $this->swooleRequest = $request;
    }
    
    public function init(): void {
        if (!$this->swooleRequest) {
            throw new Exception('Objeto request do Swoole não foi definido');
        }
        
        $this->processUrl();
        $this->processParameters();
    }
    
    /**
     * Processa a URL para extrair módulo e ação
     */
    private function processUrl(): void {
        $path = $this->swooleRequest->server['request_uri'] ?? '/';
        
        // Remove query string se existir
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        // Remove barras do início e fim
        $path = trim($path, '/');
        
        // Divide o path em partes
        $parts = explode('/', $path);
        
        // Define módulo e ação baseado na URL
        $this->requestData['module'] = $parts[0] ?? '';
        $this->requestData['op'] = $parts[1] ?? 'index';
        
        // Para compatibilidade com o sistema antigo
        $this->requestData['modulo'] = $this->requestData['module'];
        $this->requestData['acao'] = $this->requestData['op'];
        
        // Também definir no $_REQUEST para compatibilidade
        $_REQUEST['module'] = $this->requestData['module'];
        $_REQUEST['op'] = $this->requestData['op'];
        $_REQUEST['modulo'] = $this->requestData['modulo'];
        $_REQUEST['acao'] = $this->requestData['acao'];
    }
    
    /**
     * Processa parâmetros GET e POST
     */
    private function processParameters(): void {
        // Processar parâmetros GET
        if (isset($this->swooleRequest->get) && is_array($this->swooleRequest->get)) {
            foreach ($this->swooleRequest->get as $key => $value) {
                $this->requestData[$key] = $value;
                $_REQUEST[$key] = $value;
            }
        }
        
        // Processar parâmetros POST
        if (isset($this->swooleRequest->post) && is_array($this->swooleRequest->post)) {
            foreach ($this->swooleRequest->post as $key => $value) {
                $this->requestData[$key] = $value;
                $_REQUEST[$key] = $value;
            }
        }
        
        // Processar dados JSON do body (se aplicável)
        if (isset($this->swooleRequest->rawContent)) {
            $contentType = $this->swooleRequest->header['content-type'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $jsonData = json_decode($this->swooleRequest->rawContent(), true);
                if (is_array($jsonData)) {
                    foreach ($jsonData as $key => $value) {
                        $this->requestData[$key] = $value;
                        $_REQUEST[$key] = $value;
                    }
                }
            }
        }
    }
    
    public function decryptRequest(string $password): void {
        $request = $this->requestData;
        foreach ($request as $key => $value) {
            if (strpos($key, Crypt::$tag) !== false && strpos($value, Crypt::$tag) !== false) {
                unset($request[$key]);
                $key = str_replace(Crypt::$tag, '', $key);
                $value = str_replace(Crypt::$tag, '', $value);
                $request[Crypt::decryptSimple($password, $key)] = Crypt::decryptSimple($password, $value);
                continue;
            }
            if (!is_array($value) && strpos($value, Crypt::$tag) !== false) {
                $value = str_replace(Crypt::$tag, '', $value);
                $request[$key] = Crypt::decryptSimple($password, $value);
            }
        }
        $this->requestData = $request;
        
        // Atualizar $_REQUEST também para compatibilidade
        $_REQUEST = $this->requestData;
    }
    
    public function getInt(string $key, $defaultValue = null) {
        $value = $this->getKeyValue($key);
        if ($value != null) {
            return (int) $value;
        }
        return $defaultValue;
    }
    
    public function getFloat(string $key, $defaultValue = null) {
        $value = $this->getKeyValue($key);
        if ($value != null) {
            // Verificar se a classe Conversor existe
            if (class_exists('Conversor')) {
                $val = Conversor::moedaDb($value);
                return floatval($val);
            } else {
                return floatval($value);
            }
        }
        return $defaultValue;
    }
    
    public function getString(string $key, $defaultValue = null) {
        $value = $this->getKeyValue($key);
        if ($value === null && $defaultValue !== null) {
            $value = $defaultValue;
        }
        return $value;
    }
    
    public function getArray(string $key): array {
        $value = $this->getKeyValue($key);
        return ($value && is_array($value)) ? $value : array();
    }
    
    public function getBoolean(string $key, $defaultValue = null) {
        $value = $this->getKeyValue($key);
        
        if ($value != null) {
            return ($value === true || $value === "true" || $value === 1 || $value === "1" || $value === "t" || $value === "on") ? 'true' : 'false';
        } else {
            return $defaultValue;
        }
    }
    
    public function getBool(string $key, $defaultValue = null) {
        $value = $this->getKeyValue($key);
        
        if ($value != null) {
            return ($value === true || $value === "true" || $value === 1 || $value === "1" || $value === "t" || $value === "on") ? true : false;
        }
        return $defaultValue;
    }
    
    public function isRequired($key, string $message = ""): void {
        if (($key == null) || ($key == "")) {
            throw new Exception($message);
        }
    }
    
    public function removeKey(string $key): void {
        if (isset($this->requestData[$key])) {
            unset($this->requestData[$key]);
        }
        if (isset($_REQUEST[$key])) {
            unset($_REQUEST[$key]);
        }
    }
    
    protected function getKeyValue($key) {
        return isset($this->requestData[$key]) ? $this->requestData[$key] : null;
    }
    
    public function ehPost(): bool {
        return ($this->swooleRequest->server['request_method'] ?? '') === 'POST';
    }
    
    public function ehGet(): bool {
        return ($this->swooleRequest->server['request_method'] ?? '') === 'GET';
    }
    
    public function temCamposRequeridos(array $campos): bool {
        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $this->requestData)) {
                throw new Exception("O campo '{$campo}' é requerido!");
            }
        }
        return true;
    }
    
    public function getAll(): array {
        return $this->requestData;
    }
}