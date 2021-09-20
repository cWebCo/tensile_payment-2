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

include plugin_dir_path(__FILE__)."cancel.php";
include plugin_dir_path(__FILE__)."success.php";

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
			$this->method_title       = __( 'Tensile Payments', 'wc-tensile-payments' );
			$this->method_description = __( 'Lower your payment costs by allowing customers to pay with their bank accounts while also giving to causes you and they care about.', 'wc-tensile-payments' );
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			$this->testmode = $this->get_option( 'testmode', $this->testmode );
			$this->client_id = $this->get_option( 'client_id', $this->client_id );
			$this->client_secret = $this->get_option( 'client_secret', $this->client_secret );
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			/* add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 ); */
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
					'default'     => __( 'Tensile Payments', 'wc-tensile-payments' ),
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
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
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
	
			$order = wc_get_order( $order_id );
			
			$subtotal = $order->get_subtotal();
			$total = $order->get_total();
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
			   $oitems .= '"quantity":"'.$quantity.'",';
			   $oitems .= '"price":"'.$total1.'"';
			   $oitems .= '}';
			}
			$oitems .= ']';
			$data = '{';
			$data .= '"subtotal":"'.$subtotal.'",';
			$data .= '"total":"'.$total.'",';
			$data .= '"items":"'.$oitems.'",';
			$data .= '"variable_tax":"false",';
			$data .= '"variable_shipping":"false",';
			$data .= '"shipping_required":"false",';
			$data .= '"redirect_uri_success":"google.com",';
			$data .= '"redirect_uri_cancel":"google.com",';
			$data .= '"payment_type":"one-off"';
			$data .= '}';
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://api-gateway-east.dev.tensilepayments.com/payments',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>$data,
			  CURLOPT_HTTPHEADER => array(
				'correlation-id: yooo',
				'client_id: 4aa9b5ba-7583-4a11-938a-d1cfce73ec45',
				'client_secret: 0e6424a414e84a639b42ae6c02a0919485db94222291b258ac21321cfecfdd6aa2bccbe095068e5fd210af14f0b5f0048a9ac129df04ab3389bf50a3c3803e27250729566ea73420d9f4ab16f1618a52ed0502626e91c7b500c9df5d8f5f6e3ffbbb302c22738f2ff24fa8e293e9bb8f908ed565bbd9dbb63f6b1d71b08ff8785cda8120f86f0ac5f4d1104b9360965437fcd1cb9b7bbebfc6803a42b970d62e25abe8e1b79219a5b799a633558cbdf5847bac8942a36a9d303424c1d35df0caef30f78f52d402c5bce57fe9f9b1ca7e22a33f719becfb7d89fb8ad9443738f2a96c066dc2b1f6d66c3b550ba226175f8f586f9189323ac411ce643fae94f47a',
				'Content-Type: application/json'
			  ),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			echo $response;

			exit;
		}
	
  } // end \WC_Gateway_Offline class
}