<div class="page-header">
	<div class="pull-right btn-toolbar">
		<?=HTML::anchor('account', __('Cancel'), array('class' => 'btn btn-lg btn-default'));?>
	</div>
	<h2><?=__($hook->loaded() ? 'Edit hook' : 'New hook');?></h2>
</div>

<?=Form::open(NULL, array('method' => 'post', 'role' => 'form'));?>

	<div class="row">
		<div class="col-sm-3">
			<div class="form-group">
				<?=Form::label('hookName', __('Name'), array('class' => 'control-label'));?>
				<div class="controls">
					<?=Form::input('name', $hook->name, array('class' => 'form-control', 'id' => 'hookName'));?>
				</div>
			</div>

			<div class="form-group">
				<?=Form::label('hookCarrier', __('Carrier'), array('class' => 'control-label'));?>
				<div class="controls ">
					<?=Form::select('carrier_id', array('' => '') + $carriers, $hook->carrier_id, array('class' => 'form-control', 'placeholder' => __('All carriers'), 'id' => 'hookCarrier'));?>
				</div>
			</div>

			<div class="form-group">
				<?=Form::label('hookOrigin', __('Origin'), array('class' => 'control-label'));?>
				<div class="controls">
					<?=Form::select('origin', array('' => '') + Carrier::countries(), $hook->origin, array('class' => 'form-control', 'placeholder' => __('All locations'), 'id' => 'hookOrigin'));?>
				</div>
			</div>

			<div class="form-group">
				<?=Form::label('hookDestination', __('Destination'), array('class' => 'control-label'));?>
				<div class="controls">
					<?=Form::select('destination', array('' => '') + Carrier::countries(), $hook->destination, array('class' => 'form-control', 'placeholder' => __('All locations'), 'id' => 'hookDestination'));?>
				</div>
			</div>

			<div class="form-group">
				<?=Form::label('hookMethod', __('Method'), array('class' => 'control-label'));?>
				<div class="controls">
					<?=Form::select('method', array('' => '') + $methods, $hook->method, array('class' => 'form-control', 'placeholder' => __('Select method'), 'id' => 'hookMethod'));?>
				</div>
			</div>
		</div>
		<div class="col-sm-9">
			<div class="form-group">
				<?=Form::label('hookTrigger', __('Triggers'), array('class' => 'control-label'));?>
				<?=Form::hidden('trigger', $hook->trigger);?>
				<div data-widget="hookbuilder"></div>
			</div>
		</div>
	</div>

	<?=Form::button(NULL, __('Save changes'), array('type' => 'button', 'class' => 'btn btn-primary btn-lg'));?>

<?=Form::close();?>