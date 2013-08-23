<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>RSS Reader</title>
	<? if ( isset($is_phone) ): ?>
	<link rel="stylesheet" href="<?= site_url() ?>public_data/rssreader,handheld.css" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
	<script>
		var isPhone = true;
	</script>
	<? else: ?>
	<link rel="stylesheet" href="<?= site_url() ?>public_data/rssreader.css" />
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
		<div id="preferences-button"><?= $_SESSION['username'] ?><span class="sprite">&nbsp;</div>
		<ul class="display-menu">
			<li id="preferences-menu">Preferences</li>
			<li id="import-menu">Import feeds</li>
			<li class="hr">&nbsp;</li>
			<li><a href="<?= site_url() ?>login/logout">Logout</a></li>
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
				<? if ( $this->config->get('feed_updatable') ): ?>
					<li id="update-feed">Update feed</li>
				<? endif; ?>
				<li class="hr">&nbsp;</li>
				<li id="unsubscribe">Unsubscribe</li>
			</ul>
		</div>

		<div id="separator"><div class="sprite">&nbsp;</div></div>

		<div id="post-list">
<fieldset id="welcome-fieldset">
<h2>¿Qué es RSS Reader?</h2>
<p>RSS Reader es un agregador. Gracias a los agregadores o lectores de fuentes web (programas o sitios que permiten leer fuentes web) se pueden obtener resúmenes de todos los sitios que se desee desde el escritorio del sistema operativo, programas de correo electrónico o por medio de aplicaciones web que funcionan como agregadores. No es necesario abrir el navegador y visitar decenas de páginas.</p>
<h2>¿Qué es RSS?</h2>
<img src="<?= site_url() ?>/public_data/images/rsslogo.png" alt="RSS logo" />
<p>RSS son las siglas de Really Simple Syndication, un formato XML para indicar o compartir contenido en la web. Se utiliza para difundir información actualizada frecuentemente a usuarios que se han suscrito a la fuente de contenidos. El formato permite distribuir contenidos sin necesidad de un navegador, utilizando un software diseñado para leer estos contenidos RSS (agregador). A pesar de eso, es posible utilizar el mismo navegador para ver los contenidos RSS. Las últimas versiones de los principales navegadores permiten leer los RSS sin necesidad de software adicional. RSS es parte de la familia de los formatos XML, desarrollado específicamente para todo tipo de sitios que se actualicen con frecuencia y por medio del cual se puede compartir la información y usarla en otros sitios web o programas. A esto se le conoce como redifusión web o sindicación web (una traducción incorrecta, pero de uso muy común).</p>
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

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="<?= site_url() ?>public_data/rssreader.js"></script>
</body>
</html>
