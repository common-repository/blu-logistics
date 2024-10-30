<?php

require_once('lib/tcpdf/config/tcpdf_config.php');
require_once('lib/tcpdf/tcpdf.php');
require_once('lib/fpdi/fpdi.php');
include_once('../../bluAPI.php');

class PDF extends FPDI
{

}

$path = dirname(__FILE__) . DIRECTORY_SEPARATOR.'Label.pdf';

global $wpdb;

$wc_order = $_REQUEST['data'];
$the_order = wc_get_order( $wc_order );

$options = get_option( 'blu_label_settings' );
$label_print_format = $options['blu_label_print'];
$pdf = new PDF();
$pdf->AddPage();
$pdf->setSourceFile($path);
$tplIdx = $pdf->importPage(1);
$pdf->SetFont('Helvetica');

$BluApi = new BluAPI();

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


if($label_print_format =='A4' || empty($label_print_format)){

	$pdf->SetFont("dejavusans",'',10);

	$pdf->useTemplate($tplIdx, null, null, 0,0,true);
	$pdf->SetXY(20, 70);
	$pdf->Write(0, $wc_order);

	//Merchant Info
	$pdf->SetXY(135, 65);
	$pdf->Write(0, 'BluLogistics');

	$pdf->SetXY(135, 70);
	$pdf->Write(0, 'Contact: '.'098765431');

	$pdf->SetXY(135, 75);
	$pdf->Write(0, '1 North Bridge Road #17-10');

	$pdf->SetXY(135, 80);
	$pdf->Write(0, 'Singapore');

	$pdf->SetXY(135, 85);
	$pdf->Write(0, 'Singapore - 179094');

	//Customer Info
	$pdf->SetXY(20, 100);
	$pdf->Write(0, $customer);

	$pdf->SetXY(20, 105);
	$pdf->Write(0, 'Email: '.esc_html( $customerEmail ));			

	if($the_order->billing_phone){
		$pdf->SetXY(20, 110);
		$pdf->Write(0, 'Mobile: '.esc_html( $customerPhone ));
	}
	if($order_shipping_method_id != 'blu_terminal_shipping_method')
	{
		$pdf->SetXY(60, 145);
		$pdf->Write(0, $customer);	

		$pdf->SetXY(60, 150);
		$pdf->Write(0, $customerAddress);
		if($customerAddress1){
			$pdf->SetXY(60, 155);
			$pdf->Write(0, $customerAddress1);

			$pdf->SetXY(60, 160);
			$pdf->Write(0, $customerAddress2);

			$pdf->SetXY(60, 165);
			$pdf->Write(0, $customerAddress3);
		}else{
			$pdf->SetXY(60, 155);
			$pdf->Write(0, $customerAddress2);

			$pdf->SetXY(60, 160);
			$pdf->Write(0, $customerAddress3);
		}
	}else{
		$pdf->SetXY(60, 145,true);
		$selected_location = $BluApi->blsmGetBluPortAddress($wc_order);
		$port_code = substr($selected_location[4], 0,3);

		$pdf->MultiCell(130,0, $port_code.' - '.$selected_location[0] ,0,'L');
		$pdf->SetXY(60, 150,true);
		$pdf->MultiCell(130,0,$selected_location[1],0,'L');
		$pdf->SetXY(60, 158,true);
		$pdf->MultiCell(130,0,$selected_location[2],0,'L');
		$pdf->SetXY(60, 162,true);
		$pdf->MultiCell(130,0,$selected_location[3],0,'L');
	}

	$pdf->SetXY(100, 215);
	$pdf->write1DBarcode($customerQRCode, 'C128', '', '', '', 18, 0.4, $style, 'N');

	$pdf->SetXY(115, 236);
	$pdf->Write(0, $customerQRCode);	
}
elseif($label_print_format=='A5'){

	$pdf->SetFont("dejavusans",'',8);

	$pdf->useTemplate($tplIdx, null, null, 148,210,true);

	$pdf->SetXY(10, 50);
	$pdf->Write(0, $wc_order);

	//Merchant Info
	$pdf->SetXY(95, 47);
	$pdf->Write(0, 'BluLogistics');

	$pdf->SetXY(95, 52);
	$pdf->Write(0, 'Contact: '.'098765431');

	$pdf->SetXY(95, 57);
	$pdf->Write(0, '1 North Bridge Road #17-10');

	$pdf->SetXY(95, 62);
	$pdf->Write(0, 'Singapore');

	$pdf->SetXY(95, 67);
	$pdf->Write(0, 'Singapore - 179094');

	//Customer Info
	$pdf->SetXY(15, 72);
	$pdf->Write(0, $customer);

	$pdf->SetXY(15, 77);
	$pdf->Write(0, 'Email: '.esc_html( $customerEmail ));			

	if(esc_html( $the_order->billing_phone )){
		$pdf->SetXY(15, 82);
		$pdf->Write(0, 'Mobile: '.esc_html( $customerPhone ));
	}

	if($order_shipping_method_id != 'blu_terminal_shipping_method')
	{
		$pdf->SetXY(45, 102);
		$pdf->Write(0, $customer);	

		$pdf->SetXY(45, 107);
		$pdf->Write(0, $customerAddress);
		if($customerAddress1){
			$pdf->SetXY(45, 112);
			$pdf->Write(0, $customerAddress1);

			$pdf->SetXY(45, 117);
			$pdf->Write(0, $customerAddress2);

			$pdf->SetXY(45, 122);
			$pdf->Write(0, $customerAddress3);
		}else{
			$pdf->SetXY(45, 112);
			$pdf->Write(0, $customerAddress2);

			$pdf->SetXY(45, 127);
			$pdf->Write(0, $customerAddress3);
		}
	
	}else{
		$pdf->SetXY(45, 102,true);
		$selected_location = $BluApi->blsmGetBluPortAddress($wc_order);
		$port_code = substr($selected_location[4], 0,3);

		$pdf->MultiCell(100,0,$port_code.' - '.$selected_location[0],0,'L');
		$pdf->SetXY(45, 107,true);
		$pdf->MultiCell(100,0,$selected_location[1],0,'L');
		$pdf->SetXY(45, 115,true);
		$pdf->MultiCell(100,0,$selected_location[2],0,'L');
		$pdf->SetXY(45, 119,true);
		$pdf->MultiCell(100,0,$selected_location[3],0,'L');
	}

	$pdf->SetXY(60, 145);
	$pdf->write1DBarcode($customerQRCode, 'C128', '', '', '', 14, 0.4, $style, 'N');

	$pdf->SetXY(80, 165);
	$pdf->Write(0, $customerQRCode);	
}
elseif($label_print_format=='A6')	{
	$pdf->SetFont("dejavusans",'',7);
	
	$pdf->useTemplate($tplIdx, null, null, 105,148,true);

	$pdf->SetXY(8, 35);

	$pdf->Write(0, $wc_order);

	//Merchant Info
	$pdf->SetXY(68, 33);
	$pdf->Write(0, 'BluLogistics');

	$pdf->SetXY(68, 36);
	$pdf->Write(0, '098765431');

	$pdf->SetXY(68, 39);
	$pdf->Write(0, '1 North Bridge');

	$pdf->SetXY(68, 42);
	$pdf->Write(0, 'Road #17-10');

	$pdf->SetXY(68, 45);
	$pdf->Write(0, 'Singapore');

	$pdf->SetXY(68, 48);
	$pdf->Write(0, 'Singapore - 179094');

	//Customer Info
	$pdf->SetXY(10, 50);
	$pdf->Write(0, $customer);

	$pdf->SetXY(10, 53);
	$pdf->Write(0, 'Email: '.esc_html( $customerEmail ));			

	if(esc_html( $the_order->billing_phone )){
		$pdf->SetXY(10, 56);
		$pdf->Write(0, 'Mobile: '.esc_html( $customerPhone ));
	}

	if($order_shipping_method_id != 'blu_terminal_shipping_method')
	{
		$pdf->SetXY(30, 71);
		$pdf->Write(0, $customer);	

		$pdf->SetXY(30, 74);
		$pdf->Write(0, $customerAddress);
		if($customerAddress1){
			$pdf->SetXY(30, 77);
			$pdf->Write(0, $customerAddress1);
			$pdf->SetXY(30, 80);
			$pdf->Write(0, $customerAddress2);
			$pdf->SetXY(30, 83);
			$pdf->Write(0, $customerAddress3);
		}else{
			$pdf->SetXY(30, 77);
			$pdf->Write(0, $customerAddress2);

			$pdf->SetXY(30, 80);
			$pdf->Write(0, $customerAddress3);
		}
	
	}else{
		$pdf->SetXY(30, 71,true);
		$selected_location = $BluApi->blsmGetBluPortAddress($wc_order);

		$port_code = substr($selected_location[4], 0,3);

		$pdf->MultiCell(70,0,$port_code.' - '.$selected_location[0],0,'L');
		$pdf->SetXY(30, 74,true);
		$pdf->MultiCell(70,0,$selected_location[1],0,'L');
		$pdf->SetXY(30, 77,true);
		$pdf->MultiCell(70,0,$selected_location[2],0,'L');

		$pdf->SetXY(30, 85,true);
		$pdf->MultiCell(70,0,$selected_location[3],0,'L');
	}

	$pdf->SetXY(40, 102);
	$pdf->write1DBarcode($customerQRCode, 'C128', '', '', '', 12, 0.8, $style, 'N');

	$pdf->SetXY(50, 117);
	$pdf->Write(0, $customerQRCode);	
}
$pdf->Output('label-'.$wc_order.'.pdf','I');	

exit();

?>