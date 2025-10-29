<?php

namespace WPSPCORE\Environment;

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;

class Environment {

	/**
	 * Cache các biến môi trường đã load
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Flag để biết đã load chưa
	 * @var bool
	 */
	protected static $loaded = false;

	/**
	 * Dotenv version flag
	 * @var bool|null
	 */
	private static $hasImmutableMethod = null;

	/**
	 * Load environment variables
	 */
	public static function initEnvironment($envDir) {
		// Tránh load lại nhiều lần
		if (self::$loaded) {
			return;
		}

		// Cache method check
		if (self::$hasImmutableMethod === null) {
			self::$hasImmutableMethod = method_exists(Dotenv::class, 'createImmutable');
		}

		// Load dotenv
		if (self::$hasImmutableMethod) {
			$dotEnv = Dotenv::createImmutable($envDir);
		}
		else {
			$repository = RepositoryBuilder::createWithNoAdapters()
				->immutable()
				->make();
			$dotEnv     = Dotenv::create($repository, $envDir);
		}

		$dotEnv->safeLoad();

		// Validate nếu cần (có thể comment để tăng tốc độ)
		// $dotEnv->required([])->allowedValues(['local', 'dev', 'production'])->notEmpty();

		self::$loaded = true;
		self::initializeCache();
	}

	/**
	 * Initialize cache từ các nguồn
	 */
	private static function initializeCache() {
		if (self::$cache !== null) {
			return;
		}

		// Merge tất cả sources vào cache một lần
		self::$cache = array_merge(
			$_ENV ?? [],
			$_SERVER ?? [],
			getenv() ?: []
		);
	}

	/**
	 * Get environment variable với caching
	 */
	public static function get(string $varName, $default = '') {
		// Khởi tạo cache nếu chưa có
		if (self::$cache === null) {
			self::initializeCache();
		}

		// Kiểm tra trong cache trước
		if (isset(self::$cache[$varName])) {
			return self::$cache[$varName];
		}

		// Fallback: kiểm tra các nguồn khác (cho biến được set sau khi load)
		if (function_exists('env')) {
			$value = env($varName);
			if ($value !== null && $value !== false) {
				self::$cache[$varName] = $value;
				return $value;
			}
		}

		// Kiểm tra getenv
		$value = getenv($varName);
		if ($value !== false) {
			self::$cache[$varName] = $value;
			return $value;
		}

		// Kiểm tra $_SERVER
		if (isset($_SERVER[$varName])) {
			self::$cache[$varName] = $_SERVER[$varName];
			return $_SERVER[$varName];
		}

		// Kiểm tra $_ENV
		if (isset($_ENV[$varName])) {
			self::$cache[$varName] = $_ENV[$varName];
			return $_ENV[$varName];
		}

		return $default;
	}

	/**
	 * Clear cache (useful for testing)
	 */
	public static function clearCache() {
		self::$cache  = null;
		self::$loaded = false;
	}

	/**
	 * Set environment variable và update cache
	 */
	public static function set(string $varName, $value) {
		if (self::$cache === null) {
			self::initializeCache();
		}

		self::$cache[$varName] = $value;
		$_ENV[$varName]        = $value;
		$_SERVER[$varName]     = $value;
		putenv("$varName=$value");
	}

}