<?php
ob_start();
/*
Template Name: Monte Connected B2B
*/
?>
<div id="primary" class="content-area b2b-listing-page">
	<main id="main" class="site-main" role="main">
		{{data}}
	</main>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$('.tab-pane-monte-b2b').not(':first').hide();

			$('.nav-link-monte-b2b').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this);
				$('.nav-link-monte-b2b').removeClass('active');
				$('.mont-cat-tabs__item, .category-item').removeClass('is-active active-li');
				$btn.addClass('active');
				$btn.closest('li').addClass('is-active active-li');
				$('.tab-pane-monte-b2b').hide();
				var target = $btn.data('bs-target');
				$(target).show().css('display', 'block');
			});
		});
	</script>
</div>
