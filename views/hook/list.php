<div class="page-header">
	<div class="pull-right btn-toolbar">
		<?=HTML::anchor('package/'.$package->hashid(), __('Cancel'), array('class' => 'btn btn-lg btn-default'));?>
	</div>
	<h2><?=__('List of hooks for ":package"', array(':package' => $package->tracking_number));?></h2>
</div>

<table class="table table-block">
	<thead>
		<tr>
			<th><?=__('Name');?></th>
			<th><?=__('Method');?></th>
			<th><?=__('Enabled');?></th>
			<th><?=__('Processed');?></th>
			<th><?=__('Actions');?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($hooks as $hook):?>
			<tr>
				<td data-label="<?=__('Name');?>"><?=$hook->name;?></td>
				<td data-label="<?=__('Method');?>"><?=$hook->method;?></td>
				<td data-label="<?=__('Enabled');?>"><?=__(($hook->enabled == '1') ? 'Yes' : 'No');?></td>
				<td data-label="<?=__('Processed at');?>"><?=empty($hook->processed_at) ? __('Never') : $hook->processed_at;?></td>
				<td data-label="<?=__('Actions');?>">
					<?=HTML::anchor('package/'.$package->hashid().'/hook/'.$hook->id.'/edit', 'Edit', array('class' => 'btn btn-info btn-sm', 'title' => __('Edit hook')));?>
					<?=HTML::anchor('package/'.$package->hashid().'/hook/'.$hook->id.'/delete', 'Delete', array('class' => 'btn btn-danger btn-sm', 'title' => __('Delete hook'), 'onclick' => 'return confirm(\''.__('Are you sure you want to delete this hook?').'\');'));?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?=HTML::anchor('package/'.$package->hashid().'/hook', __('Add hook'), array('class' => 'btn btn-success btn-lg'));?>