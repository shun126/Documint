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

	require_once __DIR__ . DIRECTORY_SEPARATOR . 'constants.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'parsedown' . DIRECTORY_SEPARATOR . 'Parsedown.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'model.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'filesystem.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'category.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'markdown.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'renderer.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'generator.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'web.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'controller.php';

	run_documint_controller();
	?>

			</main>
		</div>
	</div>
	</body>
</html>
