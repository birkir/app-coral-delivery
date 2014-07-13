<div class="row">
	<div class="col-sm-offset-4 col-sm-4">
		<div class="text-center">
			<br /><br />
			<?=HTML::anchor('/', HTML::image('media/img/logo.png', array('style' => 'margin-top: -10px')).' '.__('Coral Delivery'), array('style' => 'font-size: 21px; font-weight: 500; color: #555; text-decoration: none;'));?>
			<br /><br />
		</div>

		<?php if ($errors): ?>
			<div class="alert alert-warning">
				<strong>Could not reset password!</strong><br>
				Make sure you are typing correct email or try using social 
			</div>
		<?php endif; ?>

		<?=Form::open('reset', array('method' => 'post', 'role' => 'form', 'class' => 'main', 'style' => 'border-bottom-width: 2px; margin-bottom: 10px'));?>

			<div class="form-group">
				<?=Form::label('registerEmail', __('E-Mail address'), array('class' => 'control-label'));?>
				<?=Form::input('email', NULL, array('class' => 'form-control input-lg', 'id' => 'registerEmail', 'placeholder' => 'example@example.com', 'type' => 'email'));?>
			</div>

			<div class="form-group">
				<?=Form::button(NULL, __('Reset password'), array('class' => 'btn btn-lg btn-info', 'type' => 'submit', 'style' => 'min-width: 110px'));?>
				&nbsp; &nbsp;
				<?=HTML::anchor('login', __('Cancel')); ?>
			</div>

		<?=Form::close();?>
	</div>
</div>