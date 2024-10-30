<?php

class BluAPI{

	public function blsm_merchant_authentication($merchant)
	{
		$post_url = 'http://52.76.74.113/authAPI/rest/MerchantAuthRS/validate';
			
		$post_url .='?merchantId='.$merchant['merchant_id'].'&authKey='.$merchant['merchant_auth_key'];

		$response = wp_remote_get( 
		    $post_url, 
		    array(
		        'method' => 'GET',
		        'timeout' => 45,
		        'redirection' => 5,
		        'httpversion' => '1.0',
		        'headers' => array(
		            'Content-Type' => 'application/x-www-form-urlencoded',
		            'Authorization' => 'Basic Ymx1UmVzdFVzZXI6UGFzc3dvcmRAMTIz'
		        ),
		        'body' => $postString,
		        'sslverify' => false
		    )
		);

		$get_response     = wp_remote_retrieve_body($response);
		return $get_response;

	}

	public function blsmGetLocationAPI()
	{
		$addressAPI = 'http://118.201.198.205:8080/infologBLUAPI/api/getMachine';
		$post_string = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
						<getAllMachines>
						</getAllMachines>';

		$response = wp_remote_post( 
		    $addressAPI, 
		    array(
		        'method' => 'POST',
		        'timeout' => 45,
		        'redirection' => 5,
		        'httpversion' => '1.0',
		        'headers' => array(
		            'Content-Type' => 'text/xml'
		        ),
		        'body' => $post_string,
		        'sslverify' => false
		    )
		);

		$body     = wp_remote_retrieve_body($response);
		$xml      = @simplexml_load_string($body);
		
		$stores = json_encode($xml);
		$newStore = json_decode($stores,true);

		return $newStore['apt'];
	}

	/**
	*	Save parcel to blu
	*	@param $params
	*	@return $status
	*/
	public function blsmSaveParcelDetails($order_id)
	{
		$the_order = wc_get_order( $order_id );
		$merchant = get_option( 'blu_auth_settings' );
		$dropoff_settings = get_option( 'blu_shipping_settings' );

		$parcelID = $merchant['blu_merchant_id'].'-'.$order_id;
		$parcelAPI = 'http://118.201.198.205:8080/infologBLUAPI/api/SAVEPARCEL';

		$dropoff = ($dropoff_settings['drop_off'] ? $dropoff_settings['drop_off'] : 0);

		if ( $the_order->user_id ) {
			$user_info = get_userdata( $the_order->user_id );
		}

		if ( ! empty( $user_info ) ) {
			if ( $user_info->first_name || $user_info->last_name ) {
				$first_name = ucfirst( $user_info->first_name );
				$last_name = ucfirst( $user_info->last_name );
			}
			else {
				$first_name = ucfirst( $user_info->display_name );
			}
		}
		else{

			if ( $the_order->billing_first_name || $the_order->billing_last_name ) {
				$first_name = $the_order->billing_first_name;
				$last_name = $the_order->billing_last_name;
			}
			else if ( $the_order->billing_company ) {
				$first_name = trim( $the_order->billing_company );
			} else {
				$first_name = 'Guest';
			}
		}

		$email = $the_order->billing_email;
		$phone = $the_order->billing_phone;
		$address = $this->blsmGetShippingAddress($order_id);

		$shipping_items = $the_order->get_items( 'shipping' );
		foreach($shipping_items as $el){
		  $order_shipping_method_id = $el['method_id'] ;
		}

		if($order_shipping_method_id != 'blu_terminal_shipping_method')
		{
			$delivery_type = $the_order->get_shipping_method();

		}else{
			
			$address = 'bluPort@bluHQ';
			$delivery_type = 'BLU_0_LM_FEC';
		}

		$phone = $this->blsmGetSingaporeNumber($phone);
		$logInfo = 'bluDID: '.$parcelID.'--dropoff'.$dropoff.'---Phone:'.$phone.' Order Reference: '.$order_id.' order_imp_id:'.$order_id.'---Customer Info:'.$first_name.' '.$last_name.'---'.$email.' Delivery Type:'.$delivery_type.' Address: '.$address;
		update_post_meta( $order_id, '_bluOrder_save_parcel', 'Yes');

		$postString = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
						<parcels>
						<parcel>
						<owner_code>'.$merchant['blu_merchant_id'].'</owner_code>
						<order_id>'.$order_id.'</order_id>
						<order_imp_id>'.$order_id.'</order_imp_id>
						<first_name>'.$first_name.'</first_name>
						<last_name>'.$last_name.'</last_name>
						<email>'.$email.'</email>
						<phone>'.$phone.'</phone>
						<delivery_type>'.$delivery_type.'</delivery_type>
						<address>'.$address.'</address>
						<parcel_id>'.$parcelID.'</parcel_id>
						<delivery_status></delivery_status>
						<dropoff>'.$dropoff.'</dropoff>
						</parcel>
						</parcels>';

		$response = $this->blsm_parcel_curl_api($parcelAPI,$postString);
		update_post_meta( $order_id, '_bluOrder_log', $postString);
		update_post_meta( $order_id, '_bluOrder_save_parcel_response', (string) $response->parcel->status);
		return $response;

	}

	public function blsm_parcel_curl_api($parcelAPI,$postString)
	{
		$response = wp_remote_post( 
		    $parcelAPI, 
		    array(
		        'method' => 'POST',
		        'timeout' => 45,
		        'redirection' => 5,
		        'httpversion' => '1.0',
		        'headers' => array(
		            'Content-Type' => 'text/xml',
		            'Authorization' => 'IFLBLU'
		        ),
		        'body' => $postString,
		        'sslverify' => false
		    )
		);

		$body     = wp_remote_retrieve_body($response);
		$xml_response      = @simplexml_load_string($body);
		return $xml_response;
	}

	public function blsmGetDeliveryStatus($order_id)
	{
		$the_order = wc_get_order( $order_id );
		$delivery_status = get_post_meta($order_id, "_bluOrder_delivey_status", true);
		if($delivery_status == 'Completed'){
			$order_date_after_30 = date('Y-m-d', strtotime($the_order->order_date.' +'.'30 days'));

			if(strtotime($order_date_after_30) > strtotime(date('Y-m-d'))){
				return 'Completed';
			}
		}
		$postString ='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
				<parcel_status>
					<id>'.$order_id.'</id>
				</parcel_status>';

		$parcelAPI = 'http://118.201.198.205:8080/infologBLUAPI/api/PARCELTRACK';
		$response = $this->blsm_parcel_curl_api($parcelAPI,$postString); 

        if (!$response->parcel->status) {
            return '';
        }

		$parcel_status = $this->blsm_parcel_status_message($response->parcel->status);
		if($parcel_status == "Completed"){
			update_post_meta( $order_id, '_bluOrder_delivey_status', $parcel_status);
		}
		return ($parcel_status ? $parcel_status : '');
	}

	public function blsm_parcel_status_message($parcel_status)
	{
		
		$parcelKey = array("Processing"=>"Pending",
						"Dropped"=>"Processing",
						"Picked up by DA_Dropped"=>"Processing",
						"Shipped"=>"Shipped",
						"Delivered"=>"Delivered to bluPort",
						"Completed"=>"Completed",
						"Picked up by Recipient"=>"Completed",
						"Redirected"=>"Redirected",
						"Picked up by DA_Redirected"=>"Redirected",
						"Stopped"=>"Stopped",
						"Picked up by DA_Stopped"=>"Stopped",
						"Picked up by DA_Expiry"=>"Collection Period Expired",
						"Rejected"=>"Failed Home Delivery",
						"Parcel Lost"=>"Please contact blu"
					);

		foreach ($parcelKey as $key => $value) {
			if(strtoupper(trim($key))==strtoupper(trim($parcel_status))){
				return $value;
			}
		}
		return 'Pending';
	}

	public function blsmCheckWebsiteExist($website){
		// Remove all illegal characters from a url
		$url = filter_var($website, FILTER_SANITIZE_URL);

		// Validate url
		if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
			return true;
		} else {
			return false;
		}
		die();
	}

	public function blsmGetShippingAddress($order_id){

	    $address = '';
	    $address .= get_post_meta( $order_id, '_shipping_address_1', true );
	    if(get_post_meta( $order_id, '_shipping_address_2', true ))
	    	$address .= ' '.get_post_meta( $order_id, '_shipping_address_2', true );
	    if(get_post_meta( $order_id, '_shipping_city', true ))
	    	$address .= ' '.get_post_meta( $order_id, '_shipping_city', true );
	    if(get_post_meta( $order_id, '_shipping_state', true ))
	    	$address .= ' '.get_post_meta( $order_id, '_shipping_state', true );
	    if(get_post_meta( $order_id, '_shipping_postcode', true ))
	    	$address .= ' '.get_post_meta( $order_id, '_shipping_postcode', true );
	    if(get_post_meta( $order_id, '_shipping_country', true ))
	    	$address .= ' '.get_post_meta( $order_id, '_shipping_country', true );

	    return $address;
	}

	public function blsmGetBluPortAddress($order_id){
		$location = get_post_meta($order_id, "_bluOrder_Location", true) ;
		$location = explode('||', $location);
		$location = array_filter($location);
		$location[3] = str_replace("SG","Singapore",$location[3]);

		return $location;
	}

	public function blsmGetAPIRate($product_weight, $dest_city, $dest_zip, $dest_state, $dest_country)
	{
		$priceAPI = 'http://52.76.74.113/shipAPI/shippingPrice/getPrice';
		$headers= array('Authorization:Basic Ymx1d29ybGQ6Qmx1dzBybGQ=','Content-Type: application/json','accept: application/json'); 
		
		$jsondata = json_encode(['city'=>$dest_city,
		                   'country'=>$dest_country,
		                   'state'=>$dest_state,
		                   'weight'=>$product_weight,
		                   'uom'=>'kg'
		]);

		$response = wp_remote_post( 
		    $priceAPI, 
		    array(
		        'method' => 'POST',
		        'timeout' => 45,
		        'redirection' => 5,
		        'httpversion' => '1.0',
		        'headers' => array(
		            'Content-Type' => 'application/json',
		            'Authorization' => 'Basic Ymx1d29ybGQ6Qmx1dzBybGQ=',
		            'accept' => 'application/json'
		        ),
		        'body' => $jsondata,
		        'sslverify' => false
		    )
		);

		$api_response     = wp_remote_retrieve_body($response);
		$result = json_decode($api_response,true);

	   	if($result['status']=='SUCCEED'){
		 	return $result['shippingPrice'];
	    }
	    else{
		    return $result['status'];
		}
	}

   /**
   *   Method: blsmGetSingaporeNumber
   *   Description: This function is using to get singapore number without country calling code.
   *   @param $mobile_number
   *   @return $mobile_number
   */

   public static function blsmGetSingaporeNumber($mobile_number)
   {
       $replaced_mobile_number = str_replace('-', '', str_replace(' ', '', str_replace('+', '', $mobile_number)));
       $mobileLength = strlen($replaced_mobile_number);
       $checkVal = substr($replaced_mobile_number, 0,2);
       if($checkVal=='65' && $mobileLength==10)
           return substr($replaced_mobile_number, 2);
       else
           return $mobile_number;
   }

}