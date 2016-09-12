<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'barion-library/library/BarionClient.php';
require_once 'includes/class-wc-gateway-barion-ipn-handler.php';

class WC_Gateway_Barion extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'barion';
        $this->method_title       = __('Barion', 'woocommerce-barion');
        $this->method_description = sprintf( __( 'Barion payment gateway sends customers to Barion to enter their payment information. Barion callback requires cURL support to update order statuses after payment. Check the %ssystem status%s page for more details.', 'woocommerce-barion' ), '<a href="' . admin_url( 'admin.php?page=wc-status' ) . '">', '</a>' );
        $this->has_fields         = false;
        $this->order_button_text  = __( 'Proceed to Barion', 'woocommerce-barion' );
        $this->icon               = $this->plugin_url() . '/assets/barion-card-payment-banner-2016-300x35px.png';
        $this->supports           = array(
            'products',
            'refunds'
        );
        $this->supported_currencies = array('USD', 'EUR', 'HUF');
        
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->barion_environment = BarionEnvironment::Prod;
        
        if ( $this->settings['environment'] == 'test' ) {
            $this->title .= ' [TEST MODE]';
            $this->description .= '<br/><br/><u>Test Mode is <strong>ACTIVE</strong>, use following Credit Card details:-</u><br/>'."\n"
                                 .'Test Card Name: <strong><em>any name</em></strong><br/>'."\n"
                                 .'Test Card Number: <strong>4908 3660 9990 0425</strong><br/>'."\n"
                                 .'Test Card CVV: <strong>823</strong><br/>'."\n"
                                 .'Test Card Expiry: <strong>Future date</strong>';    

            $this->barion_environment = BarionEnvironment::Test;                                   
        }

        $this->poskey = $this->settings['poskey'];
        $this->payee = $this->settings['payee'];
        $this->redirect_page = $this->settings['redirect_page'];
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        
        if (!$this->is_selected_currency_supported()) {
            $this->enabled = 'no';
        } else {
            $this->barion_client = new BarionClient($this->poskey, 2, $this->barion_environment, true);
            $callback_handler = new WC_Gateway_Barion_IPN_Handler($this->barion_client);
        }
    }
    
    public function plugin_url() {
        return untrailingslashit(plugins_url('/', __FILE__));
    }
    
    /** @var boolean */
    static $debug_mode = false;
    
    /** @var WC_Logger Logger instance */
    static $log = null;
    
    public static function log($message, $level = 'error') {
        if ($level != 'error' && !self::$debug_mode) {
            return;
        }
        
        if (empty(self::$log)) {
            self::$log = new WC_Logger();
        }
        
        self::$log->add('barion', $message);
    }
    
    function init_form_fields() {
        $this->form_fields = include('includes/settings-barion.php');
    }
    
    public function admin_options() {
        if ($this->is_selected_currency_supported()) {
            parent::admin_options();
        } else {
            ?>
            <div class="inline error">
                <p>
                    <strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: 
                    <?php echo sprintf(__('Barion does not support your store currency. Supported currencies: %s', 'woocommerce-barion'), implode(', ', $this->supported_currencies)); ?>
                </p>
            </div>
            <?php
        }
    }
    
    public function is_selected_currency_supported() {
        return in_array(get_woocommerce_currency(), apply_filters('woocommerce_barion_supported_currencies', $this->supported_currencies));
    }

    function process_payment($order_id) {
        $order = new WC_Order($order_id);
        
        require_once('includes/class-wc-gateway-barion-request.php');
        
        $request = new WC_Gateway_Barion_Request($this->barion_client, $this);
        
        $request->prepare_payment($order);
        
        if(!$request->is_prepared) {
            return array(
                'result' => 'failure'
            );
        }
        
        $redirectUrl = $request->get_redirect_url();
        
        $order->add_order_note(__('User redirected to the Barion payment page.', 'woocommerce-barion') . ' redirectUrl: "' . $redirectUrl . '"');
        
        return array(
            'result' => 'success', 
            'redirect' => $redirectUrl
        );
    }
    
    /**
     * Process a refund if supported.
     * @param  int    $order_id
     * @param  float  $amount
     * @param  string $reason
     * @return bool True or false based on success, or a WP_Error object
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = new WC_Order( $order_id );
        
        if(!$this->can_refund_order($order)) {
            $this->log('Refund Failed: No transaction ID');
            return new WP_Error('error', __('Refund Failed: No transaction ID', 'woocommerce-barion'));
        }
        
        include_once('includes/class-wc-gateway-barion-refund.php');
        $barionRefund = new WC_Gateway_Barion_Refund($this->barion_client, $this);
        $result = $barionRefund->refund_order($order, $amount, $reason);
        
        if($barionRefund->refund_succeeded) {
            $order->add_order_note(sprintf(__('Refunded %s - Refund ID: %s', 'woocommerce-barion' ), wc_price($barionRefund->refund_amount), $barionRefund->refund_transaction_id));
            
            return true;
        }
        
        $wp_error = new WP_Error('barion_refund', __('Barion refund failed.', 'woocommerce-barion'));
        
        if(!empty($result->Errors)) {
            foreach($result->Errors as $error) {
                $wp_error->add($error->ErrorCode, $error->Title . ' ' . $error->Description);
            }
        }
        
        return $wp_error;
    }
    
    public function can_refund_order($order) {
        return $order && $order->get_transaction_id() && $this->get_barion_payment_id($order);
    }
    
    const BARION_PAYMENT_ID_META_KEY = 'Barion paymentId';
    public function get_barion_payment_id($order) {
        $paymentMeta = get_post_meta($order->id, self::BARION_PAYMENT_ID_META_KEY);
        
        if(empty($paymentMeta)) {
            return null;
        }
        
        return $paymentMeta[0];
    }
    
    public function set_barion_payment_id($order, $paymentId) {
        update_post_meta($order->id, self::BARION_PAYMENT_ID_META_KEY, $paymentId);
    }
}
