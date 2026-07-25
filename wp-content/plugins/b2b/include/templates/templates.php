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
			$('.tab-pane-monte-b2b').removeClass('active show').hide();
			$('.tab-pane-monte-b2b').first().addClass('active show').show();

			$('.nav-link-monte-b2b').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $btn = $(this);
				var target = $btn.attr('data-bs-target') || $btn.data('bs-target');
				if (!target) return;

				$('.nav-link-monte-b2b').removeClass('active').attr('aria-selected', 'false');
				$('.mont-cat-tabs__item, #b2bmenu .category-item').removeClass('is-active active-li');
				$btn.addClass('active').attr('aria-selected', 'true');
				$btn.closest('li').addClass('is-active active-li');

				$('.tab-pane-monte-b2b').removeClass('active show').hide();
				$(target).addClass('active show').show();
			});
		});
	</script>
</div>
