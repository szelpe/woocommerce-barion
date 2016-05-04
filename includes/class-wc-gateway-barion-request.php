<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Barion_Request {
    public function __construct($barion_client, $gateway) {
        $this->barion_client = $barion_client;
        $this->gateway = $gateway;
    }
    
    public function prepare_payment($order) {
        $transaction = new PaymentTransactionModel();
        $transaction->POSTransactionId = $order->id;
        $transaction->Payee = $this->gateway->payee;
        $transaction->Total = $order->get_total();
        $transaction->Comment = "";
        
        $this->prepare_items($order, $transaction);
        
        $paymentRequest = new PreparePaymentRequestModel();
        $paymentRequest->GuestCheckout = true;
        $paymentRequest->PaymentType = PaymentType::Immediate;
        $paymentRequest->FundingSources = array(FundingSourceType::All);
        $paymentRequest->PaymentRequestId = $order->id;
        $paymentRequest->PayerHint = $order->billing_email;
        $paymentRequest->Locale = $this->get_barion_locale();
        $paymentRequest->OrderNumber = $order->get_order_number();
        $paymentRequest->ShippingAddress = $order->get_formatted_shipping_address();
        $paymentRequest->RedirectUrl = $this->gateway->get_return_url($order);
        $paymentRequest->CallbackUrl = WC()->api_request_url('WC_Gateway_Barion');
        $paymentRequest->AddTransaction($transaction);
        
        $this->payment = $this->barion_client->PreparePayment($paymentRequest);
        
        if($this->payment->RequestSuccessful) {
            update_post_meta($order->id, 'paymentId', $this->payment->PaymentId);
            $this->is_prepared = true;
        }
        else {
            WC_Gateway_Barion::log('PreparePayment failed. Errors array: ' . json_encode($this->payment->Errors));
        }
    }
    
    protected function prepare_items($order, $transaction) {
        $calculated_total = 0;
        
        foreach ( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $item ) {
            $itemModel = new ItemModel();
            $itemModel->Name = $item['name'];
            $itemModel->Description = $itemModel->Name;
            $itemModel->Unit = __('piece');
            $itemModel->Quantity = empty($item['qty']) ? 1 : $item['qty'];
        
            $itemModel->UnitPrice = $order->get_item_total($item, true);
            $itemModel->ItemTotal = $order->get_line_total($item, true);

            if('shipping' === $item['type']) {
                $itemModel->UnitPrice = $order->get_total_shipping() + $order->get_shipping_tax();
                $itemModel->ItemTotal = $itemModel->UnitPrice;
                $itemModel->SKU = '';
            }
            else if ('fee' === $item['type']) {
                $itemModel->SKU = '';
            } 
            else {
                $product          = $order->get_product_from_item($item);
                $itemModel->SKU = $product->get_sku();
            }
            
            $transaction->AddItem($itemModel);
        }
    }
    
    function get_barion_locale() {
        if(get_locale() == "hu_HU") {
            return UILocale::HU;
        }
        
        return UILocale::EN;
    }
    
    public function get_redirect_url() {
        if(!$this->is_prepared)
            throw new Exception('`prepare_payment` should have been called before `get_redirect_url`.');
        
        return $this->payment->PaymentRedirectUrl;
    }
    
    /**
     * Check if currency has decimals.
     * @param  string $currency
     * @return bool
     */
    protected function currency_has_decimals( $currency ) {
        if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD' ) ) ) {
            return false;
        }
        return true;
    }
    
    /**
     * Round prices.
     * @param  double $price
     * @param  WC_Order $order
     * @return double
     */
    protected function round( $price, $order ) {
        $precision = 2;
        if ( ! $this->currency_has_decimals( $order->get_order_currency() ) ) {
            $precision = 0;
        }
        return round( $price, $precision );
    }
    
    /**
     * Format prices.
     * @param  float|int $price
     * @param  WC_Order $order
     * @return string
     */
    protected function number_format( $price, $order ) {
        $decimals = 2;
        if ( ! $this->currency_has_decimals( $order->get_order_currency() ) ) {
            $decimals = 0;
        }
        return number_format( $price, $decimals, '.', '' );
    }
}
