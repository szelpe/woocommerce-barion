<?php

/**
 * Class WC_Gateway_Barion_Profile_Activity
 * Collects profile activities, e.g. password change for 3DS
 */
class WC_Gateway_Barion_Profile_Monitor {

    private $is_profile_just_created = false;
    private $is_profile_just_updated = false;
    private static $password_changed_key = 'wc_barion_password_updated';

    public function __construct() {
        add_action('profile_update', [$this, 'check_profile_changes'], 10, 2);
        add_action('woocommerce_created_customer', [$this, 'save_customer_created'], 10, 3);
        add_action('woocommerce_update_customer', [$this, 'save_customer_update'], 10, 2);
    }

    public function was_profile_just_created() {
        return $this->is_profile_just_created;
    }

    public function was_profile_just_updated() {
        return $this->is_profile_just_updated;
    }

    /**
     * @param $user_id
     * @param $old_user_data WP_User
     */
    public function check_profile_changes($user_id, $old_user_data) {
        $user = wp_get_current_user();

        if (!empty($_POST['pass1'])) {
            update_user_meta($user_id, self::$password_changed_key, time());
        }
    }

    public function save_customer_created($customer_id, $new_customer_data, $password_generated) {
        $this->is_profile_just_created = true;
    }

    public function save_customer_update($customer_id, $new_customer_data) {
        $this->is_profile_just_updated = true;
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public function get_date_password_changed() {
        $user = wp_get_current_user();

        $timestamp = get_user_meta($user->ID, self::$password_changed_key, true);

        if (empty($timestamp)) {
            return null;
        }

        return new DateTime('@' . $timestamp);
    }
}
