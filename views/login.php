<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>RSS Reader</title>
	<link rel="stylesheet" href="<?= site_url() ?>public_data/login.css" />
	<!--[if IE]>
	<link rel="stylesheet" href="<?= site_url() ?>public_data/normalize.css" />
	<![endif]-->
	<? if ( isset($is_phone) ): ?>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
	<? endif; ?>
	<link rel="shortcut icon" href="<?= site_url() ?>public_data/favicon.ico" />
</head>
<body>
	<div class="info" id="loader">Loading...</div>
	<div class="info" id="error"></div>
	<div class="info" id="success"></div>

	<div class="login">
		<div id="logo"></div>

		<form id="login-form">

			<div class="input">
				<label for="log-username">Username</label>
				<br />
				<input id="log-username" class="inputbox" type="text" tabindex="1" placeholder="Enter your username" autofocus="autofocus" />
			</div>

			<div class="input">
				<label for="log-password">Password</label>
				<br />
				<input id="log-password" class="inputbox" type="password" tabindex="2" placeholder="Enter your password" />
			</div>

			<div class="input">
				<input id="log-remember" type="checkbox" tabindex="3" />

				<label for="log-remember">Remember me</label>
			</div>

			<div class="controls">
				<div class="control-login">
					<button class="button-submit" id="button-login">Login</button>
				</div>

				<div class="change-form">
					<span id="button-register-form">Register now!</span>
					<br />
					<span id="button-password-form">Forgot your password?</span>
				</div>
			</div>

		</form>


		<form id ="forgoten-password-form">

			<div class="input">
				<label for="rec-email">Email</label>
				<br />
				<input id="rec-email" class="inputbox" type="text" tabindex="1" placeholder="Enter your email" autofocus="autofocus" />
			</div>

			<div class="controls">
				<div class="control-login">
					<button class="button-submit" id="button-recover">Recover</button>
				</div>
				<div class="change-form">
					<span id="button-back">Back to<br />login form</span>
				</div>
			</div>

		</form>


		<form id ="register-form">

			<div class="input">
				<label for="reg-username">Username</label>
				<br />
				<input id="reg-username" class="inputbox" type="text" tabindex="1" placeholder="Enter your username" autofocus="autofocus" />
			</div>

			<div class="input">
				<label for="reg-password">Password</label>
				<br />
				<input id="reg-password" class="inputbox" type="password" tabindex="2" placeholder="Enter your password" />
			</div>

			<div class="input">
				<label for="reg-password2">Repeat your password</label>
				<br />
				<input id="reg-password2" class="inputbox" type="password" tabindex="3" placeholder="Enter your password again" />
			</div>

			<div class="input">
				<label for="reg-email">Email</label>
				<br />
				<input id="reg-email" class="inputbox" type="text" tabindex="4" placeholder="Enter your email" />
			</div>

			<div class="controls">
				<div class="control-login">
					<button class="button-submit" id="button-register">Register</button>
				</div>
				<div class="change-form">
					<span id="button-back2">Back to<br />login form</span>
				</div>
			</div>

		</form>

	</div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="<?= site_url() ?>public_data/login.js"></script>
</body>
</html>
