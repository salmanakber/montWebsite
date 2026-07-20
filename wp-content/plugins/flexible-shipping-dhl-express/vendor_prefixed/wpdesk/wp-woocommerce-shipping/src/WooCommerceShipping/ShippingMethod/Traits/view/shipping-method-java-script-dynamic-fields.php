<script type="text/javascript">
	jQuery(document).ready(function () {
		function display_dynamic_descriptions(element) {
			const $element = jQuery(element);
			const descriptions = $element.data('descriptions');
			if (!descriptions) {
				return;
			}
			let description = '';
			if (descriptions[$element.val()]) {
				description = descriptions[$element.val()];
			}
			$element.parent().find('p.description').html(description);
		}

		const $dynamic_description = jQuery('.oct-dynamic-description');
		$dynamic_description.on('change', function () {
			display_dynamic_descriptions(this);
		});

		$dynamic_description.each(function () {
			display_dynamic_descriptions(this);
		});
	});
</script>

