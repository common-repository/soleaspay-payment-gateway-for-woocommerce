<?php

/**
 * WC_Soleaspay_Payment_Rest_Api class
 *
 * @author  Soleaspay <info@soleaspay.com>
 * @package Soleaspay\Payment_Endpoint
 * @since   1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Soleaspay Gateway Payment Class for integration endpoint Api,
 * complete Operation for Order's products
 * and redirect client into the checkout received order page
 * 
 * @class WC_Soleaspay_Payment_Rest_Api
 * @version 1.2
 */
final class WC_Soleaspay_Payment_Rest_Api
{

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'register_callback_methods']);
	}

	/**
	 * Generate type of Request-rest-route for my endpoint
	 * 
	 * @return void
	 */
	public function register_callback_methods(): void
	{
		register_rest_route('soleaspay/v1', '/response/(?P<endpoint_url>\w+)/', [
			'method' => ['GET'],
			'callback' => function(WP_REST_Request $request){return $this->get_request_params($request);},
			'permission_callback' => '__return_true'
		]);
	}

	/**
	 * Apply operations for my endpoint and generate WP_Rest_Response if error retrieved or redirect to page received order if operation is successful
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 * @throws WC_Data_Exception
	 * @throws Exception
	 */
	public function get_request_params(WP_REST_Request $request): WP_REST_Response
	{
		$soleaspay_gateway_endpoint_setting = WC()->payment_gateways()->get_available_payment_gateways()['soleaspay']->settings['rest_api_namespace'];
		$endpoint_url = $request->get_param('endpoint_url');
		$is_endpoint = explode('response/', $soleaspay_gateway_endpoint_setting)[1] === $endpoint_url;
		if(stristr($request->get_method(), "GET") && $is_endpoint) {
			$key = $request->get_param('key');
			$soleaspay_data_request = urldecode($request->get_param('soleaspay_data'));

			if ( empty($soleaspay_data_request) || json_decode( $soleaspay_data_request ) === null || wc_get_order_id_by_order_key($key) === 0)
			    return new WP_REST_Response("Bad Request !!", 403);

			$soleaspay_data = json_decode($soleaspay_data_request, true);

			$keys = [
				'success' => ['status','currency', 'amount', 'ref', 'payId'],
				'failure' => ['success', 'message']
			];
        
			
			$data = $this->soleaspay_check_data($keys, $soleaspay_data);
			
			if(!$data){
			    return new WP_REST_Response("Unknown application", 403);
			}
			$order = wc_get_order(wc_get_order_id_by_order_key($key));
			$status = $data['status'];
			
			// Initialiser la session WooCommerce si nécessaire
            if (null === WC()->session) {
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
            }
			/** 
    		 *Clear the cart
    		 */
    		WC()->cart->empty_cart();
			if($order && $status) {
				if (!in_array($order->get_status(), ['completed'], true)) {
					$logger = [];
					$base_data = $order->get_base_data();
					if ($status === 'success') {
						$order->set_transaction_id($data['transaction_id']);
						$order->add_order_note(__("Payment completed successfully with Soleaspay", 'wc-soleaspay-gateway'));
						$order->update_status('completed');
						$order->payment_complete();
						$message = [
							'message' => "Payment completed successfully with Soleaspay",
							'data' => $data['data']
						];

						
					} else if ($status === 'failed') {
						$order->add_order_note(__('Payment Failled with : '.$data['data']['message'], 'wc-soleaspay-gateway'));
						$order->update_status('failed');
						$message = [
							'message' => "Payment failed with Soleaspay",
							'data' => $data['data']
						];

						$logger['level'] 		= 'warning';
						$logger['title']	= 'Payment failled';
						$logger['start'] 	= '<-------------------------------------------------------- !!! Payment Failed  !!! -------------------------------------------------------->';
						$logger['content'] 	= $message;
						$logger['end'] 		= '<-------------------------------------------------------- !!!   End Payment   !!! -------------------------------------------------------->';
						WC_Soleaspay_Payment::soleaspay_write_log($logger);
					}
					wp_safe_redirect($order->get_checkout_order_received_url(), 301);
                    exit;
				}
			} else {
			    
				/**
				 *perform message with nonce
				 */
				wp_safe_redirect(wc_get_cart_url(), 301);
				exit;
			}
		}
        return new WP_REST_Response('Access denied !!', 404);
	}

	/**
	 * It's used to verify if format of data received correspond to form-data-response-payment.
	 * 
	 * @param array $keys_list List of different array-key for your response data
	 * 
	 * @param array $data Data received for your endpoint
	 *
	 * @return bool|array
	 *  If data not correspond to different elements of keys-list, return 'false', else, return array data with status type
	 */
	private function soleaspay_check_data(array $keys_list, array $data): bool|array
	{
		$is_check = false;
		$content = [];
		$keys = [];
		foreach($keys_list as $k) {
			if(sizeof($k) === sizeof($data))
				$keys = $k;
		}

		if(empty($keys))
			return false;

		foreach($keys as $key) {
			if(array_key_exists($key, $data) && (is_bool($data[$key]) || !empty($data[$key])))
				$is_check = true;
			else
				return false;
		}
		if(isset($data['status']) && $data['status'] === 'SUCCESS')
			$content = [
				'status' => 'success',
				'transaction_id' => $data['payId'],
				'data' => $data
			];
		if(isset($data['success']) && $data['success'] === false)
			$content = [
				'status' => 'failed',
				'data' => $data
			];
		return $is_check ? $content : false;
	}
}