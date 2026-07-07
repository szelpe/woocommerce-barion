<?php
/*
Plugin Name: Barion Payment Gateway for WooCommerce
Plugin URI: http://github.com/szelpe/woocommerce-barion
Description: Adds the ability to WooCommerce to pay via Barion
Version: 3.10.1
Author: Aron Ocsvari <ugyfelszolgalat@bitron.hu>
Author URI: https://bitron.hu
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires PHP: 8.2

WC requires at least: 3.0.0
WC tested up to: 10.0.4

Text Domain: pay-via-barion-for-woocommerce
Domain Path: /languages

*/

$plugin = new WooCommerce_Barion_Plugin();

class WooCommerce_Barion_Plugin {
    /**
     * @var WC_Gateway_Barion_Profile_Monitor
     */
    private $profile_monitor;
    private $wc_gateway_barion;
	private $barion_pixel;

    public function __construct() {
		add_action('plugins_loaded', [$this, 'init'], 0);
		add_action('plugins_loaded', [$this, 'plugin_loaded']);
        add_action('before_woocommerce_init', [$this, 'declare_woocommerce_compatibility']);
add_action('woocommerce_blocks_loaded', [$this, 'register_checkout_blocks']);

	}
	
   public function init() {
	load_plugin_textdomain('pay-via-barion-for-woocommerce', false, plugin_basename(dirname(__FILE__)) . "/languages");	
   }
   public function plugin_loaded () {
        if (!class_exists('WC_Payment_Gateway'))
            return;

        

        require_once 'includes/class-wc-gateway-barion-profile-monitor.php';

        


        require_once 'class-wc-gateway-barion.php';
        

        require_once 'includes/class-wc-gateway-barion-pixel.php';
        
$this->barion_pixel = new WC_Gateway_Barion_Pixel();		

   add_filter('woocommerce_payment_gateways', [$this, 'woocommerce_add_gateway_barion_gateway']);
               //Adds notification to dashboard
        add_action('admin_notices', array($this, 'custom_admin_ad_notice'));
        add_action('wp_ajax_custom_admin_ad_dismiss', array($this, 'custom_admin_ad_dismiss'));
    }
	public function declare_woocommerce_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
	public function register_checkout_blocks() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // 🚀 **Először betöltjük a szükséges osztályokat** 🚀
    require_once 'includes/class-wc-gateway-barion-profile-monitor.php';
    require_once 'class-wc-gateway-barion.php';

    require_once 'includes/class-wc-gateway-barion-block-checkout.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Gateway_Barion_Blocks);
        }
    );
}
    /**
    * Detects whether the standalone "Full Barion Pixel for WooCommerce" plugin
    * is already installed & in use, so we don't advertise it to those users.
    *
    * @return bool
    **/
    private function is_full_barion_pixel_in_use() {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active('barion-pixel-for-woocommerce/barion-pixel-for-woocommerce.php');
    }

    /**
    * Shows a notification about Full Barion pixel
    **/
    function custom_admin_ad_notice() {
        // Don't advertise the Full Pixel to those who already use it.
        if ($this->is_full_barion_pixel_in_use()) {
            return;
        }

        if (get_user_meta(get_current_user_id(), 'custom_admin_ad_dismissed', true)) {
            return; // Ha igen, nem jelenítjük meg az értesítést
        }

       ?>
    <div class="notice notice-info is-dismissible custom-admin-ad-notice">
        <p><?php _e('Reduce your Barion commission by using Full Pixel. Click here for more information: <a target ="_blank" href ="https://bitron.hu/barion-pixel-for-woocommerce">Full Barion Pixel for WooCommerce</a>', 'pay-via-barion-for-woocommerce'); ?></p>
    </div>

    <script>
        jQuery(document).on('click', '.custom-admin-ad-notice .notice-dismiss', function() {
					                        jQuery.post(ajaxurl, {
                action: 'custom_admin_ad_dismiss'
            });
			
        });
    </script>
    <?php
}

/**
* Saves the dismiss option
**/
function custom_admin_ad_dismiss() {
        update_user_meta(get_current_user_id(), 'custom_admin_ad_dismissed', true);
    wp_die();
}
    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_gateway_barion_gateway($methods) {
		$this->profile_monitor = new WC_Gateway_Barion_Profile_Monitor();
		$this->wc_gateway_barion = new WC_Gateway_Barion($this->profile_monitor);
        $methods[] = $this->wc_gateway_barion;
        return $methods;
    }
}
