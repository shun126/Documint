<?php

/**
 * Markdown parser class.
 */
class Markdown extends Parsedown
{
	private $sourcePath;
	private $outputPath;
	private $pages;

	public function __construct($sourcePath = NULL, $outputPath = NULL, $pages = [])
	{
		$this->sourcePath = $sourcePath;
		$this->outputPath = $outputPath;
		$this->pages = $pages;
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
					$this->outputPath,
					$this->pages
				);
			}
		}
		return $Excerpt ;
	}
};
function rewrite_markdown_link_to_html($href, $sourcePath, $outputPath, $pages = [])
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
		$outputFileName = $path['filename'] . '.html';
		if ($path['basename'] === 'README.md' && pages_use_readme_index($pages))
		{
			$outputFileName = 'index.html';
		}
		$linkPath = ($path['dirname'] === '/' ? '/' : $path['dirname'] . '/') . $outputFileName;
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
	foreach ($pages as $page)
	{
		if (normalize_file_path($page->getFilePath()) === $targetMarkdownPath)
		{
			$targetHtmlPath = $page->getOutputFilePath();
			break;
		}
	}
	$linkPath = build_relative_link_path($outputPath, $targetHtmlPath);

	if (array_key_exists('query', $url))
		$linkPath .= '?' . $url['query'];
	if (array_key_exists('fragment', $url))
		$linkPath .= '#' . $url['fragment'];

	return $linkPath;
}

function pages_use_readme_index($pages)
{
	foreach ($pages as $page)
	{
		if (basename($page->getFilePath()) === 'README.md' && basename($page->getOutputFilePath()) === 'index.html')
		{
			return true;
		}
	}

	return false;
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
function markdown_to_html($source, $sourcePath = NULL, $outputPath = NULL, $pages = [])
{
	$markdown = new Markdown($sourcePath, $outputPath, $pages);
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
 * Mask inline code spans while preserving byte offsets in the original line.
 */
function mask_inline_code($text)
{
	$masked = preg_replace_callback(
		'/(?<!`)(`+)(?!`)(.*?)\1(?!`)/u',
		function($match)
		{
			return str_repeat(' ', strlen($match[0]));
		},
		$text
	);

	return $masked !== NULL ? $masked : $text;
}

/**
 * Parse Markdown and return HTML.
 */
function parse_md($path, $pages, $outputPath = NULL)
{
	$markdown = open_input_file($path, 'Markdown file');

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
			$head .= markdown_to_html($body, $path, $outputPath, $pages);
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
			$head .= markdown_to_html($body, $path, $outputPath, $pages);
			$head .= source($markdown, $lineNumber);
			$body = '';
		}
		else if ($token === "```mermaid")
		{
			$head .= markdown_to_html($body, $path, $outputPath, $pages);
			$head .= mermaid($markdown, $lineNumber);
			$body = '';
		}
		else if ($token === "```plantuml")
		{
			$head .= markdown_to_html($body, $path, $outputPath, $pages);
			$head .= plantuml($markdown, "```", $lineNumber);
			$body = '';
		}
		else if ($token === "@startuml")
		{
			$head .= markdown_to_html($body, $path, $outputPath, $pages);
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
			$include_search_token = mask_inline_code($token);
			if (preg_match('{{{\s[0-9a-zA-Z./]+\s}}}', $include_search_token, $match))
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
				try
				{
					validate_input_file($contents_path, 'Included file');
					$inner_contents = @file_get_contents($contents_path);
					if ($inner_contents === false)
						throw new RuntimeException("Included file cannot be opened. '" . $contents_path . "'");

					// html
					if ($extension === 'html' || $extension === 'htm')
					{
						$head .= markdown_to_html($body, $path, $outputPath, $pages);
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
				catch (RuntimeException $e)
				{
					$include_error = new RuntimeException($e->getMessage() . " Referenced from '" . $path . "' line " . $lineNumber, 0, $e);
					display_generation_error($include_error);
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

	return $head . markdown_to_html($body, $path, $outputPath, $pages);
}

////////////////////////////////////////////////////////////////////////////////
/*
Get the title from {{title ...}}, the first heading, or the file name.
@param  $path  Markdown file path.
@return Title from {{title ...}}, first Markdown heading, or file name.
*/
function get_title_from_markdown($path)
{
	$markdown = open_input_file($path, 'Markdown file');

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
	$markdown = open_input_file($path, 'Markdown file');

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

