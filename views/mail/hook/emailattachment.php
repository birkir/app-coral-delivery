<?=View::factory('mail/header');?>

<p>
	<?=__('Dear Customer');?>,<br/>
	<?=__('We just sent an email to :emailrecipant with attachment for your package.', array(
		':emailrecipant' => Arr::get($data, 'email_recipant', 'unknown')));?>
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
	<a href="http://coral.forritun.org/package/<?=$package->hashid();?>" style=" line-height: 50px; background: #61d2d6; font-family: sans-serif; color: #fff; text-decoration: none; font-size: 15px;padding: 10px 20px;box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.1);">
		<?=__('View package details');?>
	</a>
</p>

<?=View::factory('mail/footer');?>