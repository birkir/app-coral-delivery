<table class="table table-block">
	<thead>
		<tr>
			<th><?=__('Name');?></th>
			<th><?=__('Username');?></th>
			<th><?=__('Password');?></th>
			<th><?=__('Method');?></th>
			<th><?=__('Enabled');?></th>
			<th><?=__('Last updated');?></th>
			<th><?=__('Actions');?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($services as $service):?>
			<tr>
				<td data-label="<?=__('Name');?>"><?=HTML::anchor('service/'.$service->id, $service->name);?></td>
				<td data-label="<?=__('Username');?>"><?=$service->username;?></td>
				<td data-label="<?=__('Password');?>" class="text-muted">******</td>
				<td data-label="<?=__('Method');?>"><?=$service->method;?></td>
				<td data-label="<?=__('Enabled');?>"><?=__(($service->enabled == '1') ? 'Yes' : 'No');?></td>
				<td data-label="<?=__('Last updated');?>"><?=$service->updated_at;?></td>
				<td data-label="<?=__('Actions');?>">
					<?=HTML::anchor('service/'.$service->id.'/edit', 'Edit', array('class' => 'btn btn-info btn-sm', 'title' => 'Edit service'));?>
					<?=HTML::anchor('service/'.$service->id.'/delete', 'Delete', array('class' => 'btn btn-danger btn-sm', 'title' => 'Delete service', 'onclick' => 'return confirm(\''.__('Are you sure you want to delete this service?').'\');'));?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?=HTML::anchor('service/add', __('Add service'), array('class' => 'btn btn-success btn-lg btn-mobile-block'));?>