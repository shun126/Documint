<?php

/*
Generate a relative link path.
*/
function build_relative_link_path($fromFilePath, $toFilePath)
{
	$fromDirectory = str_replace('\\', '/', dirname($fromFilePath));
	$toFilePath = str_replace('\\', '/', $toFilePath);
	$fromParts = explode('/', trim($fromDirectory, '/'));
	$toParts = explode('/', trim($toFilePath, '/'));
	if (count($fromParts) === 1 && $fromParts[0] === '')
	{
		$fromParts = [];
	}
	if (count($toParts) === 1 && $toParts[0] === '')
	{
		$toParts = [];
	}

	$index = 0;
	$max = min(count($fromParts), count($toParts));
	while ($index < $max && $fromParts[$index] === $toParts[$index])
	{
		$index += 1;
	}

	$relativeParts = array_fill(0, count($fromParts) - $index, '..');
	$relativeParts = array_merge($relativeParts, array_slice($toParts, $index));
	if (empty($relativeParts))
	{
		return './';
	}

	return implode('/', $relativeParts);
}

function is_external_link($href)
{
	return preg_match('/^[a-z][a-z0-9+.-]*:/i', $href) === 1 || strpos($href, '//') === 0 || strpos($href, '#') === 0;
}

function normalize_file_path($path)
{
	$path = str_replace('\\', '/', $path);
	$prefix = '';
	if (preg_match('/^[A-Za-z]:\//', $path) === 1)
	{
		$prefix = substr($path, 0, 3);
		$path = substr($path, 3);
	}
	else if (strpos($path, '/') === 0)
	{
		$prefix = '/';
		$path = substr($path, 1);
	}

	$parts = [];
	foreach (explode('/', $path) as $part)
	{
		if ($part === '' || $part === '.')
			continue;
		if ($part === '..')
		{
			if (!empty($parts) && end($parts) !== '..')
				array_pop($parts);
			else
				$parts[] = $part;
			continue;
		}
		$parts[] = $part;
	}

	return $prefix . implode('/', $parts);
}
/*
Prepare the _page_list directory.
*/
function prepare_page_list_directory($fileBasePath)
{
	$pageListDirectory = $fileBasePath . DIRECTORY_SEPARATOR . DOCUMINT_PAGE_LIST_DIR_NAME;
	if (file_exists($pageListDirectory) && !is_dir($pageListDirectory))
	{
		throw new RuntimeException("Cannot create directory. '" . $pageListDirectory . "' already exists.");
	}
	if (!file_exists($pageListDirectory) && !mkdir($pageListDirectory, 0777, true))
	{
		throw new RuntimeException("Cannot create directory. '" . $pageListDirectory . "'");
	}

	return $pageListDirectory;
}

////////////////////////////////////////////////////////////////////////////////
/**
/**
 * Validate a required input file before reading it.
 */
function validate_input_file($path, $description = 'Input file')
{
	if (!is_string($path) || trim($path) === '')
	{
		throw new RuntimeException($description . ' path is not specified.');
	}
	if (!file_exists($path))
	{
		throw new RuntimeException($description . " not found. '" . $path . "'");
	}
	if (!is_file($path))
	{
		throw new RuntimeException($description . " is not a file. '" . $path . "'");
	}
	if (!is_readable($path))
	{
		throw new RuntimeException($description . " is not readable. '" . $path . "'");
	}
}

function open_input_file($path, $description = 'Input file')
{
	validate_input_file($path, $description);
	$handle = @fopen($path, 'rt');
	if ($handle === false)
	{
		throw new RuntimeException($description . " cannot be opened. '" . $path . "'");
	}
	return $handle;
}

function write_output_file($path, $contents)
{
	$out = @fopen($path, 'wt');
	if ($out === false)
	{
		throw new RuntimeException("Cannot open output file. '" . $path . "'");
	}

	try
	{
		$length = strlen($contents);
		$written = 0;
		while ($written < $length)
		{
			$result = @fwrite($out, substr($contents, $written));
			if ($result === false || $result === 0)
			{
				throw new RuntimeException("Cannot write to output file. '" . $path . "'");
			}
			$written += $result;
		}
	}
	finally
	{
		fclose($out);
	}
}
/**
 * Search for template.html upward through parent directories.
 */
function recursive_resolve_template_path($path)
{
	$template_file_name = $path . DIRECTORY_SEPARATOR . 'template.html';
	if (file_exists($template_file_name) == true)
		return $template_file_name;

	$parent_path = dirname($path);
	if ($parent_path === $path)
		return NULL;

	return recursive_resolve_template_path($parent_path);
}

/**
 * Search for template.html upward through parent directories.
 * Fallback to the default template when no local template exists.
 */
function resolve_template_path($path)
{
	$template_file_name = recursive_resolve_template_path($path);
	if ($template_file_name != NULL)
		return $template_file_name;

	$template_file_name = __DIR__ . DIRECTORY_SEPARATOR . 'template.html';
	if (file_exists($template_file_name) == true)
		return $template_file_name;

	return NULL;
}

/**
 * Search for sidebar.md upward through parent directories.
 */
function recursive_resolve_sidebar_path($path)
{
	$sidebar_file_name = $path . DIRECTORY_SEPARATOR . 'sidebar.md';
	if (file_exists($sidebar_file_name) == true)
		return $sidebar_file_name;

	$parent_path = dirname($path);
	if ($parent_path === $path)
		return NULL;

	return recursive_resolve_sidebar_path($parent_path);
}

/**
 * Search for sidebar.md upward through parent directories.
 */
function resolve_sidebar_path($path)
{
	$sidebar_file_name = recursive_resolve_sidebar_path($path);
	if ($sidebar_file_name != NULL)
		return $sidebar_file_name;

	$sidebar_file_name = __DIR__ . DIRECTORY_SEPARATOR . 'sidebar.md';
	if (file_exists($sidebar_file_name ) == true)
		return $sidebar_file_name ;

	return NULL;
}

