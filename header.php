<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang=""> <!--<![endif]-->
<head>
	<meta http-equiv="Content-type" content="text/html; charset=<?php bloginfo('charset'); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="description" content="Сайт общественной организации Корпус волонтеров города Владивостока">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php wp_title( '»', 'true', 'right' ); ?><?php bloginfo('name'); ?></title>
	<link rel="apple-touch-icon" href="apple-touch-icon.png">
	<?php wp_head(); ?>



</head>
<body>
	<!--[if lt IE 8]>
		<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
	<![endif]-->
	<div class="header-container">
			<?php if ( ! is_front_page() ) : ?>
				<a href="/"><header class="wrapper"></header></a>
			<?php else: ?>
				<header class="wrapper"></header>
			<?php endif; ?>
	</div>
