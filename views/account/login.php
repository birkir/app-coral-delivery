<div class="row">
	<div class="col-sm-offset-4 col-sm-4">
		<div class="text-center">
			<br /><br />
			<?=HTML::anchor('/', HTML::image('media/img/logo.png', array('style' => 'margin-top: -10px')).' '.__('Coral Delivery'), array('style' => 'font-size: 21px; font-weight: 500; color: #555; text-decoration: none;'));?>
			<br /><br />
		</div>

		<?php if ($failed): ?>
			<div class="alert alert-warning alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<strong><?=__('Unable to login');?>!</strong><br>
				<?=__('Please check email or password and try again or :lostpwd', array(
				':lostpwd' => HTML::anchor('reset', __('reset your password'))));?>.
			</div>
		<?php endif; ?>

		<?php if (isset($oauth_errors)): ?>
			<div class="alert alert-warning alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<strong><?=__('Authentication error');?></strong><br>
				<?=Arr::get($oauth_errors, 'error', 'unknown_error');?>
			</div>
		<?php endif; ?>

		<?=Form::open('login', array('method' => 'post', 'role' => 'form', 'class' => 'main', 'style' => 'border-bottom-width: 2px; margin-bottom: 10px'));?>

			<div class="form-group">
				<?=Form::label('loginEmail', __('E-Mail address'), array('class' => 'control-label'));?>
				<?=Form::input('email', NULL, array('class' => 'form-control input-lg', 'id' => 'loginEmail', 'type' => 'email'));?>
			</div>

			<div class="form-group">
				<?=Form::label('loginPassword', __('Password'), array('class' => 'control-label'));?>
				<?=Form::password('password', NULL, array('class' => 'form-control input-lg', 'id' => 'loginPassword'));?>
			</div>

			<div class="form-group">
				<?=Form::button(NULL, __('Login'), array('class' => 'btn btn-lg btn-info', 'type' => 'submit', 'style' => 'min-width: 110px'));?>
				<label class="control-label inline-checkbox pull-right" for="loginRemember" style="margin-top: 8px; margin-right: 8px;">
					<?=Form::checkbox('remember', TRUE, TRUE, array('id' => 'loginRemember'));?>
					&nbsp;&nbsp;<?=__('Remember me');?>
				</label>
			</div>

			<div class="text-center" style="width: 200px; background: #fff; margin: 20px auto -30px auto; position: relative;">
				<?=__(':signup or social network', array(
					':signup' => HTML::anchor('register', __('sign up'), array('style' => 'font-weight: bold'))));?>
			</div>

			<hr />

			<div class="text-center social-authentication" style="padding-top: 10px;">
				<a href="/link/facebook" class="btn btn-lg btn-facebook"><i class="fa fa-facebook"></i></a>
				<a href="/link/google" class="btn btn-lg btn-google"><i class="fa fa-google"></i></a>
				<a href="/link/github" class="btn btn-lg btn-github"><i class="fa fa-github-alt"></i></a>
			</div>

		<?=Form::close();?>
		<div class="text-center">
			<a href="/reset" class="text-muted"><small>lost password?</small></a>
		</div>
	</div>
</div>