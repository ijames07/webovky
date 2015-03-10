$(function(){
	$('#snd').click(function() {
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
	var time = new Date();
	$('#window').append('<p>[' + time.getHours() + ':' + ("0" + time.getMinutes()).slice(-2) + ':' + ('0' + time.getSeconds()).slice(-2) + '] Adam: ' + msg + '</p>');
}
});
