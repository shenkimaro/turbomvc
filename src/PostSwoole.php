<?php

/**
 * Implementação do Post para Swoole
 * Processa apenas os parâmetros POST do objeto $request do Swoole
 */
class PostSwoole implements PostInterface {
    
    protected static $_REQ = [];
    private $swooleRequest;
    private $postData = [];
    
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
        
        $this->processPostParameters();
    }
    
    /**
     * Processa apenas parâmetros POST
     */
    private function processPostParameters(): void {
        // Processar parâmetros POST
        if (isset($this->swooleRequest->post) && is_array($this->swooleRequest->post)) {
            foreach ($this->swooleRequest->post as $key => $value) {
                $this->postData[$key] = $value;
            }
        }
        
        // Processar dados JSON do body (se aplicável)
        if (isset($this->swooleRequest->rawContent)) {
            $contentType = $this->swooleRequest->header['content-type'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $jsonData = json_decode($this->swooleRequest->rawContent(), true);
                if (is_array($jsonData)) {
                    foreach ($jsonData as $key => $value) {
                        $this->postData[$key] = $value;
                    }
                }
            }
        }
        
        // Atualizar $_POST para compatibilidade
        $_POST = $this->postData;
    }
    
    public function decryptRequest(string $password): void {
        $request = $this->postData;
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
        $this->postData = $request;
        
        // Atualizar $_POST também para compatibilidade
        $_POST = $this->postData;
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
        if (isset($this->postData[$key])) {
            unset($this->postData[$key]);
        }
        if (isset($_POST[$key])) {
            unset($_POST[$key]);
        }
    }
    
    protected function getKeyValue($key) {
        return isset($this->postData[$key]) ? $this->postData[$key] : null;
    }
    
    public function ehPost(): bool {
        return ($this->swooleRequest->server['request_method'] ?? '') === 'POST';
    }
    
    public function temCamposRequeridos(array $campos): bool {
        self::$_REQ = $this->postData;
        foreach ($campos as $campo) {
            if (!array_key_exists($campo, self::$_REQ)) {
                throw new Exception("O campo '{$campo}' é requerido!");
            }
        }
        return true;
    }
    
    public function getAll(): array {
        return $this->postData;
    }
}