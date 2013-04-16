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
			<input id="email" class="inputbox" type="text" tabindex="1" value="<?= $_SESSION['email'] ?>" disabled="disabled" />
		</div>

		<div class="input">
			<label for="timeformat">Display time format</label>
			<select id="timeformat" class="inputbox">
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
			<select id="language" class="inputbox">
				<option value="en">English</option>
				<option value="es">Spanish</option>
			</select>
		</div>
	</fieldset>

	<fieldset>
		<legend>Change password</legend>
		<div class="input">
			<label for="cur-password">Current password</label>
			<input id="cur-password" class="inputbox" type="password" tabindex="1" />
		</div>

		<div class="input">
			<label for="new-password">New password</label>
			<input id="new-password" class="inputbox" type="password" tabindex="2" />
		</div>

		<div class="input">
			<label for="new-password2">Repeat new password</label>
			<input id="new-password2" class="inputbox" type="password" tabindex="3" />
		</div>
	</fieldset>


	<button class="submit-button" id="submit-preferences">Update preferences</button>
</form>