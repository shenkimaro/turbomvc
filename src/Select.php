<?php

/**
 * $where = '';
 * Select::addRestriction($where, 'campo_tabela', 3);
 * Resultado: WHERE campo_tabela = 3
 * Select::addRestriction($where, 'campo_tabela', 2, Select::GTE);
 * Resultado: WHERE campo_tabela >= 2
 */
class Select {

	const EQUAL = 1;

	/**
	 * Greater Than
	 */
	const GT = 2;

	/**
	 * Less Than
	 */
	const LT = 3;

	/**
	 * Greater Than or Equal
	 */
	const GTE = 4;

	/**
	 * Less Than or Equal
	 */
	const LTE = 5;

	/**
	 * Formata o where retornando o valor para ser usado na sql
	 * @param string $where
	 * @param string $field
	 * @param string $value
	 * @param int $type EQUAL|GT|LT|GTE|LTE
	 * @return string
	 */
	public static function addRestriction(&$where, $field, $value, $type = self::EQUAL) {
		if (!isset($field) || $field == '') {
			return '';
		}
		self::setWhere($where);

		switch ($type) {
			case self::GT:
				$where .= self::greaterThan($field, $value);
				break;
			case self::LT:
				$where .= self::lessThan($field, $value);
				break;
			case self::GTE:
				$where .= self::greaterThanEqual($field, $value);
				break;
			case self::LTE:
				$where .= self::lessThanEqual($field, $value);
				break;
			default:
				$where .= self::equal($field, $value);
				break;
		}
	}

	/**
	 * Formata o where retornando o valor em between da sql
	 * @param string $where
	 * @param string $field
	 * @param string $valueStart
	 * @param string $valueEnd
	 * @return string
	 */
	public static function addBetween(&$where, $field, $valueStart, $valueEnd) {
		if (!isset($field) || $field == '') {
			return '';
		}
		self::setWhere($where);
		$tdg = TDG::getInstance();
		$where .= $field . " between '" . $tdg->escapeString($valueStart) . "' and '" . $tdg->escapeString($valueEnd) . "'";
	}

	/**
	 * 
	 * @param string $where
	 * @param string $value
	 * @param string $fieldStart
	 * @param string $fieldEnd
	 * @return string
	 */
	public static function addBetweenColumns(&$where, string $value, string $fieldStart, string $fieldEnd) {
		if (!isset($value) || $value == '') {
			return '';
		}
		self::setWhere($where);
		$tdg = TDG::getInstance();
		$where .= "'" . $tdg->escapeString($value) . "'" . " between $fieldStart and $fieldEnd ";
	}

	private static function equal($field, $value) {
		$tdg = TDG::getInstance();
		return $field . " = '" . $tdg->escapeString($value) . "'";
	}

	private static function greaterThan($field, $value) {
		$tdg = TDG::getInstance();
		return $field . " > '" . $tdg->escapeString($value) . "'";
	}

	private static function lessThan($field, $value) {
		$tdg = TDG::getInstance();
		return $field . " < '" . $tdg->escapeString($value) . "'";
	}

	private static function greaterThanEqual($field, $value) {
		$tdg = TDG::getInstance();
		return $field . " >= '" . $tdg->escapeString($value) . "'";
	}

	private static function lessThanEqual($field, $value) {
		$tdg = TDG::getInstance();
		return $field . " <= '" . $tdg->escapeString($value) . "'";
	}

	private static function setWhere(&$where) {
		if ($where && trim($where) != '') {
			$where .= ' AND ';
		} else {
			$where = ' WHERE ';
		}
	}

}

?>