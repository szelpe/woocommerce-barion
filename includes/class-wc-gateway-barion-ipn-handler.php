<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Barion_IPN_Handler {
	 /**
     * @var BarionClient
     */
    private $barion_client;
    /**
     * @var WCGateway
     */
    private $gateway;
       public function __construct($barion_client, $gateway) {
        $this->barion_client = $barion_client;
        $this->gateway = $gateway;
        add_action('woocommerce_api_wc_gateway_barion', array($this, 'check_barion_ipn'));
    }

    public function check_barion_ipn() {
        if(empty($_GET) || empty($_GET['paymentId']))
            exit;


        $payment_details = $this->barion_client->GetPaymentState($_GET['paymentId']);

        if(!empty($payment_details->Errors)) {
            WC_Gateway_Barion::log('GetPaymentState returned errors. Payment details: ' . json_encode($payment_details));

            exit;
        }
$args = array(
    'numberposts' => 1,
    'meta_key'    => '_order_number',
    'meta_value'  => $payment_details->PaymentRequestId,
    'post_type'   => 'shop_order',
    'post_status' => 'any'
);

$orders = get_posts($args);

if ($orders) {
        $order_id = $orders[0]->ID;

    // A WC_Order objektum létrehozása a rendelés azonosító alapján
    $order = wc_get_order($order_id);
    
} else {
   $order = new WC_Order($payment_details->PaymentRequestId);
}

        
        if(empty($order)) {
            WC_Gateway_Barion::log('Invalid PaymentRequestId: ' . $_GET['paymentId'] . '. Payment details: ' . json_encode($payment_details));

            exit;
        }

        $order->add_order_note(__('Barion callback received.', 'pay-via-barion-for-woocommerce') . ' paymentId: "' . $_GET['paymentId'] . '"');

        if(apply_filters('woocommerce_barion_custom_callback_handler', false, $order, $payment_details)) {
            $order->add_order_note(__('Barion callback was handled by a custom handler.', 'pay-via-barion-for-woocommerce'));
            exit;
        }

        if($order->has_status(array('processing', 'completed'))) {
            $order->add_order_note(__('Barion callback ignored as the payment was already completed.', 'pay-via-barion-for-woocommerce'));
            exit;
        }

        if($order->has_status(array('on-hold'))) {
            $order->add_order_note(__('Barion callback ignored as the user has chosen another payment method.', 'pay-via-barion-for-woocommerce'));
            exit;
        }

        if($payment_details->Status == PaymentStatus::Succeeded) {
            if($order->has_status('completed')) {
                exit;
            }

            $order->add_order_note(__('Payment succeeded via Barion.', 'pay-via-barion-for-woocommerce'));
            $this->gateway->payment_complete($order, $this->find_transaction_id($payment_details, $order));

            exit;
        }

        if($payment_details->Status == PaymentStatus::Canceled) {
						if (empty($this->gateway->get_rejected_status()) ||$this->gateway->get_rejected_status() =='no') {
            $order->update_status('cancelled', __('Payment canceled via Barion.', 'pay-via-barion-for-woocommerce'));
			} else {
				$order->update_status($this->gateway->get_rejected_status(), __('Payment changed via Barion.', 'pay-via-barion-for-woocommerce'));
			}

            exit;
        }

        if($payment_details->Status == PaymentStatus::Expired) {
			if (empty($this->gateway->get_expired_status()) ||$this->gateway->get_expired_status() =='no') {
            $order->update_status('cancelled', __('Payment is expired (customer progressed to Barion, but then left the page without paying).', 'pay-via-barion-for-woocommerce'));
			} else {
				$order->update_status($this->gateway->get_expired_status(), __('Payment is expired (customer progressed to Barion, but then left the page without paying).', 'pay-via-barion-for-woocommerce'));
			}
            

            exit;
        }

        $order->update_status('failed', __('Payment failed via Barion.', 'pay-via-barion-for-woocommerce'));
        WC_Gateway_Barion::log('Payment failed. Payment details: ' . json_encode($payment_details));
		exit;
    }

    function find_transaction_id($payment_details, $order) {
        foreach($payment_details->Transactions as $transaction) {
            if($transaction->POSTransactionId == $order->get_id()) {
                return $transaction->TransactionId;
            }
        }
    }
}
