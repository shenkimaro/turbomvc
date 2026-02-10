<?php

/**
 * @package Framework
 *
 * @subpackage DAO
 *
 * @filesource
 */

/**
 * Data Access Object
 *
 * @author Ibanez C. Almeida <ibanez.almeida@gmail.com>
 *
 * @version 1.0
 * @deprecated since version number Use the TDG Class
 *
 */
class DAO {

	/**
	 * Classe banco de dados
	 *
	 * @var Db
	 */
	public $db;

	/**
	 * Instancia da classe
	 *
	 * @var DAO
	 */
	public static $instance;

	/**
	 * Objeto Container
	 *
	 * @var Container
	 */
	public $container;

	/**
	 * Retorno do banco de dados
	 *
	 * @var resource
	 */
	private $resource;

	/**
	 * Array de objetos retornados da instrucao sql(texto)
	 *
	 * @var array
	 */
	private $objsFromSql;

	/**
	 * Campos mapeados da tabela de uma instrucao(sql) com objetos
	 *
	 * @var array
	 */
	private $fieldFromSql;

	/**
	 * Classe de Log
	 *
	 * @var Log
	 */
	protected $log;

	/**
	 * Contem a sql gerada
	 *
	 * @var String
	 */
	private $sql;

	function __construct($array = array()) {
		if (count($array) > 0) {
			$this->db = self::getConnectionDb($array);
		}
		if (!isset($this->db)) {
			$this->db = self::getConnectionDb($array);
			if ($GLOBALS['configLog']['status']) {
				$port = isset($GLOBALS['configLog']['port']) && $GLOBALS['configLog']['port'] != ''?$GLOBALS['configLog']['port']:'5432';
				if (\library\ueg\Util::isLocalIp() || \library\ueg\Util::isBeta()) {
					$this->log = Log::getInstance($GLOBALS['configDb']['host'], $GLOBALS['configLog']['db'], $GLOBALS['configLog']['login'], $GLOBALS['configLog']['password'], $GLOBALS['configLog']['table'],$port);
				} else if (!(\library\ueg\Util::isLocalIp() || \library\ueg\Util::isBeta())) {
					$this->log = Log::getInstance($GLOBALS['configDb']['_host'], $GLOBALS['configLog']['_db'], $GLOBALS['configLog']['_login'], $GLOBALS['configLog']['_password'], $GLOBALS['configLog']['table']);
				} else {
					throw new Exception("Nenhuma conexao configurada para o host: " . $_SERVER['SERVER_ADDR']);
				}
			}
		}
		if (!isset($this->container))
			$this->container = Container::getInstance();
	}

	//************************************************************************************************************************\\
	/**
	 * Retorna uma unica instancia da classe
	 *
	 * @return DAO
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			try {
				self::$instance = new DAO();
			} catch (ConectionDBException $e) {
				$e->printException();
			} catch (Exception $e) {
				echo $e->getMessage();
				die();
			}
		}
		return self::$instance;
	}

	//************************************************************************************************************************\\
	public static function getConnectionDb($array = array()) {
		try {
			return (new TDG())->getConnectionDb($array);
		} catch (Exception $exc) {
			die($exc->getMessage());
		}
	}

	//************************************************************************************************************************\\
	/**
	 * Retorna o numero de tuplas do resouce sql
	 *
	 * @return integer
	 */
	public function numRows() {
		return $this->db->numRows($this->resource);
	}

	//************************************************************************************************************************\\
	/**
	 * Retorna o objeto de uma pk informada
	 *
	 * @param VO $obj
	 * @param integer $value id a ser pesquisado
	 * @return VO
	 * @deprecated use the TDG Class
	 */
	public function getByPk(VO $obj, $value) {
		$objName = get_class($obj);
		if (trim($value) == '')
			throw new Exception("O valor da chave esta vazio para o VO: " . $objName);
		$sql = "	SELECT *
				FROM __$objName
				WHERE {$obj->getPkName()}='$value'";
		$objFrom = $this->getObjsFromSqlFROM($sql);
		$sql = $this->convertAsterix($sql, $obj);

		try {
			$ex = $this->runSql($sql, $obj);
			$this->sql = $this->sqlObjToDb($sql);
			if(count($ex) == 0){
				return null;
			}
			return $ex[0];
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Retorna um conjunto de objetos de um campo informado
	 *
	 * @param VO $obj
	 * @param string $field nome do campo get
	 * @param mixed $value id a ser pesquisado
	 * @return Array Contem VOs
	 * @deprecated use the TDG Class
	 */
	public function getByField(VO $obj, $field = "getNomeCampo", $value = "") {
		$objName = get_class($obj);
		if ((trim($field) == '') || (trim($field) == 'getNomeCampo'))
			throw new Exception("O nome do campo esta vazio para o VO: " . $objName);
		if (trim($value) == '')
			throw new Exception("O valor do campo esta vazio para o VO: " . $objName);
		$sql = "	SELECT *
				FROM __$objName
				WHERE __$objName" . "->" . "$field = '$value'";
		$objFrom = $this->getObjsFromSqlFROM($sql);

		$sql = $this->convertAsterix($sql, $obj);

		try {
			$this->sql = $sql;
			$ex = $this->execSql($sql, $obj);
			return $ex;
		} catch (Exception $e) {
			throw new Exception($e->getMessage() . "\n" . $this->sqlObjToDb($sql));
		}
	}

	//************************************************************************************************************************\\
	/**
	 * Retorna todos objetos com a orderm informada
	 *
	 * @param VO $obj
	 * @param string $order Campo para ordenamento
	 * @return VO
	 * @deprecated use the TDG Class
	 */
	public function getAll(?VO $obj = null, $order = "") {
		$objName = get_class($obj);

		$sql = "	SELECT *
				FROM __$objName
				$order";
		$objFrom = $this->getObjsFromSqlFROM($sql);

		$sql = $this->convertAsterix($sql, $obj);

		try {
			$this->sql = $sql;
			$ex = $this->execSql($sql, $obj);
			return $ex;
		} catch (Exception $e) {
			throw new Exception($e->getMessage() . "\n" . $this->sqlObjToDb($sql));
		}
	}

	/**
	 * Retorna a sql formatada para uma string * passada
	 *
	 * @param String $sql
	 * @param VO $obj
	 * @return VO
	 */
	private function convertAsterix($sql, $obj) {
		$objName = get_class($obj);
		for ($x = 0; $x < strlen($sql); $x++) {
			if (strtoupper($sql[$x]) == 'S') {
				for ($y = $x; $y < strlen($sql); $y++) {
					if ($sql[$y] == '*') {
						$methods = $obj->getMethodsMap();
						$select = '';
						foreach ($methods as $key => $value) {
							$vo = new $objName();
							$prop = $vo->getPropertyByMethod($key);
							if (($prop == "email") || (!class_exists($prop)))
								$select = $select ? $select . ",__$objName" . "->" . "$key" : "__$objName" . "->" . "$key";
						}

						$sql = str_replace("*", $select, $sql);
						break;
					}
				}
			}
		}
//		die($sql);
		return $sql;
	}

	//************************************************************************************************************************\\
	/**
	 * Executa uma instrucao sql direta no banco
	 *
	 * @param string $sql
	 * @return array
	 * @deprecated use the TDG Class
	 */
	public function genericQuery($sql) {
		try {
			$ex = $this->db->query($sql);
			$this->resource = $ex;
			return $this->db->fetch_all($ex);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	//************************************************************************************************************************\\
	/**
	 * Salva o log com dados do arquivo de config
	 *
	 * @param array $array array('campo'=>$value)
	 */
	public function saveLogDefault($array = array()) {
		$this->log->saveLogDefault($array);
	}

	//************************************************************************************************************************\\
	/**
	 * Realiza a insercao de um VO no banco de dados
	 *
	 * @param Table $obj Objeto do tipo VO
	 *
	 * @param boolean $pk Diz se a chave do obj será ou
	 *  nao inserida, default NAO(false)
	 *
	 * @param  array $logArray array('campo'=>$value) q deve ser
	 * gravado no log
	 *
	 * @return VO ou dispara uma excecao
	 * @deprecated use the TDG Class
	 *
	 */
	public function insert(VO $obj, $pk = false, $logArray = array()) {
		$this->sql = $this->buildInsert($obj, $pk);
		$ok = $this->db->exec($this->sql);

		if (!$ok) {
			throw new Exception($this->db->getError());
		} else {
			$this->insertLog($this->sql, $logArray);
		}
		$res = $this->db->fetchAll($ok);

		$obj = $this->setVOFromArray($res, $obj);

		return $obj[0];

//		return $ok;
	}

	//************************************************************************************************************************\\
	/**
	 * Realiza uma atualizacao de um VO no banco de dados
	 *
	 * @param Table $obj Objeto do tipo VO
	 *
	 * @param boolean $pk Diz se a chave do obj será ou
	 *  nao alterada, default NAO(false)
	 *
	 * @param  array $logArray array('campo'=>$value) q deve ser
	 * gravado no log
	 *
	 * @return bolean ou dispara uma excecao
	 * @deprecated use the TDG Class
	 *
	 */
	public function update(VO $obj, $pk = false, $logArray = array()) {
		$this->sql = $this->buildUpdate($obj, $pk);
		if ($this->sql == '')
			return $obj;
		$ok = $this->db->exec($this->sql);

		if (!$ok) {
			throw new Exception($this->db->getError());
		} else {
			$this->insertLog($this->sql, $logArray);
		}

		$res = $this->db->fetchAll($ok);
//		$obj = $this->setVOFromArray($res,$obj);
		$keyName = $obj->getPkName();
		$objx = $this->getByPk($obj, $obj->$keyName);

		return @$objx;
	}

	public function insertLog($sql, $logArray) {
		if ($GLOBALS['configLog']['status']) {
			$sql_property_name = 'sql';

			if (isset($GLOBALS['configLogFieldSqlName']) && $GLOBALS['configLogFieldSqlName'] != ''){
				$sql_property_name = $GLOBALS['configLogFieldSqlName'];
            }
			elseif (isset($GLOBALS['configLog']['sql_property_name'])){
				$sql_property_name = $GLOBALS['configLog']['sql_property_name'];
            }

			$logArray[$sql_property_name] = ($sql);
			$this->log->saveLogDefault($logArray);
		}
	}

	//************************************************************************************************************************\\
	/**
	 * Realiza a exclusao de um VO no banco de dados
	 *
	 * @param Table $obj Objeto do tipo VO
	 *
	 * @param  array $logArray array('campo'=>$value) q deve ser
	 * gravado no log
	 *
	 * @return bolean ou dispara uma excecao
	 * @deprecated use the TDG Class
	 *
	 */
	public function delete($obj, $logArray = array()) {
		$this->sql = $this->buildDelete($obj);
		$ok = $this->db->exec($this->sql);
		if (!$ok) {
			throw new Exception($this->db->getError());
		} else {
			$this->insertLog($this->sql, $logArray);
		}
		return true;
	}

	//************************************************************************************************************************\\
	/**
	 * Pega o proximo id do VO informado no banco
	 *
	 * @param VO $obj
	 * @return integer
	 */
	public function getNextSeq(VO $obj) {
		$sql = "	select pg_get_serial_sequence('{$obj->getTableName()}', '{$obj->getPkName()}')";
		$ok = $this->db->exec($sql);
		if (!$ok) {
			throw new Exception($this->db->getError());
		}
		$result = $this->db->fetch_all($ok);
		$result = $this->db->nextVal($result[0]['pg_get_serial_sequence']);
		return $result;
	}

	//************************************************************************************************************************\\
	/**
	 * Pega o id atual do VO informado no banco
	 *
	 * @param VO $obj
	 * @return integer
	 */
	public function getCurrSeq(VO $obj) {
		$sql = "	select pg_get_serial_sequence('{$obj->getTableName()}', '{$obj->getPkName()}')";
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
	 * @param VO $obj
	 * @param boolean $pk
	 * @return string
	 */
	public function buildInsert(VO $obj, $pk = false) {
		$fields = $obj->getProperties();
		if (!$pk)
			unset($fields[$obj->getPkName()]);
		$sqlFields = "";
		$sqlValues = "";
		$sql = "";
		if ($obj->getTableName() == '') {
			echo "Não existe nome de tabela definida para " . get_class($obj) . "<br>";
			return;
		}

		foreach ($fields as $key => $value) {
			if ($obj->$value != '' || is_object($obj->$value)) {
				$val = $this->db->escape(htmlspecialchars_decode(!is_object($obj->$value) ? $obj->$value : $obj->$value->{$obj->$value->getPkName()}, ENT_QUOTES));
				if ($sqlFields == '') {
					if (is_object($obj->$value)) {
						$fk = $obj->getFkName($key);
						//NOME PADRAO PARA FKs DAS TABELA 'REF_'+NOMETABELA
						$key = strtolower($key[strlen($key) - 1]) != 's' ? $key : mb_substr($key, 0, strlen($key) - 1);
						$sqlFields.=$fk ? "$fk" : "ref_" . $key;
					} else
						$sqlFields.="$key";
					if (trim(strtolower($val)) == 'null')
						$sqlValues.="$val";
					else
						$sqlValues.="'$val'";
				}
				else {
					if (is_object($obj->$value)) {
						$fk = $obj->getFkName($key);
						//NOME PADRAO PARA FKs DAS TABELA 'REF_'+NOMETABELA
						$key = strtolower($key[strlen($key) - 1]) != 's' ? $key : mb_substr($key, 0, strlen($key) - 1);
						$sqlFields.=$fk ? ", $fk" : ", ref_" . $key;
					} else
						$sqlFields.=", $key";
					if (trim(strtolower($val)) == 'null')
						$sqlValues.=", $val";
					else
						$sqlValues.=", '$val'";
				}
			}
		}

		if ($sqlFields != '') {
//			$sql="INSERT INTO {$obj->getTableName()} ($sqlFields) VALUES ($sqlValues);";
			$sql = "INSERT INTO {$obj->getTableName()} ($sqlFields) VALUES ($sqlValues) RETURNING {$obj->getPkName()},$sqlFields;";
		}
		if (trim($sql) == '') {
			throw new Exception("Todos os campos do VO " . get_class($obj) . " estão vazios para o INSERT");
		}
		return $sql;
	}

	//************************************************************************************************************************\\
	/**
	 * Constroi a instrucao sql DELETE
	 *
	 * @param VO $obj
	 * @return string
	 */
	public function buildDelete(VO $obj) {
		$key = '';
		$sql = '';
		$key = $obj->getPkName();
		if ($key == '') {
			echo "Não existe pk definida para " . get_class($obj) . "<br>";
			return;
		}
		if ($obj->$key == '') {
			echo "A pk está vazia " . get_class($obj) . "->$key<br>";
			return;
		}
		$sql = "DELETE FROM {$obj->getTableName()} WHERE $key={$obj->$key};";
		return $sql;
	}

	//************************************************************************************************************************\\
	/**
	 * Constroi a instrucao sql UPDATE
	 *
	 * @param VO $obj
	 * @param boolean $pk
	 * @return string
	 */
	public function buildUpdate(VO $obj, $pk = false) {
		$sql = '';
		$sets = '';
		$fields = $obj->getProperties();
		$keyName = $obj->getPkName();
		if (!$pk)
			unset($fields[$obj->getPkName()]);
		if ($keyName == '') {
			throw new Exception("Não existe pk definida para " . get_class($obj));
		}
		if ($obj->$keyName == '') {
			throw new Exception("A pk não pode estar vazia " . get_class($obj) . "->$keyName");
		}
		foreach ($fields as $key => $value) {
			if ($obj->$value != '' || is_object($obj->$value)) {
				$val = $this->db->escape(htmlspecialchars_decode(!is_object($obj->$value) ? $obj->$value : $obj->$value->{$obj->$value->getPkName()}, ENT_QUOTES));
				if ($sets == '') {
					if (is_object($obj->$value)) {
						$fk = $obj->getFkName($key);
						//NOME PADRAO PARA FKs DAS TABELA 'REF_'+NOMETABELA
						$key = $fk ? "$fk" : "ref_$key";
					} else
						$key = "$value";
					if (trim(strtolower($val)) == 'null')
						$sets.="$key=$val";
					else
						$sets.="$key='$val'";
				}
				else {
					if (is_object($obj->$value)) {
						$fk = $obj->getFkName($value);
						//NOME PADRAO PARA FKs DAS TABELA 'REF_'+NOMETABELA
						$key = $fk ? "$fk" : "ref_$key";
					} else
						$key = "$key";
					if (trim(strtolower($val)) == 'null')
						$sets.=", $key=$val";
					else
						$sets.=", $key='$val'";
				}
			}
		}
		if ($sets != '') {
			$sql = "UPDATE {$obj->getTableName()} SET $sets WHERE $keyName='{$obj->$keyName}';";
		}
		return $sql;
	}

	//************************************************************************************************************************\\
	/**
	 * Formata a string para pesquisa em banco de dados
	 *
	 * @param string $var
	 * @return string
	 */
	protected function formatIlike($var = '') {
		$has_magic_quotes = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();
		$var = ($has_magic_quotes) ? pg_escape_string(stripslashes($var)) : pg_escape_string($var);
		$value = str_replace(" ", "%", $var);
		@$where = " to_ascii('%" . $value . "%','LATIN1') ";
		return $where;
	}

	//************************************************************************************************************************\\
	/**
	 * Alias para genericQuery
	 *
	 * @param string $sql
	 * @return array
	 * @see DAO::genericQuery()
	 */
	public function query($sql = '') {
		try {
			return $this->genericQuery($this->sqlObjToDb($sql));
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	//************************************************************************************************************************\\
	/**
	 * Formata a sql com VOs para sql Tabelas
	 *
	 * @param string $sqlObj sql com VOs
	 * @return string sql formatada para o banco de dados
	 */
	public function sqlObjToDb($sqlObj) {
		$this->objsFromSql = $this->getObjsFromSql($sqlObj);
		$this->fieldFromSql = $this->getSqlFieldsFromObjGet($this->objsFromSql);
		return $this->replaceObjValues($this->objsFromSql, $this->fieldFromSql, $sqlObj);
	}

	//************************************************************************************************************************\\
	/**
	 * Pega os objetos da SQL
	 *
	 * @param string $sql
	 * @return array
	 */
	private function getObjsFromSql($sql) {
		$aspas = false;
		$obj = array();
		$objTemp = '';
		$contFields = 0;
		$contSelectFields = 2000; //Campos select comecam com 2000
		$selectFlag = true; //A instrucao comeca com Select
		for ($x = 0; $x < strlen($sql); $x++) {
			$fim = false;
			//Ignora aspas simples ''
			if (($sql[$x] == "'")) {
				if ($aspas)
					$aspas = false;
				else
					$aspas = true;
			}
			if (!$aspas) {
				if ((strtoupper($sql[$x]) == 'F')) {
					if (($x + 3) <= strlen($sql))
						if ((strtoupper($sql[$x + 1]) == 'R') && (strtoupper($sql[$x + 2]) == 'O') && (strtoupper($sql[$x + 3]) == 'M'))
							$selectFlag = false;
				}
				if (($sql[$x] == '_') && ($sql[$x + 1] == '_')) {
					$x = $x + 2;
					for ($y = 0; !$fim; $y++) {
						$objTemp.=$sql[$x];
						$x++;
						//Condicoes de fim de String
						if ($x == strlen($sql))
							break;
						if ($this->endWord($sql[$x]))
							$fim = true;
						if ($x > 500000000)
							$fim = true; //break forcado(fase de desenvolvimento)
					}
//					$obj[$contObjs]=$objTemp;
					if ($selectFlag) {
						$obj[$contSelectFields] = $objTemp;
						$contSelectFields+=1;
					} else {
						$obj[$contFields] = $objTemp;
						$contFields+=1;
					}
				}
			}
			$objTemp = '';
		}
		return $obj;
	}

	//************************************************************************************************************************\\
	/**
	 * Pega os objetos da diretiva FROM
	 *
	 * @param string $sql
	 * @return array
	 */
	private function getObjsFromSqlFROM($sql) {
		$objSql = $this->getObjsFromSql($sql);
		$objFrom = array();
		foreach ($objSql as $key => $value) {
			if ($key < 2000)
				$objFrom[] = $value;
		}
		return $objFrom;
	}

	//************************************************************************************************************************\\
	/**
	 * Pega os objetos da diretiva SELECT
	 *
	 * @param string $sql
	 * @return array
	 */
	private function getObjsFromSqlSELECT($sql) {
		$objSql = $this->getObjsFromSql($sql);
		$objSelect = array();
		foreach ($objSql as $key => $value) {
			if ($key < 2000)
				$objSelect[] = $value;
		}
		return $objSelect;
	}

	//************************************************************************************************************************\\
	/**
	 * Pega os campos sql da tabela a partir do array de objetos
	 *
	 * @param array $objs
	 * @return array
	 */
	private function getSqlFieldsFromObjGet($objs) {
		foreach ($objs as $key => $val) {
			$var = explode("->", $val);
			$var[0] = trim($var[0]);
			if (isset($var[1]))
				$var[1] = trim($var[1]);
			$class = new $var[0]();
			if (isset($var[1])) {
				$values[$key] = $class->getPropertyByMethod($var[1]);
			} else {
				if ($class->getSchemaName())
					$values[$key] = $class->getSchemaName() . "." . $class->getTableName();
				else
					$values[$key] = $class->getTableName();
			}
		}
		return $values;
	}

	//************************************************************************************************************************\\
	/**
	 * Testa caracteres q sao fim de instrucao sql
	 *
	 * @param char $char
	 * @return boolean
	 */
	private function endWord($char = '') {
		switch ($char) {
			case " ": return true;
				break;
			case "=": return true;
				break;
			case ",": return true;
				break;
			case ";": return true;
				break;
			case "\n": return true;
				break;
			default: return false;
				break;
		}
	}

	//************************************************************************************************************************\\
	/**
	 * Troca os valores de objeto para valores de tabela
	 *
	 * @param array $objs
	 * @param array $values
	 * @param string $sql
	 * @return string sql do banco de dados
	 */
	private function replaceObjValues($objs, $values, $sql) {
		$sqlDb = $sql;
		foreach ($objs as $key => $val) {
			if (count(explode("->", $val)) > 1) {
				$sqlDb = preg_replace("/__" . $val . "(\b|\n|\r)/", $values[$key], $sqlDb);
			} else
				$cont[] = $key;
		}
		if (is_array($cont))
			foreach ($cont as $value) {
				$sqlDb = str_replace("__" . $objs[$value], $values[$value], $sqlDb);
			}
		return $sqlDb;
	}

	//************************************************************************************************************************\\
	/**
	 * Atribui valores nos VOs baseado no retorno da sql
	 *
	 * @param array $result Resultado do fetchall
	 * @param VO $VO VO
	 * @param array $objs Array com objetos da sql
	 * @param array $values Array com os campos da sql
	 * @return array Array de VOs
	 */
	function setObjFromSql($result, VO $VO, $objs = '', $values = '') {
		if ($values == '')
			$values = $this->fieldFromSql;
		if ($objs == '')
			$objs = $this->objsFromSql;
		$objName = get_class($VO);

		if ($result) {
			foreach ($result as $linha) {
				//Seta os campos do get da select
				if (is_array($linha))
					foreach ($linha as $key => $value) {
						$classNameArray = explode("->", $objs[$this->getIDByValueField($values, $key)]);

						if (trim($classNameArray[0]) == trim($objName)) {//seta campos do VO
							$set = trim((str_replace('get', 'set', $classNameArray[1])));
							$VO->$set($value);
						} else {//seta campos do VO que sao Objetos
							if ($VO->getFkName(trim($classNameArray[0])) != '') {
								$fkObj = new $classNameArray[0]();
								$set = trim((str_replace('get', 'set', $classNameArray[1])));
								$fkObj->$set($value);
								$set = trim((str_replace('get', 'set', $VO->getMethodByObjProperty(get_class($fkObj)))));
								$VO->$set($fkObj);
							}
						}
					}
				$obj[] = clone $VO;
			}
			return $obj;
		} else {
			return array();
		}
	}

	function setVOFromArray($result, VO $VO) {

		if ($result) {
			foreach ($result as $linha) {
				//Seta os campos do get da select
				if (is_array($linha))
					foreach ($linha as $key => $value) {
						$set = trim((str_replace('get', 'set', $VO->getMethodByProperty($key))));
						$VO->$set($value);
					}
				$obj[] = clone $VO;
			}
			return $obj;
		}
		return array();
	}

	function setVOFromSql($result, VO $VO, $objs = '', $values = '') {
		if ($values == '')
			$values = $this->fieldFromSql;
		if ($objs == '')
			$objs = $this->objsFromSql;
		$objName = get_class($VO);

		if ($result) {
			foreach ($result as $linha) {
				//Seta os campos do get da select
				if (is_array($linha))
					foreach ($linha as $key => $value) {
						$classNameArray = explode("->", $objs[$this->getIDByValueField($values, $key)]);

						if (trim($classNameArray[0]) == trim($objName)) {//seta campos do VO
							$set = trim((str_replace('get', 'set', $classNameArray[1])));
							$VO->$set($value);
						}
					}
				$obj[] = clone $VO;
			}
			return $obj;
		} else {
			return array();
		}
	}

	//************************************************************************************************************************\\
	/**
	 * Pega o ID do objeto que esta no array de objetos...
	 *
	 * @param array $values Array com camspos da tabela
	 * @param string $key Campo da tabela
	 * @return integer
	 */
	private function getIDByValueField($values, $key) {
		$vals = array_flip($values);
		return (int) $vals[trim($key)];
	}

	//************************************************************************************************************************\\
	/**
	 * Seta o array do formulario para o VO
	 *
	 * @param array $res Array com valores do formulario
	 *
	 * @param VO $VO Objeto VO a ser Setado
	 *
	 * @return VO
	 *
	 * @author Ibanez C. Almeida
	 *
	 * @todo setar somente campos notnull
	 *
	 */
	public function setVO($res = '', ?VO $VO = null) {
		$fkObj = null;
		foreach ($res as $key => $value) {
			if (is_array($value) || trim($value) != '') {
				if (@$VO->getMethodByProperty($key)) {
					$set = trim(str_replace('get', 'set', $VO->getMethodByProperty($key)));
					try {
						$VO->$set($value);
					} catch (Exception $e) {
						throw new Exception($e->getMessage());
					}
				} else {
					$fkObjName = $VO->getObjByFkName($key);
					if ($fkObjName != '') {
						$fkObj = new $fkObjName();
						$set = trim(str_replace('get', 'set', $fkObj->getMethodByProperty($fkObj->getPkName())));
						try {
							$fkObj->$set($value);
							$set = trim((str_replace('get', 'set', $VO->getMethodByProperty(get_class($fkObj)))));
							$VO->$set($fkObj);
						} catch (Exception $e) {
							throw new Exception($e->getMessage());
						}
					}
				}
			}
		}
		return $VO;
	}

	//************************************************************************************************************************\\
	/**
	 * Roda o comando SQL e retorna array de objetos
	 *
	 * @param string $sql Comando SQL a ser executado
	 *
	 * @param object $object Objeto que sera setado
	 *
	 * @return array
	 *
	 * @author Ibanez C. Almeida
	 * @deprecated use the TDG Class
	 *
	 */
	protected function runSql($sql, $object) {
		$this->container->remove($object);

		try {
			$res = $this->query($sql);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if ($res) {
			$obj = $this->setObjFromSql($res, $object);
			foreach ($obj as $value) {
				$this->container->insert($value);
			}
			return $obj;
		} else {
			$this->container->insert(array());
			return array();
		}
	}

	//************************************************************************************************************************\\
	/**
	 * Executa a sql e seta o objeto
	 *
	 * @param string $sql
	 * @param VO $object
	 * @return array Array de VOs
	 * @deprecated use the TDG Class
	 */
	public function execSql($sql, $object) {
		try {
			$res = $this->queryWithId($sql);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if ($res) {
			$obj = $this->setObjFromSqlWithId($res, $object);
			return $obj;
		} else {
			//$this->container->insert(array());
			return array();
		}
	}

	//************************************************************************************************************************\\
	protected function formatOrder($array = array()) {
		$order = '';
		if (isset($array['_order']) && trim($array['_order']))
			$order = "ORDER BY " . trim($array['_order']);
		if (isset($array['_by']) && trim($array['_by']) && (trim($array['_order'])))
			$order = $order . " " . trim($array['_by']);
		return $order;
	}

	//************************************************************************************************************************\\

	private function setObjFromSqlWithId($result, VO $VO, $objs = '', $values = '') {
		if ($values == '')
			$values = $this->fieldFromSql;
		if ($objs == '')
			$objs = $this->objsFromSql;
		$objName = get_class($VO);
		$z = 0;
		if ($result) {
			foreach ($result as $linha) {
				//Seta os campos do get da select
				$obj = $VO;
				$objFKs = array();

				foreach ($linha as $key => $value) {
					$key = str_replace("l", "", $key);
					//pega o objeto com o seu metodo get
					$classNameArray = explode("->", $objs[$key]);

					if (trim($classNameArray[0]) == trim($objName)) {//seta campos do VO
						$obj = $this->setOneObjFromSqlWithId($linha, $VO);
					} else {//seta campos do VO que sao Objetos
						if (class_exists($classNameArray[0])) {
							$fkObj = new $classNameArray[0]();
							$objFKs[] = $this->setOneObjFromSqlWithId($linha, $fkObj);
						}
					}
				}

				$objFKsTemp = array();
				//Retira objetos estrangeiros setados repetidamente
				for ($xobj = 0; $xobj < count($objFKs); $xobj++) {
					$find = false;
					for ($yobj = 0; (($yobj < count($objFKsTemp)) && (!$find)); $yobj++) {
						if ($objFKs[$xobj] == $objFKsTemp[$yobj]) {
							$find = true;
						}
					}
					if (!$find)
						$objFKsTemp[] = $objFKs[$xobj];
				}


				$objFKs = $objFKsTemp;
				unset($objFKsTemp);

				$base = array(); //objetos q seram retornados
				$all = array(); //todos objetos da consulta
				$baseTemp = array(); //objetos q seram retornados temp
				$include = array(); //objetos incluidos em outros objetos

				if (count($objFKs) == 1)
					$baseTemp = $objFKs;
				for ($xobj = 0; $xobj < count($objFKs); $xobj++) {

					for ($yobj = 0; $yobj < count($objFKs); $yobj++) {

						if (get_class($objFKs[$xobj]) != get_class($objFKs[$yobj])) {
							$set = trim((str_replace('get', 'set', $objFKs[$xobj]->getMethodByProperty(get_class($objFKs[$yobj])))));
							if ($set != '') {
								array_push($include, $objFKs[$yobj]);
								$objFKs[$xobj]->$set($objFKs[$yobj]);

								array_push($include, $objFKs[$yobj]);
							}
							if (!in_array($objFKs[$xobj], $include))
								array_push($baseTemp, $objFKs[$xobj]);
						}
					}
				}

				for ($xobj = 0; $xobj < count($baseTemp); $xobj++) {
					if (!in_array($baseTemp[$xobj], $include)) {
						$base[] = $baseTemp[$xobj];
					}
				}

				$objFKs = $base;

				for ($x = 0; $x < count($objFKs); $x++) {
					$set = trim((str_replace('get', 'set', $VO->getMethodByProperty(get_class($objFKs[$x])))));
//					$obj->$set($fkObj);
					if (method_exists($obj, $set)) {
						$obj->$set($objFKs[$x]);
					} else {
						throw new Exception("Erro LQL: O metodo set para o objeto " . get_class($objFKs[$x]) . " da classe: " . get_class($obj) . " nao existe");
					}
				}

				$objArray[] = clone $obj;
			}

			return $objArray;
		} else {
			return array();
		}
	}

	//************************************************************************************************************************\\
	public function queryWithId($sql = '') {
		try {
			return $this->genericQuery($this->sqlObjToDbWithId($sql));
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	//************************************************************************************************************************\\
	protected function sqlObjToDbWithId($sqlObj) {
		$this->objsFromSql = $this->getObjsFromSql($sqlObj);
		$this->fieldFromSql = $this->getSqlFieldsFromObjGetWithId($this->objsFromSql);
		return $this->replaceObjValuesWithIds($this->objsFromSql, $this->fieldFromSql, $sqlObj);
	}

	//************************************************************************************************************************\\
	private function getSqlFieldsFromObjGetWithId($objs) {
		foreach ($objs as $key => $val) {
			$var = explode("->", $val);
			$var[0] = trim($var[0]);
			if (isset($var[1]))
				$var[1] = trim($var[1]);
			$class = new $var[0]();
			if (isset($var[1])) {
				if ($key >= 2000) {
					$values[$key] = $class->getTableName() . "." . $class->getPropertyByMethod($var[1]) . " as l" . $key;
				} else
					$values[$key] = $class->getTableName() . "." . $class->getPropertyByMethod($var[1]);
			}
			else {
				if ($class->getSchemaName())
					$values[$key] = $class->getSchemaName() . "." . $class->getTableName();
				else
					$values[$key] = $class->getTableName();
			}
		}
		return $values;
	}

	//************************************************************************************************************************\\
	private function replaceObjValuesWithIds($objs, $values, $sql) {
		$sqlArray = preg_split('/\b[Ff][Rr][Oo][Mm]\b/', $sql);
		$sqlDb1 = $sqlArray[0];
		$sqlDb2 = "FROM" . $sqlArray[1];

		foreach ($objs as $key => $val) {
			if (count(explode("->", $val)) > 1) {
				if ($key < 2000) {
					$sqlDb2 = preg_replace("/__" . $val . "(\b|\n|\r)/", $values[$key], $sqlDb2);
				} else {
					$sqlDb1 = preg_replace("/__" . $val . "(\b|\n|\r)/", $values[$key], $sqlDb1);
				}
			} else
				$cont[] = $key;
		}

		$sqlDb = $sqlDb1 . $sqlDb2;

		foreach ($cont as $value) {
//			$sqlDb = str_replace("__".$objs[$value],$values[$value],$sqlDb);
			$sqlDb = preg_replace("/__" . $objs[$value] . "(\b|\n|\r)/", $values[$value], $sqlDb);
		}
		return $sqlDb;
	}

	//************************************************************************************************************************\\
	private function setOneObjFromSqlWithId($linha, VO $VO, $objs = '', $values = '') {
		if ($values == '')
			$values = $this->fieldFromSql;
		if ($objs == '')
			$objs = $this->objsFromSql;
		$objName = get_class($VO);

		//Seta os campos do get da select

		foreach ($linha as $key => $value) {
			$key = str_replace("l", "", $key);
			//pega o objeto com o seu metodo get
			$classNameArray = explode("->", $objs[$key]);
			if (trim($classNameArray[0]) == trim($objName)) {//seta campos do VO
				$set = trim((str_replace('get', 'set', $classNameArray[1])));
				$VO->$set($value);
			}
		}

		return clone $VO;
	}

	//************************************************************************************************************************\\
	public function getSql() {
		return $this->sql;
	}

	//************************************************************************************************************************\\
	/**
	 * Formata a string para pesquisa em banco de dados
	 * @author Edson
	 * @param string $var
	 * @return string
	 */
	protected function whereOrAnd($where) {
		if ($where == '') {
			$where = " WHERE ";
		} else {
			$where .= " AND ";
		}
		return $where;
	}

	//************************************************************************************************************************\\
	/**
	 * Formata a string para pesquisa em banco de dados
	 *
	 * @param string $var
	 * @return string
	 */
	protected function formatSearchText($field, $var) {
		$var = (is_callable('get_magic_quotes_gpc')) ? pg_escape_string(stripslashes($var)) : pg_escape_string($var);
		$value = str_replace(" ", "%", $var);
		$where = "to_ascii($field, 'LATIN1') ilike to_ascii('%" . $value . "%','LATIN1') ";
		$value = htmlentities($value);
		$where .= " OR $field ilike '%" . $value . "%' ";
		return $where;
	}
	
	/**
	 * Versão teoricamente mais rápida de formatSearchText
	 * @param type $field
	 * @param type $var
	 * @return string
	 */
	protected function formatSearchText2($field, $var) {
		$var = (is_callable('get_magic_quotes_gpc')) ? pg_escape_string(stripslashes($var)) : pg_escape_string($var);
		$value = str_replace(" ", "%", $var);
		$where = "to_ascii($field, 'LATIN1') ilike to_ascii('%" . $value . "%','LATIN1') ";
		return $where;
	}	

	//************************************************************************************************************************\\

}

?>
