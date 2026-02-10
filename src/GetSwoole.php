<?php

/**
 * Implementação do Get para Swoole
 * Processa apenas os parâmetros GET do objeto $request do Swoole
 */
class GetSwoole implements GetInterface {
    
    protected static $_REQ = [];
    private $swooleRequest;
    private $getData = [];
    
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
        
        $this->processGetParameters();
    }
    
    /**
     * Processa apenas parâmetros GET
     */
    private function processGetParameters(): void {
        // Processar parâmetros GET
        if (isset($this->swooleRequest->get) && is_array($this->swooleRequest->get)) {
            foreach ($this->swooleRequest->get as $key => $value) {
                $this->getData[$key] = $value;
            }
        }
        
        // Atualizar $_GET para compatibilidade
        $_GET = $this->getData;
    }
    
    public function decryptRequest(string $password): void {
        $request = $this->getData;
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
        $this->getData = $request;
        
        // Atualizar $_GET também para compatibilidade
        $_GET = $this->getData;
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
        if (isset($this->getData[$key])) {
            unset($this->getData[$key]);
        }
        if (isset($_GET[$key])) {
            unset($_GET[$key]);
        }
    }
    
    protected function getKeyValue($key) {
        return isset($this->getData[$key]) ? $this->getData[$key] : null;
    }
    
    public function ehGet(): bool {
        return ($this->swooleRequest->server['request_method'] ?? '') === 'GET';
    }
    
    public function temCamposRequeridos(array $campos): bool {
        self::$_REQ = $this->getData;
        foreach ($campos as $campo) {
            if (!array_key_exists($campo, self::$_REQ)) {
                throw new Exception("O campo '{$campo}' é requerido!");
            }
        }
        return true;
    }
    
    public function getAll(): array {
        return $this->getData;
    }
}