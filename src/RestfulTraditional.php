<?php

/**
 * Implementação tradicional do Restful que usa echo e die
 * Mantém compatibilidade total com o código existente
 */
class RestfulTraditional extends Restful implements RestfulOutputInterface {
    
    private $shouldExit = true;
    
    /**
     * Implementação tradicional que usa echo e die
     * @param mixed $data
     * @param int $status
     */
    public function printREST($data, $status = Restful::STATUS_OK) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
        if ($this->allowedOrigins()) {
            $this->addCorsHeaders();
            $this->returnHeadersWhenOptionsMethod();
        }
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));

        if ($this->tipo_saida == 'json') {
            header("Content-Type: application/{$this->tipo_saida};");
            if ($data instanceof DTO) {
                $data = $this->getProperties($data);
            }
            if ($data == null) {
                $data = [];
            }
            echo json_encode($data);
            if ($this->shouldExit) {
                die();
            }
            return;
        }

        if ($this->tipo_saida == 'xml') {
            header("Content-Type: text/{$this->tipo_saida};charset=utf-8");
            echo $this->xml_encode($data);
            if ($this->shouldExit) {
                die();
            }
            return;
        }
    }
    
    /**
     * Define se deve terminar a execução após enviar resposta
     * @param bool $shouldExit
     */
    public function setShouldExit($shouldExit = true) {
        $this->shouldExit = $shouldExit;
    }
    
    // Métodos herdados da classe Restful já implementam as outras funcionalidades
}