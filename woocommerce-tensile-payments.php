<?php
/*
 * Plugin Name: WooCommerce Tensile Payments
 * Plugin URI: https://cwebconsultants.com/
 * Description: To add custom payment gateway for tensile payments in woocommerce.
 * Author: Webgarh Plugins Team
 * Author URI: https://cwebconsultants.com/
 * Version: 1.0.0
 */
defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function wc_wtp_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Woocommerce_Tensile_Payments';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_wtp_add_to_gateways' );


/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_tensile_payments_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woocommerce_tensile_payments' ) . '">' . __( 'Configure', 'wc-woocommerce-tensile-payments' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_tensile_payments_gateway_plugin_links' );


/**
 * Woocommerce Tensile Payments Payment Gateway
 *
 * Provides an Tensile Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Woocommerce_Tensile_Payments
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		SkyVerge
 */
add_action( 'plugins_loaded', 'wc_tensile_payments_gateway_init', 11 );

function wc_tensile_payments_gateway_init() {

	class WC_Woocommerce_Tensile_Payments extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'woocommerce_tensile_payments';
			$this->icon               = '';
			$this->has_fields         = false;
			$this->method_title       = __( 'Offset your order emissions at no cost with Tensile', 'wc-tensile-payments' );
			$this->method_description = __( 'Lower your payment costs by allowing customers to pay with their bank accounts while also giving to causes you and they care about.', 'wc-tensile-payments' );
			$this->supports           = array(
            'products',
            'refunds',
			);
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->instructions );
			$this->api_endpoint = $this->get_option( 'api_endpoint', $this->api_endpoint );
			$this->checkout_app_url = $this->get_option( 'checkout_app_url', $this->checkout_app_url );
			$this->sandbox_api_endpoint = $this->get_option( 'sandbox_api_endpoint', $this->sandbox_api_endpoint );
			$this->sandbox_checkout_app_url = $this->get_option( 'sandbox_checkout_app_url', $this->sandbox_checkout_app_url );
			$this->testmode = $this->get_option( 'testmode', $this->testmode );
			$this->sandbox_client_id = $this->get_option( 'sandbox_client_id', $this->client_id );
			$this->sandbox_client_secret = $this->get_option( 'sandbox_client_secret', $this->client_secret );
			$this->client_id = $this->get_option( 'client_id', $this->client_id );
			$this->client_secret = $this->get_option( 'client_secret', $this->client_secret );
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			
		  
			// Customer Emails
			/*add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 ); */
		}
	
		
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wc_tensile_payments_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'wc-tensile-payments' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Tensile Payments', 'wc-tensile-payments' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'wc-tensile-payments' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-tensile-payments' ),
					'default'     => __( 'Offset your order emissions at no cost with Tensile', 'wc-tensile-payments' ),
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'wc-tensile-payments' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-tensile-payments' ),
					'default'     => __( 'Lower your payment costs by allowing customers to pay with their bank accounts while also giving to causes you and they care about.', 'wc-tensile-payments' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-tensile-payments' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-tensile-payments' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'testmode' => array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the payment gateway in test mode using test API keys.',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'sandbox_api_endpoint' => array(
					'title'       => 'Sandbox API Endpoint',
					'type'        => 'text'
				),
				'sandbox_checkout_app_url' => array(
					'title'       => 'Sandbox Checkout App Url',
					'type'        => 'text'
				),
				'api_endpoint' => array(
					'title'       => 'API Endpoint',
					'type'        => 'text'
				),
				'checkout_app_url' => array(
					'title'       => 'Checkout App Url',
					'type'        => 'text'
				),
				
				'sandbox_client_id' => array(
					'title'       => 'Sandbox Client ID',
					'type'        => 'text'
				),
				'sandbox_client_secret' => array(
					'title'       => 'Sandbox Client Secret',
					'type'        => 'password',
				),
				'client_id' => array(
					'title'       => 'Live Client ID',
					'type'        => 'text'
				),
				'client_secret' => array(
					'title'       => 'Live Client Secret',
					'type'        => 'password',
				),
			) );
		}
	
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page($order_id) {
			global $woocommerce;
			if ( ! $order_id ) {
				return;
			}
			$order = wc_get_order( $order_id );
			$order->reduce_order_stock();

			// Remove cart
			$woocommerce->cart->empty_cart();
			$order->update_status( 'completed' );
		}
		
		/**
		process refund 
		**/
		public function process_refund($order_id, $amount = null, $reason = '')
		{
			global $wpdb;
			global $woocommerce;
			$tensiledata = get_option('woocommerce_woocommerce_tensile_payments_settings');
			if($tensiledata['testmode'] == 'yes')
			{
				$clientid = $tensiledata['sandbox_client_id'];
				$clientsecret = $tensiledata['sandbox_client_secret'];
				$api_endpoint = $tensiledata['sandbox_api_endpoint'];			
				$checkout_app_url = $tensiledata['sandbox_checkout_app_url'];	
			}
			else
			{
				$clientid = $tensiledata['client_id'];
				$clientsecret = $tensiledata['client_secret'];
				$api_endpoint = $tensiledata['api_endpoint'];			
				$checkout_app_url = $tensiledata['checkout_app_url'];	
			}	
				
			$payment_id = get_post_meta($order_id,'transaction_id',true);
			$curl = curl_init();
			$url = $api_endpoint.'/refunds';
			curl_setopt_array($curl, array(
			  CURLOPT_URL => $url,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>'{
						"payment_id": "'.$payment_id.'",
						"amount" : '.$amount.',
						"reason": "'.$reason.'",
						"platform_name": "Woocommerce",
						"platform_order_id": "'.$order_id.'"
					}',
			  CURLOPT_HTTPHEADER => array(
				'correlation-id: yooo',
				'client_id:'.$clientid,
				'client_secret:'.$clientsecret,
				'Accept: application/json;v=2',
				'Content-Type: application/json'
			  ),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			$res = json_decode($response,true);
			if($res['status'] == 'ok')
			{
				return true;
			}
			
			
		} 
	
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
			global $wpdb;
			global $woocommerce;
			$tensiledata = get_option('woocommerce_woocommerce_tensile_payments_settings');
			if($tensiledata['testmode'] == 'yes')
			{
				$clientid = $tensiledata['sandbox_client_id'];
				$clientsecret = $tensiledata['sandbox_client_secret'];
				$api_endpoint = $tensiledata['sandbox_api_endpoint'];			
				$checkout_app_url = $tensiledata['sandbox_checkout_app_url'];	
			}
			else
			{
				$clientid = $tensiledata['client_id'];
				$clientsecret = $tensiledata['client_secret'];
				$api_endpoint = $tensiledata['api_endpoint'];			
				$checkout_app_url = $tensiledata['checkout_app_url'];	
			}	
					
			
			$order = wc_get_order($order_id);
			$subtotal = number_format($order->get_subtotal(),2);
			$total = number_format($order->get_total(),2);
			$tax1 = number_format($order->get_total_tax(), 2);
			$oitems = '[';
			// Get and Loop Over Order Items
			foreach ( $order->get_items() as $item_id => $item ) {
			   $product_id = $item->get_product_id();
			   $variation_id = $item->get_variation_id();
			   $product = $item->get_product();
			   $product_name = $item->get_name();
			   $quantity = $item->get_quantity();
			   $subtotal1 = $item->get_subtotal();
			   $total1 = $item->get_total();
			   $tax = $item->get_subtotal_tax();
			   $taxclass = $item->get_tax_class();
			   $taxstat = $item->get_tax_status();
			   $allmeta = $item->get_meta_data();
			   $somemeta = $item->get_meta( '_whatever', true );
			   $product_type = $item->get_type();
			   $oitems .= '{';
			   $oitems .= '"name":"'.$product_name.'",';
			   $oitems .= '"quantity":'.$quantity.',';
			   $oitems .= '"price":'.$total1;
			   $oitems .= '}';
			}
			$success_redirect_url = site_url().'/checkout/order-received/'.$order->get_id().'/?key='.$order->get_order_key();
			$cancel_redirect_url = site_url().'/checkout';
			if($order->get_shipping_address_1())
			{
				$shipping_required = 'true';
			}
			else
			{
				$shipping_required = 'false';
			}
			$oitems .= ']';
			
			$curl = curl_init();
			$url = $api_endpoint.'/payments';
			if($order->get_shipping_address_1())
			{
				$post_fields = '{
								"subtotal" : '.$subtotal.',
								"tax":'.$tax1.',
								"total" : '.$total.',
								"items" : '.$oitems.',
								"shipping_required" : '.$shipping_required.',
								"redirect_uri_success" : "'.$success_redirect_url.'",
								"redirect_uri_cancel" : "'.$cancel_redirect_url.'",
								"payment_type": "one-off",
								"platform_name":"Woocommerce",
								"platform_order_id":"'.$order_id.'",
								"shipping_address": {
									"address_line_1": "'.$order->get_shipping_address_1().'",
									"city": "'.$order->get_shipping_city().'",
									"state": "'.$order->get_shipping_state().'",
									"country": "'.$order->get_shipping_country().'",
									"zip": "'.$order->get_shipping_postcode().'"
								},
								"user_info": {
									"first_name": "'.$order->get_billing_first_name().'",
									"last_name": "'.$order->get_billing_last_name().'",
									"email": "'.$order->get_billing_email().'",
									"phone_number": "'.$order->get_billing_phone().'"
								}
							}';
			}
			else
			{
				$post_fields = '{
								"subtotal" : '.$subtotal.',
								"tax":'.$tax1.',
								"total" : '.$total.',
								"items" : '.$oitems.',
								"shipping_required" : '.$shipping_required.',
								"redirect_uri_success" : "'.$success_redirect_url.'",
								"redirect_uri_cancel" : "'.$cancel_redirect_url.'",
								"payment_type": "one-off",
								"platform_name":"Woocommerce",
								"platform_order_id":"'.$order_id.'",
								"user_info": {
									"first_name": "'.$order->get_billing_first_name().'",
									"last_name": "'.$order->get_billing_last_name().'",
									"email": "'.$order->get_billing_email().'",
									"phone_number": "'.$order->get_billing_phone().'"
								}
							}';
			}
			curl_setopt_array($curl, array(
					CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_POSTFIELDS =>$post_fields, 
					CURLOPT_HTTPHEADER => array(
								'correlation-id: yooo',
								'client_id:'.$clientid,
								'client_secret:'.$clientsecret,
								'Accept: application/json;v=2',
								'Origin: localhost',
								'Content-Type: application/json'
				  ),
			));
			$response = curl_exec($curl);

			curl_close($curl);
			$res = json_decode($response,true);
			
			$rurl = '';
			if($res['payment_id'])
			{
				/* $rurl = $checkout_app_url.'/'.$res['payment_id'].'?merchant_name='.$res['merchant_name'].'&total='.$res['total']; */
				
				$rurl = $checkout_app_url.'/'.$res['payment_id'];
				/* $order->update_status('completed', __( 'Completed', 'woocommerce' )); */
				update_post_meta( $order_id, 'transaction_id',$res['payment_id']);
				return array(
					'result' => 'success',
					'redirect' => $rurl
				); 
				
			}
			
			exit;
		}
	
  } // end \WC_Gateway_Offline class
}
/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_transactionid_admin_order_meta', 10, 1 );

function display_transactionid_admin_order_meta($order){
    echo '<p><strong>'.__('Transaction Id').':</strong> <br/>' . get_post_meta( $order->get_id(), 'transaction_id', true ) . '</p>';
}
