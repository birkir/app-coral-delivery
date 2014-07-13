<div class="page-header">
	<div class="pull-right btn-toolbar">
		<?=HTML::anchor('services', __('Go back'), array('class' => 'btn btn-lg btn-default'));?>
	</div>
	<h2><?=__('Service details');?></h2>
</div>

<h3>Service details</h3>
<?=$detail;?>

<h3>Available methods</h3>
<div class="btn-toolbar">
	<?php foreach ($methods as $method => $name): ?>
		<?=HTML::anchor('service/'.$service->id.'?method='.$method, $name, array('class' => 'btn btn-primary btn-lg'));?>
	<?php endforeach; ?>
</div>