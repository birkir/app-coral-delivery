<div class="row">
	<?=Form::open('account/register', array('method' => 'post', 'role' => 'form', 'class' => 'col-md-offset-3 col-md-5'));?>

		<legend><?=__('Register');?></legend>

		<div class="form-group">
			<?=Form::label('registerEmail', __('E-Mail address'), array('class' => 'control-label'));?>
			<?=Form::input('email', NULL, array('class' => 'form-control', 'id' => 'registerEmail', 'placeholder' => 'example@example.com', 'type' => 'email'));?>
		</div>

		<div class="form-group">
			<?=Form::label('registerPassword', __('Password'), array('class' => 'control-label'));?>
			<?=Form::password('password', NULL, array('class' => 'form-control', 'id' => 'registerPassword', 'placeholder' => __('min 6 characters')));?>
		</div>

		<div class="form-group">
			<?=Form::label('registerPasswordConfirm', __('Re-type password'), array('class' => 'control-label'));?>
			<?=Form::password('password_confirm', NULL, array('class' => 'form-control', 'id' => 'registerPasswordConfirm'));?>
		</div>

		<div class="form-group">
			<?=Form::button(NULL, __('Register'), array('class' => 'btn btn-lg btn-primary', 'type' => 'submit', 'style' => 'min-width: 100px'));?>
			&nbsp; &nbsp;
			<?=HTML::anchor('account/login', __('Cancel')); ?>
		</div>

<?=Form::close();?>
</div>