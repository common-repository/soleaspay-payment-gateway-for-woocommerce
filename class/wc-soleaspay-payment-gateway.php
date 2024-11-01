<?php
/**
 * WC_Soleaspay_Payment_Gateway class
 *
 * @author   Soleaspay <info@soleaspay.com>
 * @package  Soleaspay\Payment_Gateway
 * @since    1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Soleaspay Gateway Payment class
 *
 * @class    WC_Soleaspay_Payment_Gateway
 * @extends  WC_Payment_Gateway
 * @version  1.2
 */
class WC_Soleaspay_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * List of Currencies to validate transaction
	 * 
	 * @var array
	 */
	const CURRENCIES = ['XAF','XOF', 'EUR', 'USD'];

	/**
	 * API URI for Soleaspay Payment
	 * 
	 * @var string
	 */
	const API_URI = 'https://checkout.soleaspay.com';

	/**
	 * API KEY for Soleaspay Payment
	 * 
	 * @var string
	 */
	private string $apiKey;

	/**
	 * ShopName for Soleaspay Payment
	 * 
	 * @var string
	 */
	private string $shopName;

	/**
	 * Currency used for validate transaction
	 * 
	 * @var string
	 */
	private string $currency;

	/**
	 * Namespace of the Api endpoint to receive data
	 * 
	 * @var string
	 */
	private string $rest_api_namespace;

	public function __construct()
	{
		/** 
		 * Unique ID for gateway
		 */
		$this->id = SOLEASPAY_NAME;
		$this->icon = WC_Soleaspay_Payment::plugin_url()."/assets/frontend/images/soleaspay.jpg";
		$this->has_fields = false;
		$this->title = 'SoleasPay';
		$this->method_title = __('SoleasPay Payment Gateway for Woocommerce ', 'wc-soleaspay-gateway');
		$this->method_description = __('SoleasPay Payment Gateway for WooCommerce is a plugin that allows you to sell wherever your customers are.
		Offer your customers an intuitive payment experience and let them pay ou the way they want by
		Orange Money, MTN Mobile Money, Express Union, VISA, PayPal, MasterCard, Perfect Money or bitCoin', 'wc-soleaspay-gateway');

		/** 
		 *Initialize the payment gateway settings
		 */
		$this->init_form_fields();
		$this->init_settings();
		$this->rest_api_namespace = $this->generate_rest_namespace();

		/** 
		 *Define the settings
		 */
		$this->order_button_text = $this->get_option('order_button_text');
		$this->description = $this->get_option('description');
		$this->apiKey = $this->get_option('apiKey');
		$this->shopName = $this->get_option('shopName');
		$this->currency = $this->get_option('currency');

		/** 
		 *Save settings to display in admin
		 */
		add_action("woocommerce_update_options_payment_gateways_{$this->id}", [$this, 'process_admin_options']);
	}

	/**
	 * Generate ENDPOINT URI for receive RESPONSE REQUEST from checkout if not exist
	 *
	 * @return string
	 * @throws Exception
	 */
	private function generate_rest_namespace(): string
	{
		if(!$this->get_option('rest_api_namespace')) {
			$this->update_option('rest_api_namespace', 'soleaspay/v1/response/'.md5(random_int(PHP_INT_MIN, PHP_INT_MAX).uniqid()));
		}
		return $this->get_option('rest_api_namespace');
	}

	/**
	 * Initialise admin settings form field data
	 *
	 * @return void
	 */
	public function init_form_fields(): void
	{
		$this->form_fields = [
			'enabled' => [
				'title'   => __('Enable/Disable', 'wc-soleaspay-gateway'),
				'type'    => 'checkbox',
				'label'   => 'Enable SoleasPay Gateway',
				'description' => 'Enable or Disable SoleasPay Payment',
				'default' => 'yes',
			],
			'apiKey' => [
				'title' => __('API KEY', 'wc-soleaspay-gateway'),
				'type' => 'text',
				'description' => __('Copy and place the ApiKey SoleasPay for Have Access to use this Payment. If you have not the ApiKey, please contact the administrator', 'wc-soleaspay-gateway'),
				'default' => '',
				'desc_tip' => true,
			],
			'order_button_text' => [
				'title' => __('Name of button text', 'wc-soleaspay-gateway'),
				'type' => 'text',
				'description' => __('Name of the button SoleasPay will be see in front-page', 'wc-soleaspay-gateway'),
				'default' => __('Pay with SoleasPay', 'wc-soleaspay-gateway'),
				'desc_tip' => true,
			],
			'shopName' => [
				'title' => __('Name of Your Business', 'wc-soleaspay-gateway'),
				'type' => 'text',
				'description' => __('State your business', 'wc-soleaspay-gateway'),
				'default' => __('Mysoleas payment App', 'wc-soleaspay-gateway'),
				'desc_tip' => true,
			],
			'currency' => [
				'title' => __('SoleasPay currency', 'wc-soleaspay-gateway'),
				'type' => 'select',
				'description' => __('This is the current bill to start a transaction', 'wc-soleaspay-gateway'),
				'default' => 'XAF',
				'options' => [
					'XAF' => __('Franc CFA (FC-FA)', 'wc-soleaspay-gateway'),
					'XOF' => __('Franc CFA (FC-FA)', 'wc-soleaspay-gateway'),
					'EUR' => __('Euro (€)', 'wc-soleaspay-gateway'),
					'USD' => __('Dollar ($)', 'wc-soleaspay-gateway'),
				],
				'desc_tip' => true,
			],
			'description' => [
				'title' => __('Description', 'wc-soleaspay-gateway'),
				'type' => 'textarea',
				'description' => __('Payment method description, visible by customers on your checkout page', 'wc-soleaspay-gateway'),
				'default' => __('Pay safely using Orange Money, MTN Mobile Money, PayPal, Perfect Money, MasterCard, VISA or Wave', 'wc-soleaspay-gateway'),
				'desc_tip' => true,
			],
		];
	}

	/**
	 * Get the apiKey
	 * 
	 * @return string
	 */
	public function get_apiKey(): string
	{
		return $this->apiKey;
	}

	/**
	 * Get the shopName
	 * 
	 * @return string
	 */
	public function get_shopName(): string
	{
		return $this->shopName;
	}

	/**
	 * Get current Soleaspay currency used
	 * 
	 * @return string
	 */
	public function get_currency(): string
	{
		return $this->currency;
	}

	/**
	 * Returns user's locale
	 * 
	 * @return string
	 */
	public function get_locale(): string
	{
		$lang = get_language_attributes();
		$lang = str_replace('"', '', $lang);
		return substr($lang, 5, 2);
	}

	/**
	 * Generate response form to send on the checkout page
	 * 
	 * @param array $content
	 * 
	 * @return string
	 */
	private function soleaspay_generate_form(array $content): string
	{
		$action = self::API_URI;
		$html = "<div class='soleaspay_fragment_form'>";
		$html .= "<form id='soleaspay_data_form' method='post' action='{$action}'>";
		foreach ($content as $name => $value) {
			$html .= "<input type='hidden' name='{$name}' value='{$value}' readonly>";
		}
		$html.= "</form>";
		$html .= "</div>";
		return $html;
	}

	/**
	 * Get order amount and currency for Soleaspay
	 * 
	 * @param WC_Order $order
	 * 
	 * @return Array
	 * @throws Exception
	 */
	private function soleaspay_get_order_amount_currency(WC_Order $order): array
	{
		$logger = [];
		$woocommerce_currency = get_woocommerce_currency();
		$currency = $this->get_currency();
		$amount = $order->get_total();
		/**  
		 *Throws an exception when currency is not defined in SOLEASPAY_CURRENCIES
		 */
		if (!in_array($woocommerce_currency, self::CURRENCIES)) {
			$message = "Currency '{$woocommerce_currency}' is not currently supported. Please, try using one of the following: " .self::CURRENCIES;

			$logger['level'] 	= 'error';
			$logger['title']	= 'Currency Error';
			$logger['start'] 	= '<-------------------------------------------------------- !!! Currency Not Found !!! -------------------------------------------------------->';
			$logger['content'] 	= ['message' => $message, 'supported_currencies' => self::CURRENCIES, 'active_woocommerce_currency' => $woocommerce_currency];
			$logger['end'] 		= '<-------------------------------------------------------- !!!     End Error      !!! -------------------------------------------------------->';
			WC_Soleaspay_Payment::soleaspay_write_log($logger);

			wc_add_notice( 
				printf(
					esc_html__('Payment error %s', 'wc-soleaspay-gateway'), 
					
					esc_html($message)
				)
			);
			throw new Exception($message);
		}
		
		if ($currency !== $woocommerce_currency) {
			$uri_converter = add_query_arg([
				'amount' => $amount,
				'from' => $woocommerce_currency,
				'to' => $currency
			], "https://soleaspay.com/api/convert");
			$request_convert = wp_remote_get($uri_converter, [
				'headers' => ['x-api-key' => $this->get_apiKey()],
			]);

			if (is_wp_error($request_convert) || wp_remote_retrieve_response_code($request_convert) !== 200) {
				$message = "And Error Occurred during convert Currency, Please try against or change manually the default currency";
				
				$logger['level'] = 'error';
				$logger['title'] = 'Currency Request Error';
				$logger['start'] = '<-------------------------------------------------------- !!! Request Currency Convert Error !!! -------------------------------------------------------->';
				$logger['content'] = ['message' => $message, 'request' => $request_convert];
				$logger['end'] = '<-------------------------------------------------------- !!!   End Currency Convert Error   !!! -------------------------------------------------------->';
				WC_Soleaspay_Payment::soleaspay_write_log($logger);

				wc_add_notice( 
					printf(
						esc_html__('Payment error %s', 'wc-soleaspay-gateway'), 
						
						esc_html($message)
					)
				);
				throw new Exception($message);
			}

			$raw_response = wp_remote_retrieve_body($request_convert);
			$response = json_decode($raw_response, true);
			
			if(!(isset($response['success']) && $response['success'] === true)) {
				$message = "Error due to invalid conversion";
				
				$logger['level'] 	= 'error';
				$logger['title']	= 'Currency Response Error';
				$logger['start'] 	= '<-------------------------------------------------------- !!! Response Currency Convert Error !!! -------------------------------------------------------->';
				$logger['content'] 	=  compact('message', 'raw_response', 'response');
				$logger['end'] 		= '<-------------------------------------------------------- !!!   End Currency Convert Error    !!! -------------------------------------------------------->';
				WC_Soleaspay_Payment::soleaspay_write_log($logger);

				wc_add_notice(
					printf(
						esc_html__('Payment error %s', 'wc-soleaspay-gateway'), 
						
						esc_html($message)
					)
				);
				throw new Exception($message);
			}
			$data = $response['data'];
			$amount = $data[$currency];
		}
		
		return compact('amount', 'currency');
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function process_payment($order_id): array
	{
		$logger = [];
		$order = wc_get_order($order_id);
		$order_description = implode(
			', ',
			array_map(
				function (WC_Order_Item $item) {
					return $item->get_name();
				},
				$order->get_items()
			)
		);
		$rest_url = rest_url($this->rest_api_namespace);
		$request_url = add_query_arg([
			'key' => $order->get_order_key()
		], $rest_url);
		
		$amount_currency = $this->soleaspay_get_order_amount_currency($order);

		$options = [
			'apiKey' 		=> $this->get_apiKey(),
			'amount' 		=> $amount_currency['amount'],
			'currency' 		=> $amount_currency['currency'],
			'description' 	=> $order_description,
			'orderId' 		=> $order->get_order_key(),
			'successUrl' 	=> $request_url,
			'failureUrl' 	=> $request_url,
			'shopName' 		=> $this->get_shopName(),
		];
		$request = wp_remote_post(self::API_URI, [
			'body'        => ($options),
			"sslverify"   => true,
			'timeout'     => '15',
		]);

		if(is_wp_error($request) || wp_remote_retrieve_response_code($request) !== 200) {
			$message = "An Error has occurred. Please try again now or later";
			$order->add_order_note(
				printf(
					esc_html__('Soleaspay payment init failed with message %s', 'wc-soleaspay-gateway'), 
					
					esc_html($message)
				)
			);
			
			/** 
			 *write error in the debug file
			 */
			$logger['level']	= 'error';
			$logger['title']	= 'Payment Request Error';
			$logger['start'] 	= '<-------------------------------------------------------- !!! Request Error !!! -------------------------------------------------------->';
			$logger['content']  = ['message' => $message, 'request' => $request ];
 			$logger['end']		= '<-------------------------------------------------------- !!!   End Error   !!! -------------------------------------------------------->';
			WC_Soleaspay_Payment::soleaspay_write_log($logger);

			wc_add_notice( 
				printf(
					esc_html__('Payment error %s', 'wc-soleaspay-gateway'), 
					
					esc_html($message)
				)
			);
			throw new Exception($message);
		}

		$order->add_order_note(__("Payment is processing...", 'wc-soleaspay-gateway'));
		
		/** 
		 *Clear the cart
		 */
		WC()->cart->empty_cart();

		/** 
		 *Return the Response from page redirect URL
		 */
		return [
			'result' => 'success',
			'redirect' => '',
			'soleaspay_response_data' => $this->soleaspay_generate_form($options),
		];
	}
}