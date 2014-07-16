<div class="page-header">
	<p class="text-info pull-right hidden-xs"><?=__('Last logged in :timestamp.', array(':timestamp' => Date::fuzzy_span($user->last_login)));?></p>
	<h2><?=__('Profile');?></h2>
</div>

<div class="row">
	<div class="col-sm-6">
		<?=Form::open('profile', array('method' => 'post', 'role' => 'form', 'class' => 'form-horizontal', 'autocomplete' => 'off', 'data-widget' => 'account/profile'));?>

			<?php if (isset($error)): ?>
				<div class="alert alert-warning">
					<p><?=$error;?></p>
				</div>
			<?php endif; ?>

			<div class="form-group">
				<?=Form::label('profileFullname', __('Full name'), array('class' => 'col-sm-4 control-label'));?>
				<div class="col-sm-6">
					<input type="text" name="fullname" class="hidden">
					<?=Form::input('fullname', $user->fullname, array('class' => 'form-control', 'id' => 'profileFullname', 'autocomplete' => 'off'));?>
				</div>
			</div>

			<div class="form-group">
				<?=Form::label('profileCountry', __('Country'), array('class' => 'col-sm-4 control-label'));?>
				<div class="col-sm-6">
					<?=Form::select('country_id', array('' => '') + ORM::factory('Country')->group_by('name')->order_by('name', 'ASC')->find_all()->as_array('id', 'name'), $user->country_id, array('class' => 'form-control', 'id' => 'profileCountry', 'placeholder' => 'Select country'));?>
				</div>
			</div>

			<div class="form-group">
				<?=Form::label('profileLanguage', __('Language'), array('class' => 'col-sm-4 control-label'));?>
				<div class="col-sm-6">
					<?=Form::select('language', array('' => '') + $languages, $user->language, array('class' => 'form-control', 'id' => 'profileLanguage', 'placeholder' => 'Select language'));?>
				</div>
			</div>

			<div class="form-group">
				<?=Form::label('profileEmail', __('E-Mail address'), array('class' => 'col-sm-4 control-label'));?>
				<div class="col-sm-6">
					<input type="text" name="email" class="hidden">
					<?=Form::input('email', $user->email, array('class' => 'form-control', 'id' => 'profileEmail', 'data-original' => $user->email, 'autocomplete' => 'off'));?>
					<small class="help-block hidden" data-toggle-onchange="true">
						<?=__('Confirmation link will be sent to your new email address.');?>
					</small>
				</div>
			</div>

			<div class="form-group">
				<?=Form::label('profilePassword', __('Password'), array('class' => 'col-sm-4 control-label'));?>
				<div class="col-sm-6">
					<input type="text" name="password" class="hidden">
					<?=Form::password('password', NULL, array('class' => 'form-control', 'id' => 'profilePassword', 'placeholder' => '******', 'autocomplete' => 'off'));?>
					<div class="help-block strength password-not-empty hidden">
						<div class="bar b1"></div>
						<div class="bar b2"></div>
						<div class="bar b3"></div>
						<div class="bar b4"></div>
					</div>
					<small class="help-block password-not-empty hidden" style="font-style: italic; margin-bottom: 0"><?=_('Can be cracked in');?> <span class="crack-time"></span></small>
				</div>
			</div>

			<div class="form-group password-not-empty hidden">
				<?=Form::label('profilePasswordConfirm', __('Confirm password'), array('class' => 'col-sm-4 control-label'));?>
				<div class="col-sm-6">
					<input type="text" name="password_confirm" class="hidden">
					<?=Form::password('password_confirm', NULL, array('class' => 'form-control', 'id' => 'profilePasswordConfirm', 'autocomplete' => 'off'));?>
					<small class="help-block">
						<span class="pwd-confirm text-success hidden"><?=__('Confirmation link will be sent to your email address.');?></span>
						<span class="pwd-error text-danger hidden"><?=__('The passwords are not matching, please try again.');?>
					</small>
				</div>
			</div>

			<div class="form-group">
				<div class="col-sm-offset-4" style="padding-left: 15px">
					<?=Form::button(NULL, __('Save changes'), array('type' => 'submit', 'class' => 'btn btn-lg btn-success'));?>
					<?php if ($success): ?>
						<small class="text-success" data-hide="2500"><br><br><?=__('Your profile has been updated!');?></small>
					<?php endif; ?>
				</div>
			</div>

		<?=Form::close();?>

	</div>
	<div class="col-sm-6">
		<h3 style="margin-top: 0"><?=__('Linked applications');?></h3>
		<table class="table table-condensed table-block">
			<thead>
				<tr>
					<th><?=__('Name');?></th>
					<th><?=__('Last used');?></th>
					<th><?=__('Actions');?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($user->auths->find_all() as $item): ?>
					<tr>
						<td data-label="<?=__('Name');?>"><i class="fa fa-<?=$item->method;?>"></i>&nbsp;&nbsp;<?=UTF8::ucfirst($item->method);?></td>
						<td data-label="<?=__('Last used');?>"><?=$item->updated_at;?></td>
						<td data-label="<?=__('Actions');?>"><?=HTML::anchor('unlink/'.$item->id, __('unlink'), array('onclick' => 'return confirm(\''.__('Are you sure you want to delete this linked authentication?').'\');'));?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>