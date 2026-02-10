<?php
namespace library\ueg;

require_once 'HeaderInterface.php';

/**
 * Implementação do Header para ambiente Swoole
 * Utiliza o objeto request do Swoole para obter os cabeçalhos
 */
class HeaderSwoole implements HeaderInterface {
    
    private const Authorization = 'Authorization';
    
    /** @var mixed|null Objeto request do Swoole */
    private $request = null;
    
    /** @var mixed|null Objeto response do Swoole */
    private $response = null;
    
    /**
     * Define o objeto request do Swoole
     * @param mixed $request Objeto request do Swoole
     */
    public function setRequest($request): void {
        $this->request = $request;
    }
    
    /**
     * Define o objeto response do Swoole
     * @param mixed $response Objeto response do Swoole
     */
    public function setResponse($response): void {
        $this->response = $response;
    }
    
    /**
     * Obtém o token Bearer do cabeçalho Authorization
     * @return string|null Token Bearer ou null se não encontrado
     */
    public function getAuthorizationBearer(): ?string {
        $authHeader = $this->get(self::Authorization);        
        if (!$authHeader) {
            return null;
        }
        
        $return = explode(' ', trim($authHeader));
        if (count($return) < 2) {
            return null;
        }
        return $return[1];
    }
    
    /**
     * Obtém o valor completo do cabeçalho Authorization
     * @return string|null Valor do Authorization ou null se não encontrado
     */
    public function getAuthorization(): ?string {
        return $this->get(self::Authorization);
    }
    
    /**
     * Obtém um cabeçalho específico por chave usando o request do Swoole
     * @param string $key Nome do cabeçalho
     * @return string|null Valor do cabeçalho ou null se não encontrado
     */
    public function get(string $key): ?string {
        $this->setCorsHeaders();
        
        if (!$this->request || !isset($this->request->header)) {
            return null;
        }
        
        // Swoole usa lowercase para as chaves dos headers
        $lowerKey = strtolower($key);
        
        // Tenta encontrar o header de várias formas
        if (isset($this->request->header[$lowerKey])) {
            return $this->request->header[$lowerKey];
        }
        
        // Busca case-insensitive
        foreach ($this->request->header as $headerKey => $headerValue) {
            if (strcasecmp($headerKey, $key) === 0) {
                return $headerValue;
            }
        }
        
        return null;
    }
    
    /**
     * Define os cabeçalhos CORS necessários usando o response do Swoole
     * @return void
     */
    public function setCorsHeaders(): void {
        if ($this->response) {
            $this->response->header('Access-Control-Allow-Credentials', 'true');
            $this->response->header('Access-Control-Allow-Methods', '*');
            $this->response->header('Access-Control-Allow-Headers', 'authorization');
        }
    }
}