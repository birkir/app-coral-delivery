<div class="page-header">
	<div class="pull-right btn-toolbar">
		<?php if ($package->loaded()): ?>
			<?=HTML::anchor('package/delete/'.$package->tracking_number, __('Delete'), array('class' => 'btn btn-lg btn-danger', 'data-remote' => 'true', 'data-confirm' => __('Are you sure you want to delete this package?')));?>
		<?php endif; ?>
		<?=HTML::anchor('package'.($package->loaded() ? '/detail/'.$package->tracking_number : ''), __('Cancel'), array('class' => 'btn btn-lg btn-default'));?>
	</div>
	<h2><?=__($package->loaded() ? 'Edit package' : 'Create package');?></h2>
</div>

<?=Form::open($package->loaded() ? 'package/edit/'.$package->tracking_number : 'package/create', array('method' => 'post', 'role' => 'form', 'class' => 'form-horizontal'));?>

	<?php if (count($errors)): ?>
		<div class="alert alert-warning">
			<?=__('Some errors occoured. Fix them and try again.');?>
		</div>
	<?php endif; ?>

	<?php $ro = $package->loaded() ? 'readonly' : NULL; ?>
	<?php $re = $package->loaded() ? '<strong>*</strong>' : NULL;?>

	<div class="form-group">
		<?=Form::label('packageTrackingNumber', __('Tracking number'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?=Form::input('tracking_number', $package->tracking_number, array('class' => 'form-control', 'id' => 'packageTrackingNumber', $ro => $ro));?>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('packageOriginCarrier', __('Origin carrier').$re, array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?=Form::select('origin_carrier_id', array('' => '') + $carriers, $package->origin_carrier_id, array('placeholder' => 'Select carrier', 'class' => 'form-control', 'id' => 'packageOriginCarrier'));?>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('packageDestinationCarrier', __('Destination carrier').$re, array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<?=Form::select('destination_carrier_id', array('' => 'Same as origin carrier') + $carriers, $package->destination_carrier_id, array('class' => 'form-control', 'id' => 'packageDestinationCarrier'));?>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('packageDescription', __('Description'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-5">
			<?=Form::textarea('description', $package->description, array('class' => 'form-control', 'id' => 'packageDescription', 'placeholder' => 'ex. phone or clothes', 'rows' => 4));?>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('packagePhoto', __('Photo'), array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-5">
			<?=Form::input('photo', $package->photo, array('class' => 'form-control', 'id' => 'packagePhoto'));?>
		</div>
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-6">
			<input type="hidden" name="notify_email" value="0">
			<label class="control-label inline-checkbox" for="packageNotifyEmail">
				<?=Form::checkbox('notify_email', 1, $package->loaded() ? intval($package->notify_email) === 1 : TRUE, array('id' => 'packageNotifyEmail'));?>
				&nbsp;&nbsp;<?=__('Send status updates to my email address');?>
			</label>
		</div>
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2" style="padding-left: 15px">
			<?=Form::button(NULL, $package->loaded() ? __('Save changes') : __('Add package'), array('class' => 'btn btn-lg btn-primary', 'type' => 'submit'));?>
		</div>
	</div>

	<div class="form-group">
		<?php if ($package->loaded()): ?>
			<p class="text-muted col-sm-6 col-sm-offset-2"><?=__('*Please note that by modify the origin or destination carrier, all previous statuses will be removed and fresh ones will be loaded.');?></p>
		<?php endif; ?>
	</div>

<?=Form::close();?>