$(function(){
	$('#send').click(function() {
		send();
	});
	$(document).keypress(function(e) {
		if(e.which === 13) {
			send();
		}
	});
	function send() {
		var msg = $('#msg').val();
		if (msg === '') {
			return;
		}
		$('#msg').val('');
		var author = $('#name').val();
		if (msg.indexOf('/w') === 0) {
			// ziskam jmeno ciloveho uzivatele
			var to = msg.substring(3, msg.indexOf(' ', 3));
			// vyexportuju jenom zpravu, + 1 pro preskoceni mezery za jmenem
			msg = msg.substring(msg.indexOf(to) + to.length + 1, msg.length);
		}
		postMessage(msg, author, formatDate(new Date()), to);
		var room = document.URL.substring(document.URL.lastIndexOf('/') + 1, document.URL.length);
		var jqxhr = $.ajax({ 
						method: 'POST',
						url: "/chat/www/chatrooms/send",
						cache: false,
						data: { msg: msg, room: room, to: to }
					});
		/*var time = new Date();
		$('#window').prepend('<div class="thumbnail"><p>[' + time.getHours() + ':' + ("0" + time.getMinutes()).slice(-2) + ':' + ('0' + time.getSeconds()).slice(-2) + '] Adam: ' + msg + '</p></div>');*/
	}
	$('.modal').on('shown.bs.modal', function () {
		$(this).find('[name=pw]').focus();
	});
});

if ((document.location.search).indexOf('kick') !== -1) {
	toastr.warning('Byl jsi vyhozen z místnosti');
}

function formatDate(date) {
	return date.getHours() + ':' + ('00' + date.getMinutes()).slice(-2) + ':'
			+ ('00' + date.getSeconds()).slice(-2)
			+ ' ' + date.getDate() + '.' + (date.getMonth()+1) + '.';
}

function postMessage(msg, author, time, to) {
	// jsem prijemce soukrome zpravy?
	var me = $('#name').val();
	if (to == me) {
		cls = ' privT';
		msg = '<strong>' + author + '</strong>' + ': ' + msg;
		// odeslal jsem ji ja?
	} else if (author == me) {
		cls = ' privF';
		// je tato zprava od nekoho mimo me pro vsechny?
	} else if (to === undefined || to === null) {
		cls = '';
		// odesilam nekomu soukromou zpravu?
	} else {
		cls = ' privF';
		msg = '<strong>' + to + '</strong>' + ': ' + msg;
	}
	$('#window').prepend('<div class="row"><div class="col-sm-12 col-xs-12 col-md-11 message' + 
			cls + '">' +
		'<div class="thumbnail">'+
		'	<div class="caption"><span class="author">' + author + '</span>' +
		'		<div class="pull-right">' + time +
		'		</div>' +
		'	</div>' +
		'	<p>' + msg + '</p>' +
		'</div>' +
	'</div></div>');
}

function update() {
	//var lastMsgId = $('.message').get(0).id;
	var id = document.URL.substring(document.URL.lastIndexOf('/') + 1, document.URL.length);
	var jqxhr = $.ajax({
					method: 'POST',
					url: "/chat/www/chatrooms/update",
					cache: false,
					data: { room: id }
				})
		.done(function(msg) {
			if (typeof msg.kick !== 'undefined') {
				clearInterval(aktualizuj);
				window.location.href = '/chat/www/chatrooms?kick=yes';
			}
			var me = $('#name').val();
			for (i = 0; i < msg.length; i++) {
				var message = $('.message').get(0);
				var author = $(message).find('.author').text();
				var oldMsg = $(message).find('p').text();
				try {
					if (me == msg[i].from || (msg[i].msg == oldMsg && msg[i].from == author)) {
						break;
					}
				} catch (err) {
					
				}
				postMessage(msg[i].msg, msg[i].from, msg[i].time, msg[i].to);
			}
		});
}
