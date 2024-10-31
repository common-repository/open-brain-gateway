<?php
/*
Plugin Name: OPEN-BRAIN Gateway for WooCommerce
Plugin URI: https://openbrain.digital/services/wp_openbrain_payment
Description: This add-on provides a payment gateway for your online store, supports Riyal payments for domestic and international customers, and ensures hassle-free integration through WooCommerce. You can use this add-on by having an API KEY from HillaPay. This plugin is written and developed OpenBrain for HillaPay users. And soon it will include other commercial portals as well.
Version: 4.0.0
Requires at least: 6.0.0
Requires PHP: 7.0.0
Author: OpenBrain
Author URI: https://openbrain.digital
Developer: Farid SanieePour
Developer URI: https://www.linkedin.com/in/faridsaniee
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if(!defined('ABSPATH')){die('Do not open this file directly.');}
define('openbrain_gw_dir', plugin_dir_path( __FILE__ ));
function openbrain_load_gateway_hp(){
	add_filter('woocommerce_payment_gateways', 'openbrain_add_gateway_hp');
	add_filter('woocommerce_currencies', 'openbrain_add_irr_gateway_hp');
	add_filter('woocommerce_currency_symbol', 'openbrain_add_irr_symbol_gateway_hp', 10, 2);
	require_once( openbrain_gw_dir . 'class-gateway-hp.php' );
	function openbrain_plugin_link( $actions, $plugin_file ) 
	{
	   static $plugin;
	   if (!isset($plugin))$plugin = plugin_basename(__FILE__);
	   if ($plugin == $plugin_file) 
	   {
	      $settings = array('settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=openbrain_gateway_hp">' . __('Settings', 'General') . '</a>');
	      $actions = array_merge($actions,$settings);
	   }
	   return $actions;
	}
	function openbrain_plugin_description_link( $actions, $plugin_file ) 
	{
	   	static $plugin;
	   	if (!isset($plugin)){$plugin = plugin_basename(__FILE__);}
	   	if ($plugin == $plugin_file) 
	   	{
	 	  	$site_web = array('settings' => '<a href="https://hillapay.ir/?utm_source=plugin&utm_medium=link&utm_campaign=wordpressplugin&utm_id=wordpress_site_pluginpage">' . __('HillaPay Website', 'General') . '</a>');
	      	$site_panel = array('Site' => '<a href="https://panel.hillapay.ir/merchant?utm_source=plugin&utm_medium=link&utm_campaign=wordpressplugin&utm_id=wordpress_site_pluginpage">' . __('Merchant Panel', 'General') . '</a>');	         
	      	$actions = array_merge($actions,$site_panel);
	      	$actions = array_merge($actions,$site_web);
		}
	 	return $actions;
	}
	function openbrain_add_gateway_hp($methods){
		$methods[] = 'openbrain_gateway_hp';
		return $methods;
	}
	function openbrain_add_irr_gateway_hp($currencies){
		$currencies['IRR'] = __('ریال', 'woocommerce');
		$currencies['IRT'] = __('تومان', 'woocommerce');
		$currencies['IRHR'] = __('هزار ریال', 'woocommerce');
		$currencies['IRHT'] = __('هزار تومان', 'woocommerce');
		return $currencies;
	}
	function openbrain_add_irr_symbol_gateway_hp($currency_symbol, $currency){
		switch ($currency) {
			case 'IRR':
				$currency_symbol = 'ریال';
				break;
			case 'IRT':
				$currency_symbol = 'تومان';
				break;
			case 'IRHR':
				$currency_symbol = 'هزار ریال';
				break;
			case 'IRHT':
				$currency_symbol = 'هزار تومان';
				break;
		}
		return $currency_symbol;
	}
}
add_action('plugins_loaded', 'openbrain_load_gateway_hp', 0);
add_filter('plugin_action_links', 'openbrain_plugin_link', 10, 5 );
add_filter('plugin_row_meta', 'openbrain_plugin_description_link', 10, 2 );