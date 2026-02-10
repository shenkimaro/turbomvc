<?php

class Post {

    protected static $_REQ = [];
    private static $instance = null;

    /**
     * Obtém a instância apropriada do Post baseada no ambiente
     * @return PostInterface
     */
    private static function getInstance(): PostInterface {
        if (self::$instance === null) {
            self::$instance = PostFactory::createAuto();
        }
        return self::$instance;
    }

    /**
     * Define explicitamente uma instância (útil para Swoole)
     * @param PostInterface $instance
     */
    public static function setInstance(PostInterface $instance): void {
        self::$instance = $instance;
    }

    public static function init() {
        self::getInstance()->init();
    }

    public static function decryptRequest($password) {
        self::getInstance()->decryptRequest($password);
    }

    /**
     * Retorna numerico ou null caso não exista
     * @param $key
     * @param $defaultValue Int Se o valor da chave for nulo é retornado o valor default
     * @return int
     */
    public static function getInt($key, $defaultValue = NULL) {
        return self::getInstance()->getInt($key, $defaultValue);
    }

    public static function getFloat($key, $defaultValue = null) {
        return self::getInstance()->getFloat($key, $defaultValue);
    }

    /**
     * Retorna string(texto) ou null caso não exista.
     * @param $key
     * @param $defaultValue
     * @return string
     */
    public static function getString($key, $defaultValue = NULL) {
        return self::getInstance()->getString($key, $defaultValue);
    }

    /**
     * Retorna o parâmetro com a chave informada, desde que ele seja um array.
     * Caso o valor da chave esteja vazio ou não seja um array, um array vazio será retornado.
     * @param $key
     * @return array
     */
    public static function getArray($key) {
        return self::getInstance()->getArray($key);
    }

    /**
     * Verifica se a chave na request corresponde a um valor booleano
     * e retorna String
     * @param $key
     * @param $defaultValue String - Se o valor da chave for nulo é retornado o valor default
     * @return null|string 'true' ou 'false'
     */
    public static function getBoolean($key, $defaultValue = NULL) {
        return self::getInstance()->getBoolean($key, $defaultValue);
    }

    /**
     * Retorna true|false de acordo com a chave na request
     * @param $key
     * @param $defaultValue String - Se o valor da chave for nulo é retornado o valor default
     * @return null|bool 'true' ou 'false'
     */
    public static function getBool($key, $defaultValue = NULL) {
        return self::getInstance()->getBool($key, $defaultValue);
    }

    /**
     * Verifica se a chave na request existe e / ou esta preenchida
     * @param $key que deve ser passada com Post::getString($key)
     * @param $message String - Uma mensagem qualquer, caso queira.	 
     */
    public static function isRequired($key, $message = "") {
        self::getInstance()->isRequired($key, $message);
    }

    /**
     * Remove uma key do POST
     * @param string $key
     */
    public static function removeKey($key) {
        self::getInstance()->removeKey($key);
    }

    public static function ehPost(): bool {
        return self::getInstance()->ehPost();
    }

    public static function temCamposRequeridos(array $campos): bool {
        return self::getInstance()->temCamposRequeridos($campos);
    }

    /**
     * Retorna todos os parâmetros POST da requisição
     * @return array Array com todos os parâmetros POST da requisição
     */
    public static function getAll(): array {
        return self::getInstance()->getAll();
    }
}
