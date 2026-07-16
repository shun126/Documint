<?php

/*
Get the Documint configuration file path.
*/
function get_documint_config_path()
{
	return __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
}

/*
Load and validate the Documint configuration file.
*/
function load_documint_config()
{
	$configPath = get_documint_config_path();
	if (!is_file($configPath))
	{
		throw new RuntimeException("Documint configuration file is missing. Create '_documint/config.php'.");
	}

	require_once $configPath;

	if (!defined('DOCUMINT_AUTH_ID') || !defined('DOCUMINT_AUTH_PASSWORD'))
	{
		throw new RuntimeException("Documint configuration must define DOCUMINT_AUTH_ID and DOCUMINT_AUTH_PASSWORD.");
	}
}

/*
Verify the provided generation credentials.
*/
function authenticate_generation_request($id, $password)
{
	load_documint_config();

	return hash_equals((string)DOCUMINT_AUTH_ID, (string)$id)
		&& hash_equals((string)DOCUMINT_AUTH_PASSWORD, (string)$password);
}

/*
Require valid generation credentials before running from the command line.
*/
function require_cli_generation_authentication()
{
	$id = get_cli_option_value('id', '');
	$password = get_cli_option_value('password', '');
	if (!authenticate_generation_request($id, $password))
	{
		throw new RuntimeException('Invalid Documint ID or password.');
	}
}

/*
Require valid generation credentials before running from the web form.
*/
function require_web_generation_authentication()
{
	$id = isset($_POST['auth_id']) ? $_POST['auth_id'] : '';
	$password = isset($_POST['auth_password']) ? $_POST['auth_password'] : '';
	if (!authenticate_generation_request($id, $password))
	{
		throw new RuntimeException('Invalid Documint ID or password.');
	}
}
/*
Detect whether the configured generation password still uses the default value.
*/
function is_documint_default_password_configured()
{
	try
	{
		load_documint_config();
	}
	catch (Throwable $e)
	{
		return false;
	}

	return hash_equals('password', (string)DOCUMINT_AUTH_PASSWORD);
}

