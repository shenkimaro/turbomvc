<?php

/**
 * Implementação tradicional do Get para Apache/tradicional
 * Usa $_GET como no sistema original
 */
class GetTraditional implements GetInterface {
    
    protected static $_REQ = [];
    
    public function init(): void {
        // Para GET tradicionalmente não há inicialização especial
        // Os dados já estão disponíveis em $_GET
    }
    
    public function decryptRequest(string $password): void {
        $request = $_GET;
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
        $_GET = [];
        $_GET = $request;
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
        if (isset($_GET[$key])) {
            unset($_GET[$key]);
        }
    }
    
    protected function getKeyValue($key) {
        return isset($_GET[$key]) ? $_GET[$key] : null;
    }
    
    public function ehGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    public function temCamposRequeridos(array $campos): bool {
        self::$_REQ = $_GET;
        foreach ($campos as $campo) {
            if (!array_key_exists($campo, self::$_REQ)) {
                throw new Exception("O campo '{$campo}' é requerido!");
            }
        }
        return true;
    }
    
    public function getAll(): array {
        return $_GET ?? [];
    }
}