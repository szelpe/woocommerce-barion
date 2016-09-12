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
        'label'           => __('Enable Barion', 'woocommerce-barion'),
        'default'         => 'no'
    ),
    'title' => array(
        'title'           => __('Title', 'woocommerce'),
        'type'            => 'text',
        'default'         => __('Barion', 'woocommerce-barion'),
        'description'     => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'desc_tip'        => true
    ),
    'description' => array(
        'title'           => __('Description', 'woocommerce'),
        'type'            => 'textarea',
        'default'         => __('Pay via Barion; you can pay with your credit card if you don\'t have a Barion account.', 'woocommerce-barion'),
        'description'     => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        'desc_tip'        => true
    ),
    'poskey' => array(
        'title'           => __('Secret key (POSKey)', 'woocommerce-barion'),
        'type'            => 'text',
        'description'     => __('The secret key of the online store registered in Barion (called POSKey)', 'woocommerce-barion'),
        'desc_tip'        => true
    ),
    'payee' => array(
        'title'           => __('Barion Email', 'woocommerce-barion'),
        'type'            => 'text',
        'description'     => __('Your Barion email address', 'woocommerce-barion'),
        'desc_tip'        => true,
        'default'         => get_option('admin_email'),
        'placeholder'     => 'you@youremail.com'
    ),
    'environment' => array(
        'title'           => __('Barion Environment', 'woocommerce-barion'),
        'type'            => 'select',
        'options'         => array('test' => 'Test (https://test.barion.com/)', 'live' => 'Live'),
        'default'         => 'live',
        'description'     => sprintf(__('You can select the Test environment to test payments. You\'ll need to create a shop on the <a href="%s" target="_blank">Barion test site</a>.', 'woocommerce-barion'), 'https://test.barion.com/'),
        'desc_tip'        => false
    )
);
