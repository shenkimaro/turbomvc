<?php

/**
 * Implementação do Restful para Swoole que escreve no response object
 * Compatível com execução contínua em memória
 */
class RestfulSwoole extends Restful implements RestfulOutputInterface {
    
    private $response;
    private $shouldExit = false; // Para Swoole, nunca deve fazer exit
    
    /**
     * Define o objeto response do Swoole
     * @param mixed $response Objeto response do Swoole
     */
    public function setResponse($response) {
        $this->response = $response;
    }
    
    /**
     * Implementação para Swoole que escreve no response object
     * @param mixed $data
     * @param int $status
     */
    public function printREST($data, $status = Restful::STATUS_OK) {
        if (!$this->response) {
            throw new Exception('Response object não foi definido. Use setResponse() antes de chamar printREST()');
        }
        
        // Define headers CORS
        $this->response->header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, PATCH, OPTIONS");
        
        if ($this->allowedOrigins()) {
            $this->addCorsHeadersToResponse();
            if ($this->getRequestMethod() == self::REQUEST_OPTIONS) {
                $this->returnHeadersWhenOptionsMethodToResponse();
                return;
            }
        }
        
        // Define status code
        $this->response->status($status);

        if ($this->tipo_saida == 'json') {
            $this->response->header("Content-Type", "application/json");
            $data = $this->normalizeDataForJson($data);
            if ($data == null) {
                $data = [];
            }
            $this->response->write(json_encode($data));
            $this->response->end(); // Finalizar a resposta
            return;
        }

        if ($this->tipo_saida == 'xml') {
            $this->response->header("Content-Type", "text/xml; charset=utf-8");
            $this->response->write($this->xml_encode($data));
            $this->response->end(); // Finalizar a resposta
            return;
        }
    }
    
    /**
     * Adiciona headers CORS ao response do Swoole
     */
    private function addCorsHeadersToResponse() {
        $this->response->header('Access-Control-Allow-Credentials', 'true');
        $this->response->header('Access-Control-Allow-Headers', 'Authorization');
        $this->response->header('Access-Control-Expose-Headers', 'Authorization');
        $this->response->header('Access-Control-Allow-Headers', 'user-agent');
    }
    
    /**
     * Retorna headers quando método é OPTIONS para Swoole
     */
    private function returnHeadersWhenOptionsMethodToResponse() {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            $this->response->header("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
        }

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            $this->response->header("Access-Control-Allow-Headers", $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
        }
        
        $this->response->status(self::STATUS_OK);
        $this->response->write('');
    }
    
    /**
     * Para Swoole, nunca deve fazer exit
     * @param bool $shouldExit (ignorado, sempre false para Swoole)
     */
    public function setShouldExit($shouldExit = true) {
        $this->shouldExit = false; // Sempre false para Swoole
    }
}
