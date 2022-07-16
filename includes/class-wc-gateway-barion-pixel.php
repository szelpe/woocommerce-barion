<?php

class WC_Gateway_Barion_Pixel {
    private $barion_pixel_id;

    /**
     * WC_Gateway_Barion_Pixel constructor.
     * @param $barion_pixel_id string
     */
    public function __construct($barion_pixel_id) {
        $this->barion_pixel_id = $barion_pixel_id;

        add_action('wp_head', [$this, 'add_barion_pixel'], 999999);
    }

    public function add_barion_pixel() {
        if (empty($this->barion_pixel_id)) {
            return;
        }

        if ($this->disable_tracking()) {
            return;
        }

        $barion_pixel_id = htmlspecialchars($this->barion_pixel_id, ENT_QUOTES);

        echo <<<EOL
        <script>
            // Create BP element on the window
            window["bp"] = window["bp"] || function () {
                (window["bp"].q = window["bp"].q || []).push(arguments);
            };
            window["bp"].l = 1 * new Date();
    
            // Insert a script tag on the top of the head to load bp.js
            scriptElement = document.createElement("script");
            firstScript = document.getElementsByTagName("script")[0];
            scriptElement.async = true;
            scriptElement.src = 'https://pixel.barion.com/bp.js';
            firstScript.parentNode.insertBefore(scriptElement, firstScript);
            window['barion_pixel_id'] = '{$barion_pixel_id}';            

            // Send init event
            bp('init', 'addBarionPixelId', window['barion_pixel_id']);
        </script>

        <noscript>
            <img height="1" width="1" style="display:none" alt="Barion Pixel" src="https://pixel.barion.com/a.gif?ba_pixel_id='{$barion_pixel_id}'&ev=contentView&noscript=1">
        </noscript>        
    EOL;
    }

    /**
     * Check if tracking is disabled
     *
     * @return bool True if tracking for a certain setting is disabled
     */
    private function disable_tracking() {
        if (apply_filters('woocommerce_barion_disable_tracking', false)) {
            return true;
        }

        return false;
    }
}
