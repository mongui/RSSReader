$(document).ready(function(ev) {

	var loader = $("#loader");
	var feeds, posts, selFeed, selPost, hoverFeed, unreaded;

	/* FEED LIST  */
	updateFeedlist();
	setInterval( function()	{ updateFeedlist(); }, 300000);

	$('.list-title').click( function() {
		var title = $(this);
		var list = $(this).next(".list-content");
		list.slideToggle( 400, function() {
			if( list.is(':visible') )
				title.children('div').removeClass('hidden');
			else
				title.children('div').addClass('hidden');
		});
	});

	$('#highlight-unreaded').click( function() {
		loader.fadeIn();

		if ( selFeed )
			selFeed.removeClass('selected-feed');

		loadPostlist('unreaded');
		loader.fadeOut();
		return false;
	});

	$('#highlight-starred').click( function() {
		loader.fadeIn();

		if ( selFeed )
			selFeed.removeClass('selected-feed');

		loadPostlist('starred');
		loader.fadeOut();
		return false;
	});

	function updateFeedlist() {
		loader.fadeIn();

		$.ajax({
			type    : "GET",
			dataType: "json",
			url     : "feeds"
		}).done(function(flist) {
			feeds = flist;
			var feedlist = $("#feed-list");
			feedlist.html('');
			unreaded = 0;

			var feedsBase = $("#feeds-tmpl").html();
			var feedsTmpl;

			$.each(feeds, function(i, item) {
				feedsTmpl = feedsBase;

				feedsTmpl = feedsTmpl.replace("{id_feed}", item.id_feed);
				feedsTmpl = feedsTmpl.replace("{favicon}", '<img src="' + item.favicon + '" alt="' + feeds[i].name + '" />');
				feedsTmpl = feedsTmpl.replace("{name}", item.name);
				feedsTmpl = feedsTmpl.replace("{not_readed}", ( item.count > 0 ) ? 'not-readed' : '');
				feedsTmpl = feedsTmpl.replace("{count}", ( item.count > 0 ) ? '(' + item.count + ')' : '');
				unreaded = unreaded + parseInt(item.count);

				feedlist.append(feedsTmpl);
			});
			$('title').html('RSS Reader (' + unreaded + ')');
			loader.fadeOut();
		}).fail(function() {
			$("#error").text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
	}

	$(document).on("click", "a.item_link", function() {
		if ( selFeed )
			selFeed.removeClass('selected-feed');
		selFeed = $(this);
		selFeed.addClass('selected-feed');

		loadPostlist(selFeed.attr("href"), function() {
			if ( typeof isPhone != 'undefined' ) // For phones.
				setVSeparator();
		});

		return false;
	});

	// I don't know why if I use this outerHeight inside the .feed-menu/click,
	// the post-list goes down the page when the event is called.
	var fcHeight = $('#feed-contextmenu').outerHeight();
	$(document).on("click", ".feed-menu", function(e) {
		var x = $(document).width() - $(this).offset().left - $(this).outerWidth();
		var y = $(this).offset().top + $(this).outerHeight();

		var a = y + fcHeight;
		var b = $('#feed-panel').offset().top + $('#feed-panel').outerHeight() ;

		if ( a > b )
			y = $(this).offset().top - fcHeight;

		displayMenuToggle( $("#feed-contextmenu"), x, y );

		e.stopPropagation();
		e.preventDefault();
	});

	if ( typeof isPhone != 'undefined' ) { // For phones.
		$(".feed-menu").css('display', 'block');

		$(document).on("click", ".feed-menu", function() {
				hoverFeed = $(this).parent().attr("href");
				console.log(hoverFeed);
			});
	}
	else {
		$(document).on({
			mouseenter: function() {
				hoverFeed = $(this).attr("href");
				$(this).children(".feed-menu").show();
			},
			mouseleave: function() {
				$(this).children(".feed-menu").hide();
			}
		}, 'a.item_link');
	}

	$('#mark-as-read').click( function(e) {
		if ( !isNaN(hoverFeed) ) {
			var send = {
				feed    : hoverFeed,
				action  : 'readed'
			};
			feeds = manageFeed(send);

			updateFeedlist(feeds);
		}
	});

	$('#update-feed').click( function(e) {
		if ( !isNaN(hoverFeed) ) {
			var send = {
				feed    : hoverFeed,
				action  : 'update'
			};
			feeds = manageFeed(send);

			updateFeedlist(feeds);
		}
	});

	$('#change-name').click( function(e) {
		if ( !isNaN(hoverFeed) ) {
			var send = {
				feed    : hoverFeed,
				action  : 'name',
				value	: prompt("Please enter the new feed name:", "My feed")
			};
			feeds = manageFeed(send);

			updateFeedlist(feeds);
		}
	});

	$('#unsubscribe').click( function(e) {
		if ( !isNaN(hoverFeed) && confirm('Are you sure you want to unsubscribe from this feed?') ) {
			var send = {
				feed    : hoverFeed,
				action  : 'unsubscribe'
			};
			feeds = manageFeed(send);

			updateFeedlist(feeds);
		}
	});
	/* END FEED LIST */

	/* POST LIST */
	function loadPostlist(feed, callback) {
		loader.fadeIn();

		$.ajax({
			type    : "POST",
			dataType: "json",
			url     : "posts",
			data    : {
				feed: feed
			}
		}).done(function(plist) {
			posts = plist;
			var feedTmpl = $("#feeddata-tmpl").html();
			var postBase = $("#posts-tmpl").html();
			var postlist = $("#post-list");
			var postsTmpl, readed, starred;

			postlist.html('');

			feedTmpl = feedTmpl.replace("{feed_site}", posts.site);
			feedTmpl = feedTmpl.replace("{feed_name}", posts.name);
			feedTmpl = feedTmpl.replace("{last_update}", posts.last_update);
			postlist.append(feedTmpl);

			if ( posts.last_update === '' )
				postlist.children('.feed-title').children('.feed-last-update').hide();

			if ( typeof posts.posts !== 'undefined' ) {
				$.each(posts.posts, function(i, item) {
					postsTmpl = postBase;
					readed = (item.readed > 0) ? 'readed' : '';
					starred = (item.starred > 0) ? 'starred' : '';

					postsTmpl = postsTmpl.replace("{readed}", readed);
					postsTmpl = postsTmpl.replace("{starred}", starred);
					postsTmpl = postsTmpl.replace("{starred}", starred);

					postsTmpl = postsTmpl.replace("{id_post}", item.id_post);
					postsTmpl = postsTmpl.replace("{title}", item.title);
					postsTmpl = postsTmpl.replace("{timestamp}", item.timestamp);
					postsTmpl = postsTmpl.replace("{url}", item.url);
					postsTmpl = postsTmpl.replace("{title}", item.title);

					if ( item.author != '' )
						postsTmpl = postsTmpl.replace("{author}", item.author);
					else
						postsTmpl = postsTmpl.replace("{author}", 'Anonymous');

					postlist.children('.entries').append(postsTmpl);
				});
			}

			$('#post-list').animate({scrollTop: 0},'500');
			loader.fadeOut();

			if (typeof callback === 'function')
				callback();

		}).fail(function() {
			$("#error").text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
	}

	$(document).on("click", "a.title", function(e) {
		if ( selPost )
			selPost.removeClass('selected-post');
		selPost = $(this);
		selPost.addClass('selected-post');

		var content = $(this).next("div.content");

		$("div.content").each(function() {
			if( $(this).is(':visible') && $(this)[0] != content[0]) {
				$(this).hide();
			}
		});

		if ( content.css('display') != 'block' )
			content.html( content.html().replace("{content}", posts.posts['post-' + selPost.attr("href")].content) );

		$('#post-list').animate({scrollTop: selPost.offset().top - $('#header').height()},'500');

		content.slideToggle( 400, function() {
			if ( content.css('display') == 'block' && !$(this).prev().hasClass('readed') ) {
				var send = {
					post    : selPost.attr("href"),
					action  : 'readed',
					state   : 1
				};
				managePost (send);

				$(this).prev().addClass('readed');

				$(this).parents('.entry').children('.content').children('.post-manager').children('.read').removeClass('unread');

				$.each(feeds, function(i, item) {

					if ( item.id_feed == selFeed.attr("href")) {
						feeds[i].count = item.count - 1;
						unreaded--;

						var replaceFeedData = $("#feeds-tmpl").children('li').html();
						replaceFeedData = replaceFeedData.replace("{id_feed}", feeds[i].id_feed);
						replaceFeedData = replaceFeedData.replace("{favicon}", '<img src="' + feeds[i].favicon + '" alt="' + feeds[i].name + '" />');
						replaceFeedData = replaceFeedData.replace("{name}", feeds[i].name);
						replaceFeedData = replaceFeedData.replace("{not_readed}", ( feeds[i].count > 0 ) ? 'not-readed' : '');
						replaceFeedData = replaceFeedData.replace("{count}", ( feeds[i].count > 0 ) ? '(' + feeds[i].count + ')' : '');

						$("#feed-list a[href='" + selFeed.attr("href") + "']").parent().html(replaceFeedData);
					}
				});

				$('title').html('RSS Reader (' + unreaded + ')');
			}
		});

		e.preventDefault();
	});

	$(document).on("click", ".read", function(e) {
		var send = {
			post    : $(this).parents('.entry').children('.title').attr("href"),
			action  : 'readed',
			state   : 0
		};

		if ( $(this).hasClass('unread') ) {
			send.state = 1;
			$(this).removeClass('unread');
			$(this).parents('.entry').children('.title').addClass('readed');
		}
		else {
			send.state = 0;
			$(this).addClass('unread');
			$(this).parents('.entry').children('.title').removeClass('readed');

		}
		managePost (send);
	});

	$(document).on("click", "#star1", function(e) {
		var send = {
			post    : $(this).parent().attr("href"),
			action  : 'starred',
			state   : 0
		};

		if ( $(this).hasClass('starred') ) {
			send.state = 0;
			$(this).removeClass('starred');
			$(this).parents('.entry').children('.content').children('.post-manager').children('#star2').removeClass('starred');
		}
		else {
			send.state = 1;
			$(this).addClass('starred');
			$(this).parents('.entry').children('.content').children('.post-manager').children('#star2').addClass('starred');
		}

		managePost (send);

		e.stopPropagation();
		e.preventDefault();
	});

	$(document).on("click", "#star2", function(e) {
		var send = {
			post    : $(this).parents('.entry').children('a').attr("href"),
			action  : 'starred',
			state   : 0
		};

		if ( $(this).hasClass('starred') ) {
			send.state = 0;
			$(this).removeClass('starred');
			 $(this).parents('.entry').children('a').children('#star1').removeClass('starred');
		}
		else {
			send.state = 1;
			$(this).addClass('starred');
			 $(this).parents('.entry').children('a').children('#star1').addClass('starred');
		}

		managePost (send);
	});
	/* END POST LIST */

	/* ADD FEED FORM */
	$('#add-feed').click( function() {
		var addForm = $("#add-form");

		if ( addForm.is(':visible') )
			addForm.hide();
		else {
			var addFeed = $("#add-feed");

			addForm.css('left', addFeed.offset().left + addFeed.outerWidth() + 5);
			addForm.css('top', addFeed.offset().top);

			addForm.show();
		}
	});

	$('#submit-feed').click( function() {
		loader.fadeIn();
		$.ajax({
			type    : "POST",
			url     : "add",
			data    : {
				feed_url: $('#feed-url').val()
			}
		}).done(function() {
			$("#add-form").hide();
			updateFeedlist();

			$("#success").text('The feed was successfully added.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		}).fail(function() {
			$("#error").text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});

		return false;
	});
	/* END ADD FEED FORM */

	/* SEARCH FORM */
	$('#highlight-search').click( function() {
		var searchForm = $("#search-form");

		if ( searchForm.is(':visible') )
			searchForm.hide();
		else
		{
			searchForm.show();
			$("#search-input").focus();
		}
	});

	$('#submit-search').click( function() {
		loader.fadeIn();

		if ( selFeed )
			selFeed.removeClass('selected-feed');

		loadPostlist( $('#search-input').val() );
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
		if ( displayMenu )
			displayMenuToggle();
	});

	function displayMenuToggle( element, x, y ) {
		if ( displayMenu ) {
			displayMenu.hide();
			displayMenu = null;
		}

		if ( element ) {
			displayMenu = element;
			if ( x && y ) {
				displayMenu.css('right', x);
				displayMenu.css('top', y);
			}

			displayMenu.show();
		}
	}
	/* END DISPLAY MENUs / CONTEXT MENUs */

	/* PREFERENCES MENU */
	$('#preferences-menu').click( function() {
		loader.fadeIn();
		$.ajax({
			type    : "GET",
			url     : "preferences"
		}).done(function(msg) {
			$("#post-list").html(msg);

			$("#timeformat").val(timeformat);
			$("#language").val(language);

			loader.fadeOut();
		}).fail(function() {
			$("#error").text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
	});

	$(document).on("click", "#submit-preferences", function(e) {
		var error = $("#error");
		var success = $("#success");
		error.fadeOut();
		success.fadeOut();
		loader.fadeOut();

		var curPassword  = $('#cur-password').val();
		var newPassword  = $('#new-password').val();
		var newPassword2 = $('#new-password2').val();

		if ( newPassword != '' || newPassword2 != '' ) {
			if ( newPassword.length < 6 ) {
				error.text('Your password must be at least 6 characters.');
				error.fadeIn();
				return false;
			}
			else if ( newPassword !== newPassword2 ) {
				error.text('Passwords do not match.');
				error.fadeIn();
				return false;
			}
			else if ( curPassword === '' ) {
				error.text('We need your current password to verify your identity.');
				error.fadeIn();
				return false;
			}
			else {
				error.fadeOut();
			}
		}

		loader.fadeIn();

		var prefData = {
			timeformat:     $('#timeformat').val(),
			language:       $('#language').val(),
			curPassword:    curPassword,
			newPassword:    newPassword
		};

		if ( typeof window.serverData == 'function' ) {
			var moreData = serverData();
			$.extend(prefData, moreData);
		}

		$.ajax({
			type    : "POST",
			url     : "preferences",
			data    : prefData
		}).done(function(msg) {
			if ( msg === 'success' ) {
				$("#success").text('Data saved.').fadeIn();
				setTimeout(function(){ $(".info").fadeOut(); }, 5000);
			}
			else if ( msg === 'curPass' ) {
				$("#error").text('Your current password is not correct.').fadeIn();
				setTimeout(function(){ $(".info").fadeOut(); }, 5000);
			}
			else {
				$("#error").text('Something wrong happened. We can\'t save your preferences now. Sorry.').fadeIn();
				setTimeout(function(){ $(".info").fadeOut(); }, 5000);
			}

		}).fail(function() {
			$("#error").text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
		return false;
	});

	$('#import-menu').click( function() {
		loader.fadeIn();
		$.ajax({
			type    : "GET",
			url     : "importfile"
		}).done(function(msg) {
			$("#post-list").html(msg);
			loader.fadeOut();
		}).fail(function() {
			$("#error").text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
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
		$('#import-form').submit(function() {
		//alert('Handler for .submit() called.');
		});
	});
	/* END IMPORT OPML FILE FORM */

	/* SEPARATOR */
	var sep = null;
	$("#separator").mousedown(function(e) {
		if ( typeof isPhone != 'undefined' ) // For phones.
			setVSeparator();
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
			if ( $('#post-list').is(':visible') )
				$("#post-list").height( feedPanelHeight );
			else
				$("#feed-panel").height( feedPanelHeight );
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
	$(window).load(function(ev) {

		if ( typeof isPhone != 'undefined' ) { // For phones.
			feedPanelHeight = $(window).height() - 30; // 30 from #separator
			$("#feed-panel").height( feedPanelHeight );
		}
		else {
			getSeparator();
			setSeparator(rss_sepwidth);
			$('#post-list').show();
			sep = null;

			$("#wrapper").height( ($(window).height() - 75) );
		}

	});
	/* END SEPARATOR */
});

/* SEPARATOR */
function getSeparator() {
	block = $("#separator");
	return sep = {
		w : block.width(),
		p : block.prev(),
		n : block.next(),
		dw: $(document).width(),
		pw: block.prev().width()
	};
}

function setSeparator(data) {
	var wx;
	if      ( !isNaN(data) )        wx = data;
	else if ( !isNaN(data.pageX) )  wx = data.pageX;
	else                            wx = sep.pw;

	sep.p.width(wx);
	sep.n.width(Math.floor(sep.dw - sep.w - sep.p.width() -8)); // -8 depends of borders, margins,...
	return wx;
}

var feedPanelHeight;
function setVSeparator() {
	var togglePanel1;
	var togglePanel2;
	if ( $("#feed-panel").height() > 0 ) {
			togglePanel1 = 0;
			togglePanel2 = feedPanelHeight;
	}
	else {
			togglePanel1 = feedPanelHeight;
			togglePanel2 = 0;
	}
	$('#feed-panel').animate({ height: togglePanel1 }, 400);
	$('#post-list').animate({ height: togglePanel2 }, 400, function() {
		$('#post-list').toggle();
	});
}
/* END SEPARATOR */

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
		while (c.charAt(0) == ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name, "", -1);
}

function manageFeed (send) {
	$.ajax({
		type	: "POST",
		url		: "managefeed",
		data	: send
	}).done(function(msg) {
		return msg;
	}).fail(function() {
		$("#error").text('Can\'t reach the server. Please, try again later.').fadeIn();
		setTimeout(function(){ $(".info").fadeOut(); }, 5000);
	});
}

function managePost(send) {
	$.ajax({
		type	: "POST",
		url		: "managepost",
		data	: send
	}).done(function(msg) {
		return msg;
	}).fail(function() {
		$("#error").text('Can\'t reach the server. Please, try again later.').fadeIn();
		setTimeout(function(){ $(".info").fadeOut(); }, 5000);
	});
}
