<?php

namespace library\ueg\tools;

use DateInterval;
use DateTime;

/**
 *
 * @author ibanez
 */
class DateTimeLbr {

	public static function getInterval(string $initialdate, string $endDate): DateInterval {
		$dateTimeIni = new DateTime($endDate);
		return $dateTimeIni->diff(new DateTime($initialdate));
	}

}
