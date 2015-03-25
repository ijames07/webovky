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

		postMessage(msg, author, formatDate(new Date()));
		var room = document.URL.substring(document.URL.lastIndexOf('/') + 1, document.URL.length);
		var jqxhr = $.ajax({ 
						method: 'POST',
						url: "/chat/www/chatrooms/send",
						cache: false,
						data: { msg: msg, room: room }
					});
		/*var time = new Date();
		$('#window').prepend('<div class="thumbnail"><p>[' + time.getHours() + ':' + ("0" + time.getMinutes()).slice(-2) + ':' + ('0' + time.getSeconds()).slice(-2) + '] Adam: ' + msg + '</p></div>');*/
	}
	$('.modal').on('shown.bs.modal', function () {
		$(this).find('[name=pw]').focus();
	});
});

function formatDate(date) {
	return date.getHours() + ':' + ('00' + date.getMinutes()).slice(-2) + ':'
			+ ('00' + date.getSeconds()).slice(-2)
			+ ' ' + date.getDate() + '.' + (date.getMonth()+1) + '.';
}

function postMessage(msg, author, time) {
	$('#window').prepend('<div class="row"><div class="col-sm-12 col-xs-12 col-md-11 message">' +
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
				postMessage(msg[i].msg, msg[i].from, msg[i].time);
			}
		});
}
