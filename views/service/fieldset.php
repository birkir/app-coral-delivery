<div class="page-header">
	<div class="pull-right">
		<?=HTML::anchor('services', __('Cancel'), array('class' => 'btn btn-lg btn-default'));?>
	</div>
	<h2><?=__($service->loaded() ? 'Edit service' : 'Add service');?></h2>
</div>

<?=Form::open($service->loaded() ? 'service/'.$service->id.'/edit' : 'service/add', array('method' => 'post', 'role' => 'form', 'class' => 'form-horizontal'));?>

	<?php if (count($errors)): ?>
		<div class="alert alert-warning">
			<?=__('Some errors occoured. Fix them and try again.');?>
		</div>
	<?php endif; ?>

	<div class="form-group">
		<?=Form::label('serviceName', __('Name'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?=Form::input('name', $service->name, array('class' => 'form-control', 'id' => 'serviceName'));?>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('serviceMethod', __('Method'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?=Form::select('method', $methods, $service->method, array('class' => 'form-control', 'id' => 'serviceMethod'));?>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('serviceUsername', __('Username').' / '.__('Email'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?=Form::input('username', $service->username, array('class' => 'form-control', 'id' => 'serviceUsername'));?>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('servicePassword', __('Password'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?=Form::input('password', NULL, array('class' => 'form-control', 'id' => 'servicePassword'));?>
		</div>
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<input type="hidden" name="enabled" value="0">
			<label class="control-label inline-checkbox" for="serviceEnabled">
				<?=Form::checkbox('enabled', 1, $service->loaded() ? $service->enabled == '1' : TRUE, array('id' => 'serviceEnabled'));?>
				&nbsp;&nbsp;<?=__('Enable service');?>
			</label>
		</div>
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2 col-xs-12" style="padding-left: 15px">
			<?=Form::button(NULL, $service->loaded() ? __('Save changes') : __('Add service'), array('class' => 'btn btn-lg btn-primary btn-mobile-block', 'type' => 'submit'));?>
		</div>
	</div>

<?=Form::close();?>