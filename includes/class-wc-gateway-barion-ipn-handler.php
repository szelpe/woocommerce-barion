<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Barion_IPN_Handler {
    public function __construct($barion_client) {
        $this->barion_client = $barion_client;
        add_action('woocommerce_api_wc_gateway_barion', array($this, 'check_barion_ipn'));
    }
    
    public function check_barion_ipn() {
        if(empty($_GET) || empty($_GET['paymentId']))
            return;
        
        
        $payment_details = $this->barion_client->GetPaymentState($_GET['paymentId']);
        
        if(!empty($payment_details->Errors)) {
            WC_Gateway_Barion::log('GetPaymentState returned errors. Payment details: ' . json_encode($payment_details));
            
            return;
        }
        
        $order = new WC_Order($payment_details->PaymentRequestId);
        
        if(empty($order)) {
            WC_Gateway_Barion::log('Invalid PaymentRequestId: ' . $_GET['paymentId'] . '. Payment details: ' . json_encode($payment_details));
            
            return;
        }
        
        $order->add_order_note('Barion callback received with paymentId: "' . $_GET['paymentId'] . '"');
        
        if($payment_details->Status == PaymentStatus::Succeeded) {
            if($order->has_status('completed')) {
                return;
            }
            
            $order->add_order_note('Barion payment succeeded');
            $order->payment_complete();
            
            return;
        }
        
        if($payment_details->Status == PaymentStatus::Canceled) {
            $order->update_status('failed', __('Payment canceled via Barion IPN.', 'woocommerce'));
            
            return;
        }
        
        $order->update_status('failed', __('Payment failed via Barion IPN.', 'woocommerce'));
        WC_Gateway_Barion::log('Payment failed. Payment details: ' . json_encode($payment_details));
    }
}
