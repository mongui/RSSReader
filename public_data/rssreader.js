var loader, error, success, header, feedPanel, feedList, postList, separator, feeds, posts, selFeed, selPost, selFeedId, selPostId, hoverFeed, unreaded, killScroll, lastSelFeed, reloadPostList, searchQuery = '';

$(document).ready(function(ev) {
	loader		= $("#loader");
	error		= $("#error");
	success		= $("#success");
	header		= $('#header');
	feedPanel	= $("#feed-panel");
	feedList	= $("#feed-list");
	postList	= $("#post-list");
	separator	= $("#separator");
	killScroll	= false;

	// Session lost: send the user to the login.
	$(document).ajaxError(function(e, jqXHR) {
		if (jqXHR.status == 401) {
			window.location.href = 'login';
		}
	});

	$(window).on('hashchange', function() { readHash(); });

	/* FEED LIST  */
	updateFeedlist();
	setInterval( function() { updateFeedlist(); }, 300000);

	$('.list-title').click( function() {
		var title = $(this);
		var list = $(this).next(".list-content");
		list.slideToggle( 400, function() {
			if ( list.is(':visible') ) {
				title.children('span').removeClass('hidden');
			}
			else {
				title.children('span').addClass('hidden');
			}
		});
	});

	$('#highlight-unreaded').click( function() {
		loader.fadeIn();

		if ( selFeed ) {
			selFeed.removeClass('selected-feed');
		}

		loadPostlist('unreaded', 0, function() {
			if ( typeof isPhone != 'undefined' ) { // For phones.
				setVSeparator();
			}
		});
		loader.fadeOut();
		return false;
	});

	$('#highlight-starred').click( function() {
		loader.fadeIn();

		if ( selFeed ) {
			selFeed.removeClass('selected-feed');
		}

		loadPostlist('starred', 0, function() {
			if ( typeof isPhone != 'undefined' ) { // For phones.
				setVSeparator();
			}
		});
		loader.fadeOut();
		return false;
	});

	$('#highlight-readed').click( function() {
		loader.fadeIn();

		if ( selFeed ) {
			selFeed.removeClass('selected-feed');
		}

		loadPostlist('lastreaded', 0, function() {
			if ( typeof isPhone != 'undefined' ) { // For phones.
				setVSeparator();
			}
		});
		loader.fadeOut();
		return false;
	});

	$(document).on("click", ".folder", function() {
		var folder = $(this).children(".list-content");
		if ( folder.css('display') == 'block' ) {
			folder.css('min-height', '0');
		}

		folder.slideToggle( 400, function() {
			if ( folder.css('display') == 'block' ) {
				folder.css('min-height', '15px');
			}
		});
	});

	$(document).on("click", "a.item_link", function(e) {
		if ( selFeed ) {
			selFeed.removeClass('selected-feed');
		}
		selFeed = $(this).addClass('selected-feed');

		var name = '';
		$.each(feeds, function(i, item) {
			if ( item.id_feed === filterFeedURL(selFeed.attr('href')) ) {
				name = item.name;
				return false;
			}
		});

		e.stopPropagation();
	});

	// I don't know why if I use this outerHeight inside the .feed-menu/click,
	// the post-list goes down the page when the event is called.
	var fcHeight = $('#feed-contextmenu').outerHeight();
	$(document).on("click", ".feed-menu", function(e) {
		if ( $(this).parents('.list-content').parent().hasClass("folder") ) {
			$('#add-to-folder').hide();
		}
		else {
			$('#add-to-folder').show();
		}

		hoverFeed = filterFeedURL($(this).prev('.item_link').attr('href'));

		// The position is taken from the icon (content box), not from its padding,
		// which only makes the clickable area bigger.
		var iconTop = $(this).offset().top + parseInt($(this).css('padding-top'), 10);
		var x = $(document).width() - $(this).offset().left - parseInt($(this).css('padding-left'), 10) - $(this).width();
		var y = iconTop + $(this).height();

		var a = y + fcHeight;
		var b = feedPanel.offset().top + feedPanel.outerHeight() ;

		if ( a > b ) {
			y = iconTop - fcHeight;
		}

		displayMenuToggle( $("#feed-contextmenu"), x, y );

		e.stopPropagation();
		e.preventDefault();
	});

	if ( typeof isPhone === 'undefined' ) { // For phones.
		$(document).on({
			mouseenter: function() {
				$(this).children('.feed-menu').show();
			},
			mouseleave: function() {
				$(this).children('.feed-menu').hide();
			}
		}, '#feed-list li');
	}

	$('#mark-as-read').click( function() {
		if ( !isNaN(hoverFeed) ) {
			var send = {
				feed	: hoverFeed,
				action	: 'readed'
			};
			feeds = manageFeed(send);

			// If the feed is the one shown, its posts are marked as read in the list too.
			if ( hoverFeed == selFeedId ) {
				postList.find('.entry').each(function() {
					$(this).children('.title').addClass('readed');
					$(this).children('.content').children('.post-manager').children('.read').removeClass('unread');
				});

				if ( typeof posts.posts !== 'undefined' ) {
					$.each(posts.posts, function(i, item) {
						item.readed = 1;
					});
				}
			}

			updateFeedlist();
		}
	});

	$('#update-feed').click( function() {
		updateFeed(hoverFeed);
	});

	$('#change-name').click( function() {
		var name;
		$.each(feeds, function(i, item) {
			if ( typeof item.id_feed === 'undefined' && typeof item.folder !== 'NaN' )
			{
				$.each(item.feeds, function(i2, item2) {
					if ( item2.id_feed == hoverFeed ) {
						name = item2.name;
					}
				});
			}
			else {
				if ( item.id_feed == hoverFeed ) {
					name = item.name;
				}
			}
		});

		if ( !isNaN(hoverFeed) ) {
			var send = {
				feed	: hoverFeed,
				action	: 'name',
				value	: prompt(t("Please enter the new feed name:"), name)
			};
			feeds = manageFeed(send);

			updateFeedlist();
		}
	});

	$('#add-to-folder').click( function() {
		if ( !isNaN(hoverFeed) ) {
			var nfolder = prompt(t("New folder name:"), t("New folder"));

			if ( typeof nfolder !== 'string' || nfolder == '' ) {
				return;
			}

			$(".list-content").sortable({ connectWith: '.list-content' });

			var send = {
				feed	: hoverFeed,
				action	: 'newfolder',
				value	: nfolder,
			};
			feeds = manageFeed(send);

			updateFeedlist();
		}
	});

	$('#unsubscribe').click( function(e) {
		if ( !isNaN(hoverFeed) && confirm(t('Are you sure you want to unsubscribe from this feed?')) ) {
			var send = {
				feed	: hoverFeed,
				action	: 'unsubscribe'
			};
			feeds = manageFeed(send);

			updateFeedlist();
		}
	});
	/* END FEED LIST */

	/* POST LIST */
	$(document).on("click", "a.title", function(e) {
		killScroll = true;

		if ( selPost ) {
			selPost.removeClass('selected-post');
		}

		var activatePost = $(this).attr('href').split('_p');
		activatePost = activatePost[activatePost.length -1];

		if ( !isNaN(activatePost) && activatePost > 0  && selPostId != activatePost ) {
			selPostId = activatePost;
			window.location.hash = '/' + $(this).attr('href');
		}

		selPost = $(this).addClass('selected-post');

		var content = $(this).next("div.content");

		$("div.content").each(function() {
			if ( $(this).is(':visible') && $(this)[0] != content[0] ) {
				$(this).hide();
			}
		});

		if ( content.css('display') != 'block' ) {
			var cnt = posts.posts['post-' + selPostId].content;
			cnt = cnt.replace(/<a /g, '<span class="content-link"><a target="_blank" ').replace(/<\/a>/g, '</a></span>');
			
			content.html( content.html().replace("{content}", cnt) );

			if ( lastSelFeed === 'search' ) {
				highlightSearch(content.children('.resize')[0]);
			}
		}

		// The opened post goes to the top of the visible list, while it opens.
		if ( content.css('display') != 'block' ) {
			var entry = $(this).parents('.entry');
			postList.stop().animate({ scrollTop: postList.scrollTop() + entry.offset().top - postList.offset().top }, 400);
		}

		content.slideToggle( 400, function() {
			if ( content.css('display') == 'block' && !$(this).prev().hasClass('readed') ) {
				var send = {
					post	: selPostId,
					action	: 'readed',
					state	: 1
				};
				managePost (send);

				$(this).prev().addClass('readed');

				$(this).parents('.entry').children('.content').children('.post-manager').children('.read').removeClass('unread');

				updateFeedElement(-1, feeds);
			}

			killScroll = false;
		});

		e.preventDefault();
	});

	function updateFeedElement(addToCount, feedList) {
		addToCount = ( addToCount == null ) ? 0 : addToCount;

		$.each(feedList, function(i, item) {


			if ( typeof(item.folder) !== 'undefined') {
				updateFeedElement(addToCount, item.feeds);
			}
			else {
				if ( item.id_feed == selFeedId) {
					feedList[i].count = parseInt(feedList[i].count, 10);
					feedList[i].count = item.count + addToCount;
					unreaded = unreaded + addToCount;

					var insertHere = '';
					if ( typeof(feedList[i].favicon) != 'undefined' ) {
						insertHere += '<img src="' + feedList[i].favicon + '" alt="' + feedList[i].name + '" /> ' + feedList[i].name + ' ';
					}
					else {
						insertHere += '<span class="sprite">&nbsp;</span> ' + feedList[i].name + ' ';
					}

					if ( feedList[i].count > 0 ) {
						insertHere += '(' + feedList[i].count + ')';
						selFeed.addClass('not-readed');
					}
					else {
						selFeed.removeClass('not-readed');
					}
					selFeed.html( insertHere );
				}
			}
		});

		$('title').html('RSS Reader&nbsp;(' + unreaded + ')');
		$('#shortcuticon').attr('href', 'img/' + unreaded);
	}

	$(document).on("click", ".read", function(e) {
		var send = {
			post	: selPostId,
			action	: 'readed',
			state	: 0
		};

		if ( $(this).hasClass('unread') ) {
			send.state = 1;
			$(this)
				.removeClass('unread')
				.parents('.entry').children('.title').addClass('readed');

			updateFeedElement(-1, feeds);
		}
		else {
			send.state = 0;
			$(this)
				.addClass('unread')
				.parents('.entry').children('.title').removeClass('readed');

			updateFeedElement(1, feeds);
		}
		managePost (send);
	});

	$(document).on("click", "#star1", function(e) {
		var activatePost = $(this).parent('a').attr('href').split('_p');
		activatePost = activatePost[activatePost.length-1];

		var send = {
			post	: activatePost,
			action	: 'starred',
			state	: 0
		};

		if ( $(this).hasClass('starred') ) {
			send.state = 0;
			$(this)
				.removeClass('starred')
				.parents('.entry').children('.content').children('.post-manager').children('#star2').removeClass('starred');
		}
		else {
			send.state = 1;
			$(this)
				.addClass('starred')
				.parents('.entry').children('.content').children('.post-manager').children('#star2').addClass('starred');
		}
		managePost (send);

		e.stopPropagation();
		e.preventDefault();
	});

	$(document).on("click", "#star2", function(e) {
		var send = {
			post	: selPostId,
			action	: 'starred',
			state	: 0
		};

		if ( $(this).hasClass('starred') ) {
			send.state = 0;
			$(this)
			.removeClass('starred')
			.parents('.entry').children('a').children('#star1').removeClass('starred');
		}
		else {
			send.state = 1;
			$(this)
				.addClass('starred')
				.parents('.entry').children('a').children('#star1').addClass('starred');
		}
		managePost (send);
	});

	postList.scroll( function() {
		var divTotalSize = $(this)[0].scrollHeight - $(this).height();
		if ( $(this).scrollTop() >= divTotalSize && killScroll == false ) {
			// The searches go on from the cursor sent by the server.
			var from = ( lastSelFeed === 'search' ) ? posts.next : $(".entry").size();
			if ( lastSelFeed === 'search' && !from ) {
				return;
			}

			loadPostlist(lastSelFeed, from, function() {
				killScroll = false;
			});
		}
	});
	/* END POST LIST */

	/* ADD FEED FORM */
	$('#add-feed').click( function() {
		var addForm = $(this).next("#add-form");

		if ( addForm.is(':visible') ) {
			addForm.hide();
		}
		else {
			if ( typeof isPhone != 'undefined' ) { // For phones.
				addForm.children("label").hide();
				addForm
					.css('max-width', '90%')
					.show()
					.children("#feed-url").focus();
			}
			else {
				addForm
					.css('left', $(this).offset().left + $(this).outerWidth() + 5)
					.css('top', $(this).offset().top)
					.show()
					.children("#feed-url").focus();
			}
		}
	});

	$('#submit-feed').click( function() {
		loader.fadeIn();
		$.ajax({
			type	: "POST",
			url		: "feeds/add",
			data	: {
				feed_url: $('#feed-url').val()
			}
		}).done(function() {
			$("#add-form").hide();
			updateFeedlist();

			success.text(t('The feed was successfully added.')).fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		}).fail(function() {
			error.text(t('Can\'t reach the server. Please try again later.')).fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});

		return false;
	});
	/* END ADD FEED FORM */

	/* SEARCH FORM */
	$('#highlight-search').click( function() {
		var searchForm = $("#search-form");

		if ( searchForm.is(':visible') ) {
			searchForm.hide();
		}
		else {
			searchForm.show();
			searchForm.children("#search-input").focus();
		}
	});

	$('#submit-search').click( function() {
		var query = $.trim($('#search-input').val());
		if ( query === '' ) {
			return false;
		}
		searchQuery = query;

		loader.fadeIn();

		if ( selFeed ) {
			selFeed.removeClass('selected-feed');
		}

		loadPostlist('search', 0, function() {
			if ( typeof isPhone != 'undefined' ) { // For phones.
				setVSeparator();
			}
		});
		$("#search-form").hide();

		loader.fadeOut();
		return false;
	});
	/* END SEARCH FORM */

	/* DISPLAY MENUs / CONTEXT MENUs */
	var displayMenu = null;
	$('#preferences-button').click( function(e) {
		var x = $(document).width() - $(this).offset().left - $(this).outerWidth();
		var y = $(this).offset().top + $(this).outerHeight();

		displayMenuToggle( $(this).next(".display-menu"), x , y );

		e.stopPropagation();
	});

	$(document).click( function() {
		if ( displayMenu ) {
			displayMenuToggle();
		}
	});

	function displayMenuToggle( element, x, y ) {
		if ( displayMenu ) {
			displayMenu.hide();
			displayMenu = null;
		}

		if ( element ) {
			displayMenu = element;
			if ( x && y ) {
				displayMenu
					.css('right', x)
					.css('top', y);
			}

			displayMenu.show();
		}
	}
	/* END DISPLAY MENUs / CONTEXT MENUs */

	/* PREFERENCES MENU */
	$('#preferences-menu').click( function() {
		loader.fadeIn();
		$.ajax({
			type	: "GET",
			url		: "preferences"
		}).done(function(form) {
			postList.html(form);

			$("#timeformat").val(timeformat);
			$("#language").val(language);

			if ( typeof isPhone != 'undefined' ) { // For phones.
				setVSeparator();
			}

			feed = null;
			loader.fadeOut();
		}).fail(function() {
			error.text(t('Can\'t reach the server. Please try again later.')).fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
	});

	$(document).on("click", "#submit-preferences", function(e) {
		error.fadeOut();
		success.fadeOut();
		loader.fadeOut();

		var curPassword  = $('#cur-password').val();
		var newPassword  = $('#new-password').val();
		var newPassword2 = $('#new-password2').val();

		if ( newPassword != '' || newPassword2 != '' ) {
			if ( newPassword.length < 6 ) {
				error.text(t('Your password must be at least 6 characters long.')).fadeIn();
				return false;
			}
			else if ( newPassword !== newPassword2 ) {
				error.text(t('Passwords do not match.')).fadeIn();
				return false;
			}
			else if ( curPassword === '' ) {
				error.text(t('We need your current password to verify your identity.')).fadeIn();
				return false;
			}
			else {
				error.fadeOut();
			}
		}

		loader.fadeIn();

		var prefData = {
			timeformat:		$('#timeformat').val(),
			language:		$('#language').val(),
			curPassword:	curPassword,
			newPassword:	newPassword
		};

		if ( typeof window.serverData == 'function' ) {
			var moreData = serverData();
			$.extend(prefData, moreData);
		}

		$.ajax({
			type	: "POST",
			url		: "preferences",
			data	: prefData
		}).done(function(msg) {
			if ( msg === 'success' ) {
				// The texts are translated on the server, so the page is reloaded with the new language.
				if ( prefData.language != language ) {
					window.location.reload();
					return;
				}

				success.text(t('Data saved.')).fadeIn();
			}
			else if ( msg === 'curPass' ) {
				error.text(t('Your current password is not correct.')).fadeIn();
			}
			else {
				error.text(t('Something went wrong. We can\'t save your preferences now. Sorry.')).fadeIn();
			}
			setTimeout(function(){ $('.info').fadeOut(); }, 5000);

		}).fail(function() {
			error.text(t('Can\'t reach the server. Please try again later.')).fadeIn();
			setTimeout(function(){ $('.info').fadeOut(); }, 5000);
		});
		return false;
	});

	$('#import-menu').click( function() {
		loader.fadeIn();
		$.ajax({
			type	: "GET",
			url		: "feeds/importfile"
		}).done(function(form) {
			postList.html(form);
			if ( typeof isPhone != 'undefined' ) { // For phones.
				setVSeparator();
			}

			feed = null;
			loader.fadeOut();
		}).fail(function() {
			error.text(t('Can\'t reach the server. Please try again later.')).fadeIn();
			setTimeout(function(){ $('.info').fadeOut(); }, 5000);
		});
	});
	/* END PREFERENCES MENU */

	/* IMPORT OPML FILE FORM */
	$(document).on("click", "#text-file", function() {
		$("#import-file").trigger("click");
	});

	$(document).on("change", "#import-file", function() {
		$('#text-file').val($(this).val());
	});

	$(document).on("click", "#select-file", function() {
		$("#import-file").trigger("click");
	});

	$(document).on("click", "#submit-file", function() {
		loader.fadeIn();
		if ( $("#import-file").val() == '' ) {
			error.text(t('Select a file first.')).fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
			return false;
		}

		$('#import-form').submit(function() {
			var subForm = $('#submited-form');
			var count = 0;
			var subInt = self.setInterval(function() {
				if ( $.trim(subForm.contents().find('body').html()) == 'success' ) {
					subInt = window.clearInterval(subInt);
					success.text(t('File successfully uploaded.')).fadeIn();
					setTimeout(function(){ $(".info").fadeOut(); }, 5000);
					updateFeedlist();
				}
				else if ( $.trim(subForm.contents().find('body').html()) == 'failure' ) {
					subInt = window.clearInterval(subInt);
					error.text(t('The file you tried to upload is not compatible.')).fadeIn();
					setTimeout(function(){ $(".info").fadeOut(); }, 5000);
				}
				else {
					if ( count >= 15 ) {
						subInt = window.clearInterval(subInt);
						error.text(t('Something went wrong. We can\'t upload your file now. Sorry.')).fadeIn();
						setTimeout(function(){ $(".info").fadeOut(); }, 5000);
					}

					count++;
				}
			}, 1000);
		})
	});
	/* END IMPORT OPML FILE FORM */

	/* SEPARATOR */
	var sep = null;
	$("#separator").mousedown(function(e) {
		if ( typeof isPhone != 'undefined' ) { // For phones.
			setVSeparator();
		}
		else {
			sep = getSeparator();
			e.preventDefault();
		}
	});
	$(document).mousemove(function(e) {
		if ( sep ) { widthCookie = setSeparator(e); }
		e.preventDefault();
	}).mouseup(function(e) {
		if ( sep ) {
			createCookie('rss_sepwidth', widthCookie);
			sep = null;
		}
		e.preventDefault();
	});

	$(window).resize(function(e) {
		if ( typeof isPhone != 'undefined' ) { // For phones.
			feedPanelHeight = $(window).height() - 30; // 30 from #separator
		}
		else {
			getSeparator();
			setSeparator(e);
			sep = null;
			$("#wrapper").height( ($(window).height() - 75) );// 70 from #header.height + 5
		}
	});

	var rss_sepwidth = readCookie('rss_sepwidth');
	var widthCookie;
	// Done on DOM ready instead of window load, so the layout
	// doesn't wait for every image and favicon to be downloaded.
	if ( typeof isPhone != 'undefined' ) { // For phones.
		feedPanelHeight = $(window).height() - 30; // 30 from #separator
	}
	else {
		getSeparator();
		setSeparator(rss_sepwidth);
		postList.show();
		sep = null;

		$("#wrapper").height( ($(window).height() - 75) );
	}
	/* END SEPARATOR */
});

/* SEPARATOR */
function getSeparator() {
	return sep = {
		w : separator.width(),
		p : separator.prev(),
		n : separator.next(),
		dw: $(document).width(),
		pw: separator.prev().width()
	};
}

/* LOAD FROM HASH */
function readHash() {
	var hash = window.location.hash;
	var hashChunk = window.location.hash.split('/');

	if ( typeof hashChunk[1] !== 'undefined' && hashChunk[1] !== '#' && hashChunk[1] !== '' ) {
		var activateFeed = hashChunk[1].split('_f');
		activateFeed = activateFeed[activateFeed.length -1];

		if ( !isNaN(activateFeed) && activateFeed > 0 && (activateFeed != selFeedId || reloadPostList) ) {
			selFeedId = activateFeed;
			reloadPostList = false;
			loadPostlist(selFeedId, 0, function() {
				if ( typeof isPhone != 'undefined' ) { // For phones.
					setVSeparator();
				}
			});
		}
	}

	if ( typeof hashChunk[2] !== 'undefined' && hashChunk[2] !== '' ) {
		var activatePost = hashChunk[2].split('_p');
		activatePost = activatePost[activatePost.length -1];

		if ( !isNaN(activatePost) && activatePost > 0 ) {
			selPostId = activatePost;
		}
	}
	else {
		selPostId = undefined;
	}
}
/* END LOAD FROM HASH */

function setSeparator(data) {
	var wx;
	if		( !isNaN(data) )		{ wx = data;		}
	else if	( !isNaN(data.pageX) )	{ wx = data.pageX;	}
	else							{ wx = sep.pw;		}

	sep.p.width(wx);
	//sep.n.width(Math.floor(sep.dw - sep.w - sep.p.width() -8)); // -8 depends of borders, margins,...
	sep.n.width("calc(100% - 8px - " + wx + "px)"); // -8 depends of borders, margins,...
	return wx;
}

var feedPanelHeight;
function setVSeparator() {
	var togglePanel1;
	var togglePanel2;

	if ( feedPanel.height() > 0 ) {
		togglePanel1 = 0;
		togglePanel2 = feedPanelHeight;
		separator.animate({ bottom: feedPanelHeight }, 400);
	}
	else {
		togglePanel1 = '100%';
		togglePanel2 = 0;
		separator.animate({ bottom: 0 }, 400);
	}
	feedPanel.animate({ height: togglePanel1 }, 400);
	postList.animate({ height: togglePanel2 }, 400);
}
/* END SEPARATOR */

function textToURL(text) {
	non_asciis = {'a': '[àáâãäå]', 'ae': 'æ', 'c': 'ç', 'e': '[èéêë]', 'i': '[ìíîï]', 'n': 'ñ', 'o': '[òóôõö]', 'oe': 'œ', 'u': '[ùúûűü]', 'y': '[ýÿ]'};
	for (i in non_asciis) { text = text.replace(new RegExp(non_asciis[i], 'g'), i); }
	return text.trim().toLowerCase().replace(/ +/g,'_').replace(/[^a-z0-9-_]/g,'');
}

function filterFeedURL(text) {
	var hashChunk = text.split('/');
	var fd = hashChunk[1].split('_f');
	return fd[fd.length -1];
}

function createCookie(name, value) {
	var date = new Date();
	date.setTime(date.getTime() + (9999 * 24 * 60 * 60 * 1000));
	var expires = "; expires=" + date.toGMTString();
	document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') { c = c.substring(1, c.length); }
		if (c.indexOf(nameEQ) == 0) { return c.substring(nameEQ.length, c.length); }
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name, "", -1);
}

function updateFeedlist() {
	loader.fadeIn();

	$.ajax({
		type	: "GET",
		dataType: "json",
		url		: "feeds/get"
	}).done(function(flist) {
		feeds = flist;
		feedList.html('');
		unreaded = 0;

		readHash();

		$.each(feeds, function(i, item) {
			if ( typeof item.folder !== 'undefined' ) {
				feedList.append( addFolderToList(item) );
			}
			else {
				feedList.append( addFeedToList(item) );
			}

		});

		$(".list-content").sortable({ connectWith: '.list-content' });

		selFeed = feedList.find('a.selected-feed');

		if (reloadPostList) {
			readHash();
		}

		$('title').html('RSS Reader&nbsp;(' + unreaded + ')');
		$('#shortcuticon').attr('href', 'img/' + unreaded);

		loader.fadeOut();
	}).fail(function() {
		error.text(t('Can\'t reach the server. Please try again later.')).fadeIn();
		setTimeout(function(){ $(".info").fadeOut(); }, 5000);
	});
}

function updateFeed(feedId) {
	if ( !isNaN(feedId) ) {
		var send = {
			feed	: feedId,
			action	: 'update'
		};
		feeds = manageFeed(send);
		reloadPostList = true;
		updateFeedlist();
	}
}

function loadPostlist(feed, from, callback) {
	if ( killScroll == true || typeof(feed) === 'undefined' ) {
		return;
	}

	// "from" is the number of posts already shown or, in the searches, the cursor of the next page.
	var more = ( typeof(from) === 'string' ) ? from !== '' : from > 0;

	killScroll = true;

	loader.fadeIn();

	var sendData = {
		feed : feed
	};

	if ( feed === 'search' ) {
		sendData.search = searchQuery;
	}

	if ( more ) {
		sendData.next = from;
	}
	else {
		posts = {};
	}
	plist = {};

	$.ajax({
		type    : "POST",
		dataType: "json",
		url     : "posts/get",
		data    : sendData
	}).done(function(plist) {
		if ( more && typeof plist.posts !== 'undefined' ) {
			posts.posts = $.extend(posts.posts || {}, plist.posts);
		}
		else if ( !more ) {
			$.extend(posts, plist);
		}
		posts.next = ( typeof plist.next !== 'undefined' ) ? plist.next : null;

		var feedTmpl = $("#feeddata-tmpl").html();
		var postBase = $("#posts-tmpl").html();
		var postsTmpl, readed, starred;

		if ( !more ) {
			postList.html('');

			feedTmpl = feedTmpl
				.replace("{feed_site}", posts.site)
				.replace("{feed_name}", posts.name)
				.replace("{last_update}", posts.last_update);
			postList.append(feedTmpl);
		}

		if ( posts.last_update === '' ) {
			postList.children('.feed-title').children('.feed-last-update').hide();
		}

		var NXTselPostId = undefined;
		if ( typeof plist.posts !== 'undefined' ) {
			var hashChunk = window.location.hash.split('/');

			$.each(plist.posts, function(i, item) {
				postsTmpl = postBase;
				readed = (item.readed > 0) ? 'readed' : '';
				starred = (item.starred > 0) ? 'starred' : '';

				postsTmpl = postsTmpl
					.replace("{readed}", readed)
					.replace("{starred}", starred)
					.replace("{starred}", starred)

					.replace("{id_post}", (typeof hashChunk[1] !== 'undefined') ? (hashChunk[1] + '/' + textToURL(item.title) + '_p' + item.id_post) : item.id_post)
					.replace("{title}", item.title)
					.replace("{timestamp}", item.timestamp)
					.replace("{url}", item.url)
					.replace("{title}", item.title)

					.replace("{author}", ( item.author != '' ) ? item.author : t('Anonymous'));

				postList.children('.entries').append(postsTmpl);

				if ( feed === 'search' ) {
					highlightSearch(postList.children('.entries').children('.entry').last().children('.title')[0]);
				}

				if ( selPostId == item.id_post ) {
					NXTselPostId = item.id_post;
				}
			});
		}

		if ( postList.find('.entry').length === 0 && !posts.next ) {
			postList.children('.entries').append('<li class="no-posts">' + t('No posts found.') + '</li>');
		}

		if ( more && typeof callback === 'function' ) {
			callback();
		}
		else if ( !more ) {
			postList.animate({scrollTop: 0},'500', function() {
				if (typeof callback === 'function') {
					callback();
				}
			});
		}
		lastSelFeed = feed;

		if ( !isNaN(NXTselPostId) && !isNaN(selPostId) && selPostId > 0 ) {
			$("li.entry").each(function() {
				var activatePost = $(this).children('a').attr('href').split('_p');
				activatePost = activatePost[activatePost.length -1];

				if ( activatePost == NXTselPostId ) {
					$(this).children('a').click();
				}
			});
		}

		killScroll = false;

		loader.fadeOut();

		// A search page can come with few results (or none) and more to come:
		// if the list doesn't fill the panel, there is no scroll to ask for them.
		if ( feed === 'search' && posts.next && postList[0].scrollHeight <= postList.innerHeight() ) {
			loadPostlist('search', posts.next);
		}
	}).fail(function() {
		error.text(t('Can\'t reach the server. Please try again later.')).fadeIn();
		setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		killScroll = false;
	});
}

/* SEARCH HIGHLIGHT */
// Same rules as Connections::search_posts() (models/connections.php).
var searchWordChars = '0-9A-Za-zªµºÀ-ÖØ-öø-ɏ';

function searchNormalize(text) {
	var from = 'áàäâãåéèëêíìïîóòöôõúùüûçýÿ';
	var to   = 'aaaaaaeeeeiiiiooooouuuucyy';

	return text.toLowerCase().replace(/[áàäâãåéèëêíìïîóòöôõúùüûçýÿ]/g, function(c) {
		return to.charAt(from.indexOf(c));
	});
}

// Returns the terms to highlight (not the excluded ones) as regular expressions.
function searchPatterns(query) {
	var patterns = [];
	var token, tokens = /(-?)"([^"]*)"|(\S+)/g;
	var accents = { a: '[aáàäâãå]', e: '[eéèëê]', i: '[iíìïî]', o: '[oóòöôõ]', u: '[uúùüû]', c: '[cç]', y: '[yýÿ]' };

	while ( (token = tokens.exec(query)) !== null ) {
		var text, quoted;

		if ( typeof token[3] !== 'undefined' ) {
			text = token[3];
			if ( text.length > 1 && text.charAt(0) === '-' ) {
				continue;
			}
			quoted = false;
		}
		else {
			if ( token[1] === '-' ) {
				continue;
			}
			text = token[2];
			quoted = true;
		}

		var words = $.grep(searchNormalize(text).split(new RegExp('[^' + searchWordChars + ']+')), function(word) {
			return word !== '';
		});
		if ( words.length === 0 ) {
			continue;
		}

		var parts = $.map(words, function(word) {
			return $.map(word.split(''), function(c) {
				return accents[c] ? accents[c] : c.replace(/[.*+?^${}()|[\]\\\/-]/g, '\\$&');
			}).join('');
		});

		patterns.push({
			regex	: new RegExp(parts.join('[^' + searchWordChars + ']+'), 'gi'),
			prefix	: !quoted && words.length === 1 && words[0].length >= 4
		});
	}

	return patterns;
}

// Returns the [start, end] positions of the terms in a text, sorted and without overlaps.
function searchRanges(text, patterns) {
	var isWordChar = new RegExp('[' + searchWordChars + ']');
	var ranges = [], merged = [];

	$.each(patterns, function(i, pattern) {
		var match;
		pattern.regex.lastIndex = 0;

		while ( (match = pattern.regex.exec(text)) !== null ) {
			var start = match.index;
			var end = start + match[0].length;

			// The term must start a word, and end it too if it isn't a prefix.
			if ( ( start === 0 || !isWordChar.test(text.charAt(start - 1)) ) &&
				( pattern.prefix || end === text.length || !isWordChar.test(text.charAt(end)) ) ) {
				// A prefix is highlighted until the end of the word (cena: [cenas]).
				while ( pattern.prefix && end < text.length && isWordChar.test(text.charAt(end)) ) {
					end++;
				}
				ranges.push([start, end]);
			}
			pattern.regex.lastIndex = start + 1;
		}
	});

	ranges.sort(function(a, b) { return a[0] - b[0]; });

	$.each(ranges, function(i, range) {
		var last = merged[merged.length - 1];

		if ( last && range[0] <= last[1] ) {
			last[1] = Math.max(last[1], range[1]);
		}
		else {
			merged.push([range[0], range[1]]);
		}
	});

	return merged;
}

// Wraps the searched terms in <em class="highlight">, only in the text (never in tags or URLs).
function highlightSearch(root) {
	var patterns = searchPatterns(searchQuery);
	if ( !root || patterns.length === 0 ) {
		return;
	}

	var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
	var nodes = [], node;

	while ( (node = walker.nextNode()) ) {
		if ( $(node.parentNode).closest('script, style, textarea, em.highlight, .timestamp').length === 0 ) {
			nodes.push(node);
		}
	}

	$.each(nodes, function(i, node) {
		var text = node.nodeValue;
		var ranges = searchRanges(text, patterns);

		if ( ranges.length === 0 ) {
			return;
		}

		var fragment = document.createDocumentFragment();
		var position = 0;

		$.each(ranges, function(j, range) {
			if ( range[0] > position ) {
				fragment.appendChild(document.createTextNode(text.substring(position, range[0])));
			}

			var em = document.createElement('em');
			em.className = 'highlight';
			em.appendChild(document.createTextNode(text.substring(range[0], range[1])));
			fragment.appendChild(em);

			position = range[1];
		});

		if ( position < text.length ) {
			fragment.appendChild(document.createTextNode(text.substring(position)));
		}

		node.parentNode.replaceChild(fragment, node);
	});
}
/* END SEARCH HIGHLIGHT */

function addFeedToList(feedData) {
	feedsTmpl = $("#feeds-tmpl").html();
	name = (feedData.name !== '') ? feedData.name : t('No name');
	feedsTmpl = feedsTmpl
		.replace("{id_feed}",  '#/' + textToURL(name) + '_f' + feedData.id_feed)
		.replace("{name}", name)
		.replace("{not_readed}", ( feedData.count > 0 ) ? 'not-readed' : '')
		.replace("{selected}", ( typeof(selFeedId) !== 'undefined' && feedData.id_feed == selFeedId ) ? 'selected-feed' : '')
		.replace("{inactive}", ( typeof(feedData.active) === 'undefined' || feedData.active == 0 ) ? 'inactive' : '')
		.replace("{count}", ( feedData.count > 0 ) ? '(' + feedData.count + ')' : '');

		if ( typeof(feedData.favicon) != 'undefined' ) {
			feedsTmpl = feedsTmpl.replace("{favicon}", '<img src="' + feedData.favicon + '" />');
		}
		else {
			feedsTmpl = feedsTmpl.replace("{favicon}", '<span class="sprite">&nbsp;</span>');
		}
		unreaded = unreaded + parseInt(feedData.count);

	return feedsTmpl;
}

function addFolderToList(folderData) {
	var feedsTmpl2 = $("#feeds-tmpl2").html();
	var tmp = '';
	$.each(folderData.feeds, function(j, subitem) {
		tmp = tmp + addFeedToList(subitem);
	});

	feedsTmpl2 = feedsTmpl2
		.replace("{folder}", folderData.folder)
		.replace("{name}", folderData.name)
		.replace("{feed}", tmp);

	return feedsTmpl2;
}

/* TRANSLATIONS */
// Returns the translation of an English text (see languages/*.php).
function t(text) {
	return ( typeof lang !== 'undefined' && lang[text] ) ? lang[text] : text;
}

function manageFeed(send) {
	$.ajax({
		type	: "POST",
		url		: "feeds/manage",
		data	: send
	}).done(function(msg) {
		return msg;
	}).fail(function() {
		error.text(t('Can\'t reach the server. Please try again later.')).fadeIn();
		setTimeout(function(){ $(".info").fadeOut(); }, 5000);
	});
}

function managePost(send) {
	$.ajax({
		type	: "POST",
		url		: "posts/manage",
		data	: send
	}).done(function(msg) {
		return msg;
	}).fail(function() {
		error.text(t('Can\'t reach the server. Please try again later.')).fadeIn();
		setTimeout(function(){ $(".info").fadeOut(); }, 5000);
	});
}

function sendList(elements) {
	var elmnts = [];
	var subelmnts = [];
	var folderId, inFolder;

	$.each(elements, function(i) {
		if ( $(this).hasClass('folder') ) {
			inFolder = $('> ul > li', this).size();
			folderId = $('> .foldername', this).attr('rel');
		}
		else {
			if ( inFolder > 0 ) {
				subelmnts.push( filterFeedURL($(this).children('a').attr('href')) );
				inFolder = inFolder - 1;
				if ( inFolder == 0 ) {
					elmnts.push( { folder: folderId, value: subelmnts } );
					subelmnts = [];
				}
			}
			else {
				elmnts.push( filterFeedURL($(this).children('a').attr('href')) );
			}
		}
	});
	return elmnts;
}

(function($) {
var dragging, itmData = {}, placeholders = $(), isFolder = false;
$.fn.sortable = function(options) {
	var method = String(options);
	options = $.extend({ connectWith: false	}, options);
	return this.each(function() {
		var index, items = $(this).children(options.items);
		var parent;
		var placeholder = $('<' + (/^ul|ol$/i.test(this.tagName) ? 'li' : 'div') + ' class="sortable-placeholder">');
		$(this).data('items', options.items);
		placeholders = placeholders.add(placeholder);
		$(options.connectWith).add(this).data('connectWith', options.connectWith);
		items.attr('draggable', 'true').not('a[href], img').on('selectstart', function() {
			this.dragDrop && this.dragDrop();
			return false;
		}).end();

		items.on('dragstart', function(e) {
			var dt = e.originalEvent.dataTransfer;
			dt.effectAllowed = 'move';
			dt.setData('Text', 'dummy');
			index = (dragging = $(this)).addClass('sortable-dragging').index();
			parent = dragging.parent();

			isFolder = $(this).hasClass('folder');

			e.stopPropagation();
		}).on('dragend', function() {
			if (!dragging) {
				return;
			}
			dragging.removeClass('sortable-dragging').show();
			placeholders.detach();

			dragging = null;
			parent = null;
		}).add([this, placeholder]).on('dragover dragenter drop', function(e) {
			if (!items.is(dragging) && options.connectWith !== $(dragging).parent().data('connectWith')) {
				return true;
			}
			if (e.type == 'drop') {
				e.stopPropagation();

				if ( isFolder && placeholders.parents('.list-content').parent().hasClass('folder') ) {
					return false;
				}

				placeholders.filter(':visible').after(dragging);
				dragging.trigger('dragend');

				// Empty folders? Delete them.
				$("#feed-panel .folder .list-content").each(function() {
					if ($(this).children().length === 0) {
						$(this).parent().remove();
					}
				});
				itmData.action = 'sort';
				itmData.value = sendList($('#feed-list li'));

				manageFeed(itmData);

				return false;
			}
			e.preventDefault();
			e.originalEvent.dataTransfer.dropEffect = 'move';
			if (items.is(this)) {
				if (options.forcePlaceholderSize) {
					placeholder.height(dragging.outerHeight());
				}
				dragging.hide();
				$(this)[placeholder.index() < $(this).index()?'after':'before'](placeholder);
				placeholders.not(placeholder).detach();
			} else if (!placeholders.is(this) && !$(this).children(options.items).length) {
				placeholders.detach();
				$(this).append(placeholder);
			}
			return false;
		});
	});
};
})(jQuery);
