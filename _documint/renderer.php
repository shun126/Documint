<?php

////////////////////////////////////////////////////////////////////////////////
/**
 * Apply page content to an HTML template.
 */
function template($filename, $title, $body, $sidebar_html)
{
	$html = '';
	$fh = open_input_file($filename, 'Template file');
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

