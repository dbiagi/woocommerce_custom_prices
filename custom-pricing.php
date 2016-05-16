<?php
/*
  Plugin Name: Custom Pricing
  Plugin URI:
  Description: Preços customizados por cliente por produto.
  Version: 1.0.0
  Author: Diego de Biagi <diego.biagi@twodigital.com.br>
  Author URI: https://github.com/dbiagi
  License: GPLv2
 */

require_once __DIR__ . '/vendor/autoload.php';

add_action('init', function(){
    new \TWODigital\CustomPricing\Plugin\CustomPricingPlugin();
});
