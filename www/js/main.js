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
		var jqxhr = $.ajax({ 
						method: 'POST',
						url: "/chat/www/chatrooms/send",
						cache: false,
						data: { msg: msg }
					})
			.done(function(msg) {
				console.log(msg);
			});
		/*var time = new Date();
		$('#window').prepend('<div class="thumbnail"><p>[' + time.getHours() + ':' + ("0" + time.getMinutes()).slice(-2) + ':' + ('0' + time.getSeconds()).slice(-2) + '] Adam: ' + msg + '</p></div>');*/
	}
	$('.modal').on('shown.bs.modal', function () {
		$(this).find('[name=pw]').focus();
	});
});
