<?php

class Log {

	private $db = null;
	private $tableLog;
	private $hostLog;
	private $dbLog;
	private $loginLog;
	private $passwordLog;
	private $port;
	private $vars;
	private $status;
	public static $instance;

	function __construct($hostLog = '', $dbLog = '', $loginLog = '', $passwordLog = '', $tableLog = '', $port = "5432") {
		$this->hostLog = $hostLog;
		$this->dbLog = $dbLog;
		$this->loginLog = $loginLog;
		$this->passwordLog = $passwordLog;
		$this->port = $port;
		$this->setStatus(false);
		$this->setVariables($GLOBALS['configLogVar'] ?? []);
		if (isset($GLOBALS['configLog']['status']) && $GLOBALS['configLog']['status']) {
			$this->setStatus(true);
		}
		$this->setTableLog($tableLog);
	}

	public static function getInstance($hostLog, $dbLog, $loginLog, $passwordLog, $tableLog, $port = "5432") {
		if (!isset(self::$instance)) {
			self::$instance = new Log($hostLog, $dbLog, $loginLog, $passwordLog, $tableLog, $port);
		}
		return self::$instance;
	}

	public function save($array = '') {
		$fields = '';
		$values = '';
		foreach ($array as $key => $value) {
			if (trim($value) != '') {
				$value = $this->getDb()->escape($value);
				$fields = $fields == '' ? $key : $fields . ",$key ";
				$values = $values == '' ? "'" . $value . "'" : $values . ",'$value' ";
			}
		}
		$sql = "INSERT INTO {$this->tableLog} ($fields) VALUES($values)";

		try {
			$this->getDb()->query($sql, false);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	public function getStatus() {
		return $this->status;
	}

	public function setStatus($status) {
		$this->status = $status;
	}

	public function getVariables() {
		return $this->vars;
	}

	public function setVariables(array $variables) {
		$this->vars = $variables;
	}

	/**
	 * 
	 * @param array $variables ['key'=>$value]
	 */
	public function addVariables(array $variables) {
		$this->vars = array_merge($this->getVariables(), $variables);
	}

	public function saveLogForce($array = []) {
		$this->setStatus(true);
		$this->saveLogDefault($array);
	}

	public function saveLogDefault($array = '') {
		if (!$this->getStatus()) {
			return;
		}
		if (!empty($this->getVariables())) { //se tem arquivo variaveis
			if (is_array($array)) {
				foreach ($array as $key => $value) {
					$array[$key] = ($value);
				}
				$array = array_merge($this->getVariables(), $array);
			} else {
				$array = $this->getVariables();
			}
			$this->save($array);
		} else if (is_array($array)) { //se nao, se foi passado array para ser salvo
			$this->save($array);
		}
	}

	public function setTableLog($tableLog) {
		$this->tableLog = $tableLog;
	}

	public function getDb() {
		if ($this->db == null) {
			$this->db = new DbPg($this->hostLog, $this->dbLog, $this->loginLog, $this->passwordLog, $this->port);
		}
		return $this->db;
	}

	function __destruct() {
		unset($this->db);
	}
}
