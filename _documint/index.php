<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta http-equiv="x-ua-compatible" content="IE=9">
	<meta http-equiv="x-ua-compatible" content="IE=EmulateIE9">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Documint</title>

	<!-- stylesheet (bootstrap) -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

	<!-- stylesheet (bootswatch) -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.3.5/minty/bootstrap.min.css" integrity="sha512-6238ldGQpzSPMNT495xiCIginN/nvtvE8ejAhJ9FWqhaPq6GhkRNLm2OnA1WA9KJJ7F4ZzJ0y5gLjdHqFBE9Mg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
	<nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
		<div class="container">
			<h1 class="navbar-brand">Documint</h1>
		</div>
	</nav>
	<div class="container">
		<div class="row">
			<main role="main" class="col-12">

<?php
/**
 * Generates HTML files from Markdown files.
 */

define('DOCUMINT_VERSION', '0.1.0');
define('DOCUMINT_PAGE_LIST_DIR_NAME', '_page_list');
define('DOCUMINT_CATEGORY_FILE_HASH_LENGTH', 16);
define('DOCUMINT_DEBUG_CATEGORY_LINKS', false);

require_once "parsedown/Parsedown.php";

/**
 * Markdown parser class.
 */
class Markdown extends Parsedown
{
	private $sourcePath;
	private $outputPath;

	public function __construct($sourcePath = NULL, $outputPath = NULL)
	{
		$this->sourcePath = $sourcePath;
		$this->outputPath = $outputPath;
	}

	/**
     * Override image handling.
	 */
	protected function inlineImage($Excerpt)
	{
		$Inline = parent::inlineImage($Excerpt);
		if (isset($Inline))
		{
            // Parse the file extension.
			$extension = pathinfo($Inline["element"]["attributes"]['src'], PATHINFO_EXTENSION);
			if (is_string($extension))
			{
				if (strcasecmp($extension, "mp4") == 0)
				{
                    // Video.
					$Inline["element"]["name"] = "video";
					$Inline["element"]["attributes"] += array('controls'=>'');
					$Inline["element"]["attributes"] += array('class'=>'embed-responsive embed-responsive-16by9');
				}
				else
				{
                    // Image.
					$Inline["element"]["attributes"] += array('class'=>'img-fluid');
				}
			}
		}
		return $Inline;
	}

	/**
     * Override table handling.
	 */
	protected function blockTable($Line, ?array $Block = null)
	{
		$Block = parent::blockTable($Line, $Block);
		if ($Block)
		{
			if ($Block["element"]["name"] === "table")
			{
				if (array_key_exists('attributes', $Block["element"]) && is_null($Block["element"]["attributes"]))
					$Block["element"] += array('attributes'=> array('class'=>'table'));
			}
		}
		return $Block;
	}

	/**
     * Override link handling.
	 */
	protected function inlineLink($Excerpt)
	{
		$Excerpt = parent::inlineLink($Excerpt);
		if ($Excerpt)
		{
			if ($Excerpt["element"]["name"] === "a")
			{
				$Excerpt["element"]['attributes']['href'] = rewrite_markdown_link_to_html(
					$Excerpt["element"]['attributes']['href'],
					$this->sourcePath,
					$this->outputPath
				);
			}
		}
		return $Excerpt ;
	}
};

////////////////////////////////////////////////////////////////////////////////
/*
Page information class.
*/
class PageInfomation
{
	private $title;
	private $networkPath;
	private $filePath;
	private $categories;

	public function __construct($title, $networkPath, $filePath, $categories)
	{
		$this->title = $title;
		$this->networkPath = $networkPath;
		$this->filePath = $filePath;
		$this->categories = $categories;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getNetworkPath()
	{
		return $this->networkPath;
	}

	public function getFilePath()
	{
		return $this->filePath;
	}

	public function getCategories()
	{
		return $this->categories;
	}
};

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

function rewrite_markdown_link_to_html($href, $sourcePath, $outputPath)
{
	if ($sourcePath === NULL || $outputPath === NULL || $href === '' || is_external_link($href))
		return $href;

	$url = parse_url($href);
	if ($url === false || !array_key_exists('path', $url) || $url['path'] === '')
		return $href;
	if (array_key_exists('scheme', $url) || array_key_exists('host', $url))
		return $href;

	$path = pathinfo($url['path']);
	if (!array_key_exists('extension', $path) || strcasecmp($path['extension'], 'md') !== 0)
		return $href;

	if (strpos($url['path'], '/') === 0)
	{
		$linkPath = ($path['dirname'] === '/' ? '/' : $path['dirname'] . '/') . $path['filename'] . '.html';
		if (array_key_exists('query', $url))
			$linkPath .= '?' . $url['query'];
		if (array_key_exists('fragment', $url))
			$linkPath .= '#' . $url['fragment'];
		return $linkPath;
	}

	$sourceDirectory = dirname($sourcePath);
	$targetMarkdownPath = normalize_file_path($sourceDirectory . DIRECTORY_SEPARATOR . $url['path']);
	$targetPathInfo = pathinfo($targetMarkdownPath);
	$targetHtmlPath = $targetPathInfo['dirname'] . DIRECTORY_SEPARATOR . $targetPathInfo['filename'] . '.html';
	$linkPath = build_relative_link_path($outputPath, $targetHtmlPath);

	if (array_key_exists('query', $url))
		$linkPath .= '?' . $url['query'];
	if (array_key_exists('fragment', $url))
		$linkPath .= '#' . $url['fragment'];

	return $linkPath;
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
 * Encode PlantUML text.
 */
function encodep($text)
{
	 $compressed = gzdeflate($text, 9);
	 return encode64($compressed);
}

/**
 * Encode a 6-bit value for PlantUML.
 */
function encode6bit($b)
{
	 if ($b < 10) {
		  return chr(48 + $b);
	 }
	 $b -= 10;
	 if ($b < 26) {
		  return chr(65 + $b);
	 }
	 $b -= 26;
	 if ($b < 26) {
		  return chr(97 + $b);
	 }
	 $b -= 26;
	 if ($b == 0) {
		  return '-';
	 }
	 if ($b == 1) {
		  return '_';
	 }
	 return '?';
}

/**
 * Append three bytes to the PlantUML encoding.
 */
function append3bytes($b1, $b2, $b3)
{
	 $c1 = $b1 >> 2;
	 $c2 = (($b1 & 0x3) << 4) | ($b2 >> 4);
	 $c3 = (($b2 & 0xF) << 2) | ($b3 >> 6);
	 $c4 = $b3 & 0x3F;
	 $r = "";
	 $r .= encode6bit($c1 & 0x3F);
	 $r .= encode6bit($c2 & 0x3F);
	 $r .= encode6bit($c3 & 0x3F);
	 $r .= encode6bit($c4 & 0x3F);
	 return $r;
}

/**
 * Encode compressed PlantUML bytes.
 */
function encode64($c)
{
	 $str = "";
	 $len = strlen($c);
	 for ($i = 0; $i < $len; $i+=3)
	 {
			if ($i+2==$len)
			{
				  $str .= append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i+1, 1)), 0);
			}
			else if ($i+1==$len)
			{
				  $str .= append3bytes(ord(substr($c, $i, 1)), 0, 0);
			}
			else
			{
				  $str .= append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i+1, 1)),
					  ord(substr($c, $i+2, 1)));
			}
	 }
	 return $str;
}

////////////////////////////////////////////////////////////////////////////////
/**
 * Encode a PlantUML block.
 */
function plantuml($file, $endtag, &$lineNumber)
{
	$code = '';

	while (($line = fgets($file)))
	{
		$lineNumber += 1;
		$token = trim($line);
		if ($token === $endtag)
			break;

		$code .= $line;
	}

	$encode = encodep($code);
	return '<img src="http://www.plantuml.com/plantuml/svg/' . $encode . '" alt="PlantUML diagram">' . PHP_EOL;
	// echo "<img src='http://www.plantuml.com/plantuml/svg/{$encode}'>";
	// echo file_get_contents("http://www.plantuml.com/plantuml/svg/{$encode}");
}

/**
 * Render a Mermaid block.
 */
function mermaid($file, &$lineNumber)
{
	$code = '';

	while ($line = fgets($file))
	{
		$lineNumber += 1;
		$token = trim($line);
		if ($token === '```')
			break;

		$code .= $line;
	}

	return '<pre class="mermaid">' . $code . '</pre>' . PHP_EOL;
}

////////////////////////////////////////////////////////////////////////////////
/**
 * Convert Markdown to HTML.
 */
function markdown_to_html($source, $sourcePath = NULL, $outputPath = NULL)
{
	$markdown = new Markdown($sourcePath, $outputPath);
	$markdown->setMarkupEscaped(false);
	return $markdown->text($source);
}

$parseWarnings = [];

/**
 * Register a parse warning.
 */
function register_parse_warning($path, $lineNumber, $message)
{
	global $parseWarnings;

	$key = $path . ':' . $lineNumber . ':' . $message;
	if (array_key_exists($key, $parseWarnings))
		return;

	$parseWarnings[$key] = [
		'path' => $path,
		'line' => $lineNumber,
		'message' => $message,
	];
}

/**
 * Render parse warnings as HTML.
 */
function render_parse_warnings()
{
	global $parseWarnings;

	if (count($parseWarnings) === 0)
		return '';

	$html = '<div class="alert alert-warning" role="alert">';
	$html .= '<strong>Warnings</strong>';
	$html .= '<ul class="mb-0">';
	foreach ($parseWarnings as $warning)
	{
		$html .= '<li>';
		$html .= htmlspecialchars($warning['path'], ENT_QUOTES, 'UTF-8');
		$html .= ':' . $warning['line'] . ' ';
		$html .= htmlspecialchars($warning['message'], ENT_QUOTES, 'UTF-8');
		$html .= '</li>';
	}
	$html .= '</ul>';
	$html .= '</div>';
	return $html;
}

/**
 * Returns true when a token looks like raw HTML.
 */
function looks_like_raw_html($token)
{
	return preg_match('/^(?:<!--|<!DOCTYPE\b|<!\[CDATA\[|<\?|<\/?[a-zA-Z][\w:-]*(?:\s|>|\/))/iu', $token) === 1;
}

/*
*/
function source($file, &$lineNumber)
{
	$code = '';

	while (($line = fgets($file)))
	{
		$lineNumber += 1;
		$token = trim($line);
		if ($token === "```")
			break;

		$code .= $line;
	}

	return $code;
}

/**
 * Parse Markdown and return HTML.
 */
function parse_md($path, $pages, $outputPath = NULL)
{
	$markdown = fopen($path, 'rt');
	if ($markdown === false)
	{
		throw new RuntimeException("file not found. '" . $path . "'");
	}

	$head = '';
	$body = '';
	$html = '';
	$lineNumber = 0;
	$htmlBlockStartLine = 0;
	$inHtmlBlock = false;
	$inFencedCodeBlock = false;
	while (($line = fgets($markdown)))
	{
		$lineNumber += 1;
		$token = trim($line);
		if ($inHtmlBlock)
		{
			if ($token === '{{/html}}')
			{
				$head .= $html;
				$html = '';
				$inHtmlBlock = false;
			}
			else if ($token === '{{html}}')
			{
				fclose($markdown);
				throw new RuntimeException("Nested {{html}} block is not supported. '" . $path . "' line " . $lineNumber);
			}
			else
			{
				$html .= $line;
			}
		}
		else if ($inFencedCodeBlock)
		{
			$body .= $line;
			if ($token === '```')
			{
				$inFencedCodeBlock = false;
			}
		}
		else if ($token === '{{html}}')
		{
			$head .= markdown_to_html($body, $path, $outputPath);
			$body = '';
			$html = '';
			$htmlBlockStartLine = $lineNumber;
			$inHtmlBlock = true;
		}
		else if ($token === '{{/html}}')
		{
			fclose($markdown);
			throw new RuntimeException("Unexpected {{/html}} tag. '" . $path . "' line " . $lineNumber);
		}
		else if (preg_match('/^\{\{html/u', $token))
		{
			fclose($markdown);
			throw new RuntimeException("Unsupported html tag syntax. Use {{html}} ... {{/html}}. '" . $path . "' line " . $lineNumber);
		}
		else if ($token === '{{page_list}}')
		{
			foreach ($pages as $page)
			{
				$body .= "* [" . $page->getTitle() . "](" . $page->getNetworkPath() . ")\n";
			}
			$body .= "\n";
			unset($page);
		}
		else if (preg_match('/^\{\{category_list(?:\s+(.+))?\}\}$/u', $token, $match))
		{
			$args = parse_category_list_arguments(isset($match[1]) ? $match[1] : '');
			if ($args !== NULL)
			{
				$body .= build_category_list_markdown($pages, $args['filter'], $args['heading_level']);
			}
		}
		else if (preg_match('/^\{\{category\s+(.+)\}\}$/u', $token, $match))
		{
			$categories = split_category_names($match[1]);
			$body .= build_category_links_markdown($categories, $outputPath !== NULL ? $outputPath : $path);
		}
		else if (preg_match('/^\{\{title\s+(.+)\}\}$/u', $token))
		{
            // Title is metadata and is not emitted.
		}
		else if ($token === "```source")
		{
			$head .= markdown_to_html($body, $path, $outputPath);
			$head .= source($markdown, $lineNumber);
			$body = '';
		}
		else if ($token === "```mermaid")
		{
			$head .= markdown_to_html($body, $path, $outputPath);
			$head .= mermaid($markdown, $lineNumber);
			$body = '';
		}
		else if ($token === "```plantuml")
		{
			$head .= markdown_to_html($body, $path, $outputPath);
			$head .= plantuml($markdown, "```", $lineNumber);
			$body = '';
		}
		else if ($token === "@startuml")
		{
			$head .= markdown_to_html($body, $path, $outputPath);
			$head .= plantuml($markdown, "@enduml", $lineNumber);
			$body = '';
		}
		else if (preg_match('/^```/', $token))
		{
			$inFencedCodeBlock = true;
			$body .= $line;
		}
		else
		{
			/*
            Triple braces include another file into the page.
            .pu files are rendered as PlantUML.
            .html and .htm files are included as HTML fragments.
            Other files are included as Markdown.
			*/
			if (preg_match('{{{\s[0-9a-zA-Z./]+\s}}}', $token, $match))
			{
                // Remove the outer braces.
				$length = strlen($match[0]) - 4;
				$filename = trim(substr($match[0], 2, $length));

                // Parse the file extension.
				$extension = pathinfo($filename, PATHINFO_EXTENSION);
				if (is_string($extension))
					$extension = "";

                // Resolve the included file path.
				$directory = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
				$contents_path = $directory . $filename;
				if (is_file($contents_path))
				{
					$inner_contents = file_get_contents($directory . $filename);
					if ($inner_contents == false)
						throw new RuntimeException("file open filed. '" . $directory . $filename . "'");

					// html
					if ($extension === 'html' || $extension === 'htm')
					{
						$head .= markdown_to_html($body, $path, $outputPath);
						$head .= $inner_contents;
						$body = '';
					}
					// plantuml
					else if ($extension === 'pu')
					{
						$encode = encodep($inner_contents);
						$body .= '![uml](http://www.plantuml.com/plantuml/svg/' . $encode . ')' . PHP_EOL;
					}
					// markdown
					else
					{
						$body .= $inner_contents;
					}
				}
			}
			else
			{
				$body .= $line;
			}
		}
	}

	fclose($markdown);

	if ($inHtmlBlock)
	{
		throw new RuntimeException("Unclosed {{html}} block. '" . $path . "' line " . $htmlBlockStartLine);
	}

	return $head . markdown_to_html($body, $path, $outputPath);
}

////////////////////////////////////////////////////////////////////////////////
/*
Get the title from {{title ...}}, the first heading, or the file name.
@param  $path  Markdown file path.
@return Title from {{title ...}}, first Markdown heading, or file name.
*/
function get_title_from_markdown($path)
{
	$markdown = fopen($path, 'rt');
	if ($markdown === false)
	{
		throw new RuntimeException("Markdown file not found. '" . $path . "'");
	}

	$title = pathinfo($path, PATHINFO_FILENAME);
	$heading_title = NULL;

	while (($line = fgets($markdown)))
	{
		$line = trim($line);
		if (preg_match('/^\{\{title\s+(.+)\}\}$/u', $line, $match))
		{
			$specified_title = trim($match[1]);
			if ($specified_title !== '')
			{
				fclose($markdown);
				return $specified_title;
			}
		}

		if ($heading_title === NULL && strlen($line) > 2)
		{
            // Keep the first level-one heading without the leading marker and whitespace.
			if ($line[0] === '#' && $line[1] === ' ')
			{
				$heading_title = substr($line, 2);
			}
		}
	}

	fclose($markdown);

	if ($heading_title !== NULL)
	{
		return $heading_title;
	}

	return $title;
}

////////////////////////////////////////////////////////////////////////////////
/*
Get categories from a Markdown file.
*/
function get_categories_from_markdown($path)
{
	$markdown = fopen($path, 'rt');
	if ($markdown === false)
	{
		throw new RuntimeException("Markdown file not found. '" . $path . "'");
	}

	$categories = [];
	while (($line = fgets($markdown)))
	{
		$line = trim($line);
		if (preg_match('/^\{\{category\s+(.+)\}\}$/u', $line, $match))
		{
			foreach (split_category_names($match[1]) as $name)
			{
				$categories[$name] = true;
			}
		}
	}

	fclose($markdown);
	return array_keys($categories);
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

////////////////////////////////////////////////////////////////////////////////
/**
 * Apply page content to an HTML template.
 */
function template($filename, $title, $body, $sidebar_html)
{
	$html = '';
	$fh = fopen($filename, "rt");
	while (($line = fgets($fh)))
	{
		if(preg_match('/{{[a-z_]+}}/u', $line, $match))
		{
			if ('{{title}}' === $match[0])
			{
				$line = str_replace('{{title}}', $title, $line);
			}
			else if ('{{body}}' === $match[0])
			{
				$line = str_replace('{{body}}', $body, $line);
			}
			else if ('{{sidebar}}' === $match[0])
			{
				$line = str_replace('{{sidebar}}', $sidebar_html, $line);
			}
			else if ('{{documint_version}}' === $match[0])
			{
				$line = str_replace('{{documint_version}}', DOCUMINT_VERSION, $line);
			}
		}

		$html .= $line;
	}
	fclose($fh);
	return $html;
}

////////////////////////////////////////////////////////////////////////////////
/**
 * Open directories and collect Markdown page information.
 */
function gather_markdown_info_in_directory(&$pages, $networkBasePath, $fileBasePath, $dir)
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
					gather_markdown_info_in_directory($pages, $networkBasePath, $fileBasePath, $dir . DIRECTORY_SEPARATOR . $file);
				}
			}
			else
			{
                // Register .md files.
				$path_info = pathinfo($path);
				if (array_key_exists('extension', $path_info) && $path_info['extension'] === 'md')
				{
					$network_path = $networkBasePath . $dir . '/' . $path_info['filename'] . '.html';
					if (DIRECTORY_SEPARATOR === "\\")
					{
						$network_path = str_replace(DIRECTORY_SEPARATOR, "/", $network_path);
					}
					$title = get_title_from_markdown($path);
					$categories = get_categories_from_markdown($path);
					$pages[] = new PageInfomation($title, $network_path, $path, $categories);
				}
			}
		}

		closedir($dh);
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

		$filepath = pathinfo($page->getFilePath());
		$outputHtmlPath = $filepath['dirname'] . DIRECTORY_SEPARATOR . $filepath['filename'] . '.html';
		$out = fopen($outputHtmlPath, 'wt');
		if ($out)
		{
            // Resolve template.html.
			$template_file_name = resolve_template_path($filepath['dirname']);

            // Resolve sidebar Markdown.
			$sidebar_markdown_file_name = resolve_sidebar_path($filepath['dirname']);

            // Convert Markdown to HTML.
			$html = parse_md($page->getFilePath(), $pages, $outputHtmlPath);

            // Apply the HTML template.
			$sidebar_html = '';
			if ($sidebar_markdown_file_name != NULL)
			{
				$sidebar_html = parse_md($sidebar_markdown_file_name, $pages, $outputHtmlPath);
			}
			$html = template($template_file_name, $page->getTitle(), $html, $sidebar_html);

            // Write the HTML file.
			if (fwrite($out, $html) === false)
			{
				throw new RuntimeException("Cannot write to file. '" . $outputHtmlPath . "'");
			}

			fclose($out);
		}
		else
		{
			throw new RuntimeException("Cannot open to file. '" . $outputHtmlPath . "'");
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
	$pageListDirectory = prepare_page_list_directory($fileBasePath);
	$outputPath = $pageListDirectory . DIRECTORY_SEPARATOR . 'page_list.html';
	$out = fopen($outputPath, 'wt');
	if (!$out)
	{
		throw new RuntimeException("Cannot open to file. '" . $outputPath . "'");
	}

	$html = "<h1>List of Pages</h1>";
	foreach ($pages as $page)
	{
		$html .= '<li><a href="' . $page->getNetworkPath() . '">' . $page->getTitle() . '</a></li>';
	}

	$template_file_name = resolve_template_path($fileBasePath);
	$sidebar_markdown_file_name = resolve_sidebar_path($fileBasePath);
	$sidebar_html = '';
	if ($sidebar_markdown_file_name != NULL)
	{
		$sidebar_html = parse_md($sidebar_markdown_file_name, $pages, $outputPath);
	}
	$html = template($template_file_name, 'List of Pages', $html, $sidebar_html);

	if (fwrite($out, $html) === false)
	{
		fclose($out);
		throw new RuntimeException("Cannot write to file. '" . $outputPath . "'");
	}
	fclose($out);

	echo 'generate <a href="' . $networkBasePath . '/' . DOCUMINT_PAGE_LIST_DIR_NAME . '/page_list.html">page_list.html</a></br>';
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
		$categoryPageSourceDir = $fileBasePath;
		if (count($category_pages) > 0)
		{
			$categoryPageSourceDir = dirname($category_pages[0]->getFilePath());
		}

		$outputPath = $pageListDirectory . DIRECTORY_SEPARATOR . get_category_page_file_name($category);
		$template_file_name = resolve_template_path($categoryPageSourceDir);
		$sidebar_markdown_file_name = resolve_sidebar_path($categoryPageSourceDir);
		$sidebar_html = '';
		if ($sidebar_markdown_file_name != NULL)
		{
			$sidebar_html = parse_md($sidebar_markdown_file_name, $pages, $outputPath);
		}

		$out = fopen($outputPath, 'wt');
		if (!$out)
		{
			throw new RuntimeException("Cannot open to file. '" . $outputPath . "'");
		}

		$body = "<h1>Category: " . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . "</h1>";
		$body .= "<ul>";
		foreach ($category_pages as $page)
		{
			$body .= '<li><a href="' . $page->getNetworkPath() . '">' . htmlspecialchars($page->getTitle(), ENT_QUOTES, 'UTF-8') . '</a></li>';
		}
		$body .= "</ul>";
		$html = template($template_file_name, 'Category: ' . $category, $body, $sidebar_html);

		if (fwrite($out, $html) === false)
		{
			fclose($out);
			throw new RuntimeException("Cannot write to file. '" . $outputPath . "'");
		}
		fclose($out);

		echo 'generate <a href="' . $networkBasePath . '/' . DOCUMINT_PAGE_LIST_DIR_NAME . '/' . get_category_page_file_name($category) . '">' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</a></br>';
	}
}

////////////////////////////////////////////////////////////////////////////////
/*
main
*/
try {
    // Get the filesystem base path.
	$fileBasePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');

    // Get the network base path.
	{
        // Get the current URL.
		$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
		$host = $_SERVER['HTTP_HOST'];
		$requestUri = $_SERVER['REQUEST_URI'];
		$rootUrl = $scheme . "://" . $host;
		$currentUrl = $rootUrl . $requestUri;

        // Parse the path portion.
		$path = parse_url($currentUrl, PHP_URL_PATH);
		$pathSegments = explode('/', trim($path, '/'));

        // Get one parent path.
		$networkBasePath = '';
		$segument_count = count($pathSegments);
		for ($i = 0; $i < $segument_count - 1; $i += 1)
		{
			$networkBasePath .= '/';
			$networkBasePath .= $pathSegments[$i];
		}
	}

    // Collect page information.
	$pages = [];
	gather_markdown_info_in_directory($pages, $networkBasePath, $fileBasePath, '');

    // Generate HTML files.
	build_html_from_markdown($pages);

    // Generate the page list.
	generate_page_list_html($pages, $fileBasePath, $networkBasePath);

    // Generate category pages.
	generate_category_pages_html($pages, $fileBasePath, $networkBasePath);

	echo render_parse_warnings();

    // Collect HTML page URLs.
	$urls = [];
	gather_html_file_in_directory($urls, $rootUrl . $networkBasePath, $fileBasePath, '');

    // Sort by shorter URL first.
	usort($urls, function($a, $b)
	{
		return strlen($a) - strlen($b);
	});

    // Write the sitemap.
	{
		$sitemap  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
		foreach ($urls as $url)
		{
			$sitemap .= "<url>";
			$sitemap .= "<loc>" . $url . "</loc>";
			$sitemap .= "</url>";
			$sitemap .= "\n";
		}
		$sitemap .= "</urlset>";
		unset($urls);

        // Add the page list page.
		$out = fopen($fileBasePath . '/sitemap.xml', 'wt');
		if ($out)
		{
			if (fwrite($out, $sitemap) === false)
			{
				throw new RuntimeException("Cannot write to file. '" . $fileBasePath . "/sitemap.xml'");
			}
			echo "generate <a href=\"" . $networkBasePath . "/sitemap.xml\">sitemap.xml</a></br>";
		}
	}

} catch(Exception $e) {
	echo("ERROR!!!");
	echo($e->getMessage());
}
?>

			</main>
		</div>
	</div>
	</body>
</html>
