<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>RSS Reader</title>
	<? if (isset($is_phone)): ?>
	<link rel="stylesheet" href="<?= site_url() ?>public_data/rssreader,handheld.css" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
	<? else: ?>
	<link rel="stylesheet" href="<?= site_url() ?>public_data/rssreader.css" />
	<? endif; ?>
	<!--[if IE]>
	,normalize.css
	<![endif]-->
	<link rel="shortcut icon" href="<?= site_url() ?>public_data/favicon.ico" />
</head>
<body>
	<div class="info" id="loader">Loading...</div>
	<div class="info" id="error"></div>
	<div class="info" id="success"></div>
	<div id="header">
		<div id="preferences-button"><?= $_SESSION['username'] ?><span class="sprite">&nbsp;</div>
		<ul class="display-menu">
			<li id="preferences-menu">Preferences</li>
			<li id="import-menu">Import feeds</li>
			<li class="hr">&nbsp;</li>
			<li><a href="<?= site_url() ?>logout">Logout</a></li>
		</ul>
	</div>

	<div id="wrapper">
		<div id="feed-panel">
			<div id="add-feed">
				<span class="sprite">&nbsp;</span>
				Add Feed
			</div>
			<form id="add-form" class="feed-panel-form">
				<label for="feed-url">Feed URL:</label>
				<input type="text" id="feed-url" name="feed-url" placeholder="Paste here the feed URL" />
				<button class="submit-button" id="submit-feed">Add feed</button>
			</form>

			<div class="list-title"><span class="sprite">&nbsp;</span>Highlights</div>
			<ul class="list-content">
				<li id="highlight-unreaded"><span class="sprite">&nbsp;</span>Unreaded</li>
				<li id="highlight-starred"><span class="sprite">&nbsp;</span>Starred</li>
				<li id="highlight-readed"><span class="sprite">&nbsp;</span>Last readed</li>
				<li id="highlight-search"><span class="sprite">&nbsp;</span>Search</li>
			</ul>

			<form id="search-form" class="feed-panel-form">
				<input type="text" id="search-input" name="search" placeholder="Search terms" />
				<button class="submit-button" id="submit-search">Search</button>
			</form>

			<div class="list-title"><span class="sprite">&nbsp;</span>Subscriptions</div>
			<ul class="list-content" id="feed-list">
			</ul>
			<ul class="display-menu" id="feed-contextmenu">
				<li id="mark-as-read">Mark feed as read</li>
				<li id="change-name">Change feed name</li>
				<li id="add-to-folder">Add to a folder</li>
				<? if ($feed_updatable): ?>
					<li id="update-feed">Update feed</li>
				<? endif; ?>
				<li class="hr">&nbsp;</li>
				<li id="unsubscribe">Unsubscribe</li>
			</ul>
		</div>

		<div id="separator"><div class="sprite">&nbsp;</div></div>

		<div id="post-list">
			<fieldset id="welcome-fieldset">
				<h2>What is RSS Reader?</h2>
				<img src="<?= site_url() ?>/public_data/images/rsslogo.png" alt="RSS logo" />
				<p>RSS Reader is an aggregator. Thanks to the aggregators or web feed readers (programs or sites that let you read web sources) summaries of all the sites you want can be obtained from the desktop of the operating system, e-mail programs or through web applications that function as aggregators. There is no need to open the browser and visit dozens of pages.</p>
				<h2>What is RSS?</h2>
				<p>RSS stands for Really Simple Syndication, an XML format for sharing content on the Web. It's used to spread frequently updated information to users who have subscribed to the content source. The format allows to distribute content without a browser, using software designed to read these RSS feeds (aggregator). Nevertheless, it is possible to use a browser to read RSS content. The latest versions of the major browsers can read RSS with no additional software required. RSS is part of the family of XML formats developed specifically for all types of sites that are updated frequently and through which information can be shared and used on other web sites or programs. This is known as web or web syndication.</p>
			</fieldset>
		</div>
	</div>

	<div id="templates">
		<ul id="feeds-tmpl">
			<li>
				<a href="{id_feed}" class="item_link {not_readed}">
					{favicon}
					{name} {count}
				</a><span class="feed-menu">&nbsp;</span>
			</li>
		</ul>
		<ul id="feeds-tmpl2">
			<li class="folder">
				<span class="sprite"> </span>
				<span class="foldername" rel="{folder}">{name}</span>
				<ul class="list-content">{feed}</ul>
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
					<div class="sprite star {starred}" id="star1"></div>
					{title}
					<span class="timestamp">{timestamp}</span>
				</a>
				<div class="content">
					<div class="resize">
						<a class="entry-title" href="{url}" target="_blank">{title} &raquo;</a>
						<div class="author">by <span>{author}</span>.</div>
						{content}
					</div>
					<div class="post-manager"><span class="sprite read"></span>Unreaded | <span class="sprite star {starred}" id="star2"></span> Starred</div>
				</div>
			</li>
		</ul>
	</div>

	<? if (isset($is_phone)): ?>
	<script>
		var isPhone = true;
	</script>
	<? endif; ?>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="<?= site_url() ?>public_data/rssreader.js"></script>
</body>
</html>
