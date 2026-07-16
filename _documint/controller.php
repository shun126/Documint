<?php

/*
Normalize a requested generation mode.
*/
function normalize_generation_mode($mode)
{
	if ($mode === 'readme' || $mode === 'readme-index')
	{
		return 'readme-index';
	}

	return 'site';
}

/*
Get a command-line option value.
*/
function get_cli_option_value($optionName, $defaultValue = NULL)
{
	global $argv;

	if (!isset($argv) || !is_array($argv))
	{
		return $defaultValue;
	}

	$count = count($argv);
	for ($i = 1; $i < $count; $i += 1)
	{
		$argument = $argv[$i];
		$prefix = '--' . $optionName . '=';
		if (strpos($argument, $prefix) === 0)
		{
			return substr($argument, strlen($prefix));
		}
		if ($argument === '--' . $optionName && $i + 1 < $count)
		{
			return $argv[$i + 1];
		}
	}

	return $defaultValue;
}
/*
Build filesystem and URL context for web or CLI execution.
*/
function build_execution_context()
{
	$fileBasePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
	if ($fileBasePath === false)
	{
		throw new RuntimeException('Cannot resolve the Documint base path.');
	}

	if (PHP_SAPI === 'cli')
	{
		$rootUrl = get_cli_option_value('root-url', 'http://localhost');
		$networkBasePath = get_cli_option_value('base-path', '');
		$networkBasePath = '/' . trim($networkBasePath, '/');
		if ($networkBasePath === '/')
		{
			$networkBasePath = '';
		}

		return [
			'file_base_path' => $fileBasePath,
			'root_url' => rtrim($rootUrl, '/'),
			'network_base_path' => $networkBasePath,
		];
	}

	$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
	$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
	$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/_documint/index.php';
	$rootUrl = $scheme . '://' . $host;
	$currentUrl = $rootUrl . $requestUri;

	$path = parse_url($currentUrl, PHP_URL_PATH);
	$pathSegments = explode('/', trim($path, '/'));

	$networkBasePath = '';
	$segument_count = count($pathSegments);
	for ($i = 0; $i < $segument_count - 1; $i += 1)
	{
		$networkBasePath .= '/';
		$networkBasePath .= $pathSegments[$i];
	}

	return [
		'file_base_path' => $fileBasePath,
		'root_url' => $rootUrl,
		'network_base_path' => $networkBasePath,
	];
}

/*
Run the selected generation mode.
*/
function run_generation_mode($mode)
{
	$context = build_execution_context();
	$fileBasePath = $context['file_base_path'];
	$networkBasePath = $context['network_base_path'];
	$rootUrl = $context['root_url'];
	$normalizedMode = normalize_generation_mode($mode);
	$pages = collect_markdown_pages($fileBasePath, $networkBasePath, $normalizedMode);
	if ($normalizedMode === 'readme-index')
	{
		validate_unique_page_output_paths($pages);
	}

	generate_site_html($pages, $fileBasePath, $networkBasePath, $rootUrl);
}
////////////////////////////////////////////////////////////////////////////////
/*
Run Documint for CLI or web requests.
*/
function run_documint_controller()
{
	try {
		$requestedMode = normalize_generation_mode(PHP_SAPI === 'cli' ? get_cli_option_value('mode', 'site') : (isset($_POST['mode']) ? $_POST['mode'] : 'site'));
	
		if (PHP_SAPI !== 'cli')
		{
			render_generation_form($requestedMode);
		}
	
		if (PHP_SAPI === 'cli')
		{
			require_cli_generation_authentication();
			run_generation_mode($requestedMode);
		}
		else if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			require_web_generation_authentication();
			run_generation_mode($requestedMode);
		}
		if (PHP_SAPI === 'cli' && generation_error_occurred())
		{
			exit(1);
		}
	
	} catch(Throwable $e) {
		display_generation_error($e);
		if (PHP_SAPI === 'cli')
		{
			exit(1);
		}
	}
}
