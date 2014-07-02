<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">
		<meta name="author" content="">

		<title>Coral Delivery</title>

		<!-- Bootstrap core CSS -->
		<link href="http://todc.github.io/todc-bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="http://todc.github.io/todc-bootstrap/dist/css/todc-bootstrap.min.css" rel="stylesheet">
		<link href="http://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css">
		<link href="/media/css/select2.css" rel="stylesheet">
		<link href="/media/css/coral.css" rel="stylesheet">
		<link href="/media/img/favicon.png" rel="shortcut icon">

		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
			<script src="/media/js/html5shiv.js"></script>
			<script src="/media/js/respond.min.js"></script>
		<![endif]-->
	</head>
	<body>
		<div class="navbar navbar-toolbar navbar-default navbar-static-top">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".coral-navbar-collapse">
						<?=__('Menu');?>
					</button>
					<?=HTML::anchor('/', HTML::image('media/img/logo.png').' '.__('Coral Delivery'), array('class' => 'navbar-brand'));?>
				</div>
				<div class="collapse navbar-collapse coral-navbar-collapse">
					<ul class="nav navbar-nav">
						<?php if ($auth->logged_in()): ?>
							<li<?=Nav::ac('Package');?>><?=HTML::anchor('package', __('Packages'));?></li>
							<li<?=Nav::ac('Service');?>><?=HTML::anchor('service', __('Services'));?></li>
							<li<?=Nav::ac('Account', 'index');?>><?=HTML::anchor('account', __('Profile'));?></li>
							<li><?=HTML::anchor('account/logout', __('Logout'));?></li>
						<?php else: ?>
							<li<?=Nav::ac('Account', 'login');?>><?=HTML::anchor('account/login', __('Login'));?></li>
							<li<?=Nav::ac('Account', 'register');?>><?=HTML::anchor('account/register', __('Register'));?></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>

		<div class="container">
			<div class="main">
				<?=(isset($view) ? $view : NULL); ?>
			</div>
		</div>

		<!-- Bootstrap core JavaScript
		================================================== -->
		<!-- Placed at the end of the document so the pages load faster -->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<script src="//todc.github.io/todc-bootstrap/dist/js/bootstrap.min.js"></script>
		<script src="/media/js/tablesorter.js"></script>
		<script src="/media/js/select2.js"></script>
		<script src="/media/js/application.js"></script>
	</body>
</html>