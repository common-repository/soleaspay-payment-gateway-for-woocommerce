<?php
/**
 * SoleasPay Payment Gateway for WooCommerce
 *
 * @package           		Soleaspay
 * @since					1.2
 * 
 * @wordpress-plugin
 * Plugin Name:       		SoleasPay Payment Gateway for WooCommerce
 * Plugin URI:        		https://www.SoleasPay.com
 * Description:       		SoleasPay - Payment gateway for WooCommerce allows you to easily integrate the SoleasPay online payment platform into your WooCommerce store. It provides your customers with the ability to carry out financial transactions easily, securely and conveniently. Using this plugin, you can offer multiple payment options such as credit cards, Orange Money, PayPal, and many others.
 * Version:           		1.2
 * 
 * Author:            		Mysoleas
 * Author URI:        		https://www.mysoleas.com
 * 
 * Copyright:         		© 2020-2024 mysoleas-author.
 * License:           		GNU General Public License v3.0
 * License URI:       		http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * Privacy Policy:    		https://soleaspay.com/home/terms
 *                    		https://soleaspay.com/home/Privacy 
 * 
 * Text Domain:       		wc-soleaspay-gateway
 * Domain Path:       		/i18n/languages/
 * 
 * Requires at least: 		6.4
 * Tested up to:      		6.4
 * 
 * WC requires at least:	8.3
 * WC tested up to: 		8.6
 * 
 * Requires PHP: 			8.1
 */


use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if(!defined('ABSPATH')) exit;

define('SOLEASPAY_NAME', 'soleaspay');

/**
 * WC SoleasPay Payment Gateway Plugin class
 *
 * @class WC_Soleaspay_Payment
 */
class WC_Soleaspay_Payment {

	public function __construct()
	{
		/** 
		 *Require the WooCommerce Payment Gateway class
		 */
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			return;
		}

		add_action('plugins_loaded', [$this, 'include'], 0);

		add_filter('woocommerce_payment_gateways', [$this, 'soleaspay_add_to_woocommerce']);

		add_action('woocommerce_blocks_loaded', [$this, 'soleaspay_woocommerce_block_support']);
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url(): string
	{
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin dir path.
	 *
	 * @return string
	 */
	public static function plugin_abspath(): string
	{
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Print received data to the log file
	 *
	 * @param array $data The params required is:
	 * 		-> "level"(string)  : specify level writing in the log, the following parameter is one of ['info', 'debug', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency']
	 * 		-> "title"(string)  : Message used to reference the context logger
	 * 		-> "start"(string)  : message before the logger
	 * 		-> "content"(array) : really message to writing in the log file
	 * 		-> "end"(string)    : message after the logger
	 * 		-> "context"(array) : optional additional message to save message
	 *
	 * @return void If the data message to writing log is not correct? throws exception en then write message in the log file
	 * @throws Exception
	 */
	public static function soleaspay_write_log( array $data): void
	{
		$levels = ['info', 'debug', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
		$level = 'debug';
		$required_keys = ['level', 'title', 'start', 'content', 'end'];
		$keys = array_keys($data);
		$context = ['source' => SOLEASPAY_NAME];
		$is_error = false;

		if (!(sizeof($data) === sizeof($required_keys) || sizeof($data) === (sizeof($required_keys)+1)))
			$is_error = true;
		
		foreach ($required_keys as $key) {
			if (!array_key_exists($key, $data))
				$is_error = true;
		}

		if ($is_error)
			throw new Exception("Invalid Data Format: Other Params is required!!");
		
		if (!empty($data['level'] && in_array($data['level'], $levels, true)))
			$level = $data['level'];

		$message = "{$data['title']}:\n{$data['start']}\n\t ".wc_print_r($data['content'], true)."\n{$data['end']}\n";
		
		if (!empty($data['context']))
			$context = array_merge($context, $data['context']);
		
		if (!function_exists('write_log')) {
			function write_log($log): void {
				if (WP_DEBUG)
					error_log(is_array($log) || is_object($log) ? print_r($log, true) : $log);
			}
		}

		/** 
		 * write the message in to the WP-log file
		 */
    	write_log(compact('level', 'message', 'context'));

		/** 
		 *Write message in to the wc-log file
		 */
		wc_get_logger()->log($level, $message, $context);
	}

	/**
	 * Include The Payment Gateway Class in Plugin
	 *
	 * @return void
	 */
	public function include(): void
	{
		if (class_exists("WC_Payment_Gateway")){
			require_once "class/wc-soleaspay-payment-gateway.php";
			require_once "class/wc-soleaspay-payment-rest-api.php";
			new WC_Soleaspay_Payment_Rest_Api();
		}
	}

	/**
	 * Add callback function to Hook on Woocommerce
	 *
	 * @param array $methods
	 *
	 * @return array
	 */
	public function soleaspay_add_to_woocommerce( array $methods ): array
	{
		$methods[] = 'WC_Soleaspay_Payment_Gateway';
		return $methods;
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 * @return void
	 */
	public function soleaspay_woocommerce_block_support(): void
	{
		if(class_exists(AbstractPaymentMethodType::class)) {
			require_once "class/wc-soleaspay-payment-block.php";
			add_action('woocommerce_blocks_payment_method_type_registration', static function (PaymentMethodRegistry $payment_method_registry) {
				$payment_method_registry->register(new WC_Soleaspay_Payment_Block());
			});
		}
	}
}

new WC_Soleaspay_Payment();
