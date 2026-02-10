<?php

/**
 * Interface para implementações de Get
 * Define os métodos que devem ser implementados pelas classes de Get
 */
interface GetInterface {
    
    /**
     * Inicializa o processamento dos parâmetros GET
     * @return void
     */
    public function init(): void;
    
    /**
     * Descriptografa os parâmetros GET
     * @param string $password Senha para descriptografia
     * @return void
     */
    public function decryptRequest(string $password): void;
    
    /**
     * Retorna um valor inteiro dos parâmetros GET
     * @param string $key Chave do parâmetro
     * @param int|null $defaultValue Valor padrão se não encontrado
     * @return int|null
     */
    public function getInt(string $key, $defaultValue = null);
    
    /**
     * Retorna um valor float dos parâmetros GET
     * @param string $key Chave do parâmetro
     * @param float|null $defaultValue Valor padrão se não encontrado
     * @return float|null
     */
    public function getFloat(string $key, $defaultValue = null);
    
    /**
     * Retorna um valor string dos parâmetros GET
     * @param string $key Chave do parâmetro
     * @param string|null $defaultValue Valor padrão se não encontrado
     * @return string|null
     */
    public function getString(string $key, $defaultValue = null);
    
    /**
     * Retorna um array dos parâmetros GET
     * @param string $key Chave do parâmetro
     * @return array
     */
    public function getArray(string $key): array;
    
    /**
     * Retorna um valor booleano como string dos parâmetros GET
     * @param string $key Chave do parâmetro
     * @param string|null $defaultValue Valor padrão se não encontrado
     * @return string|null
     */
    public function getBoolean(string $key, $defaultValue = null);
    
    /**
     * Retorna um valor booleano real dos parâmetros GET
     * @param string $key Chave do parâmetro
     * @param bool|null $defaultValue Valor padrão se não encontrado
     * @return bool|null
     */
    public function getBool(string $key, $defaultValue = null);
    
    /**
     * Verifica se um parâmetro GET é obrigatório
     * @param string $key Chave do parâmetro
     * @param string $message Mensagem personalizada
     * @return void
     * @throws Exception se o parâmetro não estiver presente
     */
    public function isRequired(string $key, string $message = ""): void;
    
    /**
     * Remove uma chave dos parâmetros GET
     * @param string $key Chave a ser removida
     * @return void
     */
    public function removeKey(string $key): void;
    
    /**
     * Verifica se a requisição é do tipo GET
     * @return bool
     */
    public function ehGet(): bool;
    
    /**
     * Verifica se campos obrigatórios estão presentes
     * @param array $campos Array com nomes dos campos obrigatórios
     * @return bool
     */
    public function temCamposRequeridos(array $campos): bool;
    
    /**
     * Retorna todos os parâmetros GET
     * @return array Array com todos os parâmetros GET
     */
    public function getAll(): array;
}