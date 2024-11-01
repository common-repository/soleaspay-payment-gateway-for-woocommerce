<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * WC_Soleaspay_Payment_Block class
 *
 * @author  Soleaspay <info@soleaspay.com>
 * @package Soleaspay\Payment_Blocks
 * @since   1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Soleaspay Gateway Payment Class for integration Block in view
 * 
 * @class WC_Soleaspay_Payment_Block
 * @version 1.2
 */
final class WC_Soleaspay_Payment_Block extends AbstractPaymentMethodType
{
	/**
	 * The gateway instance.
	 *
	 * @var WC_Soleaspay_Payment_Gateway
	 */
	private WC_Soleaspay_Payment_Gateway $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = SOLEASPAY_NAME;

	/**
	 * Instantiate the Payment Gateway
	 *
	 * @return void
	 */
	public function initialize(): void
	{
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
		$this->settings = $this->gateway->settings;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active(): bool
	{
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles(): array
	{
		$script_asset_path = WC_Soleaspay_Payment::plugin_abspath()."/assets/frontend/js/block.asset.php";
		$assets = file_exists($script_asset_path) ? require $script_asset_path : ['dependencies' => [], 'version' => '1.2'];
		$script_url = WC_Soleaspay_Payment::plugin_url()."/assets/frontend/js/block.js";
		wp_register_script(
			'wc-soleaspay-payment-block',
			$script_url,
			$assets['dependencies'],
			$assets['version'],
			true
		);

		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('wc-soleaspay-payment-block', 'wc-soleaspay-gateway', WC_Soleaspay_Payment::plugin_abspath().'languages/');
		}

		return ['wc-soleaspay-payment-block'];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data(): array
	{
		return [
			'name' 			=> $this->name,
			'title' 		=> $this->gateway->title,
			'button_title' 	=> $this->get_setting('order_button_text'),
			'description' 	=> $this->get_setting('description'),
			'icon' 			=> $this->gateway->icon,
			'images' 		=> $this->get_images_with_extensions("/assets/frontend/images"),
			'supports' 		=> array_filter($this->gateway->supports, [$this->gateway, 'supports'])
		];
	}

	/**
	 * Find all images names in directory with specified extensions
	 * and return all files name
	 * 
	 * @param string $base_name Plugin base name directory where images will be found!
	 * 
	 * @param array $extensions List of all extension in your file name
	 * this param is optional the default value is .png
	 * 
	 * @return array 
	 * if empty, no images found or error received during the processing, else return array-list uri of images found
	 */
	private function get_images_with_extensions(string $base_name, array $extensions = ['png']): array
	{
		$directory_abspath = WC_Soleaspay_Payment::plugin_abspath().$base_name;
		$directory_url = WC_Soleaspay_Payment::plugin_url().$base_name;
		$image_files = [];

		if($handle = opendir($directory_abspath)) {
			while (($file = readdir($handle)) !== false) {
				/** 
				 * ignoring directories started with "." and ".."
				 */
				if($file != "." && $file != "..") {
					/**
					 * verify if extension is correct
					 */ 
					$file_extension = pathinfo($file, PATHINFO_EXTENSION);
					if (in_array(strtolower($file_extension), $extensions)) {
						$image_files[] = "{$directory_url}/{$file}";
					}
				}
			}
			closedir($handle);
		}

		return $image_files;
	}

}