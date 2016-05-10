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
        'label'           => __('Enable Barion', 'woocommerce'),
        'default'         => 'no'
    ),
    'title' => array(
        'title'           => __('Title', 'woocommerce'),
        'type'            => 'text',
        'default'         => __('Barion', 'woocommerce'),
        'description'     => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'desc_tip'        => true
    ),
    'description' => array(
        'title'           => __('Description', 'woocommerce'),
        'type'            => 'textarea',
        'default'         => __('Pay via Barion; you can pay with your credit card if you don\'t have a Barion account.', 'woocommerce'),
        'description'     => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        'desc_tip'        => true
    ),
    'poskey' => array(
        'title'           => __('Secret key (POSKey)', 'woocommerce'),
        'type'            => 'text',
        'description'     => __('The secret key of the online store registered in Barion (called POSKey)'),
        'desc_tip'        => true
    ),
    'payee' => array(
        'title'           => __('Payee (Barion Email address)', 'woocommerce'),
        'type'            => 'text',
        'description'     => __('Your Barion email address'),
        'desc_tip'        => true,
        'default'         => get_option('admin_email'),
        'placeholder'     => 'you@youremail.com'
    ),
    'environment' => array(
        'title'           => __('Environment', 'woocommerce'),
        'type'            => 'select',
        'label'           => __('Barion Environment', 'woocommerce'),
        'options'         => array('test' => 'Test', 'live' => 'Live'),
        'default'         => 'test',
        'description'     => __('The Barion environment to connect to. This can be the test system, or the production environment.'),
        'desc_tip'        => true
    )
);
