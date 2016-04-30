<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Barion_Request {
    public function prepare_payment() {
        $this->is_prepared = true;
    }
    
    public function get_redirect_url() {
        if(!$this->is_prepared)
            throw new Exception('`prepare_payment` should have been called before `get_redirect_url`.');
        
        return 'https://example.com';
    }
}
