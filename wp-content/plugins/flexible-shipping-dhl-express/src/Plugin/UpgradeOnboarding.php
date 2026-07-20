<?php
/**
 * Upgrade onboarding.
 *
 * @package WPDesk\FlexibleShippingDhl
 */

namespace WPDesk\FlexibleShippingDhl;

use DhlVendor\Octolize\Onboarding\PluginUpgrade\MessageFactory\LiveRatesFsRulesTable;
use DhlVendor\Octolize\Onboarding\PluginUpgrade\PluginUpgradeOnboardingFactory;
use DhlVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use DhlVendor\WPDesk_Plugin_Info;

/**
 * Upgrade onboarding.
 */
class UpgradeOnboarding implements Hookable {

	/**
	 * Plugin info.
	 *
	 * @var WPDesk_Plugin_Info
	 */
	private WPDesk_Plugin_Info $plugin_info;

	/**
	 * UpgradeOnboarding constructor.
	 *
	 * @param WPDesk_Plugin_Info $plugin_info Plugin info.
	 */
	public function __construct( WPDesk_Plugin_Info $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'init', [ $this, 'init_upgrade_onboarding' ] );
	}

	/**
	 * Init upgrade onboarding.
	 */
	public function init_upgrade_onboarding(): void {
		$upgrade_onboarding = new PluginUpgradeOnboardingFactory(
			$this->plugin_info->get_plugin_name(),
			$this->plugin_info->get_version(),
			$this->plugin_info->get_plugin_file_name()
		);
		$upgrade_onboarding->add_upgrade_message(
			( new LiveRatesFsRulesTable() )->create_message( '4.0.0', $this->plugin_info->get_plugin_url() )
		);
		$upgrade_onboarding->create_onboarding();
	}
}
