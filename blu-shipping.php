<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/** 
* add description to custom shipping method 
* loads google map when bluPort Parcel is selected allowing user to select address
* display selected address via session variable on page load
**/
add_filter( 'woocommerce_cart_shipping_method_full_label', 'blsm_shipping_description', 10, 2 );

function blsm_shipping_description( $label, $method ) {

	$method_array = array('blu_global_shipping_method','blu_home_shipping_method','blu_terminal_shipping_method');
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	$chosen_shipping = $chosen_methods[0];

	if ($method->cost == 0) {
		$label .= ': '.(__( 'Free', 'woocommerce' ));
	}

	if(in_array($method->method_id, $method_array))
	{
			$desc = get_option( 'woocommerce_' . $method->method_id.'_settings', 'description' );
			if($desc['description'])
			{
				$label .= '<p class="shipping_description" id="description_' . $method->method_id.'">'.$desc['description'].'</p>';
			}

			if($chosen_shipping == 'blu_terminal_shipping_method'){
				if($method->method_id == 'blu_terminal_shipping_method'){
					$label .= '<a href="javascript:;" class="select-location-btn" onclick="blsm_port_parcel()">Select bluPort</a>';

					$label .= '<p id="selected_location_value">';
					if($_SESSION['order_address']){
							$selected_location = explode('||', $_SESSION['order_address_location']);
							unset($selected_location[4]);

							foreach ($selected_location as $key) {
								if($key)
									$display_location .= $key.'<br>';
							}
							$label .= '<p class="address_title"> Selected bluPort Parcel Terminal : </p>';
							$label .= '<p class="selected_address">'.$display_location.'</p>';
					}
					$label .= '</p>';
				}
			}
			else{
				unset($_SESSION['order_address']);
				unset($_SESSION['order_address_location']);
			}
	}
	return $label;
}

/**
* validate cart contents on checkout page when bluPort Parcel address is not selected by the user
**/
add_action('woocommerce_after_checkout_validation', 'blsm_validate_all_cart_contents');
function blsm_validate_all_cart_contents(){
	global $woocommerce;
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	$chosen_shipping = $chosen_methods[0];

	if( is_checkout() ){
		if($chosen_shipping == 'blu_terminal_shipping_method' && (count( $woocommerce->cart->cart_contents) > 0 )){
			if(empty($_SESSION['order_address']))
			wc_add_notice( sprintf( '<strong>Please select a bluPort location from which you wish to collect your parcel.</strong>' ), 'error' );
		}
	}
}

/**
* update meta value of the order i.e, bluDID, bluPort Parcel Address, If Ship to different Address 
**/

add_action('woocommerce_checkout_update_order_meta', 'blsm_custom_checkout_field_update_order_meta');

function blsm_custom_checkout_field_update_order_meta( $order_id ) {
	$method_array = array('blu_global_shipping_method','blu_home_shipping_method','blu_terminal_shipping_method');
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	$chosen_shipping = $chosen_methods[0];

	if(in_array($chosen_shipping, $method_array)){
			$options = get_option( 'blu_auth_settings' );
   			update_post_meta( $order_id, '_bluOrder_id', $options['blu_merchant_id'].'-'.$order_id);
		    if($_SESSION['order_address'])
		    {
		    	update_post_meta( $order_id, '_bluOrder_Address', $_SESSION['order_address']);
		    	update_post_meta( $order_id, '_bluOrder_Location', $_SESSION['order_address_location']);
		    }
		    update_post_meta( $order_id, '_bluOrder_Ship_to', $_SESSION['if_ship_to_different_address']);
	}
} 

/**
* API call to save the parcel details
**/
add_action('woocommerce_thankyou', 'blsm_send_parcel_details', 111, 1);

function blsm_send_parcel_details($order_id){

	$if_parcel = get_post_meta($order_id, "_bluOrder_save_parcel", true);
	if($if_parcel != 'Yes'){
			$BluApi = new BluAPI();
			$orders = $BluApi->blsmSaveParcelDetails($order_id);
	}
}

/**
* Display field value on the order edit page
*/
add_action( 'woocommerce_admin_order_data_after_billing_address', 'blsm_custom_checkout_field_display_admin_order_meta', 10, 1 );

function blsm_custom_checkout_field_display_admin_order_meta($order){

	$BluApi = new BluAPI();
	$selected_location = $BluApi->blsmGetBluPortAddress($order->id);
	unset($selected_location[4]);
	$selected_address = '';
	foreach ($selected_location as $key) {
			$selected_address .= $key.'<br>';
	}

	$shipping_items = $order->get_items( 'shipping' );
	foreach($shipping_items as $el){
	  $order_shipping_method_id = $el['method_id'] ;
	} 

	if(in_array($order_shipping_method_id, array('blu_terminal_shipping_method')))
    	echo '<h3>Pickup Details</h3><p><strong>'.__('Address').':</strong> <br/>' . ($selected_address ? $selected_address : '-') . '</p>';
    echo '<p><strong>'.__('Delivery Status').':</strong> <br/>' . $BluApi->blsmGetDeliveryStatus($order->id) . '</p>';

    if(get_post_meta($order->id, "_bluOrder_id", true))
    	echo '<p><span class="blu-badge">bluDID: '.get_post_meta($order->id, "_bluOrder_id", true).'</span></p>';
	$method_array = array('blu_global_shipping_method','blu_home_shipping_method','blu_terminal_shipping_method');
	if(in_array($order_shipping_method_id, $method_array)) {
    	$if_response = get_post_meta($order->id, "_bluOrder_save_parcel_response", true);

    	if($if_response != 'SUCCEEDED'){
    			echo "<p id='resend-error'>Order details could not be sent to blu due to an error. Please click on 'Resend' to try again.</p>";
    			echo "<a class='resend-btn left-margin' onclick='blsm_resend_parcel(".$order->id.")'>Resend</a>";
    	}
    }

    $options = get_option( 'blu_label_settings' ); 

    if($options['blu_enable_label'] == 'Enable' && in_array($order_shipping_method_id, $method_array)) :
		echo '<a href="admin-ajax.php?action=blsm_label_pdf&data='.$order->id.'" target="_blank" class="resend-btn tips left-margin" data-tip="Label">
		<img src="'.BLU_PLUGIN_PATH.'/admin/images/label.png" class="blu-order display-label"> Print Label</a>';
	endif;

	if(empty($order_shipping_method_id))
		echo "<p class='info'>Please key in the bluPort Name and Address to which the parcel has to be delivered in the 'Shipping Details' section.</p>";
}

/**
* display message on the checkout page
**/
add_action( 'woocommerce_after_checkout_billing_form', 'blsm_method_message' );

function blsm_method_message( $checkout ) {
	$method_array = array('blu_global_shipping_method','blu_home_shipping_method');
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	$chosen_shipping = $chosen_methods[0];

	if($chosen_shipping == 'blu_home_shipping_method' || $chosen_shipping == 'blu_global_shipping_method' || $chosen_shipping == 'blu_terminal_shipping_method') {
		echo "<span id='blu_shipping_text' class='blu-help-text'>The delivery methods shown on the right will be with respect to your Billing Address, unless the ‘Ship to a different address’ option below has been selected.</span>";
	}
}

/**
* display destination country on the cart page
**/
add_action( 'woocommerce_cart_totals_before_shipping', 'blsm_destination_country' );

function blsm_destination_country(){
	$shipping_country = WC()->customer->get_shipping_country();
	echo "<p class='blu-help-text'>The delivery methods shown below are for Delivery To ".WC()->countries->countries[ $shipping_country ].". If you are delivering to an other country, proceed to check out and update your address accordingly to obatin the relevant delivery methods available</p>";
}

/**
* send Thank you email to the customer after order is completed
**/
add_action( 'woocommerce_thankyou', 'blsm_order_completed' );

function blsm_order_completed( $order_id ) {
	$the_order = new WC_Order( $order_id );

	$shipping_items = $the_order->get_items( 'shipping' );
	foreach($shipping_items as $el){
	  $method_name = $el['name'];
	  $order_shipping_method_id = $el['method_id'] ;
	} 

	$mr_name = get_bloginfo( 'name' );

	include_once('woocommerce/emails/email-template.php');

	if(in_array($order_shipping_method_id, array('blu_terminal_shipping_method','blu_home_shipping_method', 'blu_global_shipping_method'))) {
		$to_email = $the_order->billing_email;
	    $headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
	    wp_mail($to_email, 'Thank you for choosing '.$method_name.' for Order '.$order_id, $html, $headers );
	}   
}

/**
* Ajax function to resend-parcel
**/
add_action('wp_ajax_nopriv_blsm_resend_parcel', 'blsm_resend_parcel' );
add_action('wp_ajax_blsm_resend_parcel', 'blsm_resend_parcel' );

function blsm_resend_parcel(){  
    parse_str($_REQUEST['data'], $my_array_of_vars);
    $order_id = trim($my_array_of_vars['order']);

    include_once('bluAPI.php'); 
    $BluApi = new BluAPI();
	$BluApi->blsmSaveParcelDetails($order_id);
	$result['response'] = 'success';

	echo json_encode($result);

    die();
}

add_action('wp_ajax_nopriv_blsm_label_pdf', 'blsm_label_pdf' );
add_action('wp_ajax_blsm_label_pdf', 'blsm_label_pdf' );

function blsm_label_pdf(){
	parse_str($_REQUEST['data'], $my_array_of_vars);
    $wc_order = trim($my_array_of_vars['order']);
	include_once('admin/label/display_label.php');
	$result = true;
	echo json_encode($result);
    die();
}


/**
* clear cart content once the user is logged out
**/
add_action('wp_logout', 'blsm_clear_wc_cart');
function blsm_clear_wc_cart() {
    if( function_exists('WC') ){
        WC()->cart->empty_cart();
    }
}

/**
* Ajax function when ship to different address is checked
**/

add_action('wp_ajax_nopriv_blsm_if_ship_to_different_address', 'blsm_if_ship_to_different_address' );
add_action('wp_ajax_blsm_if_ship_to_different_address', 'blsm_if_ship_to_different_address' );

function blsm_if_ship_to_different_address(){
	$_SESSION['if_ship_to_different_address'] = '1';
	$result = true;
	echo json_encode($result);
    die();
}

add_action('wp_ajax_nopriv_blsm_google_map', 'blsm_google_map' );
add_action('wp_ajax_blsm_google_map', 'blsm_google_map' );

function blsm_google_map(){
	include_once('blu-terminal.php');
    die();
}

add_action('wp_ajax_nopriv_blsm_search_radius', 'blsm_search_radius' );
add_action('wp_ajax_blsm_search_radius', 'blsm_search_radius' );

function blsm_search_radius(){
	parse_str($_REQUEST['data'], $my_array_of_vars);
    $search_location = trim($my_array_of_vars['search_location']);
	$location['lat'] = '';
	$location['long'] =  '';
	$location['status'] = 'failed';

	if($search_location){
		// We get the JSON results from this request
		$geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($search_location).'');
		// We convert the JSON to an array
		$geo = json_decode($geo, true);

		// If everything is cool
		if ($geo['status'] = 'OK') {
		  $location['lat'] = $geo['results'][0]['geometry']['location']['lat'];
		  $location['long'] =  $geo['results'][0]['geometry']['location']['lng'];
		  $location['status'] = 'success';
		}
	}

	echo json_encode($location);
	exit();
}


add_action('wp_ajax_nopriv_blsm_change_address', 'blsm_change_address' );
add_action('wp_ajax_blsm_change_address', 'blsm_change_address' );

function blsm_change_address(){
	parse_str($_REQUEST['data'], $my_array_of_vars);
    $search_location = trim($my_array_of_vars['search_location']);

	session_start();
	$_SESSION['order_address'] = trim($my_array_of_vars['address']);
	$_SESSION['order_address_location'] = trim($my_array_of_vars['location']);

	$selected_location = explode('||', trim($my_array_of_vars['location']));
	$selected_location[2] = str_replace("SG","Singapore",$selected_location[2]);
	unset($selected_location[4]);

	foreach ($selected_location as $key) {
		if($key)
			$display_location .= $key.'<br>';
	}
	$response['label'] .= '<p class="address_title"> Selected bluPort Parcel Terminal : </p>';
	$response['label'] .= '<p class="selected_address">'.$display_location.'</p>';

	echo json_encode($response);
	exit();
}

?>