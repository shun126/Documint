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
			$path = pathinfo($Inline["element"]["attributes"]['src']);
			if (array_key_exists('extension', $path) && strcasecmp($path['extension'], "mp4") == 0)
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
		return $Inline;
	}

	/**
	 * テーブルのオーバーライド関数
	 */
	protected function blockTable($Line, array $Block = null)
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
			if (strcmp($Excerpt["element"]["name"], "a") == 0)
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
function plantuml($file, $endtag)
{
	$code = '';
	
	while (($line = fgets($file)))
	{
		$token = trim($line);
		if (strcmp($token, $endtag) == 0)
			break;

		$code .= $line;
	}

	$encode = encodep($code);
	return '![](http://www.plantuml.com/plantuml/svg/' . $encode . ')' . PHP_EOL;
	// echo "<img src='http://www.plantuml.com/plantuml/svg/{$encode}'>";
	// echo file_get_contents("http://www.plantuml.com/plantuml/svg/{$encode}");
}

/**
 * Mermaidにエンコード
 */
function mermaid($file)
{
	$code = '';
	
	while ($line = fgets($file))
	{
		$token = trim($line);
		if (strcmp($token, '```') == 0)
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

/*
*/
function source($file)
{
	$code = '';
	
	while (($line = fgets($file)))
	{
		$token = trim($line);
		if (strcmp($token, "```") == 0)
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
	while (($line = fgets($markdown)))
	{
		$token = trim($line);
		if (strcmp($token, '{{page_list}}') == 0)
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
			$filter = isset($match[1]) ? trim($match[1]) : '';
			if ($filter === '' || strpos($filter, ',') === false)
			{
				$body .= build_category_list_markdown($pages, $filter);
			}
		}
		else if (preg_match('/^\{\{category\s+(.+)\}\}$/u', $token))
		{
			// categoryはメタ情報なので出力しない
		}
		else if (strcmp($token, "```source") == 0)
		{
			$head .= markdown_to_html($body);
			$head .= source($markdown);
			$body = '';
		}
		else if (strcmp($token, "```mermaid") == 0)
		{
			$body .= mermaid($markdown);
		}
		else if (strcmp($token, "```plantuml") == 0)
		{
			$body .= plantuml($markdown, "```");
		}
		else if (strcmp($token, "@startuml") == 0)
		{
			$body .= plantuml($markdown, "@enduml");
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
				$filepath = pathinfo($filename);
				$extension = $filepath['extension'];

				// ファイル名を分解
				$directory = pathinfo($path)['dirname'] . DIRECTORY_SEPARATOR;

				$inner_contents = file_get_contents($directory . $filename);
				if ($inner_contents == false)
					throw new RuntimeException("file open filed. '" . $directory . $filename . "'");

				// html
				if (strcmp($extension, 'html') == 0 || strcmp($extension, 'htm') == 0)
				{
					$head .= markdown_to_html($body);
					$head .= $inner_contents;
					$body = '';
				}
				// plantuml
				else if (strcmp($extension, 'pu') == 0)
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
			else
			{
				$body .= $line;
			}
		}
	}

	fclose($markdown);

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

	$pathinfo = pathinfo($path);
	$title = $pathinfo['filename'];

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
カテゴリ一覧をMarkdownで生成
*/
function build_category_list_markdown($pages, $filter)
{
	$body = '';
	if ($filter !== NULL && $filter !== '')
	{
		$body .= '<h2>' . $filter . '</h2>' . "\n";
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
		$body .= '<h2>' . $category . '</h2>' . "\n";
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
function template($filename, $title, $body, $sidebar_html_file_name)
{
	$html = '';
	$fh = fopen($filename, "rt");
	while (($line = fgets($fh)))
	{
		if(preg_match('/{{[a-z]+}}/u', $line, $match))
		{
			if (strcmp('{{title}}', $match[0]) == 0)
			{
				$line = str_replace('{{title}}', $title, $line);
			}
			else if (strcmp('{{body}}', $match[0]) == 0)
			{
				$line = str_replace('{{body}}', $body, $line);
			}
			else if (strcmp('{{sidebar}}', $match[0]) == 0)
			{
				if (file_exists($sidebar_html_file_name))
				{
					$sidebar_html = file_get_contents($sidebar_html_file_name);
					$line = str_replace('{{sidebar}}', $sidebar_html, $line);
				}
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
			if (strcmp($file, '.') == 0 || strcmp($file, '..') == 0)
				continue;

			$path = $current_dir . $file;
			if (is_dir($path))
			{
				// ディレクトリ名の先頭が_で始まっているなら何もしない
				if ($file[0] !== '_' && $file !== 'rs-vendor')
				{
					gather_markdown_info_in_directory($pages, $networkBasePath, $fileBasePath, $dir . DIRECTORY_SEPARATOR . $file);
				}
			}
			else
			{
				// .mdファイルを記録
				$path_info = pathinfo($path);
				if (strcmp($path_info['extension'], 'md') == 0)
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
 * sidebar.htmlを親ディレクトリに向かって検索します
 */
function recursive_resolve_sidebar_path($path)
{
	$sidebar_file_name = $path . DIRECTORY_SEPARATOR . 'sidebar.html';
	if (file_exists($sidebar_file_name) == true)
		return $sidebar_file_name;

	$parent_path = dirname($path);
	if ($parent_path === $path)
		return NULL;

	return recursive_resolve_sidebar_path($parent_path);
}

/**
 * sidebar.htmlを親ディレクトリに向かって検索します
 * 無いならデフォルトテンプレートhtmlを採用する
 */
function resolve_sidebar_path($path)
{
	$sidebar_file_name = recursive_resolve_sidebar_path($path);
	if ($sidebar_file_name != NULL)
		return $sidebar_file_name;

	$template_file_name = __DIR__ . DIRECTORY_SEPARATOR . 'sidebar.html';
	if (file_exists($template_file_name) == true)
		return $template_file_name;

	return NULL;
}

/**
 * 回収したmarkdown情報からhtmlを生成します
 */
function build_html_from_markdown($pages)
{
	foreach ($pages as $page)
	{
		echo "generate: " . $page->getTitle() . " | " . $page->getNetworkPath() . " | " . $page->getFilePath() . "</br>";

		$filepath = pathinfo($page->getFilePath());
		$outputHtmlPath = $filepath['dirname'] . DIRECTORY_SEPARATOR . $filepath['filename'] . '.html';
		$out = fopen($outputHtmlPath, 'wt');
		if ($out)
		{
			// テンプレートhtmlを検索する
			$template_file_name = resolve_template_path($filepath['dirname']);

			// サイドバーhtmlを検索する
			$sidebar_html_file_name = resolve_sidebar_path($filepath['dirname']);

			// markdownからhtmlへ変換
			$html = parse_md($page->getFilePath(), $pages);

			// html templateを適用
			$html = template($template_file_name, $page->getTitle(), $html, $sidebar_html_file_name);

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
			if (strcmp($file, '.') == 0 || strcmp($file, '..') == 0)
				continue;

			$path = $current_dir . $file;
			if (is_dir($path))
			{
				if ($file !== '_sub_domain' && $file !== 'rs-vendor')
				{
					gather_html_file_in_directory($urls, $rootUrl, $fileBasePath, $dir . DIRECTORY_SEPARATOR . $file);
				}
			}
			else
			{
				// TODO: template.htmlとsidebar.htmlをリテラル化して下さい
				if ($file !== "template.html" && $file !== "sidebar.html")
				{
					// .htmlファイルを記録
					$path_info = pathinfo($path);
					$extension = $path_info['extension'];
					if (strcmp($extension, 'html') == 0 || strcmp($extension, 'htm') == 0)
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
		for ($i = 1; $i < $segument_count; $i += 1)
		{
			$networkBasePath .= $pathSegments[$i] . '/';
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

		/*
		TODO: テンプレートの検索ルールに従ってください
		サイドバーに対応してください
		*/
		// テンプレートhtmlを検索する
		$template_file_name = resolve_template_path($fileBasePath);

		// サイドバーhtmlを検索する
		$sidebar_html_file_name = resolve_sidebar_path($filepath['dirname']);

		// html templateを適用
		$html = template($template_file_name, 'ページ一覧', $html, $sidebar_html_file_name);

		// write to html file
		if (fwrite($out, $html) === false)
		{
			throw new RuntimeException("Cannot write to file. '" . $fileBasePath . "/page_list.html'");
		}
		
		fclose($out);
	}

	// htmlページを回収
	$urls = [];
	gather_html_file_in_directory($urls, $rootUrl, $fileBasePath, '');

	usort($urls, function($a, $b)
	{
		return strlen($a) - strlen($b);
	});

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
		echo "generate sitemap.xml</br>";
	}

} catch(Exception $e) {
	echo("ERROR!!!");
	echo($e->getMessage());
}
?>
