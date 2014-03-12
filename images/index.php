<?php
/*
   Plugin Name: NetPay Payment Gateway For WooCommerce
   Description: Extends WooCommerce to Process Payments with NetPay gateway.
   Version: 1.2.3
   Plugin URI: http://stores.dotsquares.com
   Author: Dotsquares 
   Author URI: http://www.dotsquares.com
   License: Under GPL2 
*/

add_action('plugins_loaded', 'woocommerce_tech_netpay_init', 0);

function woocommerce_tech_netpay_init() {

   if ( !class_exists( 'WC_Payment_Gateway' ) ) 
      return;

   /**
   * Localisation
   */
   load_plugin_textdomain('wc-tech-netpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
   
   /**
   * NetPay Payment Gateway class
   */
   class WC_Tech_Netpay extends WC_Payment_Gateway 
   {
      protected $msg = array();
      
      public function __construct(){
		
			$this->method = 'AES-128-CBC'; // Encryption method, IT SHOULD NOT BE CHANGED
		
			$this->id               = 'netpay';
			$this->method_title     = __('NetPay', 'tech');
			$this->icon             = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
			$this->has_fields       = false;
			
			$this->init_form_fields();
			$this->init_settings();
			
			$this->title            = $this->settings['title'];
			$this->description      = $this->settings['description'];
			$this->merchant_id      = $this->settings['merchant_id'];
			$this->netpay_username  = $this->settings['netpay_username'];
			$this->netpay_password  = $this->settings['netpay_password'];
			$this->enc_key 			= $this->settings['netpay_encryption_key'];
			$this->enc_iv 			= $this->settings['netpay_encryption_iv'];
			$this->pay_method  	 	= $this->settings['netpay_payment_method'];
			$this->mode             = $this->settings['working_mode'];
			$this->success_message  = $this->settings['success_message'];
			$this->failed_message   = $this->settings['failed_message'];
			$this->liveurl          = 'https://hosted.revolution.netpay.co.uk/v1/gateway/payment';
			$this->testurl          = 'https://hostedtest.revolution.netpay.co.uk/v1/gateway/payment';
			$this->msg['message']   = "";
			$this->msg['class']     = "";
			
			add_action('init', array(&$this, 'check_netpay_response'));
			//update for woocommerce >2.0
			add_action( 'woocommerce_api_wc_tech_autho' , array( $this, 'check_netpay_response' ) );
			//add_action( 'woocommerce_api_wc_tech_netpay' , array( $this, 'check_netpay_response' ) );
			//add_action('valid-authorize-request', array(&$this, 'successful_request'));
			add_action('valid-netpay-request', array(&$this, 'successful_request'));
         
         	if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
            	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
          	} else {
             	add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
         	}

			//add_action('woocommerce_receipt_authorize', array(&$this, 'receipt_page'));
			//add_action('woocommerce_thankyou_authorize',array(&$this, 'thankyou_page'));
			add_action('woocommerce_receipt_netpay', array(&$this, 'receipt_page'));
			add_action('woocommerce_thankyou_netpay',array(&$this, 'thankyou_page'));
      }

      function init_form_fields()
      {

         $this->form_fields = array(
            'enabled'      => array(
                  'title'        => __('Enable/Disable', 'tech'),
                  'type'         => 'checkbox',
                  'label'        => __('Enable NetPay Payment Module.', 'tech'),
                  'default'      => 'no'),
            'title'        => array(
                  'title'        => __('Title:', 'tech'),
                  'type'         => 'text',
                  'description'  => __('This controls the title which the user sees during checkout.', 'tech'),
                  'default'      => __('NetPay', 'tech')),
            'description'  => array(
                  'title'        => __('Description:', 'tech'),
                  'type'         => 'textarea',
                  'description'  => __('This controls the description which the user sees during checkout.', 'tech'),
                  'default'      => __('Pay securely by Credit or Debit Card through NetPay Secure Servers.', 'tech')),
            'merchant_id'     => array(
                  'title'        => __('Merchant ID', 'tech'),
                  'type'         => 'text',
                  'description'  => __('This is your merchant account ID')),
            'netpay_username' => array(
                  'title'        => __('Username', 'tech'),
                  'type'         => 'text',
                  'description'  =>  __('This is your integration Netpay API Username to authenticate your request', 'tech')),
			'netpay_password' => array(
                  'title'        => __('Password', 'tech'),
                  'type'         => 'text',
                  'description'  =>  __('This is your integration Netpay API Password to authenticate your request', 'tech')),
            'netpay_encryption_key' => array(
                  'title'        => __('Encryption Key', 'tech'),
                  'type'         => 'text',
                  'description'  =>  __('This is your Encryption Key to encrypt your form data before posting to NetPay’s Server', 'tech')),
			'netpay_encryption_iv' => array(
                  'title'        => __('Encryption IV', 'tech'),
                  'type'         => 'text',
                  'description'  =>  __('This is your Encryption Initialising Vector (IV). It is used with your Encryption Key to encrypt the form data', 'tech')),
			'netpay_payment_method'  => array(
                  'title'        => __('Payment Method', 'tech'),
                  'type'         => 'select',
                  'options'      => array('pay_form_method'=>'Hosted Payment Form', 'pay_api_method'=>'API Method'),
                  'description'  => "Payment method either Hosted Payment Form or API." ),
            'working_mode'    => array(
                  'title'        => __('Payment Mode'),
                  'type'         => 'select',
            	  'options'      => array('live'=>'Live Mode', 'test'=>'Test/Sandbox Mode'),
                  'description'  => "Live/Test Mode" ),
			'success_message' => array(
                  'title'        => __('Transaction Success Message', 'tech'),
                  'type'         => 'textarea',
                  'description'=>  __('Message to be displayed on successful transaction.', 'tech'),
                  'default'      => __('Your payment has been procssed successfully.', 'tech')),
            'failed_message'  => array(
                  'title'        => __('Transaction Failed Message', 'tech'),
                  'type'         => 'textarea',
                  'description'  =>  __('Message to be displayed on failed transaction.', 'tech'),
                  'default'      => __('Your transaction has been declined.', 'tech'))
         );
      }
      
      /**
       * Admin Panel Options
       * - Options for bits like 'title' and availability on a country-by-country basis
      **/
	public function admin_options(){
		echo '<h3>'.__('NetPay Payment Gateway', 'tech').'</h3>';
		echo '<p>'.__('NetPay is most popular payment gateway for online payment processing').'</p>';
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}
      
	/* Returns url */
	public function form_submit_url() {
		if($this->mode == 'live')
			return $this->liveurl;
		else
			return $this->testurl;
	}
	
	/* Returns Operation Mode */
	public function operation_mode() {
		if($this->mode == 'live')
			return '1';
		else
			return '2';
	}
	
	/*	MCRYPT DECRYPTION  MODE CBC */
	public function mcrypt_decrypt_cbc($input, $key, $iv) {
		$decrypted= mcrypt_decrypt(MCRYPT_RIJNDAEL_128, pack('H*', $key), pack('H*', $input), MCRYPT_MODE_CBC, pack('H*', $iv));
		
		return $this->remove_pkcs5_padding($decrypted);
	}
	
	/* REMOVE PKCS5 PADDING */
	public function remove_pkcs5_padding($decrypted) { 
		$dec_s = strlen($decrypted); 
		$padding = ord($decrypted[$dec_s-1]); 
		$decrypted = substr($decrypted, 0, -$padding);

		return $decrypted;
	}
	
	/* Add timestamp to transaction id */
	public function create_unique_transaction_id($transaction_id) {
		return strtolower($transaction_id) . time();
	}

	/* Create token with combination of merchant_id, timestamp and transaction_id */
	public function create_unique_session_token($merchant_id, $transaction_id) {
		return strtolower($merchant_id) . time() . strtolower($transaction_id);
	}
	
	/* ADD PKCS5 PADDING */
	public function add_pkcs5_padding($text, $blocksize) { 
		$pad = $blocksize - (strlen($text) % $blocksize); 
		return $text . str_repeat(chr($pad), $pad); 
	}
	
	/*	MCRYPT ENCRYPTION MODE CBC */
	public function mcrypt_encrypt_cbc($input, $key, $iv) {
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC); 
		$input = $this->add_pkcs5_padding($input, $size); 
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, ''); 

		mcrypt_generic_init($td, pack('H*',$key), pack('H*', $iv)); 
		$data = mcrypt_generic($td, $input); 
		mcrypt_generic_deinit($td); 
		mcrypt_module_close($td); 
		$data = bin2hex($data);

		return $data; 
	}
	
	public function parse_response_url($response) {
		preg_match_all('/\{([^}]*)\}/', $response, $matches);

		$parsed_url = array();
		foreach($matches[1] as $match) {
		    list($key, $value) = explode('|', $match);
		    $parsed_url[$key] = $value;
		}

		return $parsed_url;
	}
	  
      /**
      *  There are no payment fields for NetPay, but want to show the description if set.
      **/
      function payment_fields()
      {
         if ( $this->description ) 
            echo wpautop(wptexturize($this->description))."<br>";
			
			if($this->pay_method == 'pay_api_method'){
		   ?>
		   			<fieldset>
              			<div id="inspire-new-info">
              				<!-- Credit card number -->
                    		<p class="form-row form-row-first">
								<label for="ccnum"><?php echo __( 'Credit Card number', 'woocommerce' ) ?> <span class="required">*</span></label>
								<input type="text" class="input-text" id="ccnum" name="ccnum" maxlength="16" />
                    		</p>
							<!-- Credit card type -->
                    		<p class="form-row form-row-last">
                      			<label for="cardtype"><?php echo __( 'Card type', 'woocommerce' ) ?> <span class="required">*</span></label>
                      			<select name="cardtype" id="cardtype" class="woocommerce-select">
                  					<option value="Visa"><?php _e( 'Visa', 'woocommerce' ) ?></option>
									<option value="MasterCard"><?php _e( 'MasterCard', 'woocommerce' ) ?></option>
									<option value="Discover"><?php _e( 'Discover', 'woocommerce' ) ?></option>
									<option value="American Express"><?php _e( 'American Express', 'woocommerce' ) ?></option>
                       			</select>
                    		</p>
							<div class="clear"></div>
							
							<!-- Credit card expiration -->
                    		<p class="form-row form-row-first">
                      			<label for="cc-expire-month"><?php echo __( 'Expiration date', 'woocommerce') ?> <span class="required">*</span></label>
                      			<select name="expmonth" id="expmonth" class="woocommerce-select woocommerce-cc-month">
                        			<option value=""><?php _e( 'Month', 'woocommerce' ) ?></option>
									<?php
				                       	$months = array();
				                       	for ( $i = 1; $i <= 12; $i ++ ) {
				                         	$timestamp = mktime( 0, 0, 0, $i, 1 );
				                         	$months[ date( 'n', $timestamp ) ] = date( 'F', $timestamp );
				                       	}
				                       	foreach ( $months as $num => $name ) {
				                        	printf( '<option value="%u">%s</option>', $num, $name );
				                        } 
									?>
                      			</select>
                      			<select name="expyear" id="expyear" class="woocommerce-select woocommerce-cc-year">
                        			<option value=""><?php _e( 'Year', 'woocommerce' ) ?></option>
									<?php
				                       	$years = array();
				                        for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
				                          printf( '<option value="20%u">20%u</option>', $i, $i );
				                        } 
									?>
                      			</select>
                    		</p>
							<?php
								// Credit card security code
				            ?>
				                <p class="form-row form-row-last">
				                   	<label for="cvv"><?php _e( 'Card security code', 'woocommerce' ) ?> <span class="required">*</span></label>
				                    <input oninput="validate_cvv(this.value)" type="text" class="input-text" id="cvv" name="cvv" maxlength="4" style="width:45px" />
				                    <span class="help"><br><?php _e( '3 or 4 digits usually found on the signature strip.', 'woocommerce' ) ?></span>
								</p>
								
							<?php
				            
			                    // Option to store credit card data
			                    if ( $this->saveinfo == 'yes' && ! ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) ) { ?>
			                      	<div style="clear: both;"></div>
										<p>
			                        		<label for="saveinfo"><?php _e( 'Save this billing method?', 'woocommerce' ) ?></label>
			                        		<input type="checkbox" class="input-checkbox" id="saveinfo" name="saveinfo" />
			                        		<span class="help"><?php _e( 'Select to store your billing information for future use.', 'woocommerce' ) ?></span>
			                      		</p>
							<?php  } ?>
						</div>
            		</fieldset> 
		   <?php 
		   }
      }
       
      public function thankyou_page($order_id) 
      {
        
      }
      /**
      * Receipt Page
      **/ 
      function receipt_page($order)
      { 
         echo '<p>'.__('Thank you for your order, please click the button below to pay with NetPay.', 'tech').'</p>';
         echo $this->generate_netpay_form($order);
      }
      
      /**
       * Process the payment and return the result
      **/
	function process_payment($order_id){
	  	if($this->pay_method == 'pay_api_method'){
			global $woocommerce;
	
			$order = new WC_Order( $order_id );
			
			// Convert CC expiration date from (M)M-YYYY to MMYY
			$expmonth = $this->get_post( 'expmonth' );
			if ( $expmonth < 10 ) $expmonth = '0' . $expmonth;
			$expyear = substr( $this->get_post( 'expyear' ), -2 );
			
			// Full request, new customer or new information
			$base_request = array (
									'ccnumber' 	=> $this->get_post( 'ccnum' ),
									'cvv' 		=> $this->get_post( 'cvv' ),
									'ccexp' 		=> $expmonth . $expyear,
									'firstname'   => $order->billing_first_name,
									'lastname' 	=> $order->billing_last_name,
									'address1' 	=> $order->billing_address_1,
									'city' 	    => $order->billing_city,
									'state' 		=> $order->billing_state,
									'zip' 		=> $order->billing_postcode,
									'country' 	=> $order->billing_country,
									'phone' 		=> $order->billing_phone,
									'email'       => $order->billing_email,
							);

			// Add transaction-specific details to the request
			$transaction_details = array (
				'username'  => $this->netpay_username,
				'password'  => $this->netpay_password,
				'amount' 	=> $order->order_total,
				'type' 		=> $this->salemethod,
				'payment' 	=> 'creditcard',
				'orderid' 	=> $order->id,
				'ipaddress' => $_SERVER['REMOTE_ADDR'],
			);
	
			// Send request and get response from server
			$response = $this->post_and_get_response( array_merge( $base_request, $transaction_details ) );
	
			// Check response
			if ( $response['response'] == 1 ) {
				// Success
				$order->add_order_note( __( 'Inspire Commerce payment completed. Transaction ID: ' , 'woocommerce' ) . $response['transactionid'] );
				$order->payment_complete();
	
				if ( $this->get_post( 'inspire-use-stored-payment-info' ) == 'yes' ) {
					if ( $this->is_subscription( $order ) ) {
						// Store payment method number for future subscription payments
						update_post_meta( $order->id, 'payment_method_number', $this->get_post( 'inspire-payment-method' ) );
					}
				} else if ( $this->get_post( 'saveinfo' ) || $this->is_subscription( $order ) ) {
					// Store the payment method number/customer vault ID translation table in the user's metadata
					$customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );
					$customer_vault_ids[] = $new_customer_vault_id;
					update_user_meta( $user->ID, 'customer_vault_ids', $customer_vault_ids );
	
					if ( $this->is_subscription( $order ) ) {
						// Store payment method number for future subscription payments
						update_post_meta( $order->id, 'payment_method_number', count( $customer_vault_ids ) - 1 );
					}
				}
	
				// Return thank you redirect
				return array (
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
	
			} else if ( $response['response'] == 2 ) {
				// Decline
				$order->add_order_note( __( 'Inspire Commerce payment failed. Payment declined.', 'woocommerce' ) );
				$woocommerce->add_error( __( 'Sorry, the transaction was declined.', 'woocommerce' ) );
	
			} else if ( $response['response'] == 3 ) {
				// Other transaction error
				$order->add_order_note( __( 'Inspire Commerce payment failed. Error: ', 'woocommerce' ) . $response['responsetext'] );
				$woocommerce->add_error( __( 'Sorry, there was an error: ', 'woocommerce' ) . $response['responsetext'] );
	
			} else {
				// No response or unexpected response
				$order->add_order_note( __( "Inspire Commerce payment failed. Couldn't connect to gateway server.", 'woocommerce' ) );
				$woocommerce->add_error( __( 'No response from payment gateway server. Try again later or contact the site administrator.', 'woocommerce' ) );
	
			}

		} else {
			$order = new WC_Order($order_id);
			return array('result'   => 'success',
						 'redirect'  => add_query_arg('order',
										$order->id, 
										add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
			);
		}
	}
      
    /**
     * Check for valid NetPay server callback to validate the transaction response.
    **/
	function check_netpay_response()
    {
	  	global $woocommerce;
        
		$response = $_REQUEST['response'];
		$response_array = $this->parse_response_url($decrypted_response_url_str);
		echo "Response: ".$response;exit;
         if ( count($_POST) ){
         
            $redirect_url = '';
            $this->msg['class']     = 'error';
            $this->msg['message']   = $this->failed_message;

            if ( $_POST['x_response_code'] != '' ){
               try{
               
                  $order            = new WC_Order($_POST['x_invoice_num']);
                  $amount           = $_POST['x_amount'];
                  $hash             = $_POST['x_MD5_Hash'];
                  $transauthorised  = false;
                     
                  if ( $order->status != 'completed'){
                     
                     if ( $_POST['x_response_code'] == 1 ){
                        $transauthorised        = true;
                        $this->msg['message']   = $this->success_message;
                        $this->msg['class']     = 'success';
                        
                        if ( $order->status == 'processing' ){
                           
                        }
                        else{
                            $order->payment_complete();
                            $order->add_order_note('Autorize.net payment successful<br/>Ref Number/Transaction ID: '.$_REQUEST['x_trans_id']);
                            $order->add_order_note($this->msg['message']);
                            $woocommerce->cart->empty_cart();

                        }
                     }
                     else{
                        $this->msg['class'] = 'error';
                        $this->msg['message'] = $this->failed_message;
                        $order->add_order_note($this->msg['message']);
                        $order->update_status('failed');
                        //extra code can be added here such as sending an email to customer on transaction fail
                     }
                  }
                  if ( $transauthorised==false ){
                    $order->update_status('failed');
                    $order->add_order_note($this->msg['message']);
                  }

               }
               catch(Exception $e){
                         // $errorOccurred = true;
                         $msg = "Error";

               }

            }
            $redirect_url =  add_query_arg('order',
                                                  $order->id, 
                                                  add_query_arg('key', $order->order_key, 
                                                  get_permalink(get_option('woocommerce_thanks_page_id'))));
            $this->web_redirect( $redirect_url); exit;
         }
         else{
            
            $redirect_url =  add_query_arg('order',
                                                  $order->id, 
                                                  add_query_arg('key', $order->order_key, 
                                                  get_permalink(get_option('woocommerce_thanks_page_id'))));
            $this->web_redirect($redirect_url.'?msg=Unknown_error_occured');
            exit;
         }
      }
      
      
      public function web_redirect($url){
      
         echo "<html><head><script language=\"javascript\">
                <!--
                window.location=\"{$url}\";
                //-->
                </script>
                </head><body><noscript><meta http-equiv=\"refresh\" content=\"0;url={$url}\"></noscript></body></html>";
      
      }
      /**
      * Generate NetPay button link
      **/
      public function generate_netpay_form($order_id)
      {
         global $woocommerce;
         
         $order      = new WC_Order($order_id);
         $sequence   = rand(1, 1000);
         $timeStamp  = time();

         if( phpversion() >= '5.1.2' ) { 
            $fingerprint = hash_hmac("md5", $this->login . "^" . $sequence . "^" . $timeStamp . "^" . $order->order_total . "^", $this->transaction_key); }
         else { 
            $fingerprint = bin2hex(mhash(MHASH_MD5,  $this->login . "^" . $sequence . "^" . $timeStamp . "^" . $order->order_total . "^", $this->transaction_key)); 
         }
         $redirect_url = (get_option('woocommerce_thanks_page_id') != '' ) ? get_permalink(get_option('woocommerce_thanks_page_id')): get_site_url().'/' ;
         $relay_url = add_query_arg( array('wc-api' => get_class( $this ) ,'order_id' => $order_id ), $redirect_url );
      
	   // echo "<pre>";print_r($order);exit;
		
		$orderDescr = "New order with order id ".$order_id." and amount ".$currency." ".$order->order_total." has been placed.";
		$respUrl = $this->mcrypt_encrypt_cbc('http://192.168.0.128/wpnetpay/checkout/order-received/wc-api=WC_Tech_Netpay&order_id='.$order_id,$this->enc_key,$this->enc_iv);
		//$respUrl = $this->mcrypt_encrypt_cbc('http://192.168.0.128/wp_netpay/wp-content/plugins/netpay-payment-gateway-for-woocommerce/index.php',$this->enc_key,$this->enc_iv);
		
		$netpay_args = array(
            'merchant_id'		=> 	$this->merchant_id,
            'username'          => 	$this->mcrypt_encrypt_cbc($this->netpay_username,$this->enc_key,$this->enc_iv),
            'password'          => 	$this->mcrypt_encrypt_cbc($this->netpay_password,$this->enc_key,$this->enc_iv),
            'operation_mode'    => 	$this->mcrypt_encrypt_cbc($this->operation_mode(),$this->enc_key,$this->enc_iv),
            'session_token'     => 	$this->mcrypt_encrypt_cbc($this->create_unique_session_token($this->merchant_id,$order_id),$this->enc_key,$this->enc_iv),
            'description'       => 	$this->mcrypt_encrypt_cbc($orderDescr,$this->enc_key,$this->enc_iv),
            'amount'            => 	$this->mcrypt_encrypt_cbc($order->order_total,$this->enc_key,$this->enc_iv),
            'currency'          => 	$this->mcrypt_encrypt_cbc(get_woocommerce_currency(),$this->enc_key,$this->enc_iv),
            'transaction_id'    => 	$this->mcrypt_encrypt_cbc($this->create_unique_transaction_id($order_id),$this->enc_key,$this->enc_iv),
            'response_url'      => 	$respUrl
		);
			
			
		$browserName = $this->getBrowser();
		
		$netpay_info_args = array(
			'bill_to_company'	=> 	$order->billing_company,
			'bill_to_address'	=> 	$order->billing_address_1.' '.$order->billing_address_2,
			'bill_to_town_city'	=> 	$order->billing_city,
			'bill_to_county'	=> 	$order->billing_state,
			'bill_to_postcode'	=> 	$order->billing_postcode,
			'bill_to_country'	=> 	$this->getValidCountryCode($order->billing_country),
			'customer_email'	=> 	$order->billing_email,
			'customer_phone'	=> 	$order->billing_phone,
			
			'ship_to_firstname'	=> 	$order->shipping_first_name,
			'ship_to_lastname'	=> 	$order->shipping_last_name,
			'ship_to_fullname'	=> 	$order->shipping_first_name.' '.$order->shipping_last_name,
			'ship_to_company'	=> 	$order->shipping_company,
			'ship_to_address'	=> 	$order->shipping_address_1.' '.$order->shipping_address_2,
			'ship_to_town_city'	=> 	$order->shipping_city,
			'ship_to_county'	=> 	$order->shipping_state,
			'ship_to_country'	=> 	$this->getValidCountryCode($order->shipping_country),
			'ship_to_postcode'	=> 	$order->shipping_postcode,
			'ship_to_method'	=> 	$order->shipping_method,
			
			'customer_ip_address'	=> 	$_SERVER['REMOTE_ADDR'],
			'customer_hostname'	=> 	$_SERVER['HTTP_HOST'],
			'customer_browser'	=> 	$browserName['name']
		);
		//echo "<pre>";print_r($netpay_info_args);exit;
		//echo $this->operation_mode();exit;
        $netpay_args_array = array();
		
		foreach($netpay_args as $key => $value){
        	$netpay_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
		}
		
		foreach($netpay_info_args as $key => $value){
			if($value != ''){
        		//$netpay_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
				$enc_value = $this->mcrypt_encrypt_cbc($value,$this->enc_key,$this->enc_iv);
				$netpay_args_array[] =  "<input type='hidden' name='$key' value='$enc_value'/>";
			}
		}
/*echo "<pre>";print_r($order);
echo "<pre>";print_r($netpay_info_args);
exit;*/
        if($this->mode == 'true'){
           	$processURI = $this->testurl;
        }
        else if($this->mode == 'powerpay'){
           	$processURI = $this->powerpay;
        }
         else{
             $processURI = $this->liveurl;
         }
         
         $html_form    = '<form action="'.$this->form_submit_url().'" method="post" id="netpay_payment_form">' 
               . implode('', $netpay_args_array) 
               . '<input type="submit" class="button" id="submit_netpay_payment_form" value="'.__('Pay via NetPay', 'tech').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'tech').'</a>'
               . '<script type="text/javascript">
                  jQuery(function(){
                     jQuery("body").block({
                           message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to NetPay to make payment.', 'tech').'",
                           overlayCSS:
                        {
                           background:       "#ccc",
                           opacity:          0.6,
                           "z-index": "99999999999999999999999999999999"
                        },
                     css: {
                           padding:          20,
                           textAlign:        "center",
                           color:            "#555",
                           border:           "3px solid #aaa",
                           backgroundColor:  "#fff",
                           cursor:           "wait",
                           lineHeight:       "32px",
                           "z-index": "999999999999999999999999999999999"
                     }
                     });
                  jQuery("#submit_netpay_payment_form").click();
               });
               </script>
               </form>';

         return $html_form;
      }

		/**
    	 * Send the payment data to the gateway server and return the response.
    	*/
    
		function post_and_get_response( $request ) {
      		global $woocommerce;
			echo "<pre>";print_r($request);exit;
      		// Encode request
      		$post = http_build_query( $request, '', '&' );

			// Send request
			$content = wp_remote_post( GATEWAY_URL, array(
															'body'  => $post,
															'timeout' => 45,
															'redirection' => 5,
															'httpversion' => '1.0',
															'blocking' => true,
															'headers' => array(),
															'cookies' => array(),
															'ssl_verify' => false
													)
			);

      		// Quit if it didn't work
      		if ( is_wp_error( $content ) ) {
        		$woocommerce->add_error( __( 'Problem connecting to server at ', 'woocommerce' ) . GATEWAY_URL . ' ( ' . $content->get_error_message() . ' )' );
        		return null;
      		}

			// Convert response string to array
			$vars = explode( '&', $content['body'] );
			foreach ( $vars as $key => $val ) {
				$var = explode( '=', $val );
				$data[ $var[0] ] = $var[1];
			}

      		// Return response array
      		return $data;

		}
		
		function get_post( $name ) {
			if ( isset( $_POST[ $name ] ) ) {
				return $_POST[ $name ];
			}
			return null;
		}
		
		
		 function getBrowser()
		 {
		  $u_agent = $_SERVER['HTTP_USER_AGENT'];
		  $bname = 'Unknown';
		  $platform = 'Unknown';
		  $version= "";
		 
		  //First get the platform?
		  if (preg_match('/linux/i', $u_agent)) {
		   $platform = 'linux';
		  }
		  elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
		   $platform = 'mac';
		  }
		  elseif (preg_match('/windows|win32/i', $u_agent)) {
		   $platform = 'windows';
		  }
		 
		  // Next get the name of the useragent yes seperately and for good reason
		  if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
		  {
		   $bname = 'Internet Explorer';
		   $ub = "MSIE";
		  }
		  elseif(preg_match('/Firefox/i',$u_agent))
		  {
		   $bname = 'Mozilla Firefox';
		   $ub = "Firefox";
		  }
		  elseif(preg_match('/Chrome/i',$u_agent))
		  {
		   $bname = 'Google Chrome';
		   $ub = "Chrome";
		  }
		  elseif(preg_match('/Safari/i',$u_agent))
		  {
		   $bname = 'Apple Safari';
		   $ub = "Safari";
		  }
		  elseif(preg_match('/Opera/i',$u_agent))
		  {
		   $bname = 'Opera';
		   $ub = "Opera";
		  }
		  elseif(preg_match('/Netscape/i',$u_agent))
		  {
		   $bname = 'Netscape';
		   $ub = "Netscape";
		  }
		 
		  // finally get the correct version number
		  $known = array('Version', $ub, 'other');
		  $pattern = '#(?<browser>' . join('|', $known) .
		  ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		  if (!preg_match_all($pattern, $u_agent, $matches)) {
		   // we have no matching number just continue
		  }
		 
		  // see how many we have
		  $i = count($matches['browser']);
		  if ($i != 1) {
		   //we will have two since we are not using 'other' argument yet
		   //see if version is before or after the name
		   if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
			$version= $matches['version'][0];
		   }
		   else {
			$version= $matches['version'][1];
		   }
		  }
		  else {
		   $version= $matches['version'][0];
		  }
		 
		  // check if we have a number
		  if ($version==null || $version=="") {$version="?";}
		 
		  return array(
		   'userAgent' => $u_agent,
		   'name'      => $bname,
		   'version'   => $version,
		   'platform'  => $platform,
		   'pattern'    => $pattern
		  );
		 }
		 
		function getValidCountryCode($code){
		 	$countries = array(
				'AF' => 'AFG',
				'AL' => 'ALB',
				'DZ' => 'DZA',
				'AD' => 'AND',
				'AO' => 'AGO',
				'AI' => 'AIA',
				'AQ' => 'ATA',
				'AG' => 'ATG',
				'AR' => 'ARG',
				'AM' => 'ARM',
				'AW' => 'ABW',
				'AU' => 'AUS',
				'AT' => 'AUT',
				'AZ' => 'AZE',
				'BS' => 'BHS',
				'BH' => 'BHR',
				'BD' => 'BGD',
				'BB' => 'BRB',
				'BY' => 'BLR',
				'BE' => 'BEL',
				'BZ' => 'BLZ',
				'BJ' => 'BEN',
				'BM' => 'BMU',
				'BT' => 'BTN',
				'BO' => 'BOL',
				'BA' => 'BIH',
				'BW' => 'BWA',
				'BV' => 'BVT',
				'BR' => 'BRA',
				'IO' => 'IOT',
				'VG' => 'VGB',
				'BN' => 'BRN',
				'BG' => 'BGR',
				'BF' => 'BFA',
				'BI' => 'BDI',
				'KH' => 'KHM',
				'CM' => 'CMR',
				'CA' => 'CAN',
				'CV' => 'CPV',
				'KY' => 'CYM',
				'CF' => 'CAF',
				'TD' => 'TCD',
				'CL' => 'CHL',
				'CN' => 'CHN',
				'CX' => 'CXR',
				'CC' => 'CCK',
				'CO' => 'COL',
				'KM' => 'COM',
				'CG' => 'COG',
				'CD' => 'COD',
				'CK' => 'COK',
				'CR' => 'CRI',
				'HR' => 'HRV',
				'CU' => 'CUB',
				'CY' => 'CYP',
				'CZ' => 'CZE',
				'DK' => 'DNK',
				'DJ' => 'DJI',
				'DM' => 'DMA',
				'DO' => 'DOM',
				'EC' => 'ECU',
				'EG' => 'EGY',
				'SV' => 'SLV',
				'GQ' => 'GNQ',
				'ER' => 'ERI',
				'EE' => 'EST',
				'ET' => 'ETH ',
				'FK' => 'FLK',
				'FO' => 'FRO',
				'FJ' => 'FJI',
				'FI' => 'FIN',
				'FR' => 'FRA',
				'GF' => 'GUF',
				'PF' => 'PYF',
				'TF' => 'ATF',
				'GA' => 'GAB',
				'GM' => 'GMB',
				'GE' => 'GEO',
				'DE' => 'DEU',
				'GH' => 'GHA',
				'GI' => 'GIB',
				'GR' => 'GRC',
				'GL' => 'GRL',
				'GD' => 'GRD',
				'GP' => 'GLP',
				'GT' => 'GTM',
				'GN' => 'GIN',
				'GW' => 'GNB',
				'GY' => 'GUY',
				'HT' => 'HTI',
				'HM' => 'HMD',
				'HN' => 'VAT',
				'HK' => 'HKG',
				'HU' => 'HUN',
				'IS' => 'ISL',
				'IN' => 'IND',
				'ID' => 'IDN',
				'IR' => 'IRN',
				'IQ' => 'IRQ',
				'IE' => 'IRL',
				'IL' => 'ISR',
				'IT' => 'ITA',
				'CI' => 'CIV',
				'JM' => 'JAM',
				'JP' => 'JPN',
				'JO' => 'JOR',
				'KZ' => 'KAZ',
				'KE' => 'KEN',
				'KI' => 'KIR',
				'KW' => 'KWT',
				'KG' => 'KGZ',
				'LA' => 'LAO',
				'LV' => 'LVA',
				'LB' => 'LBN',
				'LS' => 'LSO',
				'LR' => 'LBR',
				'LY' => 'LBY',
				'LI' => 'LIE',
				'LT' => 'LTU',
				'LU' => 'LUX',
				'MO' => 'MAC',
				'MK' => 'MKD',
				'MG' => 'MDG',
				'MW' => 'MWI',
				'MY' => 'MYS',
				'MV' => 'MDV',
				'ML' => 'MLI',
				'MT' => 'MLT',
				'MH' => 'MHL',
				'MQ' => 'MTQ',
				'MR' => 'MRT',
				'MU' => 'MUS',
				'YT' => 'MYT',
				'MX' => 'MEX',
				'FM' => 'FSM',
				'MD' => 'MDA',
				'MC' => 'MCO',
				'MN' => 'MNG',
				'ME' => 'MNE',
				'MS' => 'MSR',
				'MA' => 'MAR',
				'MZ' => 'MOZ',
				'MM' => 'MMR',
				'NA' => 'NAM',
				'NR' => 'NRU',
				'NP' => 'NPL',
				'NL' => 'NLD',
				'AN' => 'ANT',
				'NC' => 'NCL',
				'NZ' => 'NZL',
				'NI' => 'NIC',
				'NE' => 'NER',
				'NG' => 'NGA',
				'NU' => 'NIU',
				'NF' => 'NFK',
				'KP' => 'PRK',
				'NO' => 'NOR',
				'OM' => 'OMN',
				'PK' => 'PAK',
				'PS' => 'PSE',
				'PA' => 'PAN',
				'PG' => 'PNG',
				'PY' => 'PRY',
				'PE' => 'PER',
				'PH' => 'PHL',
				'PN' => 'PCN',
				'PL' => 'POL',
				'PT' => 'PRT',
				'QA' => 'QAT',
				'RE' => 'REU',
				'RO' => 'ROM',
				'RU' => 'RUS',
				'RW' => 'RWA',
				'BL' => 'BLM',
				'SH' => 'SHN',
				'KN' => 'KNA',
				'LC' => 'LCA',
				'MF' => 'MAF',
				'PM' => 'SPM',
				'VC' => 'VCT',
				'SM' => 'SMR',
				'ST' => 'STP',
				'SA' => 'SAU',
				'SN' => 'SEN',
				'RS' => 'SRB',
				'SC' => 'SYC',
				'SL' => 'SLE',
				'SG' => 'SGP',
				'SK' => 'SVK',
				'SI' => 'SVN',
				'SB' => 'SLB',
				'SO' => 'SOM',
				'ZA' => 'ZAF',
				'GS' => 'SGS',
				'KR' => 'KOR',
				'SS' => 'SSD',
				'ES' => 'ESP',
				'LK' => 'LKA',
				'SD' => 'SDN',
				'SR' => 'SUR',
				'SJ' => 'SJM',
				'SZ' => 'SWZ',
				'SE' => 'SWE',
				'CH' => 'CHE',
				'SY' => 'SYR',
				'TW' => 'TWN',
				'TJ' => 'TJK',
				'TZ' => 'TZA',
				'TH' => 'THA',
				'TL' => 'TLS',
				'TG' => 'TGO',
				'TK' => 'TKL',
				'TO' => 'TON',
				'TT' => 'TTO',
				'TN' => 'TUN',
				'TR' => 'TUR',
				'TM' => 'TKM',
				'TC' => 'TCA',
				'TV' => 'TUV',
				'UG' => 'UGA',
				'UA' => 'UKR',
				'AE' => 'ARE',
				'GB' => 'GBR',
				'US' => 'USA',
				'UY' => 'URY',
				'UZ' => 'UZB',
				'VU' => 'VUT',
				'VA' => 'VAT',
				'VE' => 'VEN',
				'VN' => 'VNM',
				'WF' => 'WLF',
				'EH' => 'ESH',
				'WS' => 'WSM',
				'YE' => 'YEM',
				'ZM' => 'ZMB',
				'ZW' => 'ZWE',
				'PW' => 'PLW',
				'BQ' => 'BES',
				'CW' => 'CUW',
				'GG' => 'GGY',
				'IM' => 'IMN',
				'JE' => 'JEY',
				'SX' => 'SXM'
				);
				
			return $countries[$code];
				
		}
		 
   }

   /**
    * Add this Gateway to WooCommerce
   **/
   function woocommerce_add_tech_netpay_gateway($methods) 
   {
      $methods[] = 'WC_Tech_Netpay';
      return $methods;
   }

   add_filter('woocommerce_payment_gateways', 'woocommerce_add_tech_netpay_gateway' );
}

