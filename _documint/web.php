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
		'site' => 'Standard generation (convert all Markdown files to HTML)',
		'readme-index' => 'Output each README.md as index.html in the same directory',
	];

	echo '<form method="post" class="card my-4">';
	echo '<div class="card-body">';
	echo '<h2 class="card-title h4">Generation Mode</h2>';
	$authenticated = function_exists('is_web_generation_authenticated') && is_web_generation_authenticated();
	if ($authenticated)
	{
		echo '<p class="card-text">You are authenticated. This authentication remains valid for one hour after login.</p>';
		echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_web_generation_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
	}
	else
	{
		echo '<p class="card-text">Select a Documint generation mode, enter your ID and password, then start generation.</p>';
		echo '<div class="mb-3">';
		echo '<label class="form-label" for="documint_auth_id">ID</label>';
		echo '<input class="form-control" type="text" name="auth_id" id="documint_auth_id" autocomplete="username" required>';
		echo '</div>';
		echo '<div class="mb-3">';
		echo '<label class="form-label" for="documint_auth_password">Password</label>';
		echo '<input class="form-control" type="password" name="auth_password" id="documint_auth_password" autocomplete="current-password" required>';
		if (function_exists('is_documint_default_password_configured') && is_documint_default_password_configured())
		{
			echo '<div class="alert alert-warning mt-2 mb-0" role="alert">';
			echo 'The generation password is still set to its default value. Change DOCUMINT_AUTH_PASSWORD in _documint/config.php.';
			echo '</div>';
		}
		echo '</div>';
	}
	foreach ($modes as $mode => $label)
	{
		$id = 'generation_mode_' . str_replace('-', '_', $mode);
		$checked = $selectedMode === $mode ? ' checked' : '';
		echo '<div class="form-check">';
		echo '<input class="form-check-input" type="radio" name="mode" id="' . $id . '" value="' . $mode . '"' . $checked . '>';
		echo '<label class="form-check-label" for="' . $id . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>';
		echo '</div>';
	}
	echo '<button type="submit" class="btn btn-primary mt-3">Start Generation</button>';
	echo '</div>';
	echo '</form>';

	if ($authenticated)
	{
		echo '<form method="post" class="mb-4">';
		echo '<input type="hidden" name="action" value="logout">';
		echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_web_generation_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
		echo '<button type="submit" class="btn btn-outline-secondary">Log Out</button>';
		echo '</form>';
	}
}
