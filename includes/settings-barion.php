<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings for Barion Gateway.
 */
return array(
    'enabled' => array(
        'title'           => __('Enable/Disable', 'woocommerce'),
        'type'            => 'checkbox',
        'label'           => __('Enable Barion', 'pay-via-barion-for-woocommerce'),
        'default'         => 'no'
    ),
    'title' => array(
        'title'           => __('Title', 'woocommerce'),
        'type'            => 'text',
        'default'         => __('Barion', 'pay-via-barion-for-woocommerce'),
        'description'     => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'desc_tip'        => true
    ),
    'description' => array(
        'title'           => __('Description', 'woocommerce'),
        'type'            => 'textarea',
        'default'         => __('Pay via Barion; you can pay with your credit card if you don\'t have a Barion account.', 'pay-via-barion-for-woocommerce'),
        'description'     => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        'desc_tip'        => true
    ),
    'poskey' => array(
        'title'           => __('Secret key (POSKey)', 'pay-via-barion-for-woocommerce'),
        'type'            => 'text',
        'description'     => __('The secret key of the online store registered in Barion (called POSKey)', 'pay-via-barion-for-woocommerce'),
        'desc_tip'        => true
    ),
    'payee' => array(
        'title'           => __('Barion Email', 'pay-via-barion-for-woocommerce'),
        'type'            => 'text',
        'description'     => __('Your Barion email address', 'pay-via-barion-for-woocommerce'),
        'desc_tip'        => true,
        'default'         => get_option('admin_email'),
        'placeholder'     => 'you@youremail.com'
    ),
    'order_status' => array(
        'title'           => __('Order status after payment', 'pay-via-barion-for-woocommerce'),
        'type'            => 'select',
        'options'         => array(
                                'automatic' => __('Automatic (recommended)', 'pay-via-barion-for-woocommerce'),
                                'completed' => _x('Completed', 'Order status', 'woocommerce'),
                                'processing' => _x('Processing', 'Order status', 'woocommerce'),
                                'pending' => _x('Pending payment', 'Order status', 'woocommerce'),
                             ),
        'default'         => 'automatic',
        'description'     => __('Choose the status of the order after successful Barion payment.', 'pay-via-barion-for-woocommerce'),
        'desc_tip'        => true,
        'class'           => 'wc-enhanced-select'
    ),
    'environment' => array(
        'title'           => __('Barion Environment', 'pay-via-barion-for-woocommerce'),
        'type'            => 'select',
        'options'         => array('test' => 'Test (https://test.barion.com/)', 'live' => 'Live'),
        'default'         => 'live',
        'description'     => sprintf(__('You can select the Test environment to test payments. You\'ll need to create a shop on the <a href="%s" target="_blank">Barion test site</a>.', 'pay-via-barion-for-woocommerce'), 'https://test.barion.com/'),
        'desc_tip'        => false,
        'class'           => 'wc-enhanced-select'
    )
);
