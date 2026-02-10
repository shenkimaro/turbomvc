<?php

class ConnectionData {

	private $dbType;
	private $host;
	private $dbName;
	private $login;
	private $password;

	const MYSQL = 'mysql';
	const POSTGRES = 'pgsql';
	
	
	public function __construct($dbType, $host, $dbName, $login, $password) {
		$this->setHost($host);
		$this->setDbName($dbName);
		$this->setDbType($dbType);
		$this->setLogin($login);
		$this->setPassword($password);
	}
	
	public function getHost() {
		return $this->host;
	}

	public function setHost($host) {
		$this->host = $host;
	}


	public function getLogin() {
		return $this->login;
	}

	public function setLogin($login) {
		$this->login = $login;
	}

	public function getPassword() {
		return $this->password;
	}

	public function setPassword($password) {
		$this->password = $password;
	}
	public function getDbType() {
		return $this->dbType;
	}

	public function setDbType($dbType) {
		switch ($dbType):
			case self::POSTGRES:
			case self::MYSQL:
				$this->dbType = $dbType;
				break;
			default:	
				$msg = "Argumento inválido para 'setDbType'. ";
				$msg .= "Esta função aceita os seguintes valores: ".__CLASS__."::POSTGRES e ".__CLASS__."::MYSQL";
				throw new Exception($msg);			
		endswitch;
		$this->dbType = $dbType;
	}

	public function getDbName() {
		return $this->dbName;
	}

	public function setDbName($dbName) {
		$this->dbName = $dbName;
	}


	
}

?>