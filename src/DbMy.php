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
class DbMy {

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
	 * Instancia da classe
	 *
	 * @var Db
	 */
	public static $instance;

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
	public function __construct($host, $bd, $login, $senha, $port = "3306") {
		$this->host = $host;
		$this->login = $login;
		$this->senha = $senha;
		$this->port = $port != ''?$port:'3306';
		$this->db = $bd;
		$this->conect();
	}

	//************************************************************************************************************************\\
	/**
	 * Retorna uma unica instancia da classe
	 *
	 * @return Db
	 */
	public static function getInstance($host, $bd, $login, $senha, $port = "") {
		if (!isset(self::$instance)) {
			self::$instance = new DbMy($host, $bd, $login, $senha, $port);
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
	private function conect() {
		if ($this->host == "") {
			throw new Exception("Host para conexao nao informado");
		}
		if (!function_exists('mysqli_connect')) {
			throw new Exception('Nao existe suporte para banco MySql');
		}
		$this->con = mysqli_connect($this->host, $this->login, $this->senha, $this->db, $this->port);

		if (!$this->con) {			
			$this->error = mysqli_connect_error();			
			throw new Exception("Nao foi possivel fazer a conexao com o banco de dados " . $this->error." ");
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
		$var = mysqli_query($this->con, $sql);
		$this->error = mysqli_error($this->con);
		if ($this->error) {
			throw new Exception($this->error);
		}
		return $var;
	}

	//************************************************************************************************************************\\
	/**
	 * Sql para pegar um registro
	 */
	public function getRow($table, $field, $value) {
		$sql = "	SELECT *
				FROM $table
				WHERE $field = '$value'";
		return $this->genericQuery($sql);
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
	public function num_rows($result) {
		$ok = mysqli_num_rows($result);
		$this->error = mysqli_error($this->con);
		return $ok;
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
	 *
	 * @param String $sql
	 * @return <type> 
	 */
	public function genericQuery($sql) {
		try {
			$ex = $this->query($sql);
			return $this->fetch_array($ex);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
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
	public function result($consulta, $linha, $campo = "") {
		if ($campo == "") {
			return self::mysqli_result($consulta, $linha);
		}
		return self::mysqli_result($consulta, $linha, $campo);
	}

	private static function mysqli_result($res, $row = 0, $col = 0) {
		$numrows = mysqli_num_rows($res);
		if ($numrows && $row <= ($numrows - 1) && $row >= 0) {
			mysqli_data_seek($res, $row);
			$resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
			if (isset($resrow[$col])) {
				return $resrow[$col];
			}
		}
		return false;
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
	public function fetch_array($result) {
		return mysqli_fetch_array($result);
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
		return mysqli_num_fields($consulta);
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
	public function fetchAll($result) {
		return $this->fetch_all($result);
	}

	//************************************************************************************************************************\\
	/**
	 * Retorna uma pesquisa como um array
	 *
	 * @param resource $exec Resultado retornado por query,exec ou execute
	 *
	 * @return array
	 * @see fetch_all
	 */
	public function fetch_all($result) {
		$array = array();
		for ($x = 0; $x < $this->num_rows($result); $x++) {
			$array[] = $this->fetch_array($result);
		}
		return $array;
	}

	//************************************************************************************************************************\\
	/**
	 * Inicia o processo de transacao
	 *
	 * @return boolean
	 * 
	 */
	public function beginTransaction() {
		$sql = "START TRANSACTION;";
		$var = $this->execute($sql);
		$this->error = mysql_error($this->con);
		return true;
	}

	//************************************************************************************************************************\\
	/**
	 * Desfaz a transacao
	 *
	 * @return boolean
	 * 
	 */
	public function rollback() {
		$sql = "ROLLBACK;";
		$var = $this->execute($sql);
		$this->error = mysqli_error($this->con);
		return true;
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
	 * Finaliza a transacao gravando os dados
	 *
	 * @return boolean
	 * 
	 */
	public function commit() {
		$sql = "COMMIT;";
		$var = $this->execute($sql);
		$this->error = mysqli_error($this->con);
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

	public function escape($var) {
		return mysqli_escape_string($this->con, $var);
	}

	// ********************************************************************************************************************************		
	
	/**
	 * 
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	public function preparedQuery($sql, $params=array()) {
		throw new Exception('Not implemented for Mysql');
		$this->count = 1;		
		$sql = preg_replace_callback('/\?/', array($this, 'replacement'), $sql);
		$ex = pg_query_params($this->con, $sql, $params);
		return $this->fetchAll($ex);
	}
	
	private function replacement($match) {
		return '$' . $this->count++;
	}
}

?>
