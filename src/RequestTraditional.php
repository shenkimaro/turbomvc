<?php

require_once 'RequestInterface.php';

/**
 * Implementação tradicional do Request para Apache/tradicional
 * Usa $_SERVER e $_REQUEST como no sistema original
 */
class RequestTraditional implements RequestInterface {
    
    protected static $_REQ = [];
    
    public function init(): void {
        $this->getDocumentUri();
        $this->getQueryString();
    }
    
    private function getDocumentUri(): void {
        $request = $this->getServerPropertie('DOCUMENT_URI');
        $parts = explode('/', substr($request ?? '', 1));
        if (count($parts) < 1) {
            $rest = new Restful();
            $rest->printREST(['error' => 'Url inválida xxx'], Restful::STATUS_BAD_REQUEST);
        }
        $_REQUEST['modulo'] = $parts[1] ?? '';
        $_REQUEST['acao'] = $parts[2] ?? '';
    }
    
    private function getQueryString(): void {
        $request = $this->getServerPropertie('QUERY_STRING');
        if ($request == null) {
            return;
        }
        $listVars = explode('&', $request);
        foreach ($listVars as $var) {
            $variavel = explode('=', $var);
            $_REQUEST[$variavel[0]] = $variavel[1];
        }
    }
    
    private function getServerPropertie($param) {
        $request = isset($_SERVER[$param]) ? $_SERVER[$param] : null;
        return $request;
    }
    
    public function decryptRequest(string $password): void {
        $request = $_REQUEST;
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
        $_REQUEST = [];
        $_REQUEST = $request;
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
        if (isset($_REQUEST[$key])) {
            unset($_REQUEST[$key]);
        }
    }
    
    protected function getKeyValue($key) {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    }
    
    public function ehPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    public function ehGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    public function temCamposRequeridos(array $campos): bool {
        foreach ($campos as $campo) {
            if (!array_key_exists($campo, self::$_REQ)) {
                throw new Exception("O campo '{$campo}' é requerido!");
            }
        }
        return true;
    }
    
    public function getAll(): array {
        return $_REQUEST ?? [];
    }
}