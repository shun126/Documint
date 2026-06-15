<html lang="ja">
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
 * markdownファイルからhtmlファイルを生成します
 */

require_once "parsedown/Parsedown.php";

/**
 * マークダウンパーサークラス
 */
class Markdown extends Parsedown
{
	/**
	 * イメージのオーバーライド関数
	 */
	protected function inlineImage($Excerpt)
	{
		$Inline = parent::inlineImage($Excerpt);
		if (isset($Inline))
		{
			// ファイル名を分解
			$extension = pathinfo($Inline["element"]["attributes"]['src'], PATHINFO_EXTENSION);
			if (is_string($extension))
			{
				if (strcasecmp($extension, "mp4") == 0)
				{
					// ビデオ
					$Inline["element"]["name"] = "video";
					$Inline["element"]["attributes"] += array('controls'=>'');
					$Inline["element"]["attributes"] += array('class'=>'embed-responsive embed-responsive-16by9');
				}
				else
				{
					// 画像
					$Inline["element"]["attributes"] += array('class'=>'img-fluid');
				}
			}
		}
		return $Inline;
	}

	/**
	 * テーブルのオーバーライド関数
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
	 * リンクのオーバーライド関数
	 */
	protected function inlineLink($Excerpt)
	{
		$Excerpt = parent::inlineLink($Excerpt);
		if ($Excerpt)
		{
			if ($Excerpt["element"]["name"] === "a")
			{
				$url = parse_url($Excerpt["element"]['attributes']['href']);
				if (array_key_exists('path', $url) && strpos($url['path'], '.md') !== false)
				{
					$path = pathinfo($url['path']);
					if (array_key_exists('extension', $path) && strcasecmp($path['extension'], "md") == 0)
					{
						// .md を .html に変換
						$newPath = $path['dirname'] . DIRECTORY_SEPARATOR . $path['filename'] . ".html";
						$Excerpt["element"]['attributes']['href'] = $newPath;
					}
				}
			}
		}
		return $Excerpt ;
	}
};

////////////////////////////////////////////////////////////////////////////////
/*
ページ情報クラス
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
/**
 * Base64にエンコードする
 */
function encodep($text)
{
	 $compressed = gzdeflate($text, 9);
	 return encode64($compressed);
}

/**
 * Base64にエンコードする
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
 * Base64にエンコードする
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
 * Base64にエンコードする
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
 * PlantUMLにエンコード
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
 * Mermaidにエンコード
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
 * markdownをhtmlに変換
 */
function markdown_to_html($source)
{
	$markdown = new Markdown();
	$markdown->setMarkupEscaped(false);
	return $markdown->text($source);
}

$parseWarnings = [];

/**
 * 解析時の警告を記録します
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
 * 解析時の警告をHTMLとして出力します
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
 * 生HTMLらしい行か判定します
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
 * markdownを解析して出力します
 */
function parse_md($path, $pages)
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
			$head .= markdown_to_html($body);
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
		else if (preg_match('/^\{\{category\s+(.+)\}\}$/u', $token))
		{
			// categoryはメタ情報なので出力しない
		}
		else if ($token === "```source")
		{
			$head .= markdown_to_html($body);
			$head .= source($markdown, $lineNumber);
			$body = '';
		}
		else if ($token === "```mermaid")
		{
			$head .= markdown_to_html($body);
			$head .= mermaid($markdown, $lineNumber);
			$body = '';
		}
		else if ($token === "```plantuml")
		{
			$head .= markdown_to_html($body);
			$head .= plantuml($markdown, "```", $lineNumber);
			$body = '';
		}
		else if ($token === "@startuml")
		{
			$head .= markdown_to_html($body);
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
			{{{ filename }}} と記述するとfilenameで指定したファイルをマージします。
			拡張子がpuの場合はPlantUMLとして処理
			拡張子がhtmlの場合はHTMLとして処理
			それ以外の拡張子ではMarkdownとしてマージします。
			*/
			if (preg_match('{{{\s[0-9a-zA-Z./]+\s}}}', $token, $match))
			{
				// {{ }} を取り除く
				$length = strlen($match[0]) - 4;
				$filename = trim(substr($match[0], 2, $length));

				// ファイル名を分解
				$extension = pathinfo($filename, PATHINFO_EXTENSION);
				if (is_string($extension))
					$extension = "";

				// ファイル名を分解
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
						$head .= markdown_to_html($body);
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

	return $head . markdown_to_html($body);
}

////////////////////////////////////////////////////////////////////////////////
/*
Markdownファイル内の最初の`#`をタイトルとして取得します
¥param	$path	Markdownファイルのパス
¥return	マークダウン内の最初の`#`
*/
function get_title_from_markdown($path)
{
	$markdown = fopen($path, 'rt');
	if ($markdown === false)
	{
		throw new RuntimeException("Markdown file not found. '" . $path . "'");
	}

	$title = pathinfo($path, PATHINFO_FILENAME);

	while (($line = fgets($markdown)))
	{
		$line = trim($line);
		if (strlen($line) > 2)
		{
			// 先頭の文字が#ならば、#と空白を削除して行末までの文字列を返す
			if ($line[0] === '#' && $line[1] === ' ')
			{
				fclose($markdown);
				return substr($line, 2);
			}
		}
	}

	fclose($markdown);

	return $title;
}

////////////////////////////////////////////////////////////////////////////////
/*
Markdownファイルからカテゴリを取得
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
			$parts = explode(',', $match[1]);
			foreach ($parts as $part)
			{
				$name = trim($part);
				if ($name !== '')
				{
					$categories[$name] = true;
				}
			}
		}
	}

	fclose($markdown);
	return array_keys($categories);
}

/*
category_listタグの引数を解析
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

	if ($filter !== '' && strpos($filter, ',') !== false)
	{
		return NULL;
	}

	return [
		'filter' => $filter,
		'heading_level' => $heading_level,
	];
}

/*
カテゴリ一覧をMarkdownで生成
*/
function build_category_list_markdown($pages, $filter, $heading_level = 2)
{
	$body = '';
	$heading_marker = str_repeat('#', $heading_level);
	if ($filter !== NULL && $filter !== '')
	{
		$body .= $heading_marker . ' ' . $filter . "\n\n";
		foreach ($pages as $page)
		{
			$cats = $page->getCategories();
			if (empty($cats))
			{
				continue;
			}

			if (in_array($filter, $cats, true))
			{
				$body .= "* [" . $page->getTitle() . "](" . $page->getNetworkPath() . ")\n";
			}
		}
		$body .= "\n";
		return $body;
	}

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
 * htmlテンプレートにページの内容を反映します
 */
function template($filename, $title, $body, $sidebar_html)
{
	$html = '';
	$fh = fopen($filename, "rt");
	while (($line = fgets($fh)))
	{
		if(preg_match('/{{[a-z]+}}/u', $line, $match))
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
		}

		$html .= $line;
	}
	fclose($fh);
	return $html;
}

////////////////////////////////////////////////////////////////////////////////
/**
 * ディレクトリをオープンしてMarkdownの情報を回収します
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
				 * ディレクトリ名の先頭が.または_で始まっている
				 * またはrs-vendorなら何もしない
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
				// .mdファイルを記録
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
 * template.htmlを親ディレクトリに向かって検索します
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
 * template.htmlを親ディレクトリに向かって検索します
 * 無いならデフォルトテンプレートhtmlを採用する
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
 * sidebar.mdを親ディレクトリに向かって検索します
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
 * sidebar.mdを親ディレクトリに向かって検索します
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
 * 回収したmarkdown情報からhtmlを生成します
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
			// テンプレートhtmlを検索する
			$template_file_name = resolve_template_path($filepath['dirname']);

			// サイドバーmarkdownを検索する
			$sidebar_markdown_file_name = resolve_sidebar_path($filepath['dirname']);

			// markdownからhtmlへ変換
			$html = parse_md($page->getFilePath(), $pages);

			// html templateを適用
			$sidebar_html = '';
			if ($sidebar_markdown_file_name != NULL)
			{
				$sidebar_html = parse_md($sidebar_markdown_file_name, $pages);
			}
			$html = template($template_file_name, $page->getTitle(), $html, $sidebar_html);

			// htmlをファイルへ出力
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
 * ディレクトリをオープンしてhtmlのurlを回収します
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
				 * ディレクトリ名の先頭が.または_で始まっている
				 * またはrs-vendorなら何もしない
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
					// .htmlファイルを記録
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
main
*/
try {
	// ファイルのベースパスを取得
	$fileBasePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');

	// ネットワークのベースパスを取得
	{
		// 現在のURLを取得
		$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
		$host = $_SERVER['HTTP_HOST'];
		$requestUri = $_SERVER['REQUEST_URI'];
		$rootUrl = $scheme . "://" . $host;
		$currentUrl = $rootUrl . $requestUri;

		// パス部分を分解
		$path = parse_url($currentUrl, PHP_URL_PATH);
		$pathSegments = explode('/', trim($path, '/'));

		// 一つ下のパスを取得
		$networkBasePath = '';
		$segument_count = count($pathSegments);
		for ($i = 0; $i < $segument_count - 1; $i += 1)
		{
			$networkBasePath .= '/';
			$networkBasePath .= $pathSegments[$i];
		}
	}

	// ページの情報を回収
	$pages = [];
	gather_markdown_info_in_directory($pages, $networkBasePath, $fileBasePath, '');

	// htmlファイルを生成
	build_html_from_markdown($pages);
	
	// ページ一覧ページを生成
	$out = fopen($fileBasePath . '/page_list.html', 'wt');
	if ($out)
	{	
		$html = "<h1>ページの一覧</h1>";
		foreach ($pages as $page)
		{
			$path = str_replace($fileBasePath, ".", $page->getNetworkPath());
			if (DIRECTORY_SEPARATOR === "\\")
			{
				$path = str_replace(DIRECTORY_SEPARATOR, "/", $path);
			}
			$html .= "<li><a href=\"" . $path . "\">" . $page->getTitle() . "</a></li>";
		}
		unset($page);

		// テンプレートhtmlを検索する
		$template_file_name = resolve_template_path($fileBasePath);

		// サイドバーmarkdownを検索する
		$sidebar_markdown_file_name = resolve_sidebar_path($fileBasePath);

		// html templateを適用
		$sidebar_html = '';
		if ($sidebar_markdown_file_name != NULL)
		{
			$sidebar_html = parse_md($sidebar_markdown_file_name, $pages);
		}
		$html = template($template_file_name, 'ページ一覧', $html, $sidebar_html);

		// write to html file
		if (fwrite($out, $html) === false)
		{
			throw new RuntimeException("Cannot write to file. '" . $fileBasePath . "/page_list.html'");
		}
		echo "generate <a href=\"" . $networkBasePath . "/page_list.html\">page_list.html</a></br>";
		
		fclose($out);
	}

	echo render_parse_warnings();

	// htmlページを回収
	$urls = [];
	gather_html_file_in_directory($urls, $rootUrl . $networkBasePath, $fileBasePath, '');

	// urlの短い順に並び変え
	usort($urls, function($a, $b)
	{
		return strlen($a) - strlen($b);
	});

	// サイトマップを出力
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

		// ページ一覧ページを生成
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
