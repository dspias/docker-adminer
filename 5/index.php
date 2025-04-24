<?php

namespace docker {
	function adminer_object()
	{
		/**
		 * Prefills login form fields with environment variables.
		 */
		final class DefaultLoginPlugin extends \Adminer\Plugin
		{
			public function __construct(
				private \Adminer\Adminer $adminer
			) {}

			public function loginFormField(...$args)
			{
				return (function (...$args) {
					$field = $this->loginFormField(...$args);
					$name = $args[0] ?? '';

					$defaults = [
						'driver' => $_ENV['ADMINER_DEFAULT_DRIVER'] ?? 'server',
						'server' => $_ENV['ADMINER_DEFAULT_SERVER'] ?? 'mysql',
						'username' => $_ENV['ADMINER_DEFAULT_USERNAME'] ?? '',
						'password' => $_ENV['ADMINER_DEFAULT_PASSWORD'] ?? '',
						'db' => $_ENV['ADMINER_DEFAULT_DB'] ?? '',
					];

					if ($name === 'driver') {
						$field = preg_replace(
							'/<option value="' . preg_quote($defaults['driver'], '/') . '"/',
							'<option value="' . $defaults['driver'] . '" selected',
							$field
						);
					} elseif (isset($defaults[$name])) {
						$value = htmlspecialchars($defaults[$name]);
						$field = str_replace(
							'name="auth[' . $name . ']" value=""',
							'name="auth[' . $name . ']" value="' . $value . '"',
							$field
						);
					}

					return $field;
				})->call($this->adminer, ...$args);
			}
		}

		$plugins = [];
		foreach (glob('plugins-enabled/*.php') as $plugin) {
			$plugins[] = require($plugin);
		}

		$adminer = new \Adminer\Plugins($plugins);

		(function () {
			$last = &$this->hooks['loginFormField'][\array_key_last($this->hooks['loginFormField'])];
			if ($last instanceof \Adminer\Adminer) {
				$defaultLoginPlugin = new DefaultLoginPlugin($last);
				$this->plugins[] = $defaultLoginPlugin;
				$last = $defaultLoginPlugin;
			}
		})->call($adminer);

		return $adminer;
	}
}

namespace {
	if (basename($_SERVER['DOCUMENT_URI'] ?? $_SERVER['REQUEST_URI']) === 'adminer.css' && is_readable('adminer.css')) {
		header('Content-Type: text/css');
		readfile('adminer.css');
		exit;
	}

	function adminer_object()
	{
		return \docker\adminer_object();
	}

	require('adminer.php');
}
