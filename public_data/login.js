$(document).ready(function ()
{
	$("form").submit(function(e) {
		e.preventDefault();
	});

	/******* LOGIN FORM *******/

	$("#button-login").click(function() {
		$(".info").fadeOut();
		var error = false;

		var logUsername	= $("#log-username");
		var logPass		= $("#log-password");
		var logRemember	= $("#log-remember");

		if ( logUsername.val() === '' ) {
			logUsername.addClass('empty-input');
			error = true;
		}
		else {
			logUsername.removeClass('empty-input');
		}

		if ( logPass.val() === '' ) {
			logPass.addClass('empty-input');
			error = true;
		}
		else {
			logPass.removeClass('empty-input');
		}

		if ( error ) {
			return false;
		}

		$("#loader").fadeIn();

		$.ajax({
			type: "POST",
			url: "login",
			data: {
				username: logUsername.val(),
				password: logPass.val(),
				remember: ( logRemember.is(':checked') ) ? 'yes' : 'no'
			}
		}).done(function(msg) {
			if ( msg === 'success' ) {
				window.location = './';
			}
			else if ( msg === 'failure' ) {
				$(".info").fadeOut();
				$("#error").text('Invalid username/password.');
				$("#error").fadeIn();
			}
		});
	});

	$("#button-password-form").click(function() {
		$(".info").fadeOut();
		$("#login-form").fadeOut(300, function() {
			$(".login").animate({ height: '230' }, 300, 'swing', function() {
				$("#forgoten-password-form").fadeIn();
			});
		});
	});

	$("#button-register-form").click(function() {
		$(".info").fadeOut();
		$("#login-form").fadeOut(300, function() {
			$(".login").animate({ height: '450' }, 300, 'swing', function() {
				$("#register-form").fadeIn(300);
			});
		});
	});

	/******* FORGOTEN PASSWORD FORM *******/

	$("#button-recover").click(function() {
		$(".info").fadeOut();

		var error = false;

		var recEmail = $("#rec-email");
		if ( recEmail.val() == '' ) {
			recEmail.addClass('empty-input');
			error = true;
		}
		else {
			recEmail.removeClass('empty-input');
		}

		if ( !check_email(recEmail.val()) ) {
			$("#error").text('Invalid email address.');
			$("#error").fadeIn();
			error = true;
		}

		if ( error ) {
			return false;
		}

		$("#loader").fadeIn();

		$.ajax({
			type: "POST",
			url: "login/recover",
			data: {
				email: recEmail.val()
			}
		}).done(function(msg) {
			if ( msg === 'success' ) {
				$(".info").fadeOut();
				$("#success").text('Your new password has been sent to your email.');
				$("#success").fadeIn();
			}
		});
	});

	$("#button-back").click(function() {
		$(".info").fadeOut();
		$("#forgoten-password-form").fadeOut(300, function() {
			$(".login").animate({ height: '350' }, 300, 'swing', function() {
				$("#login-form").fadeIn(300);
			});
		});
	});

	/******* REGISTER FORM *******/

	$("#button-register").click(function() {
		$(".info").fadeOut();

		var error = false;

		var regUsername	= $("#reg-username");
		var regPass		= $("#reg-password");
		var regPass2	= $("#reg-password2");
		var regEmail	= $("#reg-email");

		$("#error").text('');

		if ( regUsername.val() == '' ) {
			regUsername.addClass('empty-input');
			error = true;
		}
		else {
			regUsername.removeClass('empty-input');
		}

		if ( regPass.val().length < 6 ) {
			regPass.addClass('empty-input');
			regPass2.addClass('empty-input');
			$("#error").text('Your password must be at least 6 characters.');
			$("#error").fadeIn();
			error = true;
		}
		else if ( regPass.val() !== regPass2.val() ) {
			regPass.addClass('empty-input');
			regPass2.addClass('empty-input');
			$("#error").html('Passwords do not match.');
			$("#error").fadeIn();
			error = true;
		}
		else {
			regPass.removeClass('empty-input');
			regPass2.removeClass('empty-input');
		}

		if ( regEmail.val() == '' ) {
			regEmail.addClass('empty-input');
			error = true;
		}
		else {
			regEmail.removeClass('empty-input');
		}

		if ( !check_email(regEmail.val()) ) {
			$("#error").html($("#error").text() + '<br>Invalid email address.');
			$("#error").fadeIn();
			error = true;
		}

		if ( error ) {
			return false;
		}

		$("#loader").fadeIn();

		$.ajax({
			type: "POST",
			url: "login/register",
			data: {
				username: regUsername.val(),
				password: regPass.val(),
				email: regEmail.val()
			}
		}).done(function(msg) {
			if ( msg === 'success' )
			{
				$(".info").fadeOut();
				$("#success").text('Welcome! You are now registered. Go back and login with your user and pass.');
				$("#success").fadeIn();
			}
			else if ( msg === 'user' )
			{
				$(".info").fadeOut();
				$("#error").text('Sorry, that username is already registered.');
				$("#error").fadeIn();
			}
			else if ( msg === 'email' )
			{
				$(".info").fadeOut();
				$("#error").text('Sorry, that email is already registered.');
				$("#error").fadeIn();
			}
			else if ( msg === 'failure' )
			{
				$(".info").fadeOut();
				$("#error").text('Something went wrong on the server. Please, try again later.');
				$("#error").fadeIn();
			}
		});
	});

	$("#button-back2").click(function() {
		$(".info").fadeOut();
		$("#register-form").fadeOut(300, function() {
			$(".login").animate({ height: '350' }, 300, 'swing', function() {
				$("#login-form").fadeIn(300);
			});
		});
	});
});

function check_email(email) {
	var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
	return emailReg.test(email);
}
