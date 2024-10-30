<?php

global $wpdb;
echo 'hello2'; 

print_r($wpdb);
exit();
$wc_order = sanitize_text_field($_REQUEST['id']);
$the_order = wc_get_order( $wc_order );

print_r($wc_order);

$options = get_option( 'blu_label_settings' );
$label_print_format = $options['blu_label_print'];

$ship_to = get_post_meta($wc_order, '_bluOrder_Ship_to',true);

/*Merchant Details*/

/* Customer Details*/

if ( $the_order->user_id ) {
	$user_info = get_userdata( $the_order->user_id );
}

if ( ! empty( $user_info ) ) {
	if ( $user_info->first_name || $user_info->last_name ) {
		$customer = ucfirst( $user_info->first_name ).' '.ucfirst( $user_info->last_name );
	}
	else {
		$customer = ucfirst( $user_info->display_name );
	}
}
else{

	if($ship_to){
			if ( $the_order->shipping_first_name || $the_order->shipping_last_name ) {
				$customer = $the_order->shipping_first_name.' '.$the_order->shipping_last_name ;
			}
			else if ( $the_order->shipping_company ) {
				$customer = trim( $the_order->shipping_company );
			} else {
				$customer = 'Guest';
			}
	} else {
			if ( $the_order->billing_first_name || $the_order->billing_last_name ) {
				$customer = $the_order->billing_first_name.' '.$the_order->billing_last_name ;
			}
			else if ( $the_order->billing_company ) {
				$customer = trim( $the_order->billing_company );
			} else {
				$customer = 'Guest';
			}
	}
}
$customerEmail = $the_order->billing_email;
$customerPhone = $the_order->billing_phone;

if($ship_to == '1'){ // ship to different address

	$shipping_state = WC()->countries->states[$the_order->shipping_country][$the_order->shipping_state];
	$shipping_country = get_post_meta($wc_order, '_shipping_country',true);
	if($shipping_country != 'SG')
		$shipping_country = WC()->countries->countries[get_post_meta($wc_order, '_shipping_country',true)];

	$customerAddress = get_post_meta($wc_order, '_shipping_address_1',true);
	$customerAddress1 = get_post_meta($wc_order, '_shipping_address_2',true);
	$customerAddress2 = get_post_meta($wc_order, '_shipping_postcode',true).' '.get_post_meta($wc_order, '_shipping_city',true);
	$customerAddress3 = $shipping_state.' '.$shipping_country;
} else {

	$shipping_state = WC()->countries->states[$the_order->billing_country][$the_order->billing_state];
	$shipping_country = get_post_meta($wc_order, '_billing_country',true);
	if($shipping_country != 'SG')
		$shipping_country = WC()->countries->countries[get_post_meta($wc_order, '_billing_country',true)];

	$customerAddress = get_post_meta($wc_order, '_billing_address_1',true);
	$customerAddress1 = get_post_meta($wc_order, '_billing_address_2',true);
	$customerAddress2 = get_post_meta($wc_order, '_billing_postcode',true).' '.get_post_meta($wc_order, '_billing_city',true);
	$customerAddress3 = $shipping_state.' '.$shipping_country;
}

$customerAddress3 = str_replace('SG', '', $customerAddress3);

/* End Customer Details */

$shipping_items = $the_order->get_items( 'shipping' );
foreach($shipping_items as $el){
  $order_shipping_method_id = $el['method_id'] ;
}

$SQL = "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = '$wc_order' AND meta_key = '_bluOrder_id'";
$QRCode = $wpdb->get_row($SQL);
$customerQRCode = $QRCode->meta_value;



?>