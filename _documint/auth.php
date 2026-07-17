<?php

define('DOCUMINT_SESSION_LIFETIME_SECONDS', 60 * 60);
define('DOCUMINT_SESSION_AUTHENTICATED_AT_KEY', 'documint_authenticated_at');
define('DOCUMINT_SESSION_CREDENTIAL_KEY', 'documint_credential_fingerprint');
define('DOCUMINT_SESSION_CSRF_KEY', 'documint_csrf_token');

/*
Start the short-lived web session before any HTML is written.
*/
function initialize_documint_web_session()
{
	if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE)
	{
		return;
	}

	$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
	ini_set('session.use_strict_mode', '1');
	ini_set('session.cookie_httponly', '1');
	ini_set('session.cookie_samesite', 'Strict');
	session_set_cookie_params(DOCUMINT_SESSION_LIFETIME_SECONDS, '/', '', $secure, true);
	session_start();
}

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
Create a fingerprint so changing the configured credentials invalidates sessions.
*/
function get_documint_credential_fingerprint()
{
	load_documint_config();
	return hash('sha256', (string)DOCUMINT_AUTH_ID . "\0" . (string)DOCUMINT_AUTH_PASSWORD);
}

/*
Check whether this browser has a valid, unexpired generation session.
*/
function is_web_generation_authenticated()
{
	initialize_documint_web_session();
	if (!isset($_SESSION[DOCUMINT_SESSION_AUTHENTICATED_AT_KEY], $_SESSION[DOCUMINT_SESSION_CREDENTIAL_KEY]))
	{
		return false;
	}

	$authenticatedAt = (int)$_SESSION[DOCUMINT_SESSION_AUTHENTICATED_AT_KEY];
	if ($authenticatedAt <= 0 || time() - $authenticatedAt >= DOCUMINT_SESSION_LIFETIME_SECONDS)
	{
		clear_web_generation_authentication();
		return false;
	}

	if (!hash_equals(get_documint_credential_fingerprint(), (string)$_SESSION[DOCUMINT_SESSION_CREDENTIAL_KEY]))
	{
		clear_web_generation_authentication();
		return false;
	}

	return true;
}

/*
Remember successful web authentication for one hour.
*/
function remember_web_generation_authentication()
{
	initialize_documint_web_session();
	session_regenerate_id(true);
	$_SESSION[DOCUMINT_SESSION_AUTHENTICATED_AT_KEY] = time();
	$_SESSION[DOCUMINT_SESSION_CREDENTIAL_KEY] = get_documint_credential_fingerprint();
	$_SESSION[DOCUMINT_SESSION_CSRF_KEY] = bin2hex(random_bytes(32));
}

/*
Remove generation authentication from the current session.
*/
function clear_web_generation_authentication()
{
	initialize_documint_web_session();
	unset(
		$_SESSION[DOCUMINT_SESSION_AUTHENTICATED_AT_KEY],
		$_SESSION[DOCUMINT_SESSION_CREDENTIAL_KEY],
		$_SESSION[DOCUMINT_SESSION_CSRF_KEY]
	);
}

/*
Return the CSRF token used by authenticated generation and logout forms.
*/
function get_web_generation_csrf_token()
{
	initialize_documint_web_session();
	if (!isset($_SESSION[DOCUMINT_SESSION_CSRF_KEY]))
	{
		$_SESSION[DOCUMINT_SESSION_CSRF_KEY] = bin2hex(random_bytes(32));
	}
	return $_SESSION[DOCUMINT_SESSION_CSRF_KEY];
}

/*
Reject forged requests made against an authenticated browser session.
*/
function require_web_generation_csrf_token()
{
	$providedToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
	if ($providedToken === '' || !hash_equals(get_web_generation_csrf_token(), $providedToken))
	{
		throw new RuntimeException('Invalid or expired request token. Please reload the page.');
	}
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
	if (is_web_generation_authenticated())
	{
		require_web_generation_csrf_token();
		return;
	}

	$id = isset($_POST['auth_id']) ? $_POST['auth_id'] : '';
	$password = isset($_POST['auth_password']) ? $_POST['auth_password'] : '';
	if (!authenticate_generation_request($id, $password))
	{
		throw new RuntimeException('Invalid Documint ID or password.');
	}

	remember_web_generation_authentication();
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

