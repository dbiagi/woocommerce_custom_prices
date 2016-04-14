<?php

/*
  Plugin Name: Custom Pricing
  Plugin URI:
  Description: Preços customizados por cliente por produto.
  Version: 1.0.0
  Author: Diego de Biagi <diego.biagi@twodigital.com.br>
  Author URI:
  License: GPLv2
 */

/* @var $loader \Composer\Autoload\ClassLoader */
global $loader;

$loader->addPsr4('TWODigital\\CustomPricing\\', dirname(__FILE__) . '/src');

add_action('init', function(){
    new \TWODigital\CustomPricing\Plugin\CustomPricingPlugin();
});
