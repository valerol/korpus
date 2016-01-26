;(function ($) {

	$(document).ready(function() {
		$('.elm-button').on('click', function (e) {
			e.preventDefault();

			var $that = $(this),
				url = $that.attr('data-href'),
				nextPage = parseInt($that.attr('data-page'), 10) + 1,
				maxPages = parseInt($that.attr('data-max-pages'), 10);

			$that.addClass('is-loading');

			if (url.indexOf('?') > 0) {
				url += '&';
			} else {
				url += '?';
			}

			url += 'paged=' + nextPage;

			$.ajax({
				type : 'POST',
				url : url,
				dataType: 'text'
			}).done(function (data) {

				$that.removeClass('is-loading');

				if ($(elm_button_vars.wrapper).length) {
					$(elm_button_vars.wrapper).append($($.parseHTML(data)).find(elm_button_vars.wrapper).addBack(elm_button_vars.wrapper).html());
				} else {
					console.log('Please update Easy Load More settings with post list wrapper selector.');
				}

				if ( nextPage == maxPages ) {
					$that.remove();
				} else {
					$that.attr('data-page', nextPage);
				}

			}).fail(function () {
				console.log('Ajax failed. Navigating to ' + url + '.');
				window.location.href = url;
			});

			return false;
		});
	});

}(jQuery));