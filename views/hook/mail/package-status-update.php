<?=View::factory('mail/header');?>
<?php
	$state = intval($package->state);
	$labels = array(
		Model_Package::IN_TRANSIT => array('on the way', 'Stay tuned', '#61d2d6'),
		Model_Package::IN_CUSTOMS => array('in customs', 'Prepare to contact them with package contents and toll', '#f0ad4e'),
		Model_Package::PICK_UP    => array('ready to be picked up', 'Contact your post office if you want it delivered', '#5cb85c'),
		Model_Package::DELIVERED  => array('delivered', 'Enjoy your package contents', '#5cb85c')
	);
?>
<p>
	<?=__('Dear Customer');?>,<br/>
	<?=__('Your package is now :status. :thanks.', array(
		':status' => '<strong style="color: '.$labels[$state][2].';">'.__($labels[$state][0]).'</strong>. ',
		':thanks' => __($labels[$state][1]).'.'));?>
</p>

<p style="border: 1px solid #eee;padding: 15px; font-size:14px;background: #f5f5f5;">
	<?php if ( ! empty($package->photo)): ?>
		<img src="<?=$package->photo;?>" alt="" float="right" style="float: right;border: 1px solid #ddd;margin-left: 15px;" width="100">
	<?php endif; ?>
	<strong><?=$package->tracking_number;?></strong>
	<br>
	<span><?=$package->description;?></span>
	<br style="clear: both;">
</p>

<p style="text-align: center" align="center">
	<a href="http://coral.forritun.org/package/detail/<?=$package->tracking_number;?>" style=" line-height: 50px; background: #61d2d6; font-family: sans-serif; color: #fff; text-decoration: none; font-size: 15px;padding: 10px 20px;box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.1);">
		<?=__('View package details');?>
	</a>
</p>

<?=View::factory('mail/footer');?>