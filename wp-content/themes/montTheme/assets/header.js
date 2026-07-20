jQuery(document).ready(function($) {
	var lastScrollTop = 0;
	var delta = 5;
	var navbarHeight = $('.mont_header_sticky-header').outerHeight();

	
            // Cache DOM elements
	var $body = $('body');
	var $hamburger = $('.mobile-menu');
	var $mobileMenuContainer = $('.mont_header_mobile_menu_container');
	var $mainMobileMenu = $('.mont_header_mobile_main_menu');
	var $mobileMegaMenu = $('.mont_header_mobile_mega_menu');
	var $megaMenuLink = $('.mont_mega');
	var $backButton = $('.mont_header_mobile_back_button');

            // Initialize mobile menu state
	var isMenuOpen = false;
	var isMegaMenuOpen = false;

            // Set initial positions
	$mobileMegaMenu.css('transform', 'translateX(100%)');

	$(window).scroll(function(event) {
		var st = $(this).scrollTop();

                // Make sure they scroll more than delta
		if (Math.abs(lastScrollTop - st) <= delta)
			return;
		
			if(st > 100)
				{
					$('.mont_header_sticky-header').removeClass('removeWhite');
					$('.mont_header_sticky-header').addClass('mega-menu-width');
				}
		else
			{
				$('.mont_header_sticky-header').addClass('removeWhite');
				$('.mont_header_sticky-header').removeClass('mega-menu-width');
			}

                // If they scrolled down and are past the navbar, add class .mont_header_hide.
		if (st > lastScrollTop && st > navbarHeight) {
                    // Scroll Down
			$('.mont_header_sticky-header').addClass('mont_header_hide');
			
		} else {
                    // Scroll Up
			if (st + $(window).height() < $(document).height()) {
				$('.mont_header_sticky-header').removeClass('mont_header_hide');
						
			}
		}

		lastScrollTop = st;
	});

            // Hamburger and mega menu interaction
	let megaMenuTimeout;
	$('.mont_header_hamburger, .mont_header_mega-menu').hover(
		function() {
			clearTimeout(megaMenuTimeout);
			$('.mont_header_mega-menu').stop().slideDown(300);
		},
		function() {
			megaMenuTimeout = setTimeout(function() {
				$('.mont_header_mega-menu').stop().slideUp(300);
			}, 300);
		}
		);

            // Mobile menu toggle
	$('.mont_header_hamburger').click(function() {
		$('.mont_header_menu').toggleClass('active');
	});

            // Close mobile menu when clicking outside
	$(document).click(function(event) {
		if (!$(event.target).closest('.mont_header_nav-left, .mont_header_menu').length) {
			$('.mont_header_menu').removeClass('active');
		}
	});

            // Responsive menu
	$(window).resize(function() {
		if ($(window).width() > 768) {
			$('.mont_header_menu').removeClass('active');
		}
	});

            // Language switcher
	$('.mont_header_current-lang').click(function(e) {
		e.stopPropagation();
		$('.mont_header_lang-options').slideToggle(200);
	});

	$('.mont_header_lang-options a').click(function(e) {
		e.preventDefault();
		$('.mont_header_current-lang').text($(this).data('lang').toUpperCase());
		$('.mont_header_lang-options').slideUp(200);
	});

	$(document).click(function() {
		$('.mont_header_lang-options').slideUp(200);
	});



            // Toggle mobile menu when hamburger is clicked
	$hamburger.on('click', function(e) {
		e.stopPropagation();
		$(this).toggleClass('active');

		if (!isMenuOpen) {
			openMobileMenu();
		} else {
			closeMobileMenu();
		}
	});

            // Function to open mobile menu
	function openMobileMenu() {
		$mobileMenuContainer.fadeIn(300);
		$mainMobileMenu.css('transform', 'translateX(0)');
		$body.addClass('menu-open');
		isMenuOpen = true;
	}

            // Function to close mobile menu
	function closeMobileMenu() {
		$mobileMenuContainer.fadeOut(300);
		$mainMobileMenu.css('transform', 'translateX(0)');
		$mobileMegaMenu.css('transform', 'translateX(100%)');
		$body.removeClass('menu-open');
		isMenuOpen = false;
		isMegaMenuOpen = false;
	}

            // Show mega menu when mega menu link is clicked
	$megaMenuLink.on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();

		if (!isMegaMenuOpen) {
			openMegaMenu();
		}
	});

            // Function to open mega menu
	function openMegaMenu() {
		$mainMobileMenu.css('transform', 'translateX(-100%)');
		$mobileMegaMenu.css('transform', 'translateX(0)');
		isMegaMenuOpen = true;
	}

            // Back button functionality
	$backButton.on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();

		if (isMegaMenuOpen) {
			closeMegaMenu();
		}
	});

            // Function to close mega menu
	function closeMegaMenu() {
		$mainMobileMenu.css('transform', 'translateX(0)');
		$mobileMegaMenu.css('transform', 'translateX(100%)');
		isMegaMenuOpen = false;
	}

            // Close menu when clicking outside
	$(document).on('click touchstart', function(e) {
		if (isMenuOpen && !$(e.target).closest('.mont_header_mobile_menu_container, .mont_header_hamburger').length) {
			closeMobileMenu();
			$hamburger.removeClass('active');
		}
	});

            // Prevent clicks inside menu from closing it
	$mobileMenuContainer.on('click touchstart', function(e) {
		e.stopPropagation();
	});

            // Handle window resize
	var resizeTimer;
	$(window).on('resize', function() {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function() {
			if ($(window).width() > 768) {
				closeMobileMenu();
				$hamburger.removeClass('active');
			}
		}, 250);
	});

            // Handle scroll prevention
	function preventDefault(e) {
		e.preventDefault();
	}

	function disableScroll() {
		document.body.addEventListener('touchmove', preventDefault, { passive: false });
	}

	function enableScroll() {
		document.body.removeEventListener('touchmove', preventDefault);
	}

            // Update scroll handling
	$body.on('menu-open', function() {
		disableScroll();
	}).on('menu-close', function() {
		enableScroll();
	});

            // Handle keyboard accessibility
	$mobileMenuContainer.on('keydown', function(e) {
		if (e.key === 'Escape') {
			closeMobileMenu();
			$hamburger.removeClass('active');
		}
	});

            // Add touch support
	var touchStartX = 0;
	var touchEndX = 0;

	$mobileMenuContainer.on('touchstart', function(e) {
		touchStartX = e.originalEvent.touches[0].clientX;
	});

	$mobileMenuContainer.on('touchmove', function(e) {
		touchEndX = e.originalEvent.touches[0].clientX;
	});

	$mobileMenuContainer.on('touchend', function() {
		var swipeThreshold = 100;
		var swipeDistance = touchStartX - touchEndX;

		if (Math.abs(swipeDistance) > swipeThreshold) {
			if (swipeDistance > 0 && !isMegaMenuOpen) {
				openMegaMenu();
			} else if (swipeDistance < 0 && isMegaMenuOpen) {
				closeMegaMenu();
			}
		}
	});
            // Cache close button
	var $closeButton = $('.mont_header_mobile_close');

// Close button functionality
	$closeButton.on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		closeMobileMenu();
		$hamburger.removeClass('active');
	});

            // Handle orientation change
	$(window).on('orientationchange', function() {
		closeMobileMenu();
		$hamburger.removeClass('active');
	});
	lucide.createIcons();


});