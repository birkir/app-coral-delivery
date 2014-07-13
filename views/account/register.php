<div class="row">
	<div class="col-sm-offset-4 col-sm-4">
		<div class="text-center">
			<br /><br />
			<?=HTML::anchor('/', HTML::image('media/img/logo.png', array('style' => 'margin-top: -10px')).' '.__('Coral Delivery'), array('style' => 'font-size: 21px; font-weight: 500; color: #555; text-decoration: none;'));?>
			<br /><br />
		</div>

		<?php if ($errors): ?>
			<div class="alert alert-warning">
				<strong>Please review your input</strong><br>
				<ul>
					<?php foreach ($errors as $field => $error): ?>
						<?php if (is_array($error)): ?>
							<?php foreach ($error as $_field => $_error): ?>
								<li><?=UTF8::ucfirst($_error);?></li>
							<?php endforeach; ?>
						<?php else: ?>
							<li><?=UTF8::ucfirst($error);?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?=Form::open('register', array('method' => 'post', 'role' => 'form', 'class' => 'main', 'style' => 'border-bottom-width: 2px; margin-bottom: 10px'));?>

			<div class="form-group">
				<?=Form::label('registerName', __('Full name'), array('class' => 'control-label'));?>
				<?=Form::input('fullname', NULL, array('class' => 'form-control input-lg', 'id' => 'registerName', 'placeholder' => 'John doe'));?>
			</div>

			<div class="form-group">
				<?=Form::label('registerEmail', __('E-Mail address'), array('class' => 'control-label'));?>
				<?=Form::input('email', NULL, array('class' => 'form-control input-lg', 'id' => 'registerEmail', 'placeholder' => 'example@example.com', 'type' => 'email'));?>
			</div>

			<div class="form-group">
				<?=Form::label('registerPassword', __('Password'), array('class' => 'control-label'));?>
				<?=Form::password('password', NULL, array('class' => 'form-control input-lg', 'id' => 'registerPassword', 'placeholder' => __('min 6 characters')));?>
			</div>

			<div class="form-group">
				<?=Form::label('registerPasswordConfirm', __('Re-type password'), array('class' => 'control-label'));?>
				<?=Form::password('password_confirm', NULL, array('class' => 'form-control input-lg', 'id' => 'registerPasswordConfirm'));?>
			</div>

			<div class="form-group">
				<?=Form::button(NULL, __('Register'), array('class' => 'btn btn-lg btn-info', 'type' => 'submit', 'style' => 'min-width: 110px'));?>
				&nbsp; &nbsp;
				<?=HTML::anchor('login', __('Cancel')); ?>
			</div>

		<?=Form::close();?>
	</div>
</div>