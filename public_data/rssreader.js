var loader, error, success, header, feedPanel, feedList, postList, separator, feeds, posts, selFeed, selPost, selFeedId, selPostId, hoverFeed, unreaded, killScroll, lastSelFeed, reloadPostList;

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

	$(window).on('hashchange', function() { readHash(); });
	/* END LOAD FROM HASH */

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
			loader.fadeOut();
		}).fail(function() {
			error.text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
		});
	}

	function addFeedToList ( feedData ) {
		feedsTmpl = $("#feeds-tmpl").html();
		name = (feedData.name !== '') ? feedData.name : 'No name';
		feedsTmpl = feedsTmpl
			.replace("{id_feed}",  '#/' + textToURL(name) + '_f' + feedData.id_feed)
			.replace("{name}", name)
			.replace("{not_readed}", ( feedData.count > 0 ) ? 'not-readed' : '')
			.replace("{selected}", ( typeof(selFeedId) !== 'undefined' && feedData.id_feed == selFeedId ) ? 'selected-feed' : '')
			.replace("{count}", ( feedData.count > 0 ) ? '(' + feedData.count + ')' : '');

			if ( typeof(feedData.favicon) != 'undefined' ) {
				feedsTmpl = feedsTmpl.replace("{favicon}", '<img src="' + feedData.favicon + '" alt="' + feedData.name + '" />');
			}
			else {
				feedsTmpl = feedsTmpl.replace("{favicon}", '<span class="sprite">&nbsp;</span>');
			}
			unreaded = unreaded + parseInt(feedData.count);

		return feedsTmpl;
	}

	function addFolderToList ( folderData ) {
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

		var x = $(document).width() - $(this).offset().left - $(this).outerWidth();
		var y = $(this).offset().top + $(this).outerHeight();

		var a = y + fcHeight;
		var b = feedPanel.offset().top + feedPanel.outerHeight() ;

		if ( a > b ) {
			y = $(this).offset().top - fcHeight;
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

			updateFeedlist();
		}
	});

	$('#update-feed').click( function() {
		if ( !isNaN(hoverFeed) ) {
			var send = {
				feed	: hoverFeed,
				action	: 'update'
			};
			feeds = manageFeed(send);
			reloadPostList = true;
			updateFeedlist();
			readHash();
		}
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
				value	: prompt("Please enter the new feed name:", name)
			};
			feeds = manageFeed(send);

			updateFeedlist();
		}
	});

	$('#add-to-folder').click( function() {
		if ( !isNaN(hoverFeed) ) {
			var nfolder = prompt("New folder name:", "New folder");

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
		if ( !isNaN(hoverFeed) && confirm('Are you sure you want to unsubscribe from this feed?') ) {
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
	function loadPostlist(feed, from, callback) {
		if ( killScroll == true || typeof(feed) === 'undefined' ) {
			return;
		}

		killScroll = true;

		loader.fadeIn();

		var sendData = {
			feed : feed
		};

		if ( from > 0 ) {
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
			if ( from > 0 && typeof plist.posts !== 'undefined' ) {
				$.extend(posts.posts, plist.posts);
			}
			else if ( from == 0 ) {
				$.extend(posts, plist);
			}

			var feedTmpl = $("#feeddata-tmpl").html();
			var postBase = $("#posts-tmpl").html();
			var postsTmpl, readed, starred;

			if ( from == 0 ) {
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

						.replace("{author}", ( item.author != '' ) ? item.author : 'Anonymous');

					postList.children('.entries').append(postsTmpl);

					if ( selPostId == item.id_post ) {
						NXTselPostId = item.id_post;
					}
				});
			}

			if ( from > 0 && typeof callback === 'function' ) {
				callback();
			}
			else {
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
		}).fail(function() {
			error.text('Can\'t reach the server. Please, try again later.').fadeIn();
			setTimeout(function(){ $(".info").fadeOut(); }, 5000);
			killScroll = false;
		});
	}

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
			content.html( content.html().replace("{content}", posts.posts['post-' + selPostId].content) );
		}

		postList.animate({scrollTop: selPost.offset().top - header.height()}, '500');

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
			loadPostlist(lastSelFeed, $(".entry").size(), function() {
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

		if ( searchForm.is(':visible') ) {
			searchForm.hide();
		}
		else {
			searchForm.show();
			searchForm.children("#search-input").focus();
		}
	});

	$('#submit-search').click( function() {
		loader.fadeIn();

		if ( selFeed ) {
			selFeed.removeClass('selected-feed');
		}

		loadPostlist($('#search-input').val(), 0, function() {
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
	if		( !isNaN(data) )		{ wx = data;		}
	else if	( !isNaN(data.pageX) )	{ wx = data.pageX;	}
	else							{ wx = sep.pw;		}

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

function manageFeed(send) {
	$.ajax({
		type	: "POST",
		url		: "feeds/manage",
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
		url		: "posts/manage",
		data	: send
	}).done(function(msg) {
		return msg;
	}).fail(function() {
		error.text('Can\'t reach the server. Please, try again later.').fadeIn();
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
