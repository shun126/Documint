<?php

////////////////////////////////////////////////////////////////////////////////
/**
 * Open directories and collect Markdown page information.
 */
function gather_markdown_info_in_directory(&$pages, $networkBasePath, $fileBasePath, $dir, $mode = 'site')
{
	$current_dir = $fileBasePath . $dir . DIRECTORY_SEPARATOR;

	if ($dh = opendir($current_dir))
	{
		while (($file = readdir($dh)) !== false)
		{
			if ($file === '.' || $file === '..')
				continue;

			$path = $current_dir . $file;
			if (is_dir($path))
			{
				/*
                 * Ignore directories whose names begin with . or _.
                 * Ignore rs-vendor as well.
				 */
				if (
					$file !== '' &&
					$file !== 'rs-vendor' &&
					$file[0] !== '.' &&
					$file[0] !== '_' )
				{
					gather_markdown_info_in_directory($pages, $networkBasePath, $fileBasePath, $dir . DIRECTORY_SEPARATOR . $file, $mode);
				}
			}
			else
			{
                // Register .md files.
				$path_info = pathinfo($path);
				if (array_key_exists('extension', $path_info) && $path_info['extension'] === 'md')
				{
					try
					{
						$outputFileName = $path_info['filename'] . '.html';
						if ($mode === 'readme-index' && $file === 'README.md')
						{
							$outputFileName = 'index.html';
						}
						$outputFilePath = $path_info['dirname'] . DIRECTORY_SEPARATOR . $outputFileName;
						$network_path = $networkBasePath . $dir . '/' . $outputFileName;
						if (DIRECTORY_SEPARATOR === "\\")
						{
							$network_path = str_replace(DIRECTORY_SEPARATOR, "/", $network_path);
						}
						$title = get_title_from_markdown($path);
						$categories = get_categories_from_markdown($path);
						$pages[] = new PageInfomation($title, $network_path, $path, $outputFilePath, $categories);
					}
					catch (Throwable $e)
					{
						display_generation_error($e);
					}
				}
			}
		}

		closedir($dh);
	}
}
/**
 * Build HTML files from gathered Markdown page information.
 */
function build_html_from_markdown($pages)
{
	echo '<table class="table table-hover" width="100%">';

	echo '<thead>';
	echo '<th scope="col">Title</th><th scope="col">Network Path</th><th scope="col">File Path</th>';
	echo '</thead>';
	echo '<tbody>';

	foreach ($pages as $page)
	{
		echo "<tr>";
		echo "<td>" . $page->getTitle() . "</td><td><a href=\"" . $page->getNetworkPath() . "\">" . $page->getNetworkPath() . "</a></td><td>" . $page->getFilePath() . "</td>";
		echo "</tr>";

		try
		{
			$filepath = pathinfo($page->getFilePath());
			$outputHtmlPath = $page->getOutputFilePath();

            // Resolve and validate all input before creating the output file.
			$template_file_name = resolve_template_path($filepath['dirname']);
			validate_input_file($template_file_name, 'Template file');

			$sidebar_markdown_file_name = resolve_sidebar_path($filepath['dirname']);

			$html = parse_md($page->getFilePath(), $pages, $outputHtmlPath);

			$sidebar_html = '';
			if ($sidebar_markdown_file_name != NULL)
			{
				$sidebar_html = parse_md($sidebar_markdown_file_name, $pages, $outputHtmlPath);
			}
			$html = template($template_file_name, $page->getTitle(), $html, $sidebar_html);
			write_output_file($outputHtmlPath, $html);
		}
		catch (Throwable $e)
		{
			display_generation_error($e);
		}
	}

	echo '</tbody>';
	echo "</table>";
}

////////////////////////////////////////////////////////////////////////////////
/**
 * Open directories and collect HTML URLs.
 */
function gather_html_file_in_directory(&$urls, $rootUrl, $fileBasePath, $dir)
{
	$current_dir = $fileBasePath . $dir . DIRECTORY_SEPARATOR;

	if ($dh = opendir($current_dir))
	{
		while (($file = readdir($dh)) !== false)
		{
			if ($file === '.' || $file === '..')
				continue;

			$path = $current_dir . $file;
			if (is_dir($path))
			{
				/*
                 * Ignore directories whose names begin with . or _.
                 * Ignore rs-vendor as well.
				 */
				if (
					$file !== '' &&
					$file !== 'rs-vendor' &&
					$file[0] !== '.' &&
					$file[0] !== '_' )
				{
					gather_html_file_in_directory($urls, $rootUrl, $fileBasePath, $dir . DIRECTORY_SEPARATOR . $file);
				}
			}
			else
			{
				if ($file !== "template.html" && $file !== "sidebar.md")
				{
                    // Register .html files.
					$path_info = pathinfo($path);
					if (array_key_exists('extension', $path_info))
					{
						$extension = $path_info['extension'];
						if ($extension === 'html' || $extension === 'htm')
						{
							$network_path = $rootUrl . $dir . '/' . $path_info['basename'];
							if (DIRECTORY_SEPARATOR === "\\")
							{
								$network_path = str_replace(DIRECTORY_SEPARATOR, "/", $network_path);
							}
							$urls[] = $network_path;
						}
					}
				}
			}
		}

		closedir($dh);
	}
}

////////////////////////////////////////////////////////////////////////////////
/*
Generate the page list HTML.
*/
function generate_page_list_html($pages, $fileBasePath, $networkBasePath)
{
	$template_file_name = resolve_template_path($fileBasePath);
	validate_input_file($template_file_name, 'Template file');

	$pageListDirectory = prepare_page_list_directory($fileBasePath);
	$outputPath = $pageListDirectory . DIRECTORY_SEPARATOR . 'index.html';

	$html = "<h1>List of Pages</h1>";
	foreach ($pages as $page)
	{
		$html .= '<li><a href="' . $page->getNetworkPath() . '">' . $page->getTitle() . '</a></li>';
	}

	$sidebar_markdown_file_name = resolve_sidebar_path($fileBasePath);
	$sidebar_html = '';
	if ($sidebar_markdown_file_name != NULL)
	{
		$sidebar_html = parse_md($sidebar_markdown_file_name, $pages, $outputPath);
	}
	$html = template($template_file_name, 'List of Pages', $html, $sidebar_html);
	write_output_file($outputPath, $html);

	echo 'generate <a href="' . $networkBasePath . '/' . DOCUMINT_PAGE_LIST_DIR_NAME . '/index.html">index.html</a></br>';
}

/*
Generate category HTML pages.
*/
function generate_category_pages_html($pages, $fileBasePath, $networkBasePath)
{
	$pageListDirectory = prepare_page_list_directory($fileBasePath);
	$categories = build_category_page_map($pages);

	foreach ($categories as $category => $category_pages)
	{
		try
		{
		$categoryPageSourceDir = $fileBasePath;
		if (count($category_pages) > 0)
		{
			$categoryPageSourceDir = dirname($category_pages[0]->getFilePath());
		}

		$outputPath = $pageListDirectory . DIRECTORY_SEPARATOR . get_category_page_file_name($category);
		$template_file_name = resolve_template_path($categoryPageSourceDir);
		validate_input_file($template_file_name, 'Template file');
		$sidebar_markdown_file_name = resolve_sidebar_path($categoryPageSourceDir);
		$sidebar_html = '';
		if ($sidebar_markdown_file_name != NULL)
		{
			$sidebar_html = parse_md($sidebar_markdown_file_name, $pages, $outputPath);
		}

		$body = "<h1>Category: " . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . "</h1>";
		$body .= "<ul>";
		foreach ($category_pages as $page)
		{
			$body .= '<li><a href="' . $page->getNetworkPath() . '">' . htmlspecialchars($page->getTitle(), ENT_QUOTES, 'UTF-8') . '</a></li>';
		}
		$body .= "</ul>";
		$html = template($template_file_name, 'Category: ' . $category, $body, $sidebar_html);
		write_output_file($outputPath, $html);

		echo 'generate <a href="' . $networkBasePath . '/' . DOCUMINT_PAGE_LIST_DIR_NAME . '/' . get_category_page_file_name($category) . '">' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</a></br>';
		}
		catch (Throwable $e)
		{
			display_generation_error($e);
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
/*
Collect Markdown page metadata for the current site.
*/
function collect_markdown_pages($fileBasePath, $networkBasePath, $mode = 'site')
{
	$pages = [];
	gather_markdown_info_in_directory($pages, $networkBasePath, $fileBasePath, '', $mode);
	return $pages;
}

/*
Validate that Markdown pages do not resolve to the same HTML output.
*/
function validate_unique_page_output_paths($pages)
{
	$outputSources = [];
	foreach ($pages as $page)
	{
		$outputPath = normalize_file_path($page->getOutputFilePath());
		$outputKey = strtolower($outputPath);
		if (array_key_exists($outputKey, $outputSources))
		{
			throw new RuntimeException(
				"Multiple Markdown files resolve to the same HTML output. '" .
				$outputSources[$outputKey] . "' and '" . $page->getFilePath() .
				"' both output to '" . $page->getOutputFilePath() . "'."
			);
		}
		$outputSources[$outputKey] = $page->getFilePath();
	}
}

/*
Generate sitemap.xml from generated HTML files.
*/
function generate_sitemap_xml($fileBasePath, $networkBasePath, $rootUrl)
{
	$urls = [];
	gather_html_file_in_directory($urls, $rootUrl . $networkBasePath, $fileBasePath, '');
	usort($urls, function($a, $b)
	{
		return strlen($a) - strlen($b);
	});

	$sitemap  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
	foreach ($urls as $url)
	{
		$sitemap .= '<url>';
		$sitemap .= '<loc>' . $url . '</loc>';
		$sitemap .= '</url>';
		$sitemap .= "\n";
	}
	$sitemap .= '</urlset>';
	unset($urls);

	write_output_file($fileBasePath . '/sitemap.xml', $sitemap);
	echo 'generate <a href="' . $networkBasePath . '/sitemap.xml">sitemap.xml</a></br>';
}

/*
Generate all standard Documint site outputs.
*/
function generate_site_html($pages, $fileBasePath, $networkBasePath, $rootUrl)
{
	build_html_from_markdown($pages);
	generate_page_list_html($pages, $fileBasePath, $networkBasePath);
	generate_category_pages_html($pages, $fileBasePath, $networkBasePath);
	echo render_parse_warnings();
	generate_sitemap_xml($fileBasePath, $networkBasePath, $rootUrl);
}
