<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Gateway_Barion_Blocks extends AbstractPaymentMethodType {

	private $gateway;
	protected $name = 'barion';
    private $profile_monitor;

	public function initialize() {
        $this->profile_monitor = new WC_Gateway_Barion_Profile_Monitor();
		$this->settings = get_option( 'woocommerce_barion_settings', [] );
		$this->gateway = new WC_Gateway_Barion($this->profile_monitor);
	}

	public function is_active() {
		return $this->get_setting( 'enabled' ) === 'yes';
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-barion-blocks-integration',
			$this->gateway->plugin_url(). '/assets/checkout.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			null,
			true
		);
		if( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-barion-blocks-integration');
		}

		return [ 'wc-barion-blocks-integration' ];
	}

	public function get_payment_method_data() {
		return [
			'title' => $this->gateway->title,
			'description' => $this->gateway->description,
            'logo' => $this->gateway->plugin_url().'/assets/barion-strip.svg',
			'order_button_label' =>$this->gateway->get_order_button_text()
		];
	}

}
