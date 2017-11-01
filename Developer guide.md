# Developer guide

## Setting up the development environment

  1. Install a local copy of Wordpress with WooCommerce
  2. Create a new folder called `pay-via-barion-for-woocommerce` under `wp-content\plugins`
  3. Clone and install submodules to that folder
  
``` bash
cd pay-via-barion-for-woocommerce
git clone git@github.com:szelpe/woocommerce-barion.git .
git submodule init
git submodule update
```
  
## Barion callback

Note that Barion callback won't work in your local environment, so you'll need to call `/wc-api/WC_Gateway_Barion/?paymentId=<paymentId>` manually.

## How to generate POT file?

POT file can be generated using Grunt.

``` bash
npm install grunt grunt-wp-i18n
grunt 
```