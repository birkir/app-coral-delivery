<div class="page-header">
	<div class="pull-right btn-toolbar">
		<?php if ($hook->loaded()): ?>
			<?=HTML::anchor('package/'.$package->hashid().'/hook/'.$hook->id.'/delete', __('Delete'), array('class' => 'btn btn-lg btn-danger', 'onclick' => 'return confirm(\''.__('Are you sure you want to delete this hook?').'\');'));?>
		<?php endif; ?>
		<?=HTML::anchor('package/'.$package->hashid().'/hooks', __('Cancel'), array('class' => 'btn btn-lg btn-default'));?>
	</div>
	<h2><?=__($hook->loaded() ? 'Edit hook' : 'Create hook');?></h2>
</div>

<?php if ($errors): ?>
	<div class="alert alert-warning">
		<strong>Please review your input</strong><br>
		<ul>
			<?php foreach ($errors as $field => $error): ?>
				<?php if (is_array($error)): ?>
					<?php foreach ($error as $_field => $_error): ?>
						<li><?=UTF8::ucfirst($_error);?></li>
					<?php endforeach; ?>
				<?php else: ?>
					<li><?=UTF8::ucfirst($error);?></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<?=Form::open(NULL, array('method' => 'post', 'enctype' => 'multipart/form-data', 'role' => 'form', 'class' => 'form-horizontal'));?>

	<div class="form-group">
		<?=Form::label('hookName', __('Name'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?=Form::input('name', $hook->name, array('class' => 'form-control', 'id' => 'hookName'));?>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('hookMethod', __('Method'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?php if ($hook->loaded()): ?>
				<div class="form-control-static"><?=$hook->method;?></div>
			<?php else: ?>
				<?=Form::select('method', $methods, $hook->method, array('class' => 'form-control', 'id' => 'hookMethod'));?>
			<?php endif; ?>
		</div>
	</div>

	<?=$fields;?>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-6">
			<input type="hidden" name="enabled" value="0">
			<label class="control-label inline-checkbox" for="hookEnabled">
				<?=Form::checkbox('enabled', 1, $hook->loaded() ? intval($hook->enabled) === 1 : TRUE, array('id' => 'hookEnabled'));?>
				&nbsp;&nbsp;<?=__('Enable hook');?>
			</label>
		</div>
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2" style="padding-left: 15px">
			<?=Form::button(NULL, __('Save changes'), array('class' => 'btn btn-lg btn-success btn-mobile-block', 'type' => 'submit'));?>
		</div>
	</div>

<?=Form::close();?>