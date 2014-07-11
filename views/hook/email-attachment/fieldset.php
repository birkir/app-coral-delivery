
<div class="form-group">
	<?=Form::label('hookEmailSubject', __('E-Mail Subject'), array('class' => 'col-sm-2 control-label'));?>
	<div class="col-sm-3">
		<?=Form::input('email_subject', Arr::get($data, 'email_subject'), array('class' => 'form-control', 'id' => 'hookEmailSubject'));?>
	</div>
</div>

<div class="form-group">
	<?=Form::label('hookEmailBody', __('E-Mail Body'), array('class' => 'col-sm-2 control-label'));?>
	<div class="col-sm-3">
		<?=Form::textarea('email_body', Arr::get($data, 'email_body'), array('class' => 'form-control', 'id' => 'hookEmailBody', 'rows' => 3));?>
	</div>
</div>

<div class="form-group">
	<?=Form::label('hookEmailRecipent', __('E-Mail Recipent(s)'), array('class' => 'col-sm-2 control-label'));?>
	<div class="col-sm-3">
		<?=Form::input('email_recipent', Arr::get($data, 'email_recipent'), array('class' => 'form-control', 'id' => 'hookEmailRecipent'));?>
	</div>
</div>

<div class="form-group">
	<?=Form::label('hookEmailAttachment', __('E-Mail attachment'), array('class' => 'col-sm-2 control-label'));?>
	<div class="col-sm-3">
		<?php if ( ! empty(Arr::get($data, 'filepath')) AND ! empty(Arr::get($data, 'filename'))): ?>
			<input type="hidden" name="email_nofile" value="false" id="nofile" />
			<div id="nofile_label">
				<?=HTML::anchor('application/cache/uploads/'.$data['filepath'], $data['filename']);?> <a class="btn btn-xs btn-danger"href="#" onclick="document.getElementById('nofile').value = 'true';document.getElementById('nofile_label').style.display = 'none'; return false;">delete</a>
				<br /><br />
			</div>
		<?php endif; ?>
		<?=Form::file('email_attachment', NULL, NULL, array('id' => 'hookEmailAttachment'));?>
	</div>
</div>