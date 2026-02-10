<?php

/**
 * @package Framework
 *
 * @subpackage TDG
 *
 * @filesource
 */

/**
 * Transfer Gateway Object
 *
 * @author Ibanez C. Almeida <ibanez.almeida@gmail.com>
 *
 * @version 1.0
 *
 */
class TDG {

    /**
     * Classe banco de dados
     *
     * @var Db
     */
    public $db;

    /**
     * Instancia da classe
     *
     * @var TDG
     */
    public static $instance;

    /**
     * Classe de Log
     *
     * @var Log
     */
    public $log;

    /**
     * Contem a sql gerada
     *
     * @var String
     */
    protected $sql;

    /**
     * Contem alias como chaves e objetos e metodos como valores
     * @var array
     */
    private $objectsSql;

    function __construct($array = array()) {
        try {
            if (!isset($this->db)) {
                $this->db = self::getConnectionDb($array);
                $this->log = $this->getLogInstance($array);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     *
     * @return TDG
     */
    public static function getInstance() {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    private function getLogInstance($array) {
        if (isset($GLOBALS['configLog']['status']) && $GLOBALS['configLog']['status'] == true) {
            if (!isset($array['configDb'])) {
                $array['configDb'] = $GLOBALS['configLog'];
                $array['configDb']['host'] = $GLOBALS['configDb']['host'];
                $array['configDb']['_host'] = $GLOBALS['configDb']['_host'];
            }
            return self::getConnectionDbLog($array);
        }
        return new Log();
    }

    public static function getConnectionDbLog($array = array()) {
        $log = null;
        $serverName = str_replace("www.", "", $_SERVER['HTTP_HOST'] ?? '');
        $serverNameConfig = str_replace("www.", "", $GLOBALS['configDb']['webURL']);
        if (isset($GLOBALS['configDb']['webIp'])) {
            $ipsConfig[] = $GLOBALS['configDb']['webIp'];
        }
        if (isset($GLOBALS['configDb']['webIp2'])) {
            $ipsConfig[] = $GLOBALS['configDb']['webIp2'];
        }
        if (Util::isLocalIp() || Util::isBeta()) {
            if (count($array) > 0) {
                $port = (isset($array['configLog']['port']) && $array['configLog']['port'] != null) ? $array['configLog']['port'] : '';
                if ($port == '') {
                    $port = (isset($array['configDb']['port']) && $array['configDb']['port'] != null) ? $array['configDb']['port'] : '5432';
                }
                $log = Log::getInstance(
                    $array['configDb']['host'],
                    $array['configDb']['db'],
                    $array['configDb']['login'],
                    $array['configDb']['password'],
                    $GLOBALS['configLog']['table'],
                    $port
                );
            } else {
                if (isset($GLOBALS['configLog']['login'])) {
                    $log = Log::getInstance(
                        $GLOBALS['configDb']['host'],
                        $GLOBALS['configLog']['db'],
                        $GLOBALS['configLog']['login'],
                        $GLOBALS['configLog']['password'],
                        $GLOBALS['configLog']['table']
                    );
                } else {
                    $log = Log::getInstance(
                        $GLOBALS['configDb']['host'],
                        $GLOBALS['configLog']['db'],
                        $GLOBALS['configDb']['login'],
                        $GLOBALS['configDb']['password'],
                        $GLOBALS['configLog']['table']
                    );
                }
            }
        } elseif (self::isProductionConnection()) {
            if (count($array) > 0) {
                $log = Log::getInstance(
                    $array['configDb']['_host'],
                    $array['configDb']['_db'],
                    $array['configDb']['_login'],
                    $array['configDb']['_password'],
                    $GLOBALS['configLog']['table']
                );
            } else {
                if (isset($GLOBALS['configLog']['_login'])) {
                    $log = Log::getInstance(
                        $GLOBALS['configDb']['_host'],
                        $GLOBALS['configLog']['_db'],
                        $GLOBALS['configLog']['_login'],
                        $GLOBALS['configLog']['_password'],
                        $GLOBALS['configLog']['table']
                    );
                } else {
                    $log = Log::getInstance(
                        $GLOBALS['configDb']['_host'],
                        $GLOBALS['configLog']['_db'],
                        $GLOBALS['configDb']['_login'],
                        $GLOBALS['configDb']['_password'],
                        $GLOBALS['configLog']['table']
                    );
                }
            }
        }
        if ($log == null) {
            die("O aplicativo não está configurado para rodar no " . $_SERVER['HTTP_HOST'] . ' ou ' . $_SERVER['SERVER_ADDR']);
        }

        return $log;
    }

    public static function getConnectionDb($array = []) {
        $conf = $GLOBALS['configDb'];
        $conf['_sgbd'] = $conf['sgbd'] ?? 'DbPg';
        $paramConnection = (!empty($array)) ? $array : $conf;
        if (Util::isLocalIp() || Util::isBeta()) {
            return self::getDbDev($paramConnection);
        }
        if (self::isProductionConnection()) {
            return self::getDbProduction($paramConnection);
        }
        throw new Exception(
                "O aplicativo não está configurado para rodar no {$_SERVER['HTTP_HOST']} ou {$_SERVER['SERVER_ADDR']}"
            );
    }

    private static function isProductionConnection() {
        if (!(Util::isLocalIp() || Util::isBeta())) { //se for producao
            return true;
        }
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $serverName = str_replace("www.", "", $host);
        $serverNameConfig = str_replace("www.", "", $GLOBALS['configDb']['webURL']);
        if (isset($GLOBALS['configDb']['webIp'])) {
            $ipsConfig[] = $GLOBALS['configDb']['webIp'];
        }
        if (isset($GLOBALS['configDb']['webIp2'])) {
            $ipsConfig[] = $GLOBALS['configDb']['webIp2'];
        }
        if (in_array($_SERVER['SERVER_ADDR'], $ipsConfig) || $serverName == $serverNameConfig) {
            return true;
        }
        return false;
    }

    private static function getDbDev(array $array) {
        return self::getDb($array);
    }

    private static function getDbProduction(array $array) {
        return self::getDb($array, false);
    }

    private static function getDb(array $array, bool $local = true) {
        if (!$local) {
            $array['sgbd'] = isset($array['_sgbd']) ? $array['_sgbd'] : 'DbPg';
            $array['host'] = $array['_host'];
            $array['bd'] = $array['_bd'];
            $array['login'] = $array['_login'];
            $array['password'] = $array['_password'];
            $array['port'] = $array['_port'] ?? '';
        }
        $dbName = isset($array['sgbd']) ? $array['sgbd'] : 'DbPg';
        $container = new Container();
        $name = md5(
            $dbName . $array['host'] . $array['bd'] . $array['login'] . $array['password'] . ($array['port'] ?? '')
        );
        if ($container->exists($name)) {
            return $container->getObj($name)[0];
        }
        $dbReturn = new $dbName($array['host'], $array['bd'], $array['login'], $array['password'], $array['port'] ?? '');
        $container->insert($dbReturn, $name);
        return $dbReturn;
    }

    //************************************************************************************************************************\\

    /**
     * Retorna o objeto de uma pk informada
     *
     * @param DTO $dto
     * @param integer $value id a ser pesquisado
     * @return DTO|null
     */
    public function getByPk(DTO $dto, $value) {
        $container = Container::getInstance();
        $container->remove($dto);
        $objName = get_class($dto);
        if (trim($value) == '')
            throw new Exception("O valor da chave esta vazio para o DTO: " . $objName);
        if ($value == DTO::NULL) {
            return null;
        }
        $value = $this->db->escape($value);
        $sql = "	SELECT *
		FROM __$objName
		WHERE {$dto->getPkName()} = '$value'";

        $sql = $this->convertAsterix($sql, $dto);

        try {
            $this->sql = $sql;
            $ex = $this->execSql($sql, $dto);
            foreach ($ex as $value) {
                $container->insert($value);
            }
            if (isset($ex[0])) {
                return $ex[0];
            }
            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . "\n" . $this->sql);
        }
    }

    /**
     * Retorna o objeto de uma pk informada
     *
     * @param DTO $dto
     * @param integer $value id a ser pesquisado
     * @return DTO
     */
    public function getByPkWithCache(DTO $dto, $value, $lifeTime = CacheLbr::_TIME_DEFAULT, $folder = '') {
        try {
            $cache = new CacheLbr();
            $namedKey = get_class($dto) . '_' . $value;
            $result = $cache->get($namedKey, $lifeTime, $folder);
            if ($result == null) {
                $result = $this->getByPk($dto, $value);
                if ($result != null) {
                    $cache->add($namedKey, $result, $folder);
                }
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . "\n" . $this->sql);
        }
    }

    //************************************************************************************************************************\\

    /**
     * Retorna todos os objetos
     *
     * @param DTO $obj
     * @return array<DTO>
     */
    public function getAll(DTO $obj) {
        $objName = get_class($obj);
        $sql = "	SELECT *
		FROM __$objName";
        $sql = $this->convertAsterix($sql, $obj);
        try {
            $ex = $this->execSql($sql, $obj);
            return $ex;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . "\n");
        }
    }

    /**
     * Retorna a sql formatada para uma string com * passada
     *
     * @param String $sql
     * @param DTO $obj
     * @return string
     */
    private function convertAsterix($sql, DTO $obj) {
        $objName = get_class($obj);
        $r = new ReflectionObject($obj);
        $count = 0;
        $aliasName = "";
        for ($x = 0; $x < strlen($sql); $x++) {
            if (strtoupper($sql[$x]) == 'S') {
                for ($y = $x; $y < strlen($sql); $y++) {
                    if ($sql[$y] == '*') {
                        $methods = $r->getMethods();
                        $select = '';
                        foreach ($methods as $value) {
                            $field = $obj->getMethodTableField($value->getName());
                            //deve ser um metodo com tipo
                            if ($field == "") {
                                continue;
                            }
                            if (strtolower(mb_substr($value->getName(), 0, 3)) == "set") {
                                ++$count;
                                $aliasName = $field . $count;
                                $this->objectsSql[$aliasName][0] = $objName;
                                $this->objectsSql[$aliasName][1] = $value->getName();
                                $select = $select ? $select . ",$field as $aliasName" : "$field as $aliasName";
                            }
                        }
                        if ($select != '') {
                            $sql = str_replace("*", $select, $sql);
                        }
                        break;
                    }
                }
            }
        }
        return $sql;
    }

    //************************************************************************************************************************\\

    /**
     *
     * @param string $sql
     * @param int $lifeTime se não informado o tempo padrao é de 5 minutos
     * @param string $folder Quando o cache eh de uso pessoal informe uma pasta.
     * @return array
     */
    public function genericQueryWithCache($sql, $lifeTime = CacheLbr::_TIME_DEFAULT, $folder = '') {
        $cache = new CacheLbr();
        $namedKey = $sql;
        $result = $cache->get($namedKey, $lifeTime, $folder);
        if ($result == null) {
            $result = $this->genericQuery($sql);
            if (count($result) > 0) {
                $cache->add($namedKey, $result, $folder);
            }
        }
        return $result;
    }

    public function removeCacheFolder($folder) {
        (new CacheLbr())->removeFolder($folder);
    }

    /**
     * Executa uma instrucao sql direta no banco e faz logs quando for
     * sql de alteração(insert, update e delete)
     *
     * @param string $sql
     * @return array
     * @throws Exception
     */
    public function genericQuery($sql) {
        try {
            $ex = $this->db->query($sql);
            $result = $this->db->fetch_all($ex);
            $regexMatchPostQueries = '/(?i)(INSERT|UPDATE|DELETE)(\s|\n|\t)/';
            if (isset($GLOBALS['configLog']['status']) && ($GLOBALS['configLog']['status'] != '0' || $GLOBALS['configLog']['status'] != false)) {
                if (preg_match($regexMatchPostQueries, $sql)) {
                    $sql_property_name = 'sql';
                    if (isset($GLOBALS['configLog']['sql_property_name'])) {
                        $sql_property_name = $GLOBALS['configLog']['sql_property_name'];
                    }

                    $logArray[$sql_property_name] = $this->db->escape($sql);
                    self::getConnectionDbLog()->saveLogDefault($logArray);
                }
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function prepareQuery($sql, $params = array()) {
        try {
            $result = $this->db->preparedQuery($sql, $params);
            $regexMatchPostQueries = '/(?i)(INSERT|UPDATE|DELETE)(\s|\n|\t)/';
            if (isset($GLOBALS['configLog']['status']) && ($GLOBALS['configLog']['status'] != '0' || $GLOBALS['configLog']['status'] != false)) {
                if (preg_match($regexMatchPostQueries, $sql)) {
                    $sql_property_name = 'sql';
                    if (isset($GLOBALS['configLog']['sql_property_name'])) {
                        $sql_property_name = $GLOBALS['configLog']['sql_property_name'];
                    }

                    $logArray[$sql_property_name] = $this->db->escape($sql);
                    self::getConnectionDbLog()->saveLogDefault($logArray);
                }
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function prepareQueryWithCache($sql, $params = array(), $lifeTime = CacheLbr::_TIME_DEFAULT, $folder = '') {
        try {
            $cache = new CacheLbr();
            $namedKey = $sql;
            $result = $cache->get($namedKey, $lifeTime, $folder);
            if ($result == null) {
                $result = $this->db->preparedQuery($sql, $params);
                if (count($result) > 0) {
                    $cache->add($namedKey, $result, $folder);
                }
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function prepareQueryCount($sql, $params = array()) {
        try {
            $sql = rtrim($sql, ';');

            $sqlCount = $this->getCountSqlString($sql);

            $result = $this->prepareQuery($sqlCount, $params);
            $count = $result[0]['count'];

            $sqlLimit = $this->getSqlLimit($sql);

            $result = $this->prepareQuery($sqlLimit, $params);

            $view = $this->getInstanceView();
            $view->setCount($count, count($result));
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function consultaPaginadaComVinculacaoPosicional(
        string $sql,
        array $parametros = [],
        ?int $pagina = null,
        ?int $tamanhoDaPagina = null,
        ?string $ordem = null,
        ?string $campoDeOrdem = null
    ) {
        try {
            $sql = rtrim($sql, ';');
            $sqlCount = $this->getCountSqlString($sql);

            $resultado = $this->prepareQuery($sqlCount, $parametros);
            $count = $resultado[0]['count'];

            if (isset($campoDeOrdem)) {
                $sql .= $this->order([
                    '_by' => $campoDeOrdem,
                    '_order' => $ordem
                ]);
            }

            if (isset($tamanhoDaPagina)) {
                $pagina ?? 1;
                $sql = sprintf(
                    "%s\r\n%s",
                    $sql,
                    sprintf("limit %s offset %s", $tamanhoDaPagina, (($pagina - 1) * $tamanhoDaPagina))
                );
            }

            $resultado = $this->prepareQuery($sql, $parametros);

            return [
                'lista' => $resultado,
                'pagination' => $this->paginacao($count, $tamanhoDaPagina, $pagina)
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param mixed $contador
     * @param int|null $tamanhoDaPagina
     * @param int|null $pagina
     * @return int[]|null[]
     */
    private function paginacao(int $contador, ?int $tamanhoDaPagina, ?int $pagina): array {
        $tamanhoDaPagina ?? $contador;
        $pagina ?? 1;

        $totalDePaginas = (int) ceil($contador / $tamanhoDaPagina);

        return [
            'tamanhoDaPagina' => $tamanhoDaPagina,
            'paginaAtual' => $pagina,
            'paginaAnterior' => $pagina == 1 ? '' : $pagina - 1,
            'proximaPagina' => $pagina == $totalDePaginas ? '' : $pagina + 1,
            'totalDePaginas' => $totalDePaginas,
            'total' => $contador,
        ];
    }

    /**
     * Executa uma instrucao sql direta no banco de logs
     *
     * @param string $sql
     * @return array Dados obtidos
     * @throws Exception
     */
    public function genericLogQuery($sql) {
        try {
            $ex = self::getConnectionDbLog()->getDb()->query($sql);
            $dados = self::getConnectionDbLog()->getDb()->fetch_all($ex);
            if (!$dados)
                return array();
            return $dados;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //************************************************************************************************************************\\

    /**
     * Salva o log com dados do arquivo de config
     *
     * @param array $array array('campo'=>$value)
     */
    public function saveLogDefault($array = array()) {
        self::getConnectionDbLog()->saveLogDefault($array);
    }

    //************************************************************************************************************************\\

    /**
     * Realiza a insercao de um VO no banco de dados
     * @param DTO $dto
     * @param boolean $insertPK
     * @param array $log
     * @return DTO
     * @throws Exception
     */
    public function insert(DTO $dto, $insertPK = false, array $log = array()) {
        $isSerial = true;
        try {
            $this->getCurrSeq($dto);
            $this->sql = $this->buildInsert($dto, $insertPK);
        } catch (Exception $e) {
            $this->sql = $this->buildInsert($dto, $insertPK, true);
            $isSerial = false;
        }
        $resource = $this->db->exec($this->sql);
        if (!$resource) {
            throw new Exception($this->db->getError());
        }

        $pkName = $dto->getPkName();

        $method = $dto->getMethodByProperty($pkName);
        if (!$insertPK) { //se nao for para inserir a PK busca do banco
            if (!$isSerial) {
                $columnsInserted = $this->db->fetch_all($resource);
                $pkValue = $columnsInserted[0][$pkName];
            } else {
                $pkValue = $this->getCurrSeq($dto);
            }
        } else {
            $pkValue = $dto->$method();
        }

        if (isset($GLOBALS['configLog']['status']) && ($GLOBALS['configLog']['status'] != '0' || $GLOBALS['configLog']['status'] != false)) {
            $sql_property_name = 'sql';
            if (isset($GLOBALS['configLog']['sql_property_name'])) {
                $sql_property_name = $GLOBALS['configLog']['sql_property_name'];
            }
            $this->sql .= '-- id:' . $pkValue . ';';
            $log[$sql_property_name] = ($this->sql);
            self::getConnectionDbLog()->saveLogDefault($log);
        }

        $insertedDTO = $this->getByPk($dto, $pkValue);

        return $insertedDTO;
    }

    //************************************************************************************************************************\\

    /**
     * Realiza uma atualizacao de um VO no banco de dados
     * @param DTO $dto
     * @param boolean $pk
     * @param array $logArray
     * @return DTO
     */
    public function update(DTO $dto, $pk = false, $logArray = array()) {
        $this->sql = $this->buildUpdate($dto, $pk);
        $ok = $this->db->exec($this->sql);
        if (!$ok) {
            throw new Exception($this->db->getError());
        }
        $this->logAction($this->sql);
        $pkName = $dto->getMethodByProperty($dto->getPkName());
        $dto = $this->getByPk($dto, $dto->$pkName());
        return $dto;
    }

    private function logAction($sql) {
        if (!$GLOBALS['configLog']['status']) {
            return;
        }
        $sql_property_name = 'sql';
        if (isset($GLOBALS['configLog']['sql_property_name'])) {
            $sql_property_name = $GLOBALS['configLog']['sql_property_name'];
        }

        $logArray[$sql_property_name] = ($sql);
        self::getConnectionDbLog()->saveLogDefault($logArray);
    }

    //************************************************************************************************************************\\

    /**
     * Realiza uma atualizacao de um VO no banco de dados
     * @param DTO $dto
     * @param boolean $pk
     * @param array $logArray
     * @return DTO
     */
    public function merge(DTO $dto, $pk = false, $logArray = array()) {
        $this->sql = $this->buildUpdate($dto, $pk);
        $ok = $this->db->exec($this->sql);
        if (!$ok) {
            throw new Exception($this->db->getError());
        } else {
            if ($GLOBALS['configLog']['status']) {
                $sql_property_name = 'sql';
                if (isset($GLOBALS['configLog']['sql_property_name']))
                    $sql_property_name = $GLOBALS['configLog']['sql_property_name'];

                $logArray[$sql_property_name] = ($this->sql);
                self::getConnectionDbLog()->saveLogDefault($logArray);
            }
        }
        return $dto;
    }

    //************************************************************************************************************************\\

    /**
     * Realiza a exclusao de um VO no banco de dados
     * @param DTO $dto
     * @param array $logArray
     * @return boolean
     */
    public function delete(DTO $dto, $logArray = array()) {
        $this->sql = $this->buildDelete($dto);
        $ok = $this->db->exec($this->sql);
        if (!$ok) {
            throw new Exception($this->db->getError());
        } else {
            if ($GLOBALS['configLog']['status']) {
                $sql_property_name = 'sql';
                if (isset($GLOBALS['configLog']['sql_property_name']))
                    $sql_property_name = $GLOBALS['configLog']['sql_property_name'];

                $logArray[$sql_property_name] = ($this->sql);
                self::getConnectionDbLog()->saveLogDefault($logArray);
            }
        }
        return true;
    }

    //************************************************************************************************************************\\

    /**
     * Pega o id atual do DTO informado no banco
     *
     * @param DTO $dto
     * @return integer
     * @throws Exception
     */
    protected function getCurrSeq(DTO $dto) {
        $sql = "select pg_get_serial_sequence('{$dto->getTableSchemaName()}', '{$dto->getPkName()}')";
        $ok = $this->db->exec($sql);
        if (!$ok) {
            throw new Exception($this->db->getError());
        }
        $result = $this->db->fetch_all($ok);
        if (!$result[0]['pg_get_serial_sequence'])
            throw new Exception("Sequencia desconhecida em database");
        $result = $this->db->currVal($result[0]['pg_get_serial_sequence']);
        return $result;
    }

    //************************************************************************************************************************\\

    /**
     * Constroi a instrucao sql INSERT
     *
     * @param DTO $dto
     * @param boolean $pk
     * @param bool $returnPk se for true adiciona a clausula 'returning $pkname' no insert
     * @return string
     * @throws Exception
     */
    public function buildInsert(DTO $dto, $pk = false, $returnPk = false) {
        $sql = "";
        $tableName = $dto->getTableSchemaName();
        $pkName = $dto->getPkName();
        if ($tableName == '.') {
            echo "Nao existe nome de tabela definida para " . get_class($dto) . "<br>" . $tableName;
            return;
        }
        $sqlFields = "";
        $sqlValues = "";
        $r = new ReflectionObject($dto);
        $methods = $r->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->getName();
            $field = $dto->getMethodTableField($methodName);
            //deve ser um metodo com tipo
            if ($field == "")
                continue;
            if ($methodName[0] == 's')
                continue;
            if (!$pk) {
                if (trim($pkName) == trim(($field)))
                    continue;
            }
            $propertyName = $dto->getPropertyByMethodName($methodName);
            $value = $dto->getPropertyValue($propertyName);
            if ($value === null || trim($value) === '') {
                continue;
            }
            $value = $this->db->escape($value);
            $sqlFields .= $sqlFields == "" ? $field : ", " . $field;
            if (strtolower(trim($value)) == DTO::NULL) {
                $sqlValues .= $sqlValues == "" ? "null" : ",null";
            } elseif (strtolower(trim($value)) == DTO::EMPTY_STRING) {
                $sqlValues .= $sqlValues == "" ? "''" : ",''";
            } else {
                $sqlValues .= $sqlValues == "" ? "'$value'" : ", '$value'";
            }
        }
        if ($sqlFields != '') {
            if ($returnPk) {
                $sql = "INSERT INTO $tableName ($sqlFields) VALUES ($sqlValues) RETURNING {$pkName};";
            } else {
                $sql = "INSERT INTO $tableName ($sqlFields) VALUES ($sqlValues);";
            }
        }
        return $sql;
    }

    //************************************************************************************************************************\\

    /**
     * Constroi a instrucao sql DELETE
     *
     * @param DTO $dto
     * @return string
     */
    public function buildDelete(DTO $dto) {
        $tableName = $dto->getTableSchemaName();
        $key = $dto->getPkName();

        if ($key == '') {
            throw new Exception("Nao existe pk definida para " . get_class($dto));
            return;
        }
        $keyMethod = $dto->getMethodGetByProperty($key);
        if ($dto->$keyMethod() == '') {
            throw new Exception("A pk esta vazia " . get_class($dto) . "->$key");
        }
        $sql = "DELETE FROM $tableName WHERE $key='{$dto->$keyMethod()}';";
        return $sql;
    }

    //************************************************************************************************************************\\

    /**
     * Constroi a instrucao sql UPDATE
     * @param DTO $dto
     * @param boolean $pk
     * @return string
     */
    public function buildUpdate(DTO $dto, $pk = false) {
        $sql = "";
        $tableName = $dto->getTableSchemaName();
        $pkName = $dto->getPkName();
        if ($tableName == '.') {
            throw new Exception("Nao existe nome de tabela definida para " . get_class($dto) . "<br>" . $tableName);
        }
        if (trim($pkName) == "") {
            throw new Exception("Nao existe chave primaria em " . get_class($dto) . "<br>");
        }
        $sets = "";
        $r = new ReflectionObject($dto);
        $methods = $r->getMethods();
        $pkValue = '';
        foreach ($methods as $value) {
            $methodName = $value->getName();
            $field = $dto->getMethodTableField($methodName);
            //deve ser um metodo com tipo
            if ($field == "")
                continue;
            if ($methodName[0] == 's')
                continue;

            if (trim($pkName) == trim(($field))) {
                $pkValue = $dto->$methodName();
                if (trim($pkValue) == "")
                    throw new Exception("Valor da chave vazio para update: " . get_class($dto));
                if (!$pk) {
                    continue;
                }
            }
            $propertyName = $dto->getPropertyByMethodName($methodName);
            $value = $dto->getPropertyValue($propertyName);
            if ($value === null || trim($value) === '') {
                continue;
            }
            $value = $this->db->escape($value);
            if ($sets != "") {
                $sets .= ', ';
            }
            if (strtolower(trim($value)) == DTO::EMPTY_STRING) {
                $value = '';
            }
            if (strtolower(trim($value)) == DTO::NULL) {
                $sets .= "$field = $value";
            } else {
                $sets .= "$field ='$value'";
            }
        }
        if ($pkValue != '' && $sets == '') {
            throw new Exception('Nada foi alterado.');
        }
        $sql = "UPDATE $tableName SET $sets WHERE $pkName='$pkValue';";
        return $sql;
    }

    //************************************************************************************************************************\\

    /**
     * Constroi a instrucao sql UPDATE
     * @param DTO $dto
     * @param boolean $pk
     * @return string
     */
    public function buildUpdateMerge(DTO $dto, $pk = false) {
        $sql = "";
        $tableName = $dto->getTableSchemaName();
        $pkName = $dto->getPkName();
        if ($tableName == '.') {
            throw new Exception("Nao existe nome de tabela definida para " . get_class($dto) . "<br>" . $tableName);
        }
        if (trim($pkName) == "") {
            throw new Exception("Nao existe chave primaria em " . get_class($dto) . "<br>");
        }
        $sets = "";
        $r = new ReflectionObject($dto);
        $methods = $r->getMethods();
        foreach ($methods as $value) {
            $methodName = $value->getName();
            $field = $dto->getMethodTableField($methodName);
            //deve ser um metodo com tipo
            if ($field == "")
                continue;
            if ($methodName[0] == 's')
                continue;

            if (trim($pkName) == trim(($field))) {
                $pkValue = $dto->$methodName();
                if (trim($pkValue) == "")
                    throw new Exception("Valor da chave vazio para update: " . get_class($dto));
                if (!$pk) {
                    continue;
                }
            }
            $value = $dto->$methodName();
            $value = $this->db->escape($value);
            if (strtolower(trim($value)) == 'null') {
                $sets .= $sets == "" ? "$field =$value" : ", $field =$value";
            } elseif (trim($value) != '') {
                $sets .= $sets == "" ? "$field ='$value'" : ", $field ='$value'";
            }
        }
        $sql = "UPDATE $tableName SET $sets WHERE $pkName='$pkValue';";
        return $sql;
    }

    /**
     * Obtém o próximo id da sequência informada
     * @param $sequencia String nome da sequência
     * @return int valor da sequência
     */
    public function nextVal($sequencia) {
        $sql = "SELECT nextval('$sequencia') as nextval;";
        $val = $this->genericQuery($sql);
        $val = $val[0];
        return $val['nextval'];
    }

    //************************************************************************************************************************\\

    /**
     * Formata a string para pesquisa em banco de dados
     *
     * @param string $var
     * @return string
     */
    protected function formatIlikeToAscii($var = '') {
        $value = str_replace(" ", "%", $var);
        return " to_ascii('%" . $value . "%','LATIN1') ";
    }

    //************************************************************************************************************************\\

    /**
     * Formata a string para pesquisa em banco de dados
     *
     * @param string $var
     * @return string
     */
    public function formatIlike($var = '') {
        $value = str_replace(" ", "%", $var);
        $where = " '%" . $value . "%' ";
        return $where;
    }

    //************************************************************************************************************************\\

    /**
     * Formata a string para pesquisa em banco de dados
     *
     * @param string $var
     * @return string
     */
    private function search_text($field, $var, $inicio, $fim) {
        if (!isset($var) || $var == '' || !is_string($var)) {
            return '';
        }
        $var = $this->db->escape($var);
        $value = str_replace(" ", "%", $var);
        $where = "(unaccent($field) ilike unaccent('$inicio" . $value . "$fim') ";
        $value = htmlentities($value, ENT_QUOTES, 'UTF-8');
        $where .= " OR $field ilike '$inicio" . $value . "$fim' )";
        return $where;
    }

    /**
     * Formata a string para pesquisa em banco de dados com carateres coringas apenas no inicio do texto
     * @param string $field
     * @param string $var
     * @return string
     */
    public function formatSearchTextInicio($field, $var) {
        return $this->search_text($field, $var, '%', '');
    }

    /**
     * Formata a string para pesquisa em banco de dados com carateres coringas apenas no final do texto
     * @param string $field
     * @param string $var
     * @return string
     */
    public function formatSearchTextFinal($field, $var) {
        return $this->search_text($field, $var, '', '%');
    }

    /**
     * Formata a string para pesquisa em banco de dados com carateres coringas em todo o texto
     * @param string $field
     * @param string $var
     * @return string
     */
    public function formatSearchText($field, $var) {
        return $this->search_text($field, $var, '%', '%');
    }

    //************************************************************************************************************************\\

    /**
     * Executa a sql e seta o objeto
     *
     * @param string $sql
     * @param DTO $object
     * @return array<DTO>
     */
    public function execSql($sql, DTO $object) {
        $res = null;
        try {
            $res = $this->queryWithId($sql);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if ($res) {
            $obj = $this->setObjFromSqlWithId($res, $object);
            return $obj;
        } else {
            return array();
        }
    }

    /**
     * @param string $array Array que contem os indices _order e _by necessários para definir a ordenação
     * @param string $default_order Ordenação default para quando o array não possuir o indíce _order.
     *    Ex: formatOrder('', 'nome ASC') --> vai retornar ' ORDER BY nome ASC'
     *
     * @return string Ordenação formatada
     */
    public function formatOrder($array = array(), $default_order = '') {
        if (count($array) == 0) {
            $array = $_REQUEST;
        }
        $order = "";
        if (isset($array['_order']) && trim($array['_order']) != '')
            $order = " ORDER BY " . trim($this->db->escape($array['_order']));

        if (isset($array['_by']) && (strtoupper(trim($array['_by'])) == 'ASC' || strtoupper(
                trim($array['_by'])
            ) == 'DESC') && isset($array['_order']) && trim($array['_order']) != '')
            $order .= " " . trim($this->db->escape($array['_by']));

        if (!$order && $default_order)
            $order = " ORDER BY " . $default_order;

        return $order;
    }

    public function order($array = array(), $default_order = '') {
        if (count($array) == 0) {
            $array = $_REQUEST;
        }
        $order = "";
        if (isset($array['_by']) && trim($array['_by']) != '')
            $order = " ORDER BY " . trim($this->db->escape($array['_by']));

        if (isset($array['_order']) && (strtoupper(trim($array['_order'])) == 'ASC' || strtoupper(
                trim($array['_order'])
            ) == 'DESC') && isset($array['_by']) && trim($array['_by']) != '')
            $order .= " " . trim($this->db->escape($array['_order']));

        if (!$order && $default_order)
            $order = " ORDER BY " . $default_order;

        return $order;
    }

    public function getSql() {
        return $this->sql;
    }

    //************************************************************************************************************************\\
    private function setObjFromSqlWithId($result, DTO $dto) {
        $objArray = array();
        if ($result) {
            foreach ($result as $linha) {
                $dto = null;
                foreach ($linha as $key => $value) {
                    if (!isset($this->objectsSql[$key])) {
                        continue;
                    }
                    $object = $this->objectsSql[$key];
                    if ($dto == null) {
                        $objName = $object[0];
                        if (!class_exists($objName)) {
                            continue;
                        }
                        $dto = new $objName(array());
                    }
                    $obj = $object[1];
                    $dto->$obj($value);
                }
                if ($dto != null) {
                    $objArray[] = clone $dto;
                }
            }
        } else {
            return array();
        }
        return $objArray;
    }

    //************************************************************************************************************************\\
    private function queryWithId($sql = '') {
        try {
            $this->sql = $this->replaceObjValuesWithTablesNames($sql);
            return $this->genericQuery($this->sql);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    //************************************************************************************************************************\\
    protected function replaceObjValuesWithTablesNames($sql) {
        $sqlArray = preg_split('/\b[Ff][Rr][Oo][Mm]\b/', $sql);
        $sqlDb1 = $sqlArray[0];
        $sqlDb2 = "FROM" . $sqlArray[1];
        $objs = [];
        preg_match_all("/__[a-zA-Z0-9]*(\b|\n|\r)/", $sqlDb2, $objs);
        foreach ($objs as $val) {
            if (trim($val[0]) != "") {
                $dtoName = str_replace("__", "", $val[0]);
                $dto = new $dtoName(array());
                $sqlDb2 = preg_replace("/" . $val[0] . "(\b|\n|\r)/", $dto->getTableSchemaName(), $sqlDb2);
            }
        }
        $sqlDb = $sqlDb1 . $sqlDb2;
        return $sqlDb;
    }

    /**
     * Retorna a quantidade de linhas para uma consulta
     * passada
     *
     * @param String $sql
     * @return integer
     */
    private function getCountSql($sql) {
        $res = $this->genericQuery($this->getCountSqlString($sql));
        return $res[0]['count'];
    }

    /**
     * Retorna a string de sql envolvida com count
     * @param string $sql
     * @return string
     */
    private function getCountSqlString($sql) {
        $sqlCount = "SELECT count(*) as count
					FROM (
					$sql
					) as t";
        return $sqlCount;
    }

    //************************************************************************************************************************\\

    /**
     * Executa uma instrucao sql com LIMIT E OFFSET direta no banco
     * e executa um count do total da tabela
     *
     * @param string $sql
     * @return array
     */
    public function genericQueryCount($sql) {
        //remove o caractere ';' do fim da sql para que seja possivel manipulá-la
        $sql = rtrim($sql, ';');

        $count = $this->getCountSql($sql);

        $sqlLimit = $this->getSqlLimit($sql);

        $result = $this->genericQuery($sqlLimit);

        $view = $this->getInstanceView();
        $view->setCount($count, count($result));
        return $result;
    }

    private function getSqlLimit($sql) {
        $sql = rtrim($sql, ';');

        $whereLimit = "";
        $view = $this->getInstanceView();
        $page = $view->getPage();
        $limit = $view->getRowsLimit();

        if ($limit != "") {
            $offSet = (($page - 1) * $limit);
            $whereLimit .= " limit $limit offset $offSet";
        }
        if ($whereLimit != "") {
            $sql .= $whereLimit;
        }
        return $sql;
    }

    /**
     * Metodo que retorna a instancia da View que sera utilizada pela paginacao
     * @return View
     */
    protected function getInstanceView() {
        return View::getInstance();
    }

    //************************************************************************************************************************\\

    /**
     * Formata a string para pesquisa em banco de dados
     * @param string $var
     * @return string
     * @author Edson
     */
    public function whereOrAnd($where) {
        if ($where == '') {
            $where = " WHERE ";
        } else {
            $where .= " AND ";
        }
        return $where;
    }

    public function escapeString($var) {
        return $this->db->escape($var);
    }

    public function beginTransaction() {
        $this->db->beginTransaction();
    }

    public function rollback() {
        $this->db->rollback();
    }

    public function commit() {
        $this->db->commit();
    }

    /**
     * Converte parâmetros nomeados em parâmetros posicionais e retorna o SQL final e os valores ordenados.
     * Também remove comentários do tipo "--" do SQL.
     *
     * @param string $query A consulta com named parameters (ex.: ":id", ":name").
     * @param array $params Array de valores para os parâmetros (ex.: ['id' => 1, 'name' => 'John']).
     *
     * @return array [string, array] contendo a consulta convertida e os valores posicionais.
     */
    public function bindNamedParams(string $query, array $params): array {
        // Remove comentários no estilo "--" até o final de cada linha
        $queryWithoutComments = preg_replace('/--.*?(\n|$)/', '', $query);

        $values = [];
        $index = 1;

        // Substitui cada placeholder :param pelo positional $1, $2...
        $positionQueryParams = preg_replace_callback('/:(\w+)/', function ($matches) use ($params, &$values, &$index) {
            $name = $matches[1];
            if (!array_key_exists($name, $params)) {
                throw new InvalidArgumentException("Parâmetro $name não fornecido.");
            }
            // Adiciona o valor correspondente ao array
            $values[] = $params[$name];
            return '$' . $index++;
        }, $queryWithoutComments);

        return [$positionQueryParams, $values];
    }
}
