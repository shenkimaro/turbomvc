<?php
namespace library\ueg;

require_once 'HeaderInterface.php';

/**
 * Implementação tradicional do Header para ambiente Apache
 * Utiliza apache_request_headers() para obter os cabeçalhos
 */
class HeaderTraditional implements HeaderInterface {
    
    private const Authorization = 'Authorization';
    
    /**
     * Obtém o token Bearer do cabeçalho Authorization
     * @return string|null Token Bearer ou null se não encontrado
     */
    public function getAuthorizationBearer(): ?string {
        $return = explode(' ', \Conversor::insideTrim($this->get(self::Authorization)) ?? '');
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
     * Obtém um cabeçalho específico por chave usando apache_request_headers()
     * @param string $key Nome do cabeçalho
     * @return string|null Valor do cabeçalho ou null se não encontrado
     */
    public function get(string $key): ?string {
        $this->setCorsHeaders();
        
        if (!function_exists('apache_request_headers')) {
            return null;
        }
        
        $headers = apache_request_headers();
        return isset($headers[$key]) ? $headers[$key] : null;
    }
    
    /**
     * Define os cabeçalhos CORS necessários
     * @return void
     */
    public function setCorsHeaders(): void {
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Methods: *");
        header('Access-Control-Allow-Headers: authorization');
    }
}