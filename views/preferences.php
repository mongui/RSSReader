<form class="content-form" id="preferences-form">
	<script>
		var timeformat = '<?= $_SESSION['timeformat'] ?>';
		var language = '<?= $_SESSION['language'] ?>';
	</script>

    <div id="error" class="info"></div>
    <div id="success" class="info"></div>

	<fieldset>
		<legend>Your preferences</legend>
		<div class="input">
			<label for="email">Your email</label>
			<input id="email" class="inputbox" type="text" value="<?= $_SESSION['email'] ?>" disabled="disabled" />
		</div>

		<div class="input">
			<label for="timeformat">Display time format</label>
			<select id="timeformat" class="inputbox" tabindex="1">
				<option value="M d, Y">Jun 24, 2013</option>
				<option value="M d, Y H:i">Jun 24, 2013 23:56</option>
				<option value="M d, Y h:i A">Jun 24, 2013 11:56 PM</option>
				<option>-----------------------------</option>
				<option value="l, M d">Tue, Jun 24</option>
				<option value="l, M d H:i">Tue, Jun 24 23:56</option>
				<option value="l, M d h:i A">Tue, Jun 24 11:56 PM</option>
				<option>-----------------------------</option>
				<option value="Y-m-d">2013-05-24</option>
				<option value="m-d-Y">06-24-2013</option>
				<option value="d-m-Y">24-06-2013</option>
				<option>-----------------------------</option>
				<option value="d/m">24/06</option>
				<option value="d/m/Y">24/06/2013</option>
				<option value="m/d/Y">06/24/2013</option>
				<option value="Y/m/d">2013/06/24</option>
			</select>
		</div>

		<div class="input">
			<label for="language">Display language</label>
			<select id="language" class="inputbox" tabindex="2">
				<option value="en">English</option>
				<option value="es">Spanish</option>
			</select>
		</div>
	</fieldset>

	<fieldset>
		<legend>Change password</legend>
		<div class="input">
			<label for="cur-password">Current password</label>
			<input id="cur-password" class="inputbox" type="password" tabindex="3" />
		</div>

		<div class="input">
			<label for="new-password">New password</label>
			<input id="new-password" class="inputbox" type="password" tabindex="4" />
		</div>

		<div class="input">
			<label for="new-password2">Repeat new password</label>
			<input id="new-password2" class="inputbox" type="password" tabindex="5" />
		</div>
	</fieldset>

	<? if ( isset($is_admin) ): ?>
	<script>
	function serverData() {
		var srvData = {
			timezone:		$('#timezone').val(),
			mins_updates:   $('#mins_updates').val(),
			max_feeds:      $('#max_feeds').val(),
			feed_updatable: $('#feed_updatable').val()
		};

		return srvData;
	}
	</script>

	<fieldset>
		<legend>Server configuration</legend>
		<div class="input">
			<label for="timezone">Timezone of the server</label>
			<select id="timezone" class="inputbox" tabindex="6">
				<? foreach ( $timezones as $tz ): ?>
				<option value="<?= $tz ?>" <?= ($this->config->get('timezone') == $tz) ? 'selected="selected"' : '' ?>>
					<?= $tz ?>
				</option>
				<? endforeach; ?>
			</select>
		</div>

		<div class="input">
			<label for="mins_updates">Minutes between feeds updates</label>
			<input id="mins_updates" class="inputbox" type="text" tabindex="7" value="<?= $this->config->get('minutes_between_updates') ?>" />
		</div>

		<div class="input">
			<label for="max_feeds">Max. feeds per update</label>
			<input id="max_feeds" class="inputbox" type="text" tabindex="8" value="<?= $this->config->get('max_feeds_per_update') ?>" />
		</div>

		<div class="input">
			<label for="feed_updatable">Users can update feeds</label>
			<input id="feed_updatable" class="inputbox" type="checkbox" tabindex="9" value="1" <?= ($this->config->get('feed_updatable') == 'true') ? 'checked="checked"' : '' ?> />
			Yes
		</div>
	</fieldset>
	<? endif; ?>

	<button class="submit-button" id="submit-preferences">Update preferences</button>
</form>