var loader, error, success, header, feedPanel, feedList, postList, separator, feeds, posts, selFeed, selPost, hoverFeed, unreaded;

$(document).ready(function(ev) {
	loader		= $("#loader");
	error		= $("#error");
	success		= $("#success");
	header		= $('#header');
	feedPanel	= $("#feed-panel");
	feedList	= $("#feed-list");
	postList	= $("#post-list");
	separator	= $("#separator");

	/* FEED LIST  */
	updateFeedlist();
	setInterval( function() { updateFeedlist(); }, 300000);

	$('.list-title').click( function() {
		var title = $(this);
		var list = $(this).next(".list-content");
		list.slideToggle( 400, function() {
			if ( list.is(':visible') )
				title.children('div').removeClass('hidden');
			else
				title.children('div').addClass('hidden');
		});
	});

	$('#highlight-unreaded').click( function() {
		loader.fadeIn();

		if ( selFeed )
			selFeed.removeClass('selected-feed');

		loadPostlist('unreaded', function() {
			if ( typeof isPhone != 'undefined' ) // For phones.
				setVSeparator();
		});
		loader.fadeOut();
		return false;
	});

	$('#highlight-starred').click( function() {
		loader.fadeIn();

		if ( selFeed )
			selFeed.removeClass('selected-feed');

		loadPostlist('starred', function() {
			if ( typeof isPhone != 'undefined' ) // For phones.
				setVSeparator();
		});
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
			feedList.html('');
			unreaded = 0;

			var feedsBase = $("#feeds-tmpl").html();
			var feedsTmpl;

			$.each(feeds, function(i, item) {
				feedsTmpl = feedsBase;

				feedsTmpl = feedsTmpl
					.replace("{id_feed}", item.id_feed)
					.replace("{favicon}", '<img src="' + item.favicon + '" alt="' + feeds[i].name + '" />')
					.replace("{name}", item.name)
					.replace("{not_readed}", ( item.count > 0 ) ? 'not-readed' : '')
					.replace("{count}", ( item.count > 0 ) ? '(' + item.count + ')' : '');
				unreaded = unreaded + parseInt(item.count);

				feedList.append(feedsTmpl);
			});

			if ( selFeed ) {
				var prevSelected = selFeed.attr("href");
				$.each(feedList.children('li'), function(i, item) {
					if ( $(item.children[0]).attr('href') == prevSelected ) {
						selFeed = $(item.children[0]);
						selFeed.addClass('selected-feed');
					}
				});
			}

			$('title').html('RSS Reader (' + unreaded + ')');
			loader.fadeOut();
		}).fail(function() {
			error.text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
	}

	$(document).on("click", "a.item_link", function() {
		if ( selFeed )
			selFeed.removeClass('selected-feed');
		selFeed = $(this).addClass('selected-feed');

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
		var b = feedPanel.offset().top + feedPanel.outerHeight() ;

		if ( a > b )
			y = $(this).offset().top - fcHeight;

		displayMenuToggle( $("#feed-contextmenu"), x, y );

		e.stopPropagation();
		e.preventDefault();
	});

	if ( typeof isPhone != 'undefined' ) { // For phones.
		$(document).on("click", ".feed-menu", function() {
			hoverFeed = $(this).parent().attr("href");
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

	$('#mark-as-read').click( function() {
		if ( !isNaN(hoverFeed) ) {
			var send = {
				feed    : hoverFeed,
				action  : 'readed'
			};
			feeds = manageFeed(send);

			updateFeedlist();
		}
	});

	$('#update-feed').click( function(e) {
		if ( !isNaN(hoverFeed) ) {
			var send = {
				feed    : hoverFeed,
				action  : 'update'
			};
			feeds = manageFeed(send);

			updateFeedlist();
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

			updateFeedlist();
		}
	});

	$('#unsubscribe').click( function(e) {
		if ( !isNaN(hoverFeed) && confirm('Are you sure you want to unsubscribe from this feed?') ) {
			var send = {
				feed    : hoverFeed,
				action  : 'unsubscribe'
			};
			feeds = manageFeed(send);

			updateFeedlist();
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
			postList.html('');
			var postsTmpl, readed, starred;

			feedTmpl = feedTmpl
				.replace("{feed_site}", posts.site)
				.replace("{feed_name}", posts.name)
				.replace("{last_update}", posts.last_update);
			postList.append(feedTmpl);

			if ( posts.last_update === '' )
				postList.children('.feed-title').children('.feed-last-update').hide();

			if ( typeof posts.posts !== 'undefined' ) {
				$.each(posts.posts, function(i, item) {
					postsTmpl = postBase;
					readed = (item.readed > 0) ? 'readed' : '';
					starred = (item.starred > 0) ? 'starred' : '';

					postsTmpl = postsTmpl
						.replace("{readed}", readed)
						.replace("{starred}", starred)
						.replace("{starred}", starred)

						.replace("{id_post}", item.id_post)
						.replace("{title}", item.title)
						.replace("{timestamp}", item.timestamp)
						.replace("{url}", item.url)
						.replace("{title}", item.title)

						.replace("{author}", ( item.author != '' ) ? item.author : 'Anonymous');

					postList.children('.entries').append(postsTmpl);
				});
			}

			postList.animate({scrollTop: 0},'500', function() {
				if (typeof callback === 'function')
					callback();
			});
			loader.fadeOut();
		}).fail(function() {
			error.text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
	}

	$(document).on("click", "a.title", function(e) {
		if ( selPost )
			selPost.removeClass('selected-post');
		selPost = $(this).addClass('selected-post');

		var content = $(this).next("div.content");

		$("div.content").each(function() {
			if( $(this).is(':visible') && $(this)[0] != content[0]) {
				$(this).hide();
			}
		});

		if ( content.css('display') != 'block' )
			content.html( content.html().replace("{content}", posts.posts['post-' + selPost.attr("href")].content) );

		postList.animate({scrollTop: selPost.offset().top - header.height()}, '500');

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
						replaceFeedData = replaceFeedData
							.replace("{id_feed}", feeds[i].id_feed)
							.replace("{favicon}", '<img src="' + feeds[i].favicon + '" alt="' + feeds[i].name + '" />')
							.replace("{name}", feeds[i].name)
							.replace("{not_readed}", ( feeds[i].count > 0 ) ? 'not-readed' : '')
							.replace("{count}", ( feeds[i].count > 0 ) ? '(' + feeds[i].count + ')' : '');

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
			$(this)
				.removeClass('unread')
				.parents('.entry').children('.title').addClass('readed');
		}
		else {
			send.state = 0;
			$(this)
				.addClass('unread')
				.parents('.entry').children('.title').removeClass('readed');

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
	});

	$(document).on("click", "#star2", function(e) {
		var send = {
			post    : $(this).parents('.entry').children('a').attr("href"),
			action  : 'starred',
			state   : 0
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
	/* END POST LIST */

	/* ADD FEED FORM */
	$('#add-feed').click( function() {
		var addForm = $(this).next("#add-form");

		if ( addForm.is(':visible') )
			addForm.hide();
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
			type    : "POST",
			url     : "add",
			data    : {
				feed_url: $('#feed-url').val()
			}
		}).done(function() {
			$("#add-form").hide();
			updateFeedlist();

			success.text('The feed was successfully added.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		}).fail(function() {
			error.text('Can\'t reach the server. Please, try again later.').fadeIn();
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
			searchForm.children("#search-input").focus();
		}
	});

	$('#submit-search').click( function() {
		loader.fadeIn();

		if ( selFeed )
			selFeed.removeClass('selected-feed');

		loadPostlist($('#search-input').val(), function() {
			if ( typeof isPhone != 'undefined' ) // For phones.
				setVSeparator();
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
			type    : "GET",
			url     : "preferences"
		}).done(function(form) {
			postList.html(form);

			$("#timeformat").val(timeformat);
			$("#language").val(language);

			if ( typeof isPhone != 'undefined' ) // For phones.
				setVSeparator();

			loader.fadeOut();
		}).fail(function() {
			error.text('Can\'t reach the server. Please, try again later.').fadeIn();
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
				error.text('Your password must be at least 6 characters.').fadeIn();
				return false;
			}
			else if ( newPassword !== newPassword2 ) {
				error.text('Passwords do not match.').fadeIn();
				return false;
			}
			else if ( curPassword === '' ) {
				error.text('We need your current password to verify your identity.').fadeIn();
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
				success.text('Data saved.').fadeIn();
			}
			else if ( msg === 'curPass' ) {
				error.text('Your current password is not correct.').fadeIn();
			}
			else {
				error.text('Something wrong happened. We can\'t save your preferences now. Sorry.').fadeIn();
			}
			setTimeout(function(){ $('.info').fadeOut(); }, 5000);

		}).fail(function() {
			error.text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $('.info').fadeOut(); }, 5000);
		});
		return false;
	});

	$('#import-menu').click( function() {
		loader.fadeIn();
		$.ajax({
			type    : "GET",
			url     : "importfile"
		}).done(function(form) {
			postList.html(form);
			if ( typeof isPhone != 'undefined' ) // For phones.
				setVSeparator();
			loader.fadeOut();
		}).fail(function() {
			error.text('Can\'t reach the server. Please, try again later.').fadeIn();
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
			error.text('Select a file first.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
			return false;
		}

		$('#import-form').submit(function() {
			var subForm = $('#submited-form');
			var count = 0;
			var subInt = self.setInterval(function() {
				if ( $.trim(subForm.contents().find('body').html()) == 'success' ) {
					subInt = window.clearInterval(subInt);
					success.text('File successfully uploaded.').fadeIn();
					setTimeout(function(){ $(".info").fadeOut(); }, 5000);
					updateFeedlist();
				}
				else if ( $.trim(subForm.contents().find('body').html()) == 'failure' ) {
					subInt = window.clearInterval(subInt);
					error.text('The file you tried to upload is not compatible.').fadeIn();
					setTimeout(function(){ $(".info").fadeOut(); }, 5000);
				}
				else {
					if ( count >= 15 ) {
						subInt = window.clearInterval(subInt);
						error.text('Something wrong happened. We can\'t upload your file now. Sorry.').fadeIn();
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
		}
		else {
			getSeparator();
			setSeparator(rss_sepwidth);
			postList.show();
			sep = null;

			$("#wrapper").height( ($(window).height() - 75) );
		}

	});
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
		error.text('Can\'t reach the server. Please, try again later.').fadeIn();
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
		error.text('Can\'t reach the server. Please, try again later.').fadeIn();
		setTimeout(function(){ $(".info").fadeOut(); }, 5000);
	});
}
