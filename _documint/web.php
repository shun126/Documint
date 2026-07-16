<?php

$generationErrorOccurred = false;

function display_generation_error($e)
{
	global $generationErrorOccurred;
	$generationErrorOccurred = true;

	if (PHP_SAPI === 'cli')
	{
		fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
		return;
	}

	echo '<div class="alert alert-danger" role="alert"><strong>ERROR:</strong> ';
	echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
	echo '</div>';
}

function generation_error_occurred()
{
	global $generationErrorOccurred;
	return $generationErrorOccurred;
}
/*
Render the generation mode form.
*/
function render_generation_form($selectedMode = 'site')
{
	$modes = [
		'site' => '通常生成（すべてのMarkdownをHTML化）',
		'readme-index' => '各README.mdを同一ディレクトリのindex.htmlとして出力',
	];

	echo '<form method="post" class="card my-4">';
	echo '<div class="card-body">';
	echo '<h2 class="card-title h4">生成モード</h2>';
	echo '<p class="card-text">Documintの実行モードを選択し、IDとパスワードを入力してから生成を開始します。</p>';
	echo '<div class="mb-3">';
	echo '<label class="form-label" for="documint_auth_id">ID</label>';
	echo '<input class="form-control" type="text" name="auth_id" id="documint_auth_id" autocomplete="username" required>';
	echo '</div>';
	echo '<div class="mb-3">';
	echo '<label class="form-label" for="documint_auth_password">Password</label>';
	echo '<input class="form-control" type="password" name="auth_password" id="documint_auth_password" autocomplete="current-password" required>';
	echo '</div>';
	foreach ($modes as $mode => $label)
	{
		$id = 'generation_mode_' . str_replace('-', '_', $mode);
		$checked = $selectedMode === $mode ? ' checked' : '';
		echo '<div class="form-check">';
		echo '<input class="form-check-input" type="radio" name="mode" id="' . $id . '" value="' . $mode . '"' . $checked . '>';
		echo '<label class="form-check-label" for="' . $id . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>';
		echo '</div>';
	}
	echo '<button type="submit" class="btn btn-primary mt-3">生成開始</button>';
	echo '</div>';
	echo '</form>';
}
