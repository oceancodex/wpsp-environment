<?php

namespace WPSPCORE\Environment;

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;

class Environment {

	public static function load($envDir) {
		if (method_exists(Dotenv::class, 'createImmutable')) {
			$dotEnv = Dotenv::createImmutable($envDir);
		}
		else {
			$repository = RepositoryBuilder::createWithNoAdapters()/*->addAdapter(EnvConstAdapter::class)->addWriter(PutenvAdapter::class)*/->immutable()->make();
			$dotEnv     = Dotenv::create($repository, $envDir);
		}
		$dotEnv->safeLoad();
		$dotEnv->required([])->allowedValues(['local', 'dev', 'production'])->notEmpty();
	}

	public static function get(string $varName, $default = '') {
		return env($varName) ?: getenv($varName) ?: $_SERVER[$varName] ?? ($_ENV[$varName] ?? $default);
	}

}
