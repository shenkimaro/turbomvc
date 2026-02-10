<?php

/**
 * Interface para definir o contrato de saída do Restful
 * Permite implementações diferentes para tradicional (echo/die) e Swoole (response)
 */
interface RestfulOutputInterface {
    
    /**
     * Método principal para enviar resposta REST
     * @param mixed $data Dados a serem enviados
     * @param int $status Código de status HTTP
     */
    public function printREST($data, $status = 200);
    
    /**
     * Define o tipo de saída (json/xml)
     * @param string $tipoSaida
     */
    public function setTipoSaida($tipoSaida);
    
    /**
     * Escolhe automaticamente o tipo de saída baseado no Content-Type
     */
    public function escolheTipoSaida();
    
    /**
     * Define se deve terminar a execução após enviar resposta
     * @param bool $shouldExit
     */
    public function setShouldExit($shouldExit = true);
    
    /**
     * Retorna os valores requisitados do cliente
     * @return array
     */
    public function getREQUEST();
    
    /**
     * Retorna o método requisitado pelo cliente
     * @return string
     */
    public function getMethod();
    
    /**
     * Retorna headers da requisição
     * @return array
     */
    public function getRequestHeaders();
}