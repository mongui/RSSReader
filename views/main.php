<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>RSS Reader</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="<?= site_url() ?>public_data/rssreader.js"></script>

	<link rel="stylesheet" href="<?= site_url() ?>public_data/rssreader.css" />
	<? if ( isset($is_phone) ): ?>
	<link rel="stylesheet" href="<?= site_url() ?>public_data/handheld.css" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
	<script>
		var isPhone = true;
	</script>
	<? endif; ?>
	<!--[if IE]>
	<link rel="stylesheet" href="<?= site_url() ?>public_data/normalize.css" />
	<![endif]-->
	<link rel="shortcut icon" href="<?= site_url() ?>public_data/favicon.ico" />
</head>
<body>
	<div class="info" id="loader">Loading...</div>
	<div class="info" id="error"></div>
	<div class="info" id="success"></div>
	<div id="header">
		<div id="preferences-button"><?= $_SESSION['username'] ?></div>
		<ul class="display-menu">
			<li id="preferences-menu">Preferences</li>
			<li id="import-menu">Import feeds</li>
			<li class="hr">&nbsp;</li>
			<li><a href="<?= site_url() ?>login/logout">Logout</a></li>
		</ul>
	</div>

	<div id="wrapper">
		<div id="feed-panel">
			<div id="alt-logo">&nbsp;</div>
			<div id="add-feed"> <img src="<?= site_url() ?>/public_data/images/add_feed.gif" alt="Add feed" />Add Feed</div>
			<form id="add-form" class="feed-panel-form">
				<label for="feed-url">Feed URL:</label>
				<input type="text" id="feed-url" name="feed-url" placeholder="Paste here the feed URL" />
				<button class="submit-button" id="submit-feed">Add feed</button>
			</form>

			<div class="list-title"><div>&nbsp;</div>Highlights</div>
			<ul class="list-content">
				<li id="highlight-unreaded"><span><img src="<?= site_url() ?>/public_data/images/read.png" alt="Unreaded" /></span>Unreaded</li>
				<li id="highlight-starred"><span><img src="<?= site_url() ?>/public_data/images/stars.png" alt="Starred" /></span>Starred</li>
				<li id="highlight-search"><span><img src="<?= site_url() ?>/public_data/images/search.png" alt="Search" /></span>Search</li>
			</ul>

			<form id="search-form" class="feed-panel-form">
				<input type="text" id="search-input" name="search" placeholder="Search terms" />
				<button class="submit-button" id="submit-search">Search</button>
			</form>

			<div class="list-title"><div>&nbsp;</div>Subscriptions</div>
			<ul class="list-content" id="feed-list">
			</ul>
			<ul class="display-menu" id="feed-contextmenu">
				<li id="mark-as-read">Mark feed as read</li>
				<li id="change-name">Change feed name</li>
				<? if ( $this->config->get('feed_updatable') == 'true' ): ?>
					<li id="update-feed">Update feed</li>
				<? endif; ?>
				<li class="hr">&nbsp;</li>
				<li id="unsubscribe">Unsubscribe</li>
			</ul>
		</div>

		<div id="separator"></div>

		<div id="post-list">
			<div class="welcome-message">Welcome to your RSS Reader</div>
		</div>
	</div>

	<div id="templates">
		<ul id="feeds-tmpl">
			<li>
				<a href="{id_feed}" class="item_link {not_readed}">
					{favicon}
					{name} {count}<div class="feed-menu">&nbsp;</div>
				</a>
			</li>
		</ul>
		<div id="feeddata-tmpl">
			<div class="feed-title">
				<a href="{feed_site}" target="_blank">{feed_name} &raquo;</a>
				<span class="feed-last-update">
					Last update: {last_update}
				</span>
			</div>
			<ul class="entries">
			</ul>
		</div>
		<ul id="posts-tmpl">
			<li class="entry">
				<a href="{id_post}" class="title {readed}">
					<div class="star {starred}" id="star1"></div>
					{title}
					<span class="timestamp">{timestamp}</span>
				</a>
				<div class="content">
					<div class="resize">
						<a class="entry-title" href="{url}" target="_blank">{title} &raquo;</a>
						<div class="author">by <span>{author}</span>.</div>
						{content}
					</div>
					<div class="post-manager"><span class="read"></span>Unreaded | <span class="star {starred}" id="star2"></span> Starred</div>
				</div>
			</li>
		</ul>
	</div>

</body>
</html>
