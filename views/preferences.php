<form class="content-form" id="preferences-form">
	<script>
		var timeformat = '<?= $_SESSION['timeformat'] ?>';
		var language = '<?= $_SESSION['language'] ?>';
	</script>
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
			mins_updates:	$('#mins_updates').val(),
			max_feeds:		$('#max_feeds').val(),
			show_favicons:	$('#show_favicons').is(':checked') ? 'true' : 'false',
			feed_updatable:	$('#feed_updatable').is(':checked') ? 'true' : 'false'
		};

		return srvData;
	}
	</script>

	<fieldset>
		<legend>Server configuration</legend>
		<div class="input">
			<label for="timezone">Timezone of the server</label>
			<select id="timezone" class="inputbox" tabindex="6">
				<? foreach ($timezones as $tz): ?>
				<option value="<?= $tz ?>" <?= ($timezone == $tz) ? 'selected="selected"' : '' ?>><?= $tz ?></option>
				<? endforeach; ?>
			</select>
		</div>

		<div class="input">
			<label for="mins_updates">Minutes between feeds updates</label>
			<input id="mins_updates" class="inputbox" type="text" tabindex="7" value="<?= $minutes_between_updates ?>" />
		</div>

		<div class="input">
			<label for="max_feeds">Max. feeds per update</label>
			<input id="max_feeds" class="inputbox" type="text" tabindex="8" value="<?= $max_feeds_per_update ?>" />
		</div>

		<div class="input">
			<label for="show_favicons">Show favicons in the feedlist</label>
			<input id="show_favicons" class="inputbox" type="checkbox" tabindex="9" value="1" <?= ($show_favicons) ? 'checked="checked"' : '' ?> />
			Yes
		</div>

		<div class="input">
			<label for="feed_updatable">Users can update feeds</label>
			<input id="feed_updatable" class="inputbox" type="checkbox" tabindex="10" value="1" <?= ($feed_updatable) ? 'checked="checked"' : '' ?> />
			Yes
		</div>
	</fieldset>
	<? endif; ?>

	<button class="submit-button" id="submit-preferences">Update preferences</button>
</form>

<? if ( isset($is_admin) ): ?>
<div class="content-form">
	<fieldset>
		<legend>Global feed list</legend>
		<table>
			<tr>
				<th>Feed name</th>
				<th colspan="4">Conf.</th>
			</tr>
			<? foreach($feed_list as $feed): ?>
			<tr <?= ($feed->active == 0) ? 'class="inactive"' : '' ?> >
				<td><?= $feed->name ?></td>
				<td><a class="sprite load-feed" href="#/access_f<?= $feed->id_feed ?>"></a></td>
				<td><i class="sprite update-feed" rel="<?= $feed->id_feed ?>"></i></td>
				<td><i class="sprite modify-feed" rel="<?= $feed->id_feed ?>"></i></td>
				<td><i class="delete-feed" rel="<?= $feed->id_feed ?>" title="Delete feed">&#10006;</i></td>
			</tr>
			<? endforeach; ?>
		</table>
	</fieldset>
</div>

<div id="modify-feed-dialog" class="hidden">
	<form name="modify-feed-form" id="modify-feed-form" method="post">
		<label for="modify-feed-id">Feed ID:</label><input id="modify-feed-id" type="text" disabled />
		<label for="modify-feed-name">Feed name:</label><input id="modify-feed-name" type="text" />
		<label for="modify-feed-site">Site main URL:</label><input id="modify-feed-site" type="text" />
		<label for="modify-feed-url">RSS Feed URL:</label><input id="modify-feed-url" type="text" />
		<label for="modify-feed-favicon">Favicon URL:</label><input id="modify-feed-favicon" type="text" />
		<input id="modify-feed-active" type="checkbox" /><label for="modify-feed-active">Active</label>
		<div>
			<button id="modify-feed-cancel" class="submit-button">Cancel</button>
			<input type="submit" id="modify-feed-update" class="submit-button" value="Update" />
		</div>
	</form>
</div>
<script>
	// This view is loaded again every time the preferences are opened, so the
	// events are bound to its own elements instead of to the document.
	var modifyDialog = $('#modify-feed-dialog');

	$('.content-form .update-feed').click(function() {
		updateFeed($(this).attr('rel'));
	});

	$('.content-form .modify-feed').click(function() {
		loader.fadeIn();

		$.ajax({
			type	: 'GET',
			dataType: 'json',
			url		: 'feeds/get/' + $(this).attr('rel')
		}).done(function(feedData) {
			$('#modify-feed-id').val(feedData.id_feed);
			$('#modify-feed-name').val(feedData.name);
			$('#modify-feed-site').val(feedData.site);
			$('#modify-feed-url').val(feedData.url);
			$('#modify-feed-favicon').val(feedData.favicon);
			$('#modify-feed-active').prop('checked', feedData.active == 1);

			modifyDialog.removeClass('hidden');
			loader.fadeOut();
		}).fail(function() {
			loader.fadeOut();
			error.text("Can't reach the server. Please, try again later.").fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});

		return false;
	});

	$('#modify-feed-form').submit(function(e) {
		e.preventDefault();

		var feedId = $('#modify-feed-id').val();
		var feedData = {
			name	: $('#modify-feed-name').val(),
			site	: $('#modify-feed-site').val(),
			url		: $('#modify-feed-url').val(),
			favicon	: $('#modify-feed-favicon').val(),
			active	: $('#modify-feed-active').is(':checked') ? 1 : 0
		};

		loader.fadeIn();

		$.ajax({
			type	: 'POST',
			url		: 'feeds/manage',
			data	: {
				feed	: feedId,
				action	: 'modify',
				value	: feedData
			}
		}).done(function(msg) {
			loader.fadeOut();

			if (msg == 'success') {
				var row = $('.content-form .modify-feed[rel="' + feedId + '"]').closest('tr');
				row.children('td').first().text(feedData.name);
				row.toggleClass('inactive', feedData.active == 0);

				modifyDialog.addClass('hidden');
				updateFeedlist();

				success.text('The feed was successfully modified.').fadeIn();
			} else {
				error.text("The feed couldn't be modified.").fadeIn();
			}
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		}).fail(function() {
			loader.fadeOut();
			error.text("Can't reach the server. Please, try again later.").fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
	});

	$('.content-form .delete-feed').click(function() {
		var feedId = $(this).attr('rel');
		var row = $(this).closest('tr');
		var feedName = row.children('td').first().text();

		if (!confirm('Delete the feed "' + feedName + '" with all its posts for every user?\n\nThis can\'t be undone.')) {
			return false;
		}

		loader.fadeIn();

		$.ajax({
			type	: 'POST',
			url		: 'feeds/manage',
			data	: {
				feed	: feedId,
				action	: 'delete'
			}
		}).done(function(msg) {
			loader.fadeOut();

			if (msg == 'success') {
				row.remove();
				updateFeedlist();

				success.text('The feed was successfully deleted.').fadeIn();
			} else {
				error.text("The feed couldn't be deleted.").fadeIn();
			}
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		}).fail(function() {
			loader.fadeOut();
			error.text("Can't reach the server. Please, try again later.").fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});

		return false;
	});

	$('#modify-feed-cancel').click(function() {
		modifyDialog.addClass('hidden');
		return false;
	});
</script>
<? endif; ?>
