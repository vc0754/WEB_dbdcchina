(function() {
	var carousel = new Hammer(document.getElementById("carousel"));
	carousel.on('swipeleft', function() {
		$('#carousel').find('.next').trigger('click');
	});
	carousel.on('swiperight', function() {
		$('#carousel').find('.prev').trigger('click');
	});
})();