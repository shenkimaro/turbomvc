<?php

namespace library\ueg\validators;

use library\ueg\tools\DateTimeLbr;

/**
 *
 * @author ibanez
 */
class DateValidator {

	/**
	 * 
	 * @param string $greaterDate Y-m-d
	 * @param string $lessDate Y-m-d
	 * @return bool
	 */
	public static function isGreaterOrEqual(string $greaterDate, string $lessDate): bool {
		$diff = DateTimeLbr::getInterval($greaterDate, $lessDate);
		return $diff->invert == 0 && $diff->days >= 0;
	}

	/**
	 * 
	 * @param string $greaterDate Y-m-d
	 * @param string $lessDate Y-m-d
	 * @return bool
	 */
	public static function isGreater(string $greaterDate, string $lessDate): bool {
		$diff = DateTimeLbr::getInterval($greaterDate, $lessDate);
		return $diff->invert == 0 && $diff->days > 0;
	}

}
