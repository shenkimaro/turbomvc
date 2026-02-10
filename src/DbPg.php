<?php

/**
 * @package Library
 *
 * @subpackage DAO
 *
 * @filesource
 */

/**
 * Esta classe cria uma camada que mascara os metodos de acesso ao
 * banco de dados
 *
 * @package Library
 *
 * @author Ibanez C. Almeida <ibanez.almeida@gmail.com>
 *
 * @version 2.0
 *
 */
class DbPg {

	/**
	 * Resultado da conexao com banco de dados
	 *
	 * @var resource
	 */
	private $con;

	/**
	 * Host para conexao com banco de dados
	 *
	 * @var string
	 */
	private $host = "";

	/**
	 * Login para conexao com banco de dados
	 *
	 * @var string
	 */
	private $login = "";

	/**
	 * Senha para conexao com banco de dados
	 *
	 * @var string
	 */
	private $senha = "";

	/**
	 * Banco de dados a ser selecionadao
	 *
	 * @var string
	 */
	private $db = "";

	/**
	 * Porta de conexao com banco de dados
	 *
	 * @var string
	 */
	private $port;

	/**
	 * Erro de execucao de comando sql
	 *
	 * @var string
	 */
	private $error;

	/**
	 * Objeto Debug
	 *
	 * @var Debug
	 */
	private $debug;

	/**
	 * Objeto Email
	 *
	 * @var Email
	 */
	private $email;

	/**
	 * Instancia da classe
	 *
	 * @var Db
	 */
	public static $instance;

	/**
	 * usada para contagem no metodo prepareQuery
	 * @var int
	 */
	private $count;
	private $startTimeConnection;

	//************************************************************************************************************************\\

	/**
	 * Metodo construtor de Db
	 *
	 * @param string $host Endereco onde se encontra o banco de dados
	 *
	 * @param string $bd Nome do banco de dados
	 *
	 * @param string $login Login de conexao com o banco de dados
	 *
	 * @param string $senha Senha de conexao com o banco de dados
	 *
	 */
	public function __construct($host, $bd, $login, $senha, $port = "5432") {
		$this->host = $host;
		$this->login = $login;
		$this->senha = $senha;
		$this->port = $port == '' ? '5432' : $port;
		$this->db = $bd;
		$this->startTimeConnection = Debug::getStartExecutionTime();				
	}

	public function __destruct() {
		if ($this->con && is_resource($this->con)) {			
			if(! pg_close($this->con)){
				Debug::tail('Nao foi possivel fechar a conexao.');
			}
			
			if (defined("_SYSNAME") && _SYSNAME == 'cms_template') {
				$tempoDecorrido = Debug::getElapsedExecutionTime($this->startTimeConnection);
				if ($tempoDecorrido >= 10) {
					trigger_error("Tempo demorado de conexao aberta: $tempoDecorrido", E_USER_WARNING);
				}
			}
		}
	}
	
	private function getCon() {
		$this->connect();
		return $this->con;
	}

	//************************************************************************************************************************\\

	/**
	 * Retorna uma unica instancia da classe
	 *
	 * @return Db
	 */
	public static function getInstance($host, $bd, $login, $senha, $port = "5432") {
		if (!isset(self::$instance)) {
			self::$instance = new DbPg($host, $bd, $login, $senha, $port);
		}
		return self::$instance;
	}

	//************************************************************************************************************************\\

	/**
	 * Escreve os dados da conexao com o banco de dados
	 *
	 * @param integer $tipo Indica o que deve ser retornado,
	 * atualmente existe somente uma opcao a padrao "1"
	 *
	 * @param integer $senha Indica se existe uma senha que possa ser
	 * retornada
	 *
	 */
	function writeConnection($tipo = 1, $senha = 0) {
		echo "<pre>";
		echo "Host........: " . $this->host . "\n";
		echo "Login.......: " . $this->login . "\n";
		if ($senha == 1) {
			echo "Senha.......: " . $this->senha . "\n";
		}
		echo "Banco Dados.: " . $this->db . "\n";
		echo "</pre>";
	}

	//************************************************************************************************************************\\

	/**
	 * Realiza a conexao com o banco de dados
	 */
	private function connect() {
		if ($this->host == "") {
			throw new Exception("Host para conexão não informado");
		}
		if ($this->con) {
			return;
		}

		$mensagem_erro = "Não foi possível fazer a conexão com o banco de dados";		
		$connectParans = "host=$this->host dbname=$this->db user=$this->login password=$this->senha port=$this->port connect_timeout=1";

		$this->con = @pg_connect($connectParans);		
		
		if ($this->con) {
			$this->error = @pg_last_error($this->con);		
			if ($this->error) {
				throw new ConectionDBException($mensagem_erro . $this->error);
			}
		} else {
			$complementoMsg = " host: " . $this->host . ' db: ' . $this->db . ' user: ' . $this->login;
			if(Util::isLocalIp() || Util::isBeta()){
				throw new ConectionDBException($mensagem_erro . $complementoMsg);
			}
			Debug::tail($mensagem_erro . $complementoMsg);
			throw new ConectionDBException($mensagem_erro .". Banco de dados de produção.");
		}
	}

	//************************************************************************************************************************\\

	/**
	 * Executa a sql
	 *
	 * @param string $sql Deve conter a sql a ser executada pelo
	 * banco de dados
	 *
	 * @param integer $stop Indica se a query vai parar a execucao em
	 * caso de erro "1", ou vai retorna-lo "0"
	 */
	public function query($sql = '') {
		if (pg_connection_busy($this->getCon())) {
			return false;
		}
		/**
		 * validacao de ausencia de escape
		 */
		if((substr_count($sql, "'")%2) != 0){
			throw new Exception("0x4C6962: Erro de sintaxe na linguagem de consulta estruturada.");
		}
		pg_send_query($this->getCon(), $sql);
		$var = pg_get_result($this->getCon());		
		while (pg_get_result($this->getCon()));
		if ($var !== FALSE) {
			$this->error = pg_result_error($var);
			$this->error = $this->error ?: pg_last_error($this->getCon());
		}
		if ($this->error) {
			throw new Exception($this->error);
		}
		return $var;
	}

	//************************************************************************************************************************\\

	/**
	 * Apelido para query
	 *
	 * @param string $sql Deve conter a sql a ser executada pelo
	 * banco de dados
	 * @see query
	 *
	 */
	public function execute($sql = '') {
		return $this->query($sql);
	}

	//************************************************************************************************************************\\

	/**
	 * Apelido para query
	 *
	 * @param string $sql Deve conter a sql a ser executada pelo
	 * banco de dados
	 * @see query
	 *
	 */
	public function exec($sql) {
		return $this->query($sql);
	}

	//************************************************************************************************************************\\

	/**
	 * Indeficar o numero de linhas no resultado do SQL
	 *
	 * @param resource $consulta Resultado retornado por query,exec ou execute
	 *
	 * @return interger O numero de linhas da consulta SQL
	 *
	 */
	public function num_rows($consulta) {
		$var = @pg_num_rows($consulta);
		$this->error = pg_last_error($this->getCon());
		return $var;
	}

	//************************************************************************************************************************\\

	/**
	 * Apelido para num_rows
	 *
	 * @param resource $consulta Resultado retornado por query,exec ou execute
	 * @see num_rows
	 *
	 */
	public function numRows($consulta) {
		return $this->num_rows($consulta);
	}

	//************************************************************************************************************************\\

	/**
	 * Encontrar o ultimo OID
	 *
	 * @param resource $consulta Resultado retornado por query
	 *
	 * @return resouce A oid inserida
	 *
	 */
	public function last_oid($result) {
		$var = pg_last_oid($result);
		$this->error = pg_last_error($this->getCon());
		return $var;
	}

	//************************************************************************************************************************\\

	/**
	 * Apelido para last_oid
	 *
	 * @param resource $consulta Resultado retornado por query
	 *
	 * @return resouce A oid inserida
	 * @see last_oid
	 *
	 */
	public function lastOid($result) {
		return $this->last_oid($result);
	}

	//************************************************************************************************************************\\

	/**
	 * Encontrar o ultimo registro inserido
	 *
	 * @param string $table Nome da tabela a ser pesquisada
	 *
	 * @param resource $exec  Resultado retornado por query,exec ou execute
	 *
	 * @return resouce Registo inserido no banco de dados
	 *
	 */
	public function last_insert_record($table = '', $exec = '') {
		$sql = "	SELECT cod_slip
				FROM $table
				WHERE oid=" . $this->last_oid($exec);
		$var = $this->query($sql);
		$this->error = pg_last_error($this->getCon());
		return $this->query($sql);
	}

	//************************************************************************************************************************\\

	/**
	 * Encontrar valor de um campo em uma linha especifica do resultado
	 *
	 * @param resource $consulta Resultado retornado por query,exec ou execute
	 *
	 * @param interger $linha O numero da linha do resultado
	 *
	 * @param string $campo O campo a ser retornado
	 *
	 * @return resource Valor do resultado
	 *
	 */
	public function result($consulta, $linha, $campo) {
		return @pg_result($consulta, $linha, $campo);
	}

	//************************************************************************************************************************\\

	/**
	 * Retorna um array associativa com os nomes os campos ou com
	 * numeros de uma consulta
	 *
	 * @param resource $consulta Resultado retornado por query,exec ou execute
	 *
	 * @return array Com os nomes dos campos ou com numeros de uma
	 * consulta
	 *
	 */
	public function fetch_array($consulta) {
		return @pg_fetch_array($consulta, 0, PGSQL_BOTH);
	}

	//************************************************************************************************************************\\

	/**
	 * Encontrar o numero de campos da consulta
	 *
	 * @param resource $consulta Resultado retornado por query,exec ou execute
	 *
	 * @return integer Numero de campos da consulta
	 *
	 */
	public function num_fields($consulta) {
		return @pg_num_fields($consulta);
	}

	//************************************************************************************************************************\\

	/**
	 * Apelido para num_fields
	 *
	 * @param resource $consulta Resultado retornado por query,exec ou execute
	 *
	 * @return integer Numero de campos da consulta
	 * @see num_fields
	 *
	 */
	public function numFields($consulta) {
		return $this->num_fields($consulta);
	}

	//************************************************************************************************************************\\

	/**
	 * Apelido para fetch_all
	 *
	 * @param resource $exec Resultado retornado por query,exec ou execute
	 *
	 * @return array
	 * @see fetch_all
	 */
	public function fetchAll($exec) {
		return $this->fetch_all($exec);
	}

	//************************************************************************************************************************\\

	/**
	 * Retorna todas as linhas (registros) como um array
	 *
	 * @param resource $exec Resultado retornado por query,exec ou execute
	 *
	 * @return array
	 */
	public function fetch_all($exec) {
		if ($this->num_rows($exec) == 0) {
			return array();
		}
		return pg_fetch_all($exec);
	}

	//************************************************************************************************************************\\

	/**
	 * Obtem o proximo valor da sequencia informada
	 *
	 * @param string $sequencia Nome da sequencia no banco
	 * @return integer
	 */
	public function getSequencia($sequencia) {
		$sql = "SELECT nextval('$sequencia') as proximo;";
		$ex = $this->execute($sql);
		$res = $this->fetchAll($ex);
		return $res[0]['proximo'];
	}

	//************************************************************************************************************************\\

	/**
	 * Apelido para getSequencia
	 *
	 * @param string $sequencia Nome da sequencia no banco
	 * @return integer
	 * @see getSequencia
	 */
	public function nextVal($sequencia) {
		$sql = "SELECT nextval('$sequencia') as proximo;";
		$ex = $this->execute($sql);
		$res = $this->fetchAll($ex);
		return $res[0]['proximo'];
	}

	//************************************************************************************************************************\\

	/**
	 * Apelido para getSequencia
	 *
	 * @param string $sequencia Nome da sequencia no banco
	 * @return integer
	 * @see getSequencia
	 */
	public function currVal($sequencia) {
		if (!$sequencia) {
			throw new Exception("Erro interno: sequencia desconhecida em currval");
		}
		$sql = "SELECT last_value as current FROM $sequencia;";
		$ex = $this->execute($sql);
		$res = $this->fetchAll($ex);
		return $res[0]['current'];
	}

	//************************************************************************************************************************\\

	/**
	 * Inicia o processo de transação
	 *
	 * @return boolean
	 *
	 */
	public function beginTransaction() {		
		$sql = "begin;";
		$var = $this->execute($sql);
		$this->error = pg_last_error($this->getCon());
		return true;
	}

	//************************************************************************************************************************\\

	/**
	 * Desfaz a transação
	 *
	 * @return boolean
	 *
	 */
	public function rollback() {
		$sql = "rollback;";
		$var = $this->execute($sql);
		$this->error = pg_last_error($this->getCon());
		return true;
	}

	//************************************************************************************************************************\\

	/**
	 * Finaliza a transacao gravando os dados
	 *
	 * @return boolean
	 *
	 */
	public function commit() {
		$sql = "commit;";
		$var = $this->execute($sql);
		$this->error = pg_last_error($this->getCon());
		return true;
	}

	//************************************************************************************************************************\\

	/**
	 * Pega o erro da instrucao sql no banco
	 *
	 * @return string
	 *
	 */
	public function getError() {
		return $this->error;
	}

	// ********************************************************************************************************************************

	/**
	 * Verifica se se o campo de uma tabela é chave estrangeira em outra e retorna o nome da 1 ª tabela que tem aquele campo com valor, ou seja chave estrangeira em uso
	 *
	 * @param string $tabela
	 * @param string $campo
	 * @param string $valor
	 * @param string $esquema
	 * @return string nomeDaTabelaUsando
	 */
	public function tabelaUsando($tabela, $campo, $valor, $esquema = "public") {

		$sql = "SELECT	n.nspname AS esquema,
						cl.relname AS tabela,
						a.attname AS coluna
				FROM 	pg_catalog.pg_attribute a
						JOIN pg_catalog.pg_class cl ON (a.attrelid = cl.oid AND cl.relkind = 'r')
						JOIN pg_catalog.pg_namespace n ON (n.oid = cl.relnamespace)
						JOIN pg_catalog.pg_constraint ct ON (a.attrelid = ct.conrelid AND ct.confrelid != 0 AND ct.conkey[1] = a.attnum)
						JOIN pg_catalog.pg_class clf ON (ct.confrelid = clf.oid AND clf.relkind = 'r')
						JOIN pg_catalog.pg_namespace nf ON (nf.oid = clf.relnamespace)
						JOIN pg_catalog.pg_attribute af ON (af.attrelid = ct.confrelid AND af.attnum = ct.confkey[1])
				where n.nspname='$esquema'
				AND clf.relname='$tabela'
				AND af.attname='$campo'
				ORDER BY n.nspname, cl.relname;";
		$que = $this->exec($sql);
		while ($row = $_DATABASE->DB_fetch_array($que)) {
			$sql2 = "SELECT count($row[coluna]) as cont
				  FROM $row[esquema].$row[tabela]
				  WHERE $row[coluna] = '{$valor}'";
			$que2 = $this->exec($sql2);
			$row2 = $this->fetch_array($que2);

			if ($row2["cont"] > 0) {
				return $row["tabela"];
			}
		}
		return "";
	}

	public function escape($var) {
		return pg_escape_string($this->getCon(), $var);
	}

	/**
	 * 
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	public function preparedQuery($sql, $params = array()) {
		$callBack = function ($match) {
			return '$' . $this->count++;
		};
		$this->count = 1;
		$sql = preg_replace_callback('/\?/', $callBack, $sql);
		$ex = pg_query_params($this->getCon(), $sql, $params);
		if (!$ex) {
			throw new Exception(pg_errormessage($this->getCon()));
		}
		return $this->fetchAll($ex);
	}

}
