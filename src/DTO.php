<?php

class DTO {

	const NULL = 'null';
	const EMPTY_STRING = 'empty';

	private $errors;

	function __construct($array = null) {
		if (is_array($array)) {
			$this->setDTO($array);
		} else {
			$this->setDTO($_REQUEST);
		}
	}

	public function getSchemaName() {
		$r = new ReflectionClass(get_class($this));
		$comment = $r->getDocComment();
		if ((!preg_match("#@schemaName\s+([a-z_0-9]+)#", $comment, $a)))
			throw new Exception("DTO nao tem o nome do schema use @schemaName");
		return $a[1];
	}

	public function getTableName() {
		$r = new ReflectionClass(get_class($this));
		$comment = $r->getDocComment();
		if ((!preg_match("#@tableName\s+([a-z_0-9]+)#", $comment, $a)))
			throw new Exception("DTO nao tem o nome da tabela use @tableName");
		return $a[1];
	}

	public function getErrors() {
		return $this->errors;
	}

	public function getErrorsAsString() {
		return join(', ', $this->errors);
	}

	public function addError($error) {
		if (!$this->errors) {
			$this->errors = array();
		}
		$this->errors[] = $error;
	}

	public function hasErrors() {
		return false;
	}

	/**
	 * Seta as propriedades do objeto a partir do array, considerando apenas as chaves do array que correspondam a uma
	 * propriedade do DTO
	 * @param $array Array com os dados do objeto
	 *
	 * TODO - PHP version >= 5.3
	 */
//	public function fromArray($array) {
//		if (is_array($array)) {
//			$r = new ReflectionObject($this);
//
//			foreach ($array as $key => $value) {
//				if (property_exists($this, $key)) {
//					$p = $r->getProperty($key);
//					$p->setAccessible(true);
//					$p->setValue($this, $value);
//				}
//			}
//		}
//	}
	//************************************************************************************************************************\\

	/**
	 * Retorna o esquema.nome_tabela
	 * @return string
	 */
	public function getTableSchemaName() {
		return $this->getSchemaName() . "." . $this->getTableName();
	}

	//************************************************************************************************************************\\
	public function getPkName() {
		$r = new ReflectionClass(get_class($this));
		$comment = $r->getDocComment();
		if ((!preg_match("#@pkName\s+([a-z_]+)#", $comment, $a)))
			throw new Exception("DTO nao tem o nome da chave primaria da tabela use @pkName");
		return $a[1];
	}

	//************************************************************************************************************************\\

	/**
	 * Seta o DTO baseado em um array de colunas
	 * @param array $record
	 * @return DTO
	 */
	public function setDTO($record = array()) {
		foreach ($record as $key => $val) {
			$set = $this->getMethodSetByProperty($key);
			if ($set == '' || ((is_string($val) && trim($val) == '') || (is_array($val) && count($val) == 0))) {
                continue;
			}			
			try {
				$type = $this->getMethodType($set);
				if ($this->isValid($set, $val, $type)){
					$this->$set($val);
				}
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}
		return $this;
	}

	/**
	 * Seta a string 'null' em todos os atributos que estão efetivamente null no objeto, desconsiderando apenas a primaryKey.
	 * Com isso o TDG é capaz de construir as SQLs necessárias para campos que devem ser NULL.
	 * @return $this
	 */
	public function nullifyEmptyFields() {
		$r = new ReflectionObject($this);
		$fields = $r->getProperties();
		foreach ($fields as $field) {

			//desconsidera a primaryKey
			$pk_name = $this->getPkName();
			if ($pk_name == $field->getName()) {
				continue;
			}

			$set = $this->getMethodSetByProperty($field->getName());
			$get = $this->getMethodGetByProperty($field->getName());

			if (trim($set) == '' || trim($get) == '') {
				continue;
			}
			$val = $this->$get();
			if ($val == null) {
				$this->$set('null');
			}
		}
		return $this;
	}

	//************************************************************************************************************************\\

	/**
	 *
	 * @param string $propertyName
	 * @return string
	 */
	private function hasProperty($propertyName) {
		$r = new ReflectionObject($this);
		return $r->hasProperty($propertyName);
	}

	//************************************************************************************************************************\\
	private function hasMethod($methodName) {
		$r = new ReflectionObject($this);
		try {
			//Validacao de case sensitive
			$name = $r->getMethod($methodName);
			if ($name->getName() != $methodName)
				return false;
		} catch (Exception $e) {
			return false;
		}
		return $r->hasMethod($methodName);
	}

	//************************************************************************************************************************\\
	private function getPropertyType($propertyName) {
		$o = new ReflectionObject($this);

		$p = $o->getProperty($propertyName);

		$dc = $p->getDocComment();
		if (!preg_match("#@var\s+([a-z]+)#", $dc, $a))
			return false;
		return $a[1];
	}

	//************************************************************************************************************************\\
	public function getMethodType($methodName) {
		$o = new ReflectionObject($this);

		$p = $o->getMethod($methodName);

		$dc = $p->getDocComment();


		if ((!preg_match("#@param\s+([a-z]+)#", $dc, $a)) && (!preg_match("#@return\s+([a-z]+)#", $dc, $a)))
			return false;
		return $a[1];
	}

	//************************************************************************************************************************\\

	/**
	 * Retorna o nome do campo na tabela do bd
	 * @param string $methodName
	 * @return string
	 */
	public function getMethodTableField($methodName) {
		try {
			$o = new ReflectionObject($this);

			$p = $o->getMethod($methodName);

			$dc = $p->getDocComment();

			if (!preg_match("#@tableField\s+([a-z0-9_]+)#", $dc, $a)) {
				return false;
			}
			return $a[1];
		} catch (ReflectionException $exc) {
			$className = get_class($this);
			Debug::tail("DTO: " . $className . ". Método: $methodName. Erro: " . $exc->getMessage());
		}
	}

	//************************************************************************************************************************\\

	/**
	 * Retorna o nome do metodo de acordo com a propriedade
	 * @param string $property
	 * @return string
	 */
	public function getMethodByProperty($property) {
		$r = new ReflectionObject($this);
		$methods = $r->getMethods();
		foreach ($methods as $key => $value) {
			$field = $this->getMethodTableField($value->getName());
			if (trim($field) == trim($property))
				return $value->getName();
		}
		return "";
	}
    
	/**
	 * Alias para getMethodTableField
	 * @param string $methodName
	 * @return string
	 */
	public function getPropertyByMethodName($methodName) {
        return $this->getMethodTableField($methodName);
	}
    
	/**
	 * Retorna o valor de uma determinada propriedade do DTO
	 * @param string $propertyName
	 * @return string
	 */
	public function getPropertyValue($propertyName) {
		$r = new ReflectionObject($this);
		$property = $r->getProperty($propertyName);
		$property->setAccessible(true);
		return $property->getValue($this);
	}

	//************************************************************************************************************************\\

	/**
	 * Retorna o nome do metodo setter de acordo com a propriedade
	 * @param string $property
	 * @return string
	 */
	public function getMethodSetByProperty($property) {
		$r = new ReflectionObject($this);
		$methods = $r->getMethods();
		foreach ($methods as $key => $value) {
			$methodName = $value->getName();
			if ($methodName[0] != 's')
				continue;
			$field = $this->getMethodTableField($value->getName());
			if (trim($field) == trim($property))
				return $value->getName();
		}
		return "";
	}

	//************************************************************************************************************************\\

	/**
	 * Retorna o nome do metodo getter de acordo com a propriedade
	 * @param string $property
	 * @return string
	 */
	public function getMethodGetByProperty($property) {
		$r = new ReflectionObject($this);
		$methods = $r->getMethods();
		foreach ($methods as $key => $value) {
			$methodName = $value->getName();
			if ($methodName[0] == 's')
				continue;
			$field = $this->getMethodTableField($value->getName());
			if (trim($field) == trim($property))
				return $value->getName();
		}
		return "";
	}

	//************************************************************************************************************************\\
	public function __get($propertyName) {
		if (!$this->hasProperty($propertyName))
			throw new Exception(sprintf("'%s' nao tem propriedade '%s'", get_class($this), $propertyName));
        
        $property = new ReflectionProperty($this, $propertyName);
        $property->setAccessible(true);
		if (empty($property->getValue($this))){
			return NULL;
        }
        
		return $property->getValue($this);
	}

	//************************************************************************************************************************\\
	public function __set($propertyName, $value) {
		if (!$this->hasProperty($propertyName))
			throw new Exception(sprintf("'%s' nao tem a propridade '%s'", get_class($this), $propertyName));

		if (!($type = $this->getPropertyType($propertyName)))
			throw new Exception(sprintf("'%s'.'%s' has no type set", get_class($this), $propertyName));

		if (!$this->isValid($propertyName, $value, $type))
			throw new Exception(sprintf("%s->%s = '%s' nao eh valido para '%s'", get_class($this), $propertyName, $value, $type));

		$this->$k = $value;
	}

	//************************************************************************************************************************\\
	public function __call($methodName, $arguments) {
		if (!isset($arguments[0])) {
			$v = null;
		} else {
			$v = $arguments[0];
		}
		if (!$this->hasMethod($methodName))
			throw new Exception(get_class($this) . " nao tem o metodo $methodName<br>");

		if (!($type = $this->getMethodType($methodName)))
			throw new Exception(get_class($this) . "->$methodName nao tem tipo");

		if (strtolower(mb_substr($methodName, 0, 3)) == 'set') {
			if (!$this->isValid($methodName, $v, $type))
				throw new Exception(sprintf("%s->%s = '%s' nao eh valido para '%s'", get_class($this), $methodName, $v, $type));

			if (count($arguments) == 1)
				$this->$k($arguments[0]);
			else if (count($arguments) == 2)
				$this->$k($arguments[0], $arguments[1]);
			else
				throw new Exception("Somente sao aceitos ate 2 parametros para os DTOs");
		} elseif ((strtolower(mb_substr($methodName, 0, 3)) == 'get') || (strtolower(mb_substr($methodName, 0, 2)) == 'is'))
			return $this->$k();
	}

	//************************************************************************************************************************\\

	/**
	 * Usado para validar de acordo com o tipo @var
	 * @param string $key
	 * @param string $value
	 * @param string $type
	 * @return boolean
	 */
	public function isValid($key, $value, $type) {
		if (!isset($value) || is_null($value)) {
			return false;
		}
		switch ($type) {
			case "int":
			case "integer":
			case "timestamp":
				if (is_numeric($value) || strtolower($value) == 'null')
					return true;
				throw new Exception(" o valor '$value' deve ser numerico");
				break;
			case "date":
				if (Validador::ehData($value) || strtolower($value) == 'null')
					return true;
				throw new Exception(" o valor '$value' deve ser uma data valida no formato 99/99/9999 ou 9999-99-99");
				break;
			case "cpf":
				if (Validador::ehCpf($value) || strtolower($value) == 'null')
					return true;
				throw new Exception(" o valor '$value' deve ser um CPF valido");
				break;
			case "string":
			case "mixed":
				return true;
			default:
				throw new Exception(sprintf("'%s'.'%s' nao tem um tipo valido: '%s'", get_class($this), $key, $type));
		}
	}

}

