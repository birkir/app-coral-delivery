<div class="page-header">
	<div class="pull-right">
		<?php if ($package->loaded()): ?>
			<?=HTML::anchor('package/'.$package->hashid().'/delete', __('Delete'), array('class' => 'btn btn-lg btn-danger', 'onclick' => 'return confirm(\''.__('Are you sure you want to delete this package?').'\');'));?>
		<?php endif; ?>
		<?=HTML::anchor($package->loaded() ? 'package/'.$package->hashid() : 'packages', __('Cancel'), array('class' => 'btn btn-lg btn-default'));?>
	</div>
	<h2><?=__($package->loaded() ? 'Edit package' : 'Add package');?></h2>
</div>

<?=Form::open($package->loaded() ? 'package/'.$package->hashid().'/edit' : 'package/add', array('method' => 'post', 'role' => 'form', 'class' => 'form-horizontal'));?>

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
		<?=Form::label('packageCarrier', __('Carrier').$re, array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<select name="carrier" id="packageCarrier" class="form-control">
				<optgroup label="<?=__('Express');?>">
					<?php foreach ($carriers as $carrier): ?>
						<?php if ($carrier->express == '1'): ?>
							<?php $selected = $package->loaded() ? ($package->origin_carrier_id == $carrier->id) : FALSE; ?>
							<option data-express="true" value="<?=$carrier->id;?>"<?=($selected ? ' selected="selected"' : '');?>><?=$carrier->name;?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</optgroup>
				<optgroup label="<?=__('Global');?>">
					<?php foreach ($carriers as $carrier): ?>
						<?php if ($carrier->express == '0'): ?>
							<?php $selected = $package->loaded() ? ($package->origin_carrier_id == $carrier->id) : (ORM::factory('Carrier', array('driver' => NULL))->id == $carrier->id); ?>
							<option data-global="true" value="<?=$carrier->id;?>"<?=($selected ? ' selected="selected"' : '');?>><?=$carrier->name;?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</optgroup>
			</select>
		</div>
	</div>

	<div class="form-group">
		<?=Form::label('packageCarrier2', __('Destination Carrier').$re, array('class' => 'col-sm-2 control-label'));?>
		<div class="col-sm-3">
			<select name="carrier2" id="packageCarrier2" class="form-control">
				<optgroup label="<?=__('Global');?>">
					<?php foreach ($carriers as $carrier): ?>
						<?php if ($carrier->express == '0'): ?>
							<?php $selected = $package->loaded() ? ($package->destination_carrier_id == $carrier->id) : (ORM::factory('Carrier', array('driver' => NULL))->id == $carrier->id); ?>
							<option value="<?=$carrier->id;?>"<?=($selected ? ' selected="selected"' : '');?>><?=$carrier->name;?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</optgroup>
			</select>
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
		<div class="col-sm-offset-2 col-xs-12" style="padding-left: 15px">
			<?=Form::button(NULL, $package->loaded() ? __('Save changes') : __('Add package'), array('class' => 'btn btn-lg btn-primary btn-mobile-block', 'type' => 'submit'));?>
		</div>
	</div>

	<div class="form-group">
		<?php if ($package->loaded()): ?>
			<p class="text-muted col-sm-6 col-sm-offset-2"><?=__('*Please note that by modify the origin or destination carrier, all previous statuses will be removed and fresh ones will be loaded.');?></p>
		<?php endif; ?>
	</div>

<?=Form::close();?>