<?php
/*
Plugin Name: Barion Payment Gateway for WooCommerce
Plugin URI: http://github.com/szelpe/woocommerce-barion
Description: Adds the ability to WooCommerce to pay via Barion
Version: 3.6.1
Author: Aron Ocsvari <ugyfelszolgalat@bitron.hu>
Author URI: https://bitron.hu
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WC requires at least: 3.0.0
WC tested up to: 8.7.0

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

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init'], 10);
    }

    function init() {
		load_plugin_textdomain('pay-via-barion-for-woocommerce', false, plugin_basename(dirname(__FILE__)) . "/languages");
        if (!class_exists('WC_Payment_Gateway'))
            return;

        

        require_once 'includes/class-wc-gateway-barion-profile-monitor.php';

        $this->profile_monitor = new WC_Gateway_Barion_Profile_Monitor();


        require_once 'class-wc-gateway-barion.php';
        $this->wc_gateway_barion = new WC_Gateway_Barion($this->profile_monitor);

        require_once 'includes/class-wc-gateway-barion-pixel.php';
        $barion_pixel = new WC_Gateway_Barion_Pixel($this->wc_gateway_barion->get_barion_pixel_id());

        add_filter('woocommerce_payment_gateways', [$this, 'woocommerce_add_gateway_barion_gateway']);

        //Mark compatibility with checkout blocks
        add_action( 'before_woocommerce_init', function() {
            if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
            }
        } );

        //Load checkout block class
        add_action( 'woocommerce_blocks_loaded', function() {

            if( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
                return;
            }
        
            require_once 'includes/class-wc-gateway-barion-block-checkout.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new WC_Gateway_Barion_Blocks );
            } );
        
        } );
		//Adds notification to dashboard
        add_action('admin_notices', array($this, 'custom_admin_ad_notice'));
        add_action('wp_ajax_custom_admin_ad_dismiss', array($this, 'custom_admin_ad_dismiss'));
    }
    /**
    * Shows a notification about Full Barion pixel
    **/
    function custom_admin_ad_notice() {
        if (get_user_meta(get_current_user_id(), 'custom_admin_ad_dismissed', true)) {
            return; // Ha igen, nem jelenítjük meg az értesítést
        }

       ?>
    <div class="notice notice-info is-dismissible custom-admin-ad-notice">
        <p><?php _e('Reduce your Barion commission by using Full Pixel. Click here for more information: <a target ="_blank" href ="https://bitron.hu/barion-pixel-for-woocommerce">Full Barion Pixel for WooCommerce</a>', 'pay-via-barion-for-woocommerce'); ?></p>
    </div>

    <script>
        jQuery(document).on('click', '.custom-admin-ad-notice .notice-dismiss', function() {
					            // AJAX kérés az értesítés elrejtéséhez
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
        $methods[] = $this->wc_gateway_barion;
        return $methods;
    }
}
