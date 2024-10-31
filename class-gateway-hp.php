<?php
if(!defined('ABSPATH')){die('Do not open this file directly.');}
if( class_exists('WC_Payment_Gateway') && !class_exists('openbrain_gateway_hp') ){
	class openbrain_gateway_hp extends WC_Payment_Gateway{

		public function __construct()
		{
			$this->id = 'openbrain_gateway_hp';
			$this->method_title = __('OpenBrain Gateway', 'woocommerce');
			$this->method_description = __('OpenBrain payment gateway settings for WooCommerce', 'woocommerce');
			$this->icon = apply_filters('gateway_hp_logo', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png');
			$this->has_fields = false;
			$this->openbrain_init_form_fields();
			$this->init_settings();
			$this->server_url  = 'https://api.hillapay.ir/plugin/v1';
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->hillapay_api_key = $this->settings['hillapay_api_key'];
			$this->success_massage = $this->settings['success_massage'];
			$this->failed_massage = $this->settings['failed_massage'];
			$this->Debug_Mode = $this->settings['Debug_Mode'];
			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')){
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			}
			else
				add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
				add_action('woocommerce_receipt_' . $this->id . '', array($this, 'openbrain_send_to_gateway_hp'));
				add_action('woocommerce_api_' . strtolower(get_class($this)) . '', array($this, 'openbrain_gateway_return_from_gateway_hp'));
		}
		public function openbrain_gateway_admin_options()
		{
			parent::openbrain_gateway_admin_options();
		}
		public function openbrain_init_form_fields()
		{
			$this->form_fields = apply_filters('gateway_hp_Config', array(
					'base_confing' => array(
						'title' => __('Settings', 'woocommerce'),
						'type' => 'title',
						'description' => '',
					),
					'enabled' => array(
						'title' => __('Activation/Deactivation', 'woocommerce'),
						'type' => 'checkbox',
						'label' => __('HillaPay Activation', 'woocommerce'),
						'default' => 'yes',
						'desc_tip' => true,
					),
					'Debug_Mode' => array(
						'title' => __('Debug', 'woocommerce'),
						'type' => 'checkbox',
						'label' => __('Debug mode', 'woocommerce'),
						'description' => __('Check to enable debug mode.', 'woocommerce'),
						'default' => 'no',
						'desc_tip' => true
					),
					'title' => array(
						'title' => __('Title', 'woocommerce'),
						'type' => 'text',
						'default' => __('Payment By HillaPay', 'woocommerce'),
						'desc_tip' => true,
					),
					'description' => array(
						'title' => __('Description', 'woocommerce'),
						'type' => 'text',
						'desc_tip' => true,
						'default' => __('Payment all Shetab member cards by HillaPay Gateway', 'woocommerce')
					),
					'hillapay_api_key' => array(
						'title' => __('API KEY', 'woocommerce'),
						'type' => 'text',
						'description' => __('API Key', 'woocommerce'),
						'default' => '',
						'desc_tip' => true
					),
					'addtional_data' => array(
						'title' => __('Additional data', 'woocommerce'),
						'type' => 'text',
						'description' => __('Enter the data format to send additional data.', 'woocommerce'),
						'default' => __('{email}', 'woocommerce'),
					),
					'payment_confing' => array(
						'title' => __('Messages', 'woocommerce'),
						'type' => 'title',
						'description' => '',
					),
					'success_massage' => array(
						'title' => __('Successful Transaction message', 'woocommerce'),
						'type' => 'textarea',
						'description' => __('Enter the text of the message you want to display to the user after successful payment. You can also use the {transaction_id} shortcode to display the HillaPay tracking code.', 'woocommerce'),
						'default' => __('thank you . Your order has been successfully paid.', 'woocommerce'),
					),
					'failed_massage' => array(
						'title' => __('Unsuccessful Transaction message', 'woocommerce'),
						'type' => 'textarea',
						'description' => __('Enter the text of the message you want to display to the user after unsuccessful payment. You can also use the {fault} shortcode to show the reason for the error.', 'woocommerce'),
						'default' => __('Your payment has failed. Please try again or contact the site administrator in case of problems.', 'woocommerce'),
					),
					'dashboard_title' => array(
						'title' => __('OpenBrain gateway settings for WooCommerce'),
						'type' => 'title',
						'description' => '',
					),
                    'hillapay_link_panel' => array(
                        'description' => __('You can enter merchant panel using this link below.<br><a href = "https://panel.hillapay.ir/merchant?utm_source=plugin&utm_medium=link&utm_campaign=wordpressplugin&utm_id=wordpress_plugin" target = "_blank">https://panel.hillapay.ir/merchant</a>', 'woocommerce'),
                        'type' => 'title',
                    ),
                    'hillapay_link_site' => array(
                        'description' => __('HillaPay Web Site<br><a href = "https://hillapay.ir/?utm_source=plugin&utm_medium=link&utm_campaign=wordpressplugin&utm_id=wordpress_site" target = "_blank">https://hillapay.ir</a>', 'woocommerce'),
                        'type' => 'title',
                    ),
					'terminal_description' => array(
						'type' => 'title',
						'description' => 'OpenBrain gateway plugin for WooCommerce. Using this plugin, you can manage the payment process in your stroe(accepting all Shetab cards). To view the comprehensive reports of your payment gateway, you can refer to your merchant panel. HillaPay is a provider of smart payment services and has a payment assistance license from the Central Bank of the Islamic Republic of Iran (number 4568/p/98).',
					)
				)
			);
		}
		public function process_payment($order_id)
		{
			$order = new WC_Order($order_id);
			return array(
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url(true)
			);
		}
		public function openbrain_send_to_gateway_hp($order_id)
		{
			$transaction_id = get_post_meta($order_id, '_hillapay_payCode', true);
			if($transaction_id )
			{
				wp_redirect($this->server_url.'/pay?transaction_id='.$transaction_id );
				exit;
			}
			global $woocommerce;
			$woocommerce->session->order_id_hillapay = $order_id;
			$order = new WC_Order($order_id);
			$currency = $order->get_currency();
			$currency = apply_filters('gateway_hp_Currency', $currency, $order_id);
			$form = '<form action="" method="POST" class="hillapay-checkout-form" id="hillapay-checkout-form">
					<input type="submit" name="hillapay_submit" class="button alt" id="hillapay-payment-button" value="' . __('پرداخت', 'woocommerce') . '"/>
					<a class="button cancel" href="' . wc_get_checkout_url() . '">' . __('بازگشت', 'woocommerce') . '</a>
				 </form><br/>';

			$form = apply_filters('gateway_hp_Form', $form, $order_id, $woocommerce);
			do_action('gateway_hp_Gateway_Before_Form', $order_id, $woocommerce);
			//echo esc_js($form);
			do_action('gateway_hp_Gateway_After_Form', $order_id, $woocommerce);
			$Amount = intval( $order->get_total() );
			$Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
			$Amount = $this->openbrain_gateway_check_currency( $Amount, $currency);
			$Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency);
			$Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_irt', $Amount, $currency);
			$Amount = apply_filters('woocommerce_order_amount_total_gateway_hp', $Amount, $currency);
			$callback_url = add_query_arg('wc_order', $order_id, WC()->api_request_url('openbrain_gateway_hp'));
			$products = array();
			$order_items = $order->get_items();
			foreach ((array)$order_items as $product) 
			{
				$products[] = $product['name'] . ' (' . $product['qty'] . ') ';
			}
			$products = implode(' - ', $products);
			$Description = 'خرید به شماره سفارش : ' . $order->get_order_number() . ' | خریدار : ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . ' | محصولات : ' . $products;
			$gateway_description = 'خرید به شماره سفارش : ' . $order->get_order_number();

			$Mobile = get_post_meta($order_id, '_billing_phone', true) ? get_post_meta($order_id, '_billing_phone', true) : '-';
			$Email = $order->get_billing_email();
			$Paymenter = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$ResNumber = intval($order->get_order_number());
			$Description = apply_filters('gateway_hp_Description', $Description, $order_id);
			$Mobile = apply_filters('gateway_hp_Mobile', $Mobile, $order_id);
			$Email = apply_filters('gateway_hp_Email', $Email, $order_id);
			$Paymenter = apply_filters('gateway_hp_Paymenter', $Paymenter, $order_id);
			$ResNumber = apply_filters('gateway_hp_ResNumber', $ResNumber, $order_id);
			do_action('gateway_hp_Gateway_Payment', $order_id, $Description, $Mobile);
			$Email = !filter_var($Email, FILTER_VALIDATE_EMAIL) === false ? $Email : '';
			$Mobile = preg_match('/^09[0-9]{9}/i', $Mobile) ? $Mobile : '';
			$Debug_Mode = $this->Debug_Mode;
			$debug_text = "";
			if($Debug_Mode){$debug_text = "&debug=1";}
			$addtional_data = $this->addtional_data;
			$additional_data_text = "";
			if ( $Email == '' ){$payerIdentity = $Mobile;}
			else {$payerIdentity = $Email;}
			if($addtional_data == "mobile"){$additional_data_text = $Mobile;}
			$order_id = $order->get_order_number();
			$data = array(
				'amount' => $Amount,
				'mobile' => $Mobile,
				'additional_data'=> $additional_data_text,
				'callback' => $callback_url,
				'description' => $gateway_description,
				'order_id' => $order_id
			);
			$args = array(
				'body' => json_encode($data),
				'timeout' => '45',
				'redirection' => '5',
				'httpsversion' => '1.0',
				'blocking' => true,
				'headers' => array
				(
					'api-key' => $this->hillapay_api_key,
					'Content-Type'  => 'application/json',
					'Accept' => 'application/json'
				),
				'cookies' => array()
			);
			$api_url = apply_filters( 'gateway_hp_Gateway_Payment_api_url', $this->server_url . "/send?debug_text", $order_id);
			$api_args = apply_filters( 'gateway_hp_Gateway_Payment_api_args', $args, $order_id );
			$response_send = wp_remote_post($api_url, $api_args);
			if( is_wp_error($response_send) ){
				$Message = $response_send->get_error_message();
			}
			else
			{	
				$http_status_send = wp_remote_retrieve_response_code($response_send);
				if($http_status_send == 200)
				{
					$response_send_body = json_decode($response_send['body'], true);
					$status_send = $response_send_body["status"]['status'];
					if($status_send == "200")
					{
						$transaction_url = $response_send_body["result_transaction_send"]['transaction_url'];
						$transaction_id = sanitize_text_field($response_send_body["result_transaction_send"]['transaction_id']);
						$amount_wage = sanitize_text_field($response_send_body["result_transaction_send"]['amount']['wage']);
						$amount_merchant = sanitize_text_field($response_send_body["result_transaction_send"]['amount']['merchant']);

						update_post_meta($order_id, 'woo_hillapay_amount_wage', $amount_wage);
						update_post_meta($order_id, 'woo_hillapay_amount_merchant', $amount_merchant);

						$code_pay = wp_remote_retrieve_body($send_response);
						$code_pay =  json_decode($code_pay, true);
						update_post_meta($order_id, '_hillapay_payCode', $transaction_id);
						wp_redirect($transaction_url);
						exit;
					}
					if($status_send == "207")
					{
						$Message = 'تراکنش تکراری';
						$Fault = $Message;
						$order->update_status('failed', __('Duplicate Order ID.', 'wptut'));
					}
					else
					{
						$Message = 'تراکنش ناموفق';
						$Fault = $Message;
						$order->update_status('failed', __('Error.', 'wptut'));
					}
				}
				else
				{
						$Message = 'کد خطا: ' . $http_status_send;
						$Fault = $Message;
						$order->update_status('failed', __('Error.', 'wptut'));
				}
			}
			if(!empty($Message) && $Message)
			{

				$Note = sprintf(__('خطا در هنگام ارسال به بانک : %s', 'woocommerce'), $Message);
				$Fault = sprintf(__('خطا در هنگام ارسال به بانک : %s', 'woocommerce'), $Message);
				$Note = apply_filters('gateway_hp_Send_to_Gateway_Failed_Note', $Note, $order_id, $Fault);
				$order->add_order_note($Note);

				$Fault = sprintf(__('در هنگام اتصال به بانک خطای زیر رخ داده است : <br/>%s', 'woocommerce'), $Message);
				$Notice = sprintf(__('در هنگام اتصال به بانک خطای زیر رخ داده است : <br/>%s', 'woocommerce'), $Message);
				$Notice = apply_filters('gateway_hp_Send_to_Gateway_Failed_Notice', $Notice, $order_id, $Fault);
				if ($Notice)
					wc_add_notice($Notice, 'error');
				$Fault = $Notice;
				do_action('gateway_hp_Send_to_Gateway_Failed', $order_id, $Fault);
			}
		}
		public function openbrain_gateway_return_from_gateway_hp()
		{
			global $woocommerce;
			if(isset($_GET['wc_order'])){$order_id = esc_sql( $_GET['wc_order'] );}
			else
			{
				$order_id = $woocommerce->session->order_id_hillapay;
				unset( $woocommerce->session->order_id_hillapay );
			}
			$callback_status =  sanitize_text_field($_POST['status']['status']);
			if( isset( $_POST['result_transaction_callback']['transaction_id'] )){$transaction_id = esc_sql( $_POST['result_transaction_callback']['transaction_id'] );}
			else{$transaction_id = null;}
			$order_id = apply_filters('gateway_hp_return_order_id', $order_id);
			if(isset($order_id))
			{
				if( $transaction_id != null){update_post_meta($order_id, 'woo_hillapay_transaction_id', $transaction_id );}
				$transaction_id = get_post_meta($order_id, '_hillapay_payCode', true);
				$order = new WC_Order($order_id);
				$currency = $order->get_currency();
				$currency = apply_filters('gateway_hp_Currency', $currency, $order_id);
				if($order->status != 'completed')
				{
					if($callback_status == 400)
					{
						$rrn = sanitize_text_field($_POST['result_transaction_callback']['rrn']);
						update_post_meta($order_id, 'woo_hillapay_rrn', $rrn );
						$Amount = intval($order->order_total);
						$Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
						$Amount = $this->openbrain_gateway_check_currency( $Amount, $currency );
						$transaction_id = apply_filters('gateway_hp_return_refid', $transaction_id);
						$data = array('order_id' => $order_id, 'transaction_id' => $transaction_id, 'rrn' => $rrn);
						$args = array
						(
							'body' => json_encode($data),
							'timeout' => '45',
							'redirection' => '5',
							'httpsversion' => '1.0',
							'blocking' => true,
							'headers' => array
							(
								'api-key' => $this->hillapay_api_key,
								'Content-Type'  => 'application/json',
								'Accept' => 'application/json'
							),
							'cookies' => array()
						);
						$verify_api_url = apply_filters( 'gateway_hp_Gateway_Payment_verify_api_url', $this->server_url . '/verify?show_data=1', $order_id );
						$response_verify = wp_remote_post($verify_api_url, $args);
						$body = wp_remote_retrieve_body( $response_verify );
						WC_GPP_Debug_Log($this->Debug_Mode, $response_verify, "Verify");
						if( is_wp_error($response_verify) )
						{
							$Status = 'failed';
							$Fault = $response_verify->get_error_message();
							$Message = 'خطا در ارتباط به هیلاپی : شرح خطا '.$response_verify->get_error_message();
						}
						else
						{
							$code = wp_remote_retrieve_response_code( $response_verify );
							$txtmsg = $this->openbrain_gateway_status_message( $code );
							if( $code === 200 )
							{
								$response_verify_body = json_decode($response_verify['body'], true);
								$body_json = json_decode($body['body'], true);
								$verify_status =  $response_verify_body['status']['status'];
								if( $verify_status == 500)
								{
									$card = $response_verify_body['result_transaction_verify']['card'];
									update_post_meta($order_id, 'card', $card);
									$Status = 'completed';
									$Message = 'farid';									
								}
								elseif( $verify_status == 506)
								{
									$Status = 'Duplicate';
									$Message = 'Duplicate';
									$Fault = $Message;
								}
								else
								{
									$Status = 'failed';
									$Message = 'متاسفانه سامانه قادر به دریافت کد پیگیری نمی باشد! نتیجه درخواست : <br /> شماره خطا: '.$verify_status;
									$Fault = $Message;
								}
							}
							else
							{
								$Status = 'failed';
								$Message = $txtmsg;
								$Fault = $Message;
							}
						}
						if( isset( $transaction_id ) && $transaction_id != 0 )
						{
							update_post_meta($order_id, '_transaction_id', $transaction_id );
							if($Status == 'completed' )
							{
								$order->payment_complete($transaction_id);
								$order->update_status( 'wc-completed' );
								$woocommerce->cart->empty_cart();
								$Note = sprintf( __('%s .<br/> شماره سفارش: %s', 'woocommerce'), $txtmsg, $transaction_id) ;
								$Note = apply_filters('gateway_hp_Return_from_Gateway_Success_Note', $Note, $order_id, $transaction_id );
								if( $Note ){ $order->add_order_note($Note, 1); }
								$Notice = wpautop(wptexturize($this->success_massage));
								$Notice = str_replace("{transaction_id}", $transaction_id, $Notice);
								$Notice = apply_filters('gateway_hp_Return_from_Gateway_Success_Notice', $Notice, $order_id, $transaction_id);
								if( $Notice ){ wc_add_notice($Notice, 'success'); }
								do_action('gateway_hp_Return_from_Gateway_Success', $order_id, $transaction_id, $response_verify);
								wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
								exit;
							}
							else
							{
								$tr_id = ($transaction_id && $transaction_id != 0) ? ('<br/>کد پیگیری : ' . $transaction_id) : '';
								$Note = sprintf(__('خطا در هنگام بازگشت از بانک : %s', 'woocommerce'), $Message, $tr_id);
								$Note = apply_filters('gateway_hp_Return_from_Gateway_Failed_Note', $Note, $order_id, $transaction_id, $Fault);
								if($Note){ $order->add_order_note($Note, 1); }
								$Notice = wpautop(wptexturize($Note));
								$Notice = str_replace("{transaction_id}", $transaction_id, $Notice);
								$Notice = str_replace("{fault}", $Message, $Notice);
								$Notice = apply_filters('gateway_hp_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $transaction_id, $Fault);
								if($Notice){ wc_add_notice($Notice, 'error'); }
								do_action('gateway_hp_Return_from_Gateway_Failed', $order_id, $transaction_id, $Fault);
								wp_redirect($woocommerce->cart->get_checkout_url());
								exit;
							}
						}
						else
						{
							update_post_meta($order_id, '_transaction_id', $transaction_id );
						}
					}
					elseif($callback_status == 401)
					{
						$Status = 'failed';
						$Message = "کاربر در صفحه بانک از پرداخت انصراف داده است.";
						$Fault = 'تراكنش توسط شما لغو شد.';

						$tr_id = ($transaction_id && $transaction_id != 0) ? ('<br/>کد پیگیری : ' . $transaction_id) : '';
						$Note = sprintf(__('خطا در هنگام بازگشت از بانک : %s', 'woocommerce'), $Message, $tr_id);
						$Note = apply_filters('gateway_hp_Return_from_Gateway_Failed_Note', $Note, $order_id, $transaction_id, $Fault);
						if($Note){ $order->add_order_note($Note, 1); }
						$Notice = wpautop(wptexturize($Note));
						$Notice = str_replace("{transaction_id}", $transaction_id, $Notice);
						$Notice = str_replace("{fault}", $Message, $Notice);
						$Notice = apply_filters('gateway_hp_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $transaction_id, $Fault);
						if($Notice){ wc_add_notice($Notice, 'error'); }
						do_action('gateway_hp_Return_from_Gateway_Failed', $order_id, $transaction_id, $Fault);
						wp_redirect($woocommerce->cart->get_checkout_url());
						exit;
					}
					else
					{

						$Status = 'failed';
						$Message = "کاربر در صفحه بانک از پرداخت انصراف داده است.<br>کد پرداخت: $transaction_id <br> شماره خطا: $callback_status";
						// $Fault = 'تراكنش توسط شما لغو شد.';
						$Fault = __('شماره سفارش وجود ندارد .', 'woocommerce');
						$transaction_id = get_post_meta($order_id, '_transaction_id', true);
						$Notice = wpautop(wptexturize($this->success_massage.' شناسه خطای هیلاپی:'.$callback_status));
						$Notice = str_replace("{transaction_id}", $transaction_id, $Notice);
						$Notice = apply_filters('gateway_hp_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $transaction_id);
						$Notice = apply_filters('gateway_hp_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $Fault);
						if($Notice){ wc_add_notice($Notice, 'failed'); }
						do_action('gateway_hp_Return_from_Gateway_Failed', $order_id, $transaction_id, $Fault);
						wp_redirect($woocommerce->cart->get_checkout_url());
					}
				}
				else
				{
					$transaction_id = get_post_meta($order_id, '_transaction_id', true);
					$Notice = wpautop(wptexturize($this->success_massage.' شناسه خطای هیلاپی:'.$callback_status));
					$Notice = str_replace("{transaction_id}", $transaction_id, $Notice);
					$Notice = apply_filters('gateway_hp_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $transaction_id);
					if($Notice){ wc_add_notice($Notice, 'error'); }
					do_action('gateway_hp_Return_from_Gateway_ReSuccess', $order_id, $transaction_id);
					//wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
					wp_redirect($woocommerce->cart->get_checkout_url());
					exit;
				}
			}
			else
			{
				// $tr_id = ($transaction_id && $transaction_id != 0) ? ('<br/>کد پیگیری : ' . $transaction_id) : '';
				// $Note = sprintf(__('خطا در هنگام بازگشت از بانک : %s', 'woocommerce'), $Message, $tr_id);
				// $Note = apply_filters('gateway_hp_Return_from_Gateway_Failed_Note', $Note, $order_id, $transaction_id, $Fault);
				// if($Note){ $order->add_order_note($Note, 1); }
				// $Notice = wpautop(wptexturize($Note));
				// $Notice = str_replace("{transaction_id}", $transaction_id, $Notice);
				// $Notice = str_replace("{fault}", $Message, $Notice);
				// $Notice = apply_filters('gateway_hp_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $transaction_id, $Fault);
				// if($Notice){ wc_add_notice($Notice, 'error'); }
				// do_action('gateway_hp_Return_from_Gateway_Failed', $order_id, $transaction_id, $Fault);
				// wp_redirect($woocommerce->cart->get_checkout_url());

				$Fault = __('شماره سفارش وجود ندارد .', 'woocommerce');
				$Notice = wpautop(wptexturize($this->failed_massage.' شناسه خطای هیلاپی:'.$callback_status));
				$Notice = str_replace("{fault}", $Fault, $Notice);
				$Notice = apply_filters('gateway_hp_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $Fault);
				if($Notice){ wc_add_notice($Notice, 'error'); }
				do_action('gateway_hp_Return_from_Gateway_No_Order_ID', $order_id, $transaction_id, $Fault);
				wp_redirect($woocommerce->cart->get_checkout_url());
				exit;
			}
		}

		public function openbrain_gateway_check_currency( $Amount, $currency )
		{
			if(strtolower( $currency ) == strtolower('IRT') || strtolower( $currency ) == strtolower('TOMAN') || strtolower( $currency ) == strtolower('Iran TOMAN') || strtolower( $currency ) == strtolower('Iranian TOMAN') || strtolower( $currency ) == strtolower('Iran-TOMAN') || strtolower( $currency ) == strtolower('Iranian-TOMAN') || strtolower( $currency ) == strtolower('Iran_TOMAN') || strtolower( $currency ) == strtolower('Iranian_TOMAN') || strtolower( $currency ) == strtolower('تومان') || strtolower( $currency ) == strtolower('تومان ایران') ){
				$Amount = $Amount * 10;
			}elseif(strtolower($currency) == strtolower('IRHT')){
				$Amount = $Amount * 1000;
			}elseif( strtolower( $currency ) == strtolower('IRHR') ){
				$Amount = $Amount * 100;					
			}elseif( strtolower( $currency ) == strtolower('IRR') ){
				$Amount = $Amount / 1;
			}
			return  $Amount;
		}
		public function openbrain_gateway_status_message($code){
			switch ($code){
				case 200 :
					return 'عملیات با موفقیت انجام شد';
					break ;
				case 400 :
					return 'مشکلی در ارسال درخواست وجود دارد';
					break ;
				case 500 :
					return 'مشکلی در سرور رخ داده است';
					break;
				case 503 :
					return 'سرور در حال حاضر قادر به پاسخگویی نمی‌باشد';
					break;
				case 401 :
					return 'عدم دسترسی';
					break;
				case 403 :
					return 'دسترسی غیر مجاز';
					break;
				case 404 :
					return 'آیتم درخواستی مورد نظر موجود نمی‌باشد';
					break;
			}
		}
	}

}