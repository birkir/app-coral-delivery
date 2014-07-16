<div class="page-header">
	<?=HTML::anchor('package/add', __('Add package'), array('class' => 'btn btn-success btn-lg pull-right'));?>
	<h2><?=__('Packages');?></h2>
</div>

<div class="row" data-widget="package/list">
	<div class="col-sm-3 hidden-xs">
		<div class="panel panel-default">
			<div class="panel-heading" style="border-bottom: 1px solid #eee; padding: 10px 20px; font-weight: 500">
				<?=__('Filter packages');?>
			</div>
			<ul class="list-group">
				<li class="list-group-header"><?=__('Search');?></li>
				<li class="list-group-item" style="padding: 5px 20px 0 30px">
					<input type="search" class="form-control" placeholder="Search query" data-filter="query" data-column="0">
				</li>
				<li class="list-group-header"><?=__('State');?></li>
				<li class="list-group-item active"><a href="#0" data-filter="5"><?=__('All packages');?></a></li>
				<li class="list-group-item"><a href="#1" data-filter="5"><?=__('Unavailable');?></a></li>
				<li class="list-group-item"><a href="#3" data-filter="5"><?=__('Dispatched');?></a></li>
				<li class="list-group-item"><a href="#6" data-filter="5"><?=__('Completed');?></a></li>
				<li class="list-group-header"><?=__('Origin');?></li>
				<li class="list-group-item" style="padding: 5px 20px 0 30px">
					<?=Form::select(NULL, array('' => 'Any country') + $countries, NULL, array('class' => 'form-control', 'placeholder' => __('Any country'), 'data-filter' => '2'));?>
				</li>
				<li class="list-group-header"><?=__('Destination');?></li>
				<li class="list-group-item" style="padding: 5px 20px 15px 30px">
					<?=Form::select(NULL, array('' => 'Any country') + $countries, NULL, array('class' => 'form-control', 'placeholder' => __('Any country'), 'data-filter' => '3'));?>
				</li>
			</ul>
			<div class="panel-footer" style="border-top: 1px solid #eee">
				<?=Form::button(NULL, __('Clear filters'), array('class' => 'btn btn-danger btn-sm', 'data-filter-clear' => 'true'));?>
			</div>
		</div>
	</div>
	<div class="col-sm-9">
		<table class="table table-condensed table-block">
			<thead>
				<tr>
					<th class="hidden"></th>
					<th class="sorter-query"><?=__('Tracking number');?></th>
					<th><?=__('Origin');?></th>
					<th><?=__('Destination');?></th>
					<th><?=__('Last date');?></th>
					<th class="sorter-state"><?=__('State');?></th>
				</tr>
			</thead>
			<tfoot class="floating">
				<tr>
					<td colspan="5">
						<button type="button" class="btn btn-lg btn-default prev"><i class="fa fa-arrow-left"></i></button>
						<button type="button" class="btn btn-lg btn-default next"><i class="fa fa-arrow-right"></i></button>
						<h3 class="pagedisplay pull-right" style="margin-top: 5px"></h3>
						<h3 class="visible-xs pull-right" style="margin-top: 5px">page:&nbsp;</h3>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($packages as $package): ?>
					<tr>
						<td class="mobile-row-header visible-xs">
							<?php if ( ! empty($package->photo)):?>
								<div class="photo">
									<img src="<?=$package->photo;?>" alt="">
								</div>
							<?php endif; ?>
							<p class="title"><?=$package->description;?></p>
						</td>
						<td data-label="Tracking no." data-description="<?=$package->description;?>" data-photo="<?=$package->photo;?>">
							<?=HTML::anchor('package/'.$package->hashid(), $package->tracking_number, array('title' => __('Show details for package')));?>
						</td>
						<td data-label="Origin"><?=$package->origin_country->name;?></td>
						<td data-label="Destination"><?=$package->destination_country->name;?></td>
						<td data-label="Last date">
							<?php if ( ! empty($package->completed_at)): ?>
								<?=date('M j, Y', strtotime($package->completed_at));?>
							<?php elseif ( ! empty($package->dispatched_at)): ?>
								<?=date('M j, Y', strtotime($package->dispatched_at));?>
							<?php elseif ( ! empty($package->registered_at)): ?>
								<?=date('M j, Y', strtotime($package->registered_at));?>
							<?php else: ?>
								<?=date('M j, Y', strtotime($package->created_at));?>
							<?php endif; ?>
						</td>
						<td data-label="State" data-state="<?=$package->state;?>"><?=$package->state(TRUE);?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>