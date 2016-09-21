<?php

include_once('var/meta.inc');
include_once('php/xssclean.php');
include_once('php/yams.php');
$site = new YAMS();
$page = 'pages/'.$site->lang['active'].'/'.$site->page.'.php';

if (!file_exists($page)) {
	header('HTTP/1.1 404 Not Found');
	$notfound = true;
}

?>

<!DOCTYPE html>
<html class="no-js" lang="<?php echo $site->lang['active'] ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="description" content="<?php echo $meta['description'][$site->lang['active']] ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $site->title ?> | <?php echo $meta['title'][$site->lang['active']] ?></title>
	<base href="<?php echo $site->baseurl ?>">
	<link rel="stylesheet" href="css/normalize.min.css">
</head>
<body>
	
	<header>
		<a href="<?php if (count($site->lang['list']) > 1) echo $site->lang['active'] ?>">Home</a>
	</header>
	
	<nav>
		<?php echo $site->getMenuList('main'); ?>
		<?php if (count($site->lang['list']) > 1) echo $site->getMenuList('lang'); ?>
	</nav>
		
	<article>
		<?php
		if (!isset($notfound)) {
			include_once($page);
		} else {
			echo '<h1>404</h1>';
		}
		?>
	</article>
	
	<footer>
		<?php echo $site->getMenuList('footer'); ?>
	</footer>
		
</body>
</html>
