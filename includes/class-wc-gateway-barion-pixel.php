<?php

class WC_Gateway_Barion_Pixel {
    private $barion_pixel_id;

    /**
     * WC_Gateway_Barion_Pixel constructor.
     * @param $barion_pixel_id string
     */
    public function __construct($barion_pixel_id) {
        $this->barion_pixel_id = $barion_pixel_id;

        add_action('wp_head', [$this, 'add_barion_pixel']);
    }

    public function add_barion_pixel() {
        if (empty($this->barion_pixel_id)) {
            return;
        }

        $barion_pixel_id = htmlspecialchars($this->barion_pixel_id, ENT_QUOTES);

        echo <<<EOL
<script>
    (function(i,s,o,g,r,a,m){i['BarionAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window, document, 'script', 'https://pixel.barion.com/bp.js', 'bp');

    // send page view event
    bp('init', 'addBarionPixelId', '{$barion_pixel_id}');
</script>
EOL;
    }
}
