<script type="text/javascript">

    jQuery(document).ready(function() {

		let $fs_costs_calculation = jQuery('.fs-costs-calculation-enabled-adv');

		function fs_costs_calculation_change() {
			jQuery('.fs-method-rules').closest('tr').toggle($fs_costs_calculation.is(':checked'));
		}

		$fs_costs_calculation.change(function () {
			fs_costs_calculation_change()
		});

		fs_costs_calculation_change();

	} );

</script>

