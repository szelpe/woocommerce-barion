<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Barion extends WC_Payment_Gateway {

	public function __construct() {
		$this->id 					= 'barion';
		$this->method_title 		= 'Barion';
		$this->method_description	= 'Barion Payment Gateway';
		$this->has_fields 			= false;
		
		$this->init_form_fields();
		$this->init_settings();
        
		$this->barion_base_address = 'https://secure.barion.com';
		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];
        
		if ( $this->settings['test_mode'] == 'test' ) {
			$this->title .= ' [TEST MODE]';
			$this->description .= '<br/><br/><u>Test Mode is <strong>ACTIVE</strong>, use following Credit Card details:-</u><br/>'."\n"
								 .'Test Card Name: <strong><em>any name</em></strong><br/>'."\n"
								 .'Test Card Number: <strong>4908 3660 9990 0425</strong><br/>'."\n"
								 .'Test Card CVV: <strong>823</strong><br/>'."\n"
								 .'Test Card Expiry: <strong>Future date</strong>';	

            $this->barion_base_address = 'https://test.barion.com';                                     
		}

        $this->poskey = $this->settings['poskey'];
        $this->payee = $this->settings['payee'];
		$this->redirect_page = $this->settings['redirect_page'];
		
        $this->msg['message']	= '';
        $this->msg['class'] 	= '';
        
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_barion_ipn'));
		
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_barion', array(&$this, 'receipt_page'));	
	}
	
	function init_form_fields() {
		$this->form_fields = include('settings-barion.php');
	}
	
	public function admin_options() {
		echo '<h3>'.__('Barion', 'woocommerce').'</h3>';
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}
	
	function receipt_page($order) {
		echo '<p><strong>' . __('Thank you for your order.', 'woocommerce').'</strong></p>';
	}

    function process_payment($order_id) {
		global $woocommerce;
        $order = new WC_Order($order_id);
        
        require_once('class-wc-gateway-barion-request.php');
        
        $request = new WC_Gateway_Barion_Request($order);
        
        $request->prepare_payment();
        
        if(!$request->is_prepared) {
            return array(
                'result' => 'failure'
            );
        }
                
        return array(
			'result' => 'success', 
			'redirect' => $request->get_redirect_url();
		);
	}

	function barion_get_pages($title = false, $indent = true) {
		$wp_pages = get_pages('sort_column=menu_order');
		$page_list = array();
		if ($title) $page_list[] = $title;
		foreach ($wp_pages as $page) {
			$prefix = '';
			// show indented child pages?
			if ($indent) {
            	$has_parent = $page->post_parent;
            	while($has_parent) {
                	$prefix .=  ' - ';
                	$next_page = get_post($has_parent);
                	$has_parent = $next_page->post_parent;
            	}
        	}
            
        	$page_list[$page->ID] = $prefix . $page->post_title;
    	}
    	return $page_list;
	}
    
    function check_barion_ipn() {
        require_once('class-wc-gateway-barion-ipn-handler.php');
    }

} //END-class
