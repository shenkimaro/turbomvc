<?php
/**
 * @deprecated Use the DTO Class
 */
abstract class VO {
	private $schemaName;
	private $tableName;
	private $pkName;
	private $fkName;
	private $objFkName;
	/**
	 * Mapeamento propriedade objeto tabela
	 *
	 * @var array
	 */
	private $properties;

	private $methodsToBd;
	/**
	 * Campo da tabela com sua obrigatoriedade
	 *
	 * @var array
	 */
	private $tablesFields;

	function __construct() {
		$this->methodsMap();
	}

	protected function setSchemaName($val) {
		$this->schemaName = $val;
	}

	protected function setTableName($val) {
		$this->tableName=$val;
	}

	public function getTableName() {
		return $this->tableName;
	}

	public function getSchemaName() {
		return $this->schemaName;
	}

	public function setPkName($var) {
		$this->pkName=$var;
	}

	public function addPropertyMap($propObj,$propTable) {
		$this->properties[$propObj]=$propTable;
	}
	/**
	 * Faz o mapeamento dos metodos do VO
	 *
	 * @param string $method
	 * @param string $propTable
	 */
	public function addMethodsToBdMap($method,$propTable,$null=true) {
		$this->methodsToBd[trim($method)]=trim($propTable);
		if ($null)	$this->tablesFields[trim($propTable)] = "null";
		else $this->tablesFields[trim($propTable)] = "notnull";
	}

	private function getFieldRestriction($field='') {
		return @$this->tablesFields[trim($field)];
	}

	protected function methodsMap() {

	}

	public function getPropertyByMethod($method) {
		return $this->methodsToBd[$method];
	}

	public function getMethodByProperty($property) {
		$array = array_flip($this->methodsToBd);
		return isset($array[trim($property)]) ? $array[trim($property)] : '';
	}

	public function getMethodByObjName($objName) {
		return @$this->getMethodByProperty($objName);
	}

	public function getMethodByObjProperty($property) {
		//echo $property."<br>";
		//$array=array_flip($this->objFkName);
		//Util::Debug($array);
		//return @$array[$property];
		return @$this->getMethodByProperty($this->objFkName[$property]);
	}

	public function getMethodsMap() {
		return $this->methodsToBd;
	}

	public function getProperties() {
		return $this->properties;
	}

	public function getProperty($key) {
		return $this->properties[$key];
	}

	public function setFields($array) {		
		foreach ($array as $key=>$value) {
			if (!isset($this->properties[$key])){
				$this->properties[$key]=$key;
			}
		}
	}

	public function getPkName() {
		return $this->pkName;
	}

	public function setFkName($obj,$fk) {
		if (is_object($obj))$objName=get_class($obj);
		else $objName=$obj;
//		$objName=strtolower($objName);
//		$this->fkName[]=$objName;
		$this->fkName[$objName]['name']=$fk;
		$this->objFkName[$objName]=$fk;
	}
	/**
	 * Procura o ref_da_tabela relacionada ao objeto passado
	 *
	 * @param Mixed $obj
	 * @return string
	 * @deprecated
	 * @see getFkNameWithOutDefault
	 */
	public function getFkName($obj) {
		if (is_object($obj))$objName=get_class($obj);
		else $objName=$obj;
		return $this->fkName[$objName]['name']?$this->fkName[$objName]['name']:"ref_".$objName;
	}

	public function getFkNameWithOutDefault($obj) {
		if (is_object($obj))$objName=get_class($obj);
		else $objName=$obj;
		return $this->fkName[$objName]['name'];
	}

	public function getObjByFkName($fk_name) {
		$array=$this->fkName;
		if (is_array($array))
			foreach ($array as $key => $value) {
				foreach ($value as $k => $v) {
					if (trim($v)==trim($fk_name)) {
						return $key;
					}
				}
			}
		return '';
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
	public function setVO($res = '') {
		$VO = &$this;
		$fkObj = null;
		$fields = $this->tablesFields;
		
		foreach ($fields as $key => $val) {
			
			$value = isset($res[$key]) ? $res[$key] : '';
			if (!$this->valueExists($key, $value)) {
				continue;
			}

			if (($this->getFieldRestriction($key) == 'notnull') && (trim($value) == '')) {
				throw new Exception($key . " vazio");
			}
			
			if ($VO->getMethodByProperty($key)) {
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
		return $VO;
	}

	private function valueExists($key, $value): bool {
		$fieldRestriction = $this->getFieldRestriction($key);
		$isNotNull = $fieldRestriction === 'notnull';
        $isValueArray = is_array($value);
		$isValueNotEmpty = $value !== null && !$isValueArray && trim($value) !== '';

		return ($isNotNull || $isValueNotEmpty || $isValueArray);
	}

	abstract public function __get($name);

}
