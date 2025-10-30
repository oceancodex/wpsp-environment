<?php

namespace WPSPCORE\Environment;

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;

class Environment {

	public function __construct() {}

	/**
	 * Cache các biến môi trường đã load
	 * @var array|null
	 */
	public $cache = null;

	/**
	 * Dotenv version flag
	 * @var bool|null
	 */
	public $hasImmutableMethod = null;

	/**
	 * Load environment variables
	 */
	public function load($envDir) {
		// Cache method check
		if ($this->hasImmutableMethod === null) {
			$this->hasImmutableMethod = method_exists(Dotenv::class, 'createImmutable');
		}

		// Load dotenv
		if ($this->hasImmutableMethod) {
			$dotEnv = Dotenv::createImmutable($envDir);
		}
		else {
			$repository   = RepositoryBuilder::createWithNoAdapters()->immutable()->make();
			$dotEnv = Dotenv::create($repository, $envDir);
		}
		$dotEnv->safeLoad();

		// Validate nếu cần (có thể comment để tăng tốc độ)
		// $this->dotEnv->required([])->allowedValues(['local', 'dev', 'production'])->notEmpty();

		// Cache.
		$this->cache();

		return $this;
	}

	/**
	 * Initialize cache từ các nguồn
	 */
	public function cache() {
		if ($this->cache !== null) {
			return;
		}

		// Merge tất cả sources vào cache một lần
		$this->cache = array_merge(
			$_ENV ?? [],
			$_SERVER ?? [],
			getenv() ?: []
		);
	}

	/**
	 * Get environment variable với caching
	 */
	public function get(string $varName, $default = '') {
		// Khởi tạo cache nếu chưa có
		if ($this->cache === null) {
			$this->cache();
		}

		// Kiểm tra trong cache trước
		if (isset($this->cache[$varName])) {
			return $this->cache[$varName];
		}

		// Fallback: kiểm tra các nguồn khác (cho biến được set sau khi load)
		if (function_exists('env')) {
			$value = env($varName);
			if ($value !== null && $value !== false) {
				$this->cache[$varName] = $value;
				return $value;
			}
		}

		// Kiểm tra getenv
		$value = getenv($varName);
		if ($value !== false) {
			$this->cache[$varName] = $value;
			return $value;
		}

		// Kiểm tra $_SERVER
		if (isset($_SERVER[$varName])) {
			$this->cache[$varName] = $_SERVER[$varName];
			return $_SERVER[$varName];
		}

		// Kiểm tra $_ENV
		if (isset($_ENV[$varName])) {
			$this->cache[$varName] = $_ENV[$varName];
			return $_ENV[$varName];
		}

		return $default;
	}

	/**
	 * Clear cache (useful for testing)
	 */
	public function clearCache() {
		$this->cache = null;
	}

	/**
	 * Set environment variable và update cache
	 */
	public function set(string $varName, $value) {
		if ($this->cache === null) {
			$this->cache();
		}

		$this->cache[$varName] = $value;
		$_ENV[$varName]        = $value;
		$_SERVER[$varName]     = $value;
		putenv("$varName=$value");
	}

}