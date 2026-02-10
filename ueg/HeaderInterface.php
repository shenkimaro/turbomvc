<?php
namespace library\ueg;

/**
 * Interface para implementações de Header
 * Permite alternar entre implementação tradicional (Apache) e Swoole
 */
interface HeaderInterface {
    
    /**
     * Obtém o token Bearer do cabeçalho Authorization
     * @return string|null Token Bearer ou null se não encontrado
     */
    public function getAuthorizationBearer(): ?string;
    
    /**
     * Obtém o valor completo do cabeçalho Authorization
     * @return string|null Valor do Authorization ou null se não encontrado
     */
    public function getAuthorization(): ?string;
    
    /**
     * Obtém um cabeçalho específico por chave
     * @param string $key Nome do cabeçalho
     * @return string|null Valor do cabeçalho ou null se não encontrado
     */
    public function get(string $key): ?string;
    
    /**
     * Define os cabeçalhos CORS necessários
     * @return void
     */
    public function setCorsHeaders(): void;
}