<?=View::factory('mail/header');?>
<p>
	Dear Customer,<br/>
	Password change was requested for your account.
</p>

<p>Please click button below if you want to proceed or ignore this email if you want to keep your old password.</p>

<p style="text-align: center" align="center">
	<a href="http://coral.forritun.org<?=$link;?>" style=" line-height: 50px; background: #61d2d6; font-family: sans-serif; color: #fff; text-decoration: none; font-size: 15px;padding: 10px 20px;box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.1);">
		Confirm password
	</a>
</p>

<?=View::factory('mail/footer');?>