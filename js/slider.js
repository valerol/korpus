jQuery(function() {
	jQuery('.jcarousel').jcarousel({
		animation: 'slow',
		wrap: 'circular'
	});
	
	jQuery('.jcarousel-prev').jcarouselControl({
	  target: '-=1'
	});

	jQuery('.jcarousel-next').jcarouselControl({
	  target: '+=1'
	});
	
	jQuery('.jcarousel').jcarouselAutoscroll({
	  interval: 3000,
	  target: '+=3',
	  autostart: true
	});
}); 
