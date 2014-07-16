<?php defined('SYSPATH') OR die('No direct script access.');
$error_id = uniqid('error');
function parse_source($source)
{
	$source = preg_replace('/<span class=\"number\">([0-9]+)<\/span>/', '$1', $source);
	$source = strip_tags($source);
	return $source;
}
try {
	$user = Auth::instance()->get_user();
	$admin = $user->is_admin();
}
catch (Exception $e)
{
	$admin = FALSE;
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="description" content="">
		<meta name="author" content="">
		<title><?=SITE_NAME;?></title>
		<link href="http://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css">
		<link href="/media/css/bootstrap.min.css" rel="stylesheet">
		<link href="/media/css/todc-bootstrap.min.css" rel="stylesheet">
		<link href="/media/css/font-awesome.min.css" rel="stylesheet">
		<link href="/media/css/coral.css?t=1" rel="stylesheet">
		<link href="/media/css/highlight.css" rel="stylesheet">
		<link href="/media/img/favicon.png" rel="shortcut icon">
		<link href="/media/img/128x128.png" rel="icon" sizes="128x128">
		<link href="/media/img/128x128.png" rel="apple-touch-icon" sizes="128x128">
		<link href="/media/img/128x128.png" rel="apple-touch-icon-precomposed" sizes="128x128">
		<!--[if lt IE 9]>
			<script src="/media/js/html5shiv.js"></script>
			<script src="/media/js/respond.min.js"></script>
		<![endif]-->
	</head>
	<body>
		<br />
		<div class="text-center" style="margin-bottom: -20px;">
			<img src="http://static.delivery.pipe.is/img/128x128.png" alt="" />
		</div>
		<div class="col-xs-8 col-xs-offset-2">
			<div style="margin-top: 0px;" <?php if ((Kohana::$environment === Kohana::DEVELOPMENT OR $admin)): ?> class="main"<?php endif; ?>>
				<div class="text-center">
					<?php if ((Kohana::$environment === Kohana::DEVELOPMENT OR $admin)): ?>
						<span style="color: #61d2d6;font-size: 72px;line-height: 66px;font-weight: 300;">
							<?=$code;?>
						</span>
						<h2>
							<?=$class;?>: <?php echo htmlspecialchars( (string) $message, ENT_QUOTES, Kohana::$charset, TRUE); ?><br>
							<div class="text-left">
								<h3 style="color: #61d2d6;"><?php echo Debug::path($file) ?><span class="text-muted">:<?php echo $line; ?></span></h3>
								<pre style="background: transparent; border: 0; border-bottom: 2px solid #eee; padding: 0"><code class="php"><?php echo parse_source(Debug::source($file, $line)) ?></code></pre>
							</div>
						</h2>
				<?php else: ?>
						<span style="color: #61d2d6;font-size: 32px; font-weight: 500;">
							<?php     if ($code === 404): echo 'Page not found'; ?>
							<?php elseif ($code === 403): echo 'Forbidden'; ?>
							<?php else: echo 'Server Error'; ?>
							<?php endif; ?>
						</span>
						<p>
							<?php if ($code === 404): ?>
								<?=$message;?>
							<?php else: ?>
								Something went wrong, the error has been reported to our staff.
							<?php endif; ?>
						</p>
				<?php endif; ?>
				</div>
			</div>
	
			<?php if (Kohana::$environment === Kohana::DEVELOPMENT OR $admin): ?>

				<h1 style="margin-bottom: 0;">Stack Trace</h1>

				<div class="main" style="padding: 20px; font-size: 12px;">
					<?php foreach (Debug::trace($trace) as $i => $step): ?>
						<h3 style="color: #61d2d6;">
							<?php if ($step['file']): $source_id = $error_id.'source'.$i; ?>
								<?php echo Debug::path($step['file']) ?><span class="text-muted">:<?php echo $step['line'] ?></span>
							<?php else: ?>
								{<?php echo __('PHP internal call') ?>}
							<?php endif ?>
							<small class="pull-right">
								<?=$step['function']?>
								(<?php if ($step['args']): $args_id = $error_id.'args'.$i; ?><a href="#" onclick="$('#<?=$args_id ?>').collapse('toggle'); return false;" class="btn btn-info btn-xs"><?php echo __('arguments') ?></a><?php endif ?>)
							</small>
						</h3>
						<?php if (isset($source_id)): ?>
							<pre style="background: transparent; border: 0; border-bottom: 2px solid #eee; padding: 0"><code class="php"><?php echo parse_source($step['source']) ?></code></pre>
						<?php endif ?>
						<?php if (isset($args_id)): ?>
							<div class="collapse" id="<?php echo $args_id ?>">
								<?php foreach ($step['args'] as $name => $arg): ?>
									<pre style="padding: 0; border: 0; border-bottom: 2px solid #eee; background: transparent;"><?php echo Debug::dump($arg) ?></pre>
								<?php endforeach ?>
							</div>
						<?php endif ?>
					<?php endforeach; ?>
				</div>

				<h1>Environment</h1>

				<!-- GLOBALS -->
				<?php foreach (array('_SESSION', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER') as $var): ?>
					<?php if (empty($GLOBALS[$var]) OR ! is_array($GLOBALS[$var])) continue ?>
					<h4 style="color: #61d2d6; font-weight: 500; margin-bottom: 0" onclick="$('#globals_<?=$var;?>').collapse('toggle');">$<?=$var;?></h4>
					<div id="globals_<?=$var;?>" class="collapse in">
						<div class="main" style="padding: 20px; font-size: 13px;">
							<table>
								<?php foreach ($GLOBALS[$var] as $key => $value): ?>
									<tr>
										<td valign="top"><?php echo htmlspecialchars( (string) $key, ENT_QUOTES, Kohana::$charset, TRUE); ?></td>
										<td><code>"<?=$value;?>"</code></td>
									</tr>
								<?php endforeach;?>
							</table>
						</div>
					</div>
				<?php endforeach;?>

				<div class="row">
					<div class="col-xs-6">
						<!-- Included files -->
						<h4 style="color: #61d2d6; font-weight: 500; margin-bottom: 0" onclick="$('#files').collapse('toggle');"><?php echo __('Included files') ?></h4>
						<div id="files" class="collapse in">
							<div class="main" style="padding: 20px; font-size: 13px;">
								<table>
									<?php foreach (get_included_files() as $file): ?>
									<tr>
										<td><code><?php echo Debug::path($file) ?></code></td>
									</tr>
									<?php endforeach ?>
								</table>
							</div>
						</div>
					</div>
					<div class="col-xs-6">
						<!-- Loaded extensions -->
						<h4 style="color: #61d2d6; font-weight: 500; margin-bottom: 0" onclick="$('#extensions').collapse('toggle');"><?php echo __('Loaded extensions') ?></h4>
						<div id="extensions" class="collapse in">
							<div class="main" style="padding: 20px; font-size: 13px;">
								<table>
									<?php foreach (get_loaded_extensions() as $file): ?>
									<tr>
										<td><code><?php echo Debug::path($file) ?></code></td>
									</tr>
									<?php endforeach ?>
								</table>
							</div>
						</div>
					</div>
				</div>

			<?php endif; ?>

			<br />

			<div class="text-center text-muted">
				<ol class="list-inline">
					<li><a href="/support">Support</a></li>
					<li>|</li>
					<li><a href="mqilto:webmaster@pipe.is">Contact</a></li>
					<li>|</li>
					<li><a href="/">Home</a></li>
				</ol>
			</div>
		</div>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<script src="/media/js/bootstrap.min.js"></script>
		<script src="/media/js/highlight.js"></script>
		<script>hljs.initHighlightingOnLoad();</script>
	</body>
</html>