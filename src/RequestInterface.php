<?php

/**
 * Interface para implementações de Request
 * Define os métodos que devem ser implementados pelas classes de Request
 */
interface RequestInterface {
    
    /**
     * Inicializa o processamento da requisição
     * @return void
     */
    public function init(): void;
    
    /**
     * Retorna um valor inteiro da requisição
     * @param string $key Chave do parâmetro
     * @param int|null $defaultValue Valor padrão se não encontrado
     * @return int|null
     */
    public function getInt(string $key, $defaultValue = null);
    
    /**
     * Retorna um valor float da requisição
     * @param string $key Chave do parâmetro
     * @param float|null $defaultValue Valor padrão se não encontrado
     * @return float|null
     */
    public function getFloat(string $key, $defaultValue = null);
    
    /**
     * Retorna um valor string da requisição
     * @param string $key Chave do parâmetro
     * @param string|null $defaultValue Valor padrão se não encontrado
     * @return string|null
     */
    public function getString(string $key, $defaultValue = null);
    
    /**
     * Retorna um array da requisição
     * @param string $key Chave do parâmetro
     * @return array
     */
    public function getArray(string $key): array;
    
    /**
     * Retorna um valor booleano como string da requisição
     * @param string $key Chave do parâmetro
     * @param string|null $defaultValue Valor padrão se não encontrado
     * @return string|null
     */
    public function getBoolean(string $key, $defaultValue = null);
    
    /**
     * Retorna um valor booleano da requisição
     * @param string $key Chave do parâmetro
     * @param bool|null $defaultValue Valor padrão se não encontrado
     * @return bool|null
     */
    public function getBool(string $key, $defaultValue = null);
    
    /**
     * Verifica se uma chave é obrigatória
     * @param string|null $key Valor da chave
     * @param string $message Mensagem de erro
     * @throws Exception
     */
    public function isRequired($key, string $message = ""): void;
    
    /**
     * Remove uma chave da requisição
     * @param string $key Chave a ser removida
     */
    public function removeKey(string $key): void;
    
    /**
     * Verifica se a requisição é POST
     * @return bool
     */
    public function ehPost(): bool;
    
    /**
     * Verifica se a requisição é GET
     * @return bool
     */
    public function ehGet(): bool;
    
    /**
     * Verifica se os campos obrigatórios estão presentes
     * @param array $campos Array com nomes dos campos
     * @return bool
     * @throws Exception
     */
    public function temCamposRequeridos(array $campos): bool;
    
    /**
     * Descriptografa a requisição
     * @param string $password Senha para descriptografia
     */
    public function decryptRequest(string $password): void;
    
    /**
     * Retorna todos os parâmetros da requisição
     * @return array Array com todos os parâmetros da requisição
     */
    public function getAll(): array;
}