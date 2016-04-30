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
        $paymentRequest->Locale = UILocale::EN;
        $paymentRequest->OrderNumber = $order->get_order_number();
        $paymentRequest->ShippingAddress = "12345 NJ, Example ave. 6.";
        $paymentRequest->RedirectUrl = $this->gateway->get_return_url($order);
        $paymentRequest->CallbackUrl = WC()->api_request_url('WC_Gateway_Barion');
        $paymentRequest->AddTransaction($transaction);
        
        $this->payment = $this->barion_client->PreparePayment($paymentRequest);
        
        if($this->payment->RequestSuccessful) {
            $this->is_prepared = true;
        }
    }
    
    protected function prepare_items($order, $transaction) {
		$calculated_total = 0;
        
		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
            $itemModel = new ItemModel();
            $itemModel->Name = $item['name'];
            $itemModel->Description = $itemModel->Name;
            $itemModel->Unit = 'piece';
        
			if ( 'fee' === $item['type'] ) {
				$item_line_total  = $this->number_format( $item['line_total'], $order );
                
                $itemModel->Quantity = 1;
                $itemModel->UnitPrice = $item_line_total;
                $itemModel->ItemTotal = $item_line_total;
                $itemModel->SKU = '';
                
				$calculated_total += $item_line_total;
			} else {
				$product          = $order->get_product_from_item( $item );
				$item_line_total  = $this->number_format( $order->get_item_subtotal( $item, false ), $order );
                
                $itemModel->Quantity = $item['qty'];
                $itemModel->UnitPrice = $item_line_total;
                $itemModel->ItemTotal = $item_line_total * $item['qty'];
                $itemModel->SKU = $product->get_sku();
                
				$calculated_total += $item_line_total * $item['qty'];
			}
            
            $transaction->AddItem($itemModel);
		}
        
		// Check for mismatched totals.
		if ( $this->number_format( $calculated_total + $order->get_total_tax() + $this->round( $order->get_total_shipping(), $order ) - $this->round( $order->get_total_discount(), $order ), $order ) != $this->number_format( $order->get_total(), $order ) ) {
			return false;
		}
		return true;
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
