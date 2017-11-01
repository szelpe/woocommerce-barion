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
        $transaction->POSTransactionId = $order->get_id();
        $transaction->Payee = $this->gateway->payee;
        $transaction->Total = $this->round($order->get_total(), $order->get_currency());
        $transaction->Comment = "";
        
        $this->prepare_items($order, $transaction);
        
        $paymentRequest = new PreparePaymentRequestModel();
        $paymentRequest->GuestCheckout = true;
        $paymentRequest->PaymentType = PaymentType::Immediate;
        $paymentRequest->FundingSources = array(FundingSourceType::All);
        $paymentRequest->PaymentRequestId = $order->get_id();
        $paymentRequest->PayerHint = $order->get_billing_email();
        $paymentRequest->Locale = $this->get_barion_locale();
        $paymentRequest->OrderNumber = $order->get_order_number();
        $paymentRequest->ShippingAddress = $order->get_formatted_shipping_address();
        $paymentRequest->RedirectUrl = $this->gateway->get_return_url($order);
        $paymentRequest->CallbackUrl = WC()->api_request_url('WC_Gateway_Barion');
        $paymentRequest->Currency = $order->get_currency();
        $paymentRequest->AddTransaction($transaction);
        
        $this->payment = $this->barion_client->PreparePayment($paymentRequest);
        
        if($this->payment->RequestSuccessful) {
            $this->gateway->set_barion_payment_id($order, $this->payment->PaymentId);
            $this->is_prepared = true;
        }
        else {
            WC_Gateway_Barion::log('PreparePayment failed. Errors array: ' . json_encode($this->payment->Errors));
        }
    }
    
    protected function prepare_items($order, $transaction) {
        $calculated_total = 0;
        
        foreach ( $order->get_items( array( 'line_item', 'fee', 'shipping', 'coupon' ) ) as $item_id => $item ) {
            $itemModel = new ItemModel();
            $itemModel->Name = $item['name'];
            $itemModel->Description = $itemModel->Name;
            $itemModel->Unit = __('piece', 'woocommerce-barion');
            $itemModel->Quantity = empty($item['qty']) ? 1 : $item['qty'];
        
            $itemModel->UnitPrice = $order->get_item_subtotal($item, true);
            $itemModel->ItemTotal = $order->get_line_subtotal($item, true);

            if('coupon' === $item['type']) {
                $itemModel->Name = __('Coupon', 'woocommerce') . ' (' . $item['name'] . ')';
                
                $discount_amount = wc_get_order_item_meta($item_id, 'discount_amount');
                $discount_amount_tax = wc_get_order_item_meta($item_id, 'discount_amount_tax');
                
                if(!empty($discount_amount_tax)) {
                    $discount_amount += $discount_amount_tax;
                }
                
                $itemModel->UnitPrice = -1 * $discount_amount;
                $itemModel->ItemTotal = -1 * $discount_amount;
                $itemModel->SKU = '';
            }
            elseif('shipping' === $item['type']) {
                $shipping_cost = wc_get_order_item_meta($item_id, 'cost');
                $shipping_taxes = wc_get_order_item_meta($item_id, 'taxes');
                if(!empty($shipping_taxes)) {
                    $shipping_cost += array_sum($shipping_taxes);
                }
                $itemModel->UnitPrice = $shipping_cost;
                $itemModel->ItemTotal = $shipping_cost;
                $itemModel->SKU = '';
            }
            elseif ('fee' === $item['type']) {
                $itemModel->SKU = '';
            }
            else {
                $product = $order->get_product_from_item($item);
                
                if($product->is_type('variable')) {
                    $itemModel->Name .= ' (' . $product->get_formatted_variation_attributes(true) . ')';
                }
                
                $itemModel->SKU = $product->get_sku();
            }
            
            $transaction->AddItem($itemModel);
        }
    }
    
    function get_barion_locale() {
        switch(get_locale()) {
            case "hu_HU":
                return UILocale::HU;
            case "de_DE":
                return UILocale::DE;
            case "sl_SI":
                // This doesn't work due to a bug in the Barion library
                //return UILocale::SL;
                return "sl-SI";
            case "sk_SK":
                return UILocale::SK;
            case "fr_FR":
                return UILocale::FR;

            default:
                return UILocale::EN;
        }
    }
    
    public function get_redirect_url() {
        if(!$this->is_prepared)
            throw new Exception('`prepare_payment` should have been called before `get_redirect_url`.');
        
        return $this->payment->PaymentRedirectUrl;
    }
    
    /**
     * Round prices.
     * @param  double $price
     * @param  WC_Order $order
     * @return double
     */
    protected function round($price, $currency) {
        $precision = 2;
        if (!$this->currency_has_decimals($currency)) {
            $precision = 0;
        }
        
        return round($price, $precision);
    }
    
    /**
     * Check if currency has decimals.
     * @param  string $currency
     * @return bool
     */
    protected function currency_has_decimals($currency) {
        if(in_array($currency, array('HUF'))) {
            return false;
        }
        
        return true;
    }
}
