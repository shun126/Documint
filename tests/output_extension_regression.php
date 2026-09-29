<?php

require_once __DIR__ . '/../_documint/constants.php';
require_once __DIR__ . '/../_documint/parsedown/Parsedown.php';
require_once __DIR__ . '/../_documint/model.php';
require_once __DIR__ . '/../_documint/filesystem.php';
require_once __DIR__ . '/../_documint/category.php';
require_once __DIR__ . '/../_documint/markdown.php';
require_once __DIR__ . '/../_documint/renderer.php';
require_once __DIR__ . '/../_documint/generator.php';
require_once __DIR__ . '/../_documint/web.php';

function expect_equal($expected, $actual, $description)
{
	if ($expected !== $actual)
		throw new RuntimeException($description . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}

function expect_throws($callback, $description)
{
	try
	{
		$callback();
	}
	catch (RuntimeException $e)
	{
		return;
	}
	throw new RuntimeException($description . ': expected an exception');
}

$testRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'documint_regression_' . bin2hex(random_bytes(6));
$files = [];
$directories = [];
function make_directory($path)
{
	global $directories;
	if (!mkdir($path))
		throw new RuntimeException('Cannot create test directory: ' . $path);
	$directories[] = $path;
}
function make_file($path, $contents)
{
	global $files;
	if (file_put_contents($path, $contents) === false)
		throw new RuntimeException('Cannot create test file: ' . $path);
	$files[] = $path;
}

try
{
	make_directory($testRoot);
	make_directory($testRoot . '/docs');
	make_directory($testRoot . '/guide');
	make_file($testRoot . '/template.html', '<html><title>{{title}}</title>{{body}}{{sidebar}}</html>');
	make_file($testRoot . '/foo.md', "# Root\n");
	make_file($testRoot . '/guide/foo.md', "{{output_extension php}}\n# Nested\n");
	make_file($testRoot . '/docs/My Page.md', "{{output_extension php}}\n# Encoded\n");
	make_file($testRoot . '/docs/other.md', "{{output_extension aspx}}\n# Other\n");
	$fences = "````text\n```php\n{{output_extension phtml}}\n````\n~~~text\n{{output_extension php}}\n~~~\n";
	make_file($testRoot . '/docs/fences.md', $fences);
	make_file($testRoot . '/docs/unsafe.md', "{{output_extension md}}\n# Unsafe\n");

	expect_equal('html', get_output_extension_from_markdown($testRoot . '/docs/fences.md'), 'metadata inside fenced code');
	$rendered = parse_md($testRoot . '/docs/fences.md', []);
	if (strpos($rendered, '{{output_extension phtml}}') === false || strpos($rendered, '{{output_extension php}}') === false)
		throw new RuntimeException('Fenced metadata examples disappeared from rendered code');
	expect_throws(function() use ($testRoot) { get_output_extension_from_markdown($testRoot . '/docs/unsafe.md'); }, 'source overwriting extension');
	unlink($testRoot . '/docs/unsafe.md');

	$pages = collect_markdown_pages($testRoot, '/Documint');
	expect_equal('/foo.html', rewrite_markdown_link_to_html('/foo.md', $testRoot . '/docs/fences.md', $testRoot . '/docs/fences.html', $pages), 'root page with duplicate basename');
	expect_equal('/guide/foo.php', rewrite_markdown_link_to_html('/guide/foo.md', $testRoot . '/docs/fences.md', $testRoot . '/docs/fences.html', $pages), 'nested root-relative link');
	expect_equal('/Documint/guide/foo.php', rewrite_markdown_link_to_html('/Documint/guide/foo.md', $testRoot . '/docs/fences.md', $testRoot . '/docs/fences.html', $pages), 'link including deployment base path');
	expect_equal('/docs/My%20Page.php', rewrite_markdown_link_to_html('/docs/My%20Page.md', $testRoot . '/docs/fences.md', $testRoot . '/docs/fences.html', $pages), 'URL-encoded root-relative source path');
	expect_equal('My%20Page.php', rewrite_markdown_link_to_html('My%20Page.md', $testRoot . '/docs/fences.md', $testRoot . '/docs/fences.html', $pages), 'URL-encoded relative source path');

	$siteMapPage = new PageInfomation('Sitemap', '/Documint/sitemap.xml', $testRoot . '/sitemap.md', $testRoot . '/sitemap.xml', []);
	expect_throws(function() use ($testRoot, $siteMapPage) { validate_unique_page_output_paths([$siteMapPage], $testRoot); }, 'reserved sitemap output');
	expect_throws(function() use ($testRoot, $siteMapPage) { generate_site_html([$siteMapPage], $testRoot, '/Documint', 'https://example.test'); }, 'reserved output rejected before generation');
	$sourcePage = new PageInfomation('Source', '/Documint/source.md', $testRoot . '/source.md', $testRoot . '/source.md', []);
	expect_throws(function() use ($testRoot, $sourcePage) { validate_unique_page_output_paths([$sourcePage], $testRoot); }, 'source and output collision');

	foreach ($pages as $page)
		$files[] = $page->getOutputFilePath();
	$files[] = $testRoot . '/sitemap.xml';
	$files[] = $testRoot . '/_page_list/index.html';
	$directories[] = $testRoot . '/_page_list';
	ob_start();
	try
	{
		generate_site_html($pages, $testRoot, '/Documint', 'https://example.test');
	}
	finally
	{
		ob_end_clean();
	}
	$sitemap = file_get_contents($testRoot . '/sitemap.xml');
	if (strpos($sitemap, '<loc>https://example.test/Documint/docs/other.aspx</loc>') === false)
		throw new RuntimeException('Custom output extension missing from sitemap');

	$repositoryPages = collect_markdown_pages(dirname(__DIR__), '', 'readme-index');
	$foundFeatureReference = false;
	foreach ($repositoryPages as $page)
	{
		if (str_replace('\\', '/', $page->getSourceRelativePath()) === '/docs/feature-reference.md')
		{
			$foundFeatureReference = true;
			expect_equal('feature-reference.html', basename($page->getOutputFilePath()), 'documentation example is not metadata');
			break;
		}
	}
	if (!$foundFeatureReference)
		throw new RuntimeException('Feature reference page was not collected');

	echo "Output extension regressions passed\n";
}
finally
{
	foreach (array_reverse($files) as $file)
	{
		if (is_file($file))
			unlink($file);
	}
	foreach (array_reverse($directories) as $directory)
	{
		if (is_dir($directory))
			rmdir($directory);
	}
}
