<?php

class Container {

	private static $objs;
	private $scope;
	private $objsName;
	private $objsValue;
	public static $instance;

	function __construct($scope = "App") {
		$this->scope = $scope;
	}

	//************************************************************************************************************************\\
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new Container();
		}
		return self::$instance;
	}

	public function insert($obj, $objAlias = '') {
		if (is_object($obj)) {
			self::$objs[$objAlias ? $objAlias : get_class($obj)][] = &$obj;
			if (strtoupper($this->scope) == "SESSION")
				$_SESSION[$objAlias ? $objAlias : get_class($obj)] = serialize(self::$objs[$objAlias ? $objAlias : get_class($obj)]);
		}
	}

	public function insertSession($obj, $objAlias = '') {
		$scope = $this->getScope();
		$this->setScope("SESSION");
		$this->insert($obj, $objAlias);
		$this->setScope($scope);
	}

	public function getObjectsName() {
		return $this->objsName;
	}

	public function getObjectsValueByName($objName = '') {
		$objects = $this->getObjects($objName);
		return $this->getObjectsValue($objects);
	}

	public function getObjectsValue($objects) {
		$fields = $objects[0]->getProperties();
		for ($x = 0; $x < count($objects); $x++) {
			foreach ($fields as $key => $value) {
				if (!is_object($objects[$x]->$key))
					$var[$key] = $objects[$x]->$key;
				else {
					$f = $objects[$x]->$key->getProperties();
					foreach ($f as $k => $v) {
						$var[get_class($objects[$x]->$key) . "->" . $k] = $objects[$x]->$key->$k;
					}
				}
			}
			$objsValue[$x] = $var;
		}
		return $objsValue;
	}

	/**
	 * Para obter detalhes de um objeto, digite getObjects('NomeObjeto');
	 *
	 * @param string $obj
	 * @return array
	 */
	public function getObjects($obj = '') {
		$objects = array();
		if ($obj == '') {
			if (is_array(self::$objs)) {
				foreach (self::$objs as $key => $val) {
					$objects[] = $key;
				}
			}
			return $objects;
		}
		if (is_object($obj))
			$objName = get_class($obj);
		else
			$objName = $obj;
		if (self::$objs[$objName])
			$objects = self::$objs[$objName];
		elseif (@$_SESSION[$objName]) {
			$objects = unserialize($_SESSION[$objName]);
		}
		return $objects;
	}

	public function exists($obj) {
		if (is_object($obj))
			$objName = get_class($obj);
		else
			$objName = $obj;
		if ((self::$objs[$objName]??null))
			return true;
		elseif ($_SESSION[$objName] ?? null) {
			return true;
		}
		return false;
	}

	public function getObj($obj, $index = '0') {
		if (is_object($obj))
			$objName = get_class($obj);
		else
			$objName = $obj;
		if (self::$objs[$objName])
			$object = self::$objs[$objName];
		elseif ($_SESSION[$objName]) {
			$object = serialize($_SESSION[$objName]);
		}
		return $object;
	}

	public function getSize($obj) {
		if (is_object($obj))
			$objName = get_class($obj);
		else
			$objName = $obj;
		if (self::$objs[$objName])
			$object = self::$objs[$objName];
		elseif ($_SESSION[$objName]) {
			$object = $_SESSION[$objName];
		}
		return count($object);
	}

	public function remove($obj) {
		if (is_object($obj))
			$objName = get_class($obj);
		else
			$objName = $obj;
		unset(self::$objs[$objName]);
		if(isset($_SESSION) && isset($_SESSION[$objName])){
			unset($_SESSION[$objName]);
		}		
	}

	public function setScope($scope) {
		$this->scope = $scope;
	}

	public function getScope() {
		return $this->scope;
	}

}
