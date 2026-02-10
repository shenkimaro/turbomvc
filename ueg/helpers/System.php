<?php

namespace library\ueg\helpers;

class System {

	public const LINUX = "Linux";
	public const WINDOWS = "Windows";
	public const UNKNOWN = "Unknown";

	public static function isWindows(): bool {
		return PHP_OS_FAMILY == self::WINDOWS;
	}

	public static function isLinux(): bool {
		return PHP_OS_FAMILY == self::LINUX;
	}

}
