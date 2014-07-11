<div class="page-header">
	<div class="pull-right btn-toolbar">
		<?=HTML::anchor('hook/list/'.$package->tracking_number, __('Manage hooks'), array('class' => 'btn btn-lg btn-info btn-mobile-block'));?>
		<?=HTML::anchor('package/edit/'.$package->tracking_number, __('Edit package'), array('class' => 'btn btn-lg btn-warning btn-mobile-block'));?>
		<?php if ($user->is_admin()): ?>
			<div class="btn-group btn-mobile-left">
				<?=HTML::anchor('package/refresh/'.$package->tracking_number, __('Refresh'), array('class' => 'btn btn-lg btn-primary col-xs-9'));?>
				<button type="button" class="btn btn-primary btn-lg col-xs-3 dropdown-toggle" data-toggle="dropdown">
					<span class="caret"></span>
					<span class="sr-only"><?=__('Toggle Dropdown');?></span>
				</button>
				<ul class="dropdown-menu" role="menu">
					<li><?=HTML::anchor('package/refresh/'.$package->tracking_number.'?direction='.Carrier::ORIGIN, __('Origin carrier only'));?></li>
					<li><?=HTML::anchor('package/refresh/'.$package->tracking_number.'?direction='.Carrier::DESTINATION, __('Destination carrier only'));?></li>
				</ul>
			</div>
		<?php endif; ?>
		<?=HTML::anchor('package', __('Go back'), array('class' => 'btn btn-lg btn-default btn-mobile-right'));?>
	</div>
	<h2><?=__('Package detail');?></h2>
</div>

<div class="row">
	<div class="col-sm-6">
		<dl class="dl-horizontal">
			<dt><?=__('Tracking number');?></dt>
			<dd><?=$package->tracking_number;?></dd>
			<?php if ( ! empty($package->destination_tracking_number)): ?>
				<dd><?=$package->destination_tracking_number;?> <small class="text-muted">(destination)</small></dd>
			<?php endif; ?>

			<dt><?=__('Description');?></dt>
			<dd><?=$package->description;?></dd>

			<dt><?=__('Weight');?></dt>
			<dd><?=empty($package->weight) ? __('Not reported') : number_format($package->weight, 0).'g';?></dd>

			<dt><?=__('Registered');?></dt>
			<dd><?=empty($package->registered_at) ? __('Not registered') : date('M j, Y - H:i', strtotime($package->registered_at));?></dd>

			<dt><?=__('Dispatched');?></dt>
			<dd><?=empty($package->dispatched_at) ? __('Not dispatched') : date('M j, Y - H:i', strtotime($package->dispatched_at));?></dd>

			<dt><?=__('Completed');?></dt>
			<dd><?=empty($package->completed_at) ? __('Not completed') : date('M j, Y - H:i', strtotime($package->completed_at));?></dd>
		</dl>
	</div>
	<div class="col-sm-6">
		<dl class="dl-horizontal">
			<dt><?=__('Status');?></dt>
			<dd><?=$package->state(TRUE);?></dd>

			<?php if (isset($extra->customs_type) OR isset($extra->customs_number)): ?>
				<dt><?=__('Customs details');?></dt>
				<?php if (isset($extra->customs_type)): ?><dd><?=$extra->customs_type;?></dd><?php endif; ?>
				<?php if (isset($extra->customs_number)): ?><dd><?=$extra->customs_number;?></dd><?php endif; ?>
			<?php endif; ?>

			<?php if (isset($extra->payment)): ?>
				<dt><?=__('Payment');?></dt>
				<dd><?=number_format($extra->payment->amount, 0, NULL, '.');?> <?=$extra->payment->currency;?></dd>
			<?php endif; ?>

			<?php if (isset($extra->signature)): ?>
				<dt><?=__('Signature');?></dt>
				<dd><?=$extra->signature;?></dd>
			<?php endif; ?>

			<?php if ( ! empty($package->photo)): ?>
				<dt><?=__('Photo');?></dt>
				<dd>
					<img src="<?=$package->photo;?>" alt="" width="100" class="img-thumbnail">
				</dd>
			<?php endif; ?>
		</dl>
	</div>
</div>

<hr>
<?php if (intval($package->destination_carrier_id) > 0 AND intval($package->destination_carrier_id) !== 2): ?>
	<h3><?=__('Destination (:country)', array(':country' => $package->destination_location));?></h3>
	<table class="table table-block">
		<thead>
			<tr>
				<th width="15%" colspan="2"><?=__('Date');?></th>
				<th width="40%"><?=__('Location');?></th>
				<th width="45%"><?=__('Message');?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($destination as $status): ?>
				<tr data-coordinates="<?=$status->coordinates;?>">
					<td width="7%" data-label="<?=__('Date');?>">
						<?php $dt = new DateTime($status->timestamp); ?>
						<time datetime="<?=$dt->format('c');?>"><?=$dt->format('M j');?></time>
					</td>
					<td class="text-muted" data-label="<?=__('Time');?>">
						<?php if (intval($dt->format('Hi')) > 0): ?>
							<?=$dt->format('H:i');?>
						<?php endif; ?>
					</td>
					<td data-label="<?=__('Location');?>"><?=$status->location;?></td>
					<td data-label="<?=__('Message');?>"><?=$status->message;?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h3><?=__('Origin (:country)', array(':country' => $package->origin_location));?></h3>

<?php else: ?>
	<h3>From <strong><?=$package->origin_location;?></strong> to <strong><?=$package->destination_location;?></strong></h3>
<?php endif; ?>

<table class="table table-block">
	<thead>
		<tr>
			<th width="15%" colspan="2"><?=__('Date');?></th>
			<th width="40%"><?=__('Location');?></th>
			<th width="45%"><?=__('Message');?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($origin as $status): ?>
			<tr data-coordinates="<?=$status->coordinates;?>">
				<td width="7%" data-label="<?=__('Date');?>">
					<?php $dt = new DateTime($status->timestamp); ?>
					<time datetime="<?=$dt->format('c');?>"><?=$dt->format('M j');?></time>
				</td>
				<td class="text-muted" data-label="<?=__('Time');?>">
					<?php if (intval($dt->format('Hi')) > 0): ?>
						<?=$dt->format('H:i');?>
					<?php endif; ?>
				</td>
				<td data-label="<?=__('Location');?>"><?=$status->location;?></td>
				<td data-label="<?=__('Message');?>"><?=$status->message;?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<h3><?=__('Tracking map');?></h3>

<div id="map-canvas" style="width: 100%; height: 480px;"></div>

<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js"></script>
<script type="text/javascript">
  function initialize() {

	var style = [{"stylers": [{"visibility": "off"}]}, {"featureType": "road","stylers": [{"visibility": "on"}, {"color": "#ffffff"}]}, {"featureType": "road.arterial","stylers": [{"visibility": "on"}, {"color": "#fee379"}]}, {"featureType": "road.highway","stylers": [{"visibility": "on"}, {"color": "#fee379"}]}, {"featureType": "landscape","stylers": [{"visibility": "on"}, {"color": "#f3f4f4"}]}, {"featureType": "water","stylers": [{"visibility": "on"}, {"color": "#7fc8ed"}]}, {}, {"featureType": "road","elementType": "labels","stylers": [{"visibility": "off"}]}, {"featureType": "poi.park","elementType": "geometry.fill","stylers": [{"visibility": "on"}, {"color": "#83cead"}]}, {"elementType": "labels","stylers": [{"visibility": "off"}]}, {"featureType": "landscape.man_made","elementType": "geometry","stylers": [{"weight": 0.9}, {"visibility": "off"}]}];
	var mapOptions = {
	  center: new google.maps.LatLng(0, 0),
	  zoom: 3
	};

	var map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
	map.setOptions({
        styles: style
    });
	var planCoordinates = [];
	var bounds = new google.maps.LatLngBounds();
    var image = {
        url: 'https://dl.dropboxusercontent.com/u/814783/fiddle/marker.png',
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(12, 59)
    };


	$($('[data-coordinates]').get().reverse()).each(function () {
		
		var coords = $(this).data('coordinates'),
			latLng = coords.split(','),
			lat = parseFloat(latLng[0], 0) || null,
			lng = parseFloat(latLng[1], 0) || null;

		if (lat && lng) {
		
			var x = new google.maps.LatLng(lat, lng);
		
			for (var i = 0; i < planCoordinates.length; i++) {
				if (planCoordinates[i].equals(x)) return;
			}

			this.marker = new google.maps.Marker({
				position: x,
				map: map,
				icon: image
			});
			planCoordinates.push(x);
			bounds.extend(x);
		}

		var flightPath = new google.maps.Polyline({
			path: planCoordinates,
			geodesic: true,
			strokeColor: '#f0ad4e',
			strokeOpacity: 1.0,
			strokeWeight: 4
		});

		flightPath.setMap(map);
		map.fitBounds(bounds);
	});

  }
  google.maps.event.addDomListener(window, 'load', initialize);
</script>