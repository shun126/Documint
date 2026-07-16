<?php

////////////////////////////////////////////////////////////////////////////////

/*
Convert a category tag argument into category names.
*/
function split_category_names($text)
{
	$categories = [];
	$parts = explode(',', $text);
	foreach ($parts as $part)
	{
		$name = trim($part);
		if ($name !== '')
		{
			$categories[$name] = true;
		}
	}

	return array_keys($categories);
}

/*
Generate the category page file name from a category name.
*/
function get_category_page_file_name($category)
{
	$hash = substr(hash('sha256', $category), 0, DOCUMINT_CATEGORY_FILE_HASH_LENGTH);
	return 'category-' . $hash . '.html';
}

/*
Generate the category page file path from a category name.
*/
function get_category_page_file_path($fileBasePath, $category)
{
	return $fileBasePath . DIRECTORY_SEPARATOR . DOCUMINT_PAGE_LIST_DIR_NAME . DIRECTORY_SEPARATOR . get_category_page_file_name($category);
}

/*
Generate category links as Markdown.
*/
function build_category_links_markdown($categories, $sourcePath)
{
	$fileBasePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
	$links = [];
	$debug = '';
	foreach ($categories as $category)
	{
		$categoryPagePath = get_category_page_file_path($fileBasePath, $category);
		$linkPath = build_relative_link_path($sourcePath, $categoryPagePath);
		if (DOCUMINT_DEBUG_CATEGORY_LINKS === true)
		{
			$debug .= '<!-- DOCUMINT_CATEGORY_LINK_DEBUG';
			$debug .= ' category="' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '"';
			$debug .= ' sourcePath="' . htmlspecialchars($sourcePath, ENT_QUOTES, 'UTF-8') . '"';
			$debug .= ' fileBasePath="' . htmlspecialchars($fileBasePath, ENT_QUOTES, 'UTF-8') . '"';
			$debug .= ' categoryPagePath="' . htmlspecialchars($categoryPagePath, ENT_QUOTES, 'UTF-8') . '"';
			$debug .= ' linkPath="' . htmlspecialchars($linkPath, ENT_QUOTES, 'UTF-8') . '"';
			$debug .= " -->\n";
		}
		$links[] = '[' . $category . '](' . $linkPath . ')';
	}

	return $debug . implode(', ', $links) . "\n\n";
}

/*
Build the page map for each category.
*/
function build_category_page_map($pages)
{
	$categories = [];
	foreach ($pages as $page)
	{
		$cats = $page->getCategories();
		if (empty($cats))
		{
			continue;
		}

		foreach ($cats as $cat)
		{
			if (!array_key_exists($cat, $categories))
			{
				$categories[$cat] = [];
			}
			$categories[$cat][] = $page;
		}
	}

	return $categories;
}

/*
Parse category_list tag arguments.
*/
function parse_category_list_arguments($rawArgs)
{
	$args = trim($rawArgs);
	$heading_level = 2;
	$filter = '';

	if ($args !== '')
	{
		if (preg_match('/^size\s*=\s*([1-6])(?:\s*,\s*(.*))?$/u', $args, $match))
		{
			$heading_level = intval($match[1]);
			if (isset($match[2]))
			{
				$filter = trim($match[2]);
			}
		}
		else if (preg_match('/^size\s*=/u', $args))
		{
			return NULL;
		}
		else
		{
			$filter = $args;
		}
	}

	return [
		'filter' => $filter,
		'heading_level' => $heading_level,
	];
}

/*
Generate a category list as Markdown.
*/
function build_category_list_markdown($pages, $filter, $heading_level = 2)
{
	$body = '';
	$categories = build_category_page_map($pages);
	$filters = split_category_names($filter);
	$heading_marker = str_repeat('#', $heading_level);

	if (!empty($filters))
	{
		foreach ($filters as $category)
		{
			$body .= $heading_marker . ' ' . $category . "\n\n";
			if (array_key_exists($category, $categories))
			{
				foreach ($categories[$category] as $page)
				{
					$body .= "* [" . $page->getTitle() . "](" . $page->getNetworkPath() . ")\n";
				}
			}
			$body .= "\n";
		}

		return $body;
	}

	foreach ($categories as $category => $category_pages)
	{
		$body .= $heading_marker . ' ' . $category . "\n\n";
		foreach ($category_pages as $page)
		{
			$body .= "* [" . $page->getTitle() . "](" . $page->getNetworkPath() . ")\n";
		}
		$body .= "\n";
	}

	return $body;
}
