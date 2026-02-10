<?php

namespace library\ueg\helpers;

class Cache {

	public static function disable() {
		defined('_LIBRARY_CACHE_ENABLED') OR define('_LIBRARY_CACHE_ENABLED', false);
		self::setLocation(System::isWindows() ? "nul" : "/dev/null");
	}

	public static function enable() {
		defined('_LIBRARY_CACHE_ENABLED') OR define('_LIBRARY_CACHE_ENABLED', true);
	}

	public static function setLocation($full_dir_path) {
		defined('_LIBRARY_CACHE_FOLDER') OR define('_LIBRARY_CACHE_FOLDER', $full_dir_path);
	}

	public static function isEnabled(): bool {
		return defined('_LIBRARY_CACHE_ENABLED') ? _LIBRARY_CACHE_ENABLED : false;
	}

}
