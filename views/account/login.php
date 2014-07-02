<div class="row">
	<?=Form::open('account/login', array('method' => 'post', 'role' => 'form', 'class' => 'col-md-offset-3 col-md-5'));?>

		<legend><?=__('Login');?></legend>

		<?php if ($failed): ?>
			<div class="alert alert-warning">
				<?=__('Could not login. Please check email or password and try again.');?>
			</div>
		<?php endif; ?>

		<div class="form-group">
			<?=Form::label('loginEmail', __('E-Mail address'), array('class' => 'control-label'));?>
			<?=Form::input('email', NULL, array('class' => 'form-control', 'id' => 'loginEmail', 'type' => 'email'));?>
		</div>

		<div class="form-group">
			<?=Form::label('loginPassword', __('Password'), array('class' => 'control-label'));?>
			<?=Form::password('password', NULL, array('class' => 'form-control', 'id' => 'loginPassword'));?>
		</div>

		<div class="form-group">
			<label class="control-label inline-checkbox" for="loginRemember">
				<?=Form::checkbox('remember', TRUE, TRUE, array('id' => 'loginRemember'));?>
				&nbsp;&nbsp;<?=__('Remember me');?>
			</label>
		</div>

		<div class="form-group">
			<?=Form::button(NULL, __('Login'), array('class' => 'btn btn-lg btn-primary', 'type' => 'submit', 'style' => 'min-width: 100px'));?>
		</div>

	<?=Form::close();?>
</div>