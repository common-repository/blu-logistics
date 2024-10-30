<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( $the_order->user_id ) {
	$user_info = get_userdata( $the_order->user_id );
}

if ( ! empty( $user_info ) ) {
	if ( $user_info->first_name || $user_info->last_name ) {
		$customer = ucfirst( $user_info->first_name );
	}
	else {
		$customer = ucfirst( $user_info->display_name );
	}
}
else{

	if ( $the_order->billing_first_name || $the_order->billing_last_name ) {
		$customer = $the_order->billing_first_name ;
	}
	else if ( $the_order->billing_company ) {
		$customer = trim( $the_order->billing_company );
	} else {
		$customer = 'Guest';
	}
}

$BluApi = new BluAPI();
$selected_location = $BluApi->blsmGetBluPortAddress($order_id);

$html = '<html>
		<head>
		<title></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		</head>
		<body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
		<table id="Table_01" width="600" height="900" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto" align="center">
			<tr>
				<td>
					<a href="#"><img src="'.BLU_PLUGIN_PATH.'/admin/images/mockup_01.png" width="465" height="178" alt=""></a></td>
				<td rowspan="2" style="vertical-align:top">
					<img src="'.BLU_PLUGIN_PATH.'/admin/images/mockup_02.png" width="135" height="235" alt=""></td>
			</tr>
			<tr>
				<td width="465" height="57" style="font-family:Arial;font-size:22pt;padding-left:20px;color:#ffd923;padding-top:10px" valign="top">Nice to meet you, '.$customer.'!</td>
			</tr>
			<tr>
				<td width="600" height="194" colspan="2" style="padding-left:25px;padding-right:25px;padding-bottom:20px;font-family:Arial;font-size:10pt">';
if($order_shipping_method_id == 'blu_home_shipping_method' || $order_shipping_method_id == 'blu_global_shipping_method'){
	if($order_shipping_method_id == 'blu_home_shipping_method')
		$method_name = 'bluHome';
	else
		$method_name = 'bluGlobal';
	$html .= '<p>We’re so glad you chose '.$method_name.' as your preferred delivery method for Order #'.$order_id.' placed with '.$mr_name.'.</p>

						<p>Now all you have to do is sit back and relax while we work on receiving your parcel and delivering it to you. If you wish to track your parcel, click on the following link <span style="color:#0055b8"><a href="https://go.blu.today/parcel">https://go.blu.today/parcel</a></span> and key in your Order Reference in the "Enter bluDID" field to obtain the latest delivery status.</p>
			
						<p>Should you have any queries or need further assistance on your parcel’s delivery, feel free to give bluCare a ring at <span style="color:#0055b8"><a href="tel:+65%206817%203620" value="+6568173620" target="_blank">+65 6817 3620</a> </span> or send us an email at <span style="color:#0055b8"><a href="mailto:blucare@go.blu.today">blucare@go.blu.today</a></span>.</p>

						<p>Hope to see you again soon!</p>
						<p>
						Love,<br>
						The blu Team
		  </p>';
} else {
	$html .= '<p>We’re so glad you chose bluPort Parcel Terminal as your preferred delivery method for Order #'.$order_id.' placed with '.$mr_name.'.</p>

				<p>Now all you have to do is sit back and relax while we work on receiving your parcel and delivering it to your selected bluPort, <span style="color:#0055b8">'.$selected_location[0].'</span>. Once the parcel is delivered, you will receive an SMS containing the bluCode with which you will be able to collect your parcel.</p>
				<p> If you wish to track your parcel, click on the following link <span style="color:#0055b8"><a href="https://go.blu.today/parcel">https://go.blu.today/parcel</a></span> and key in your Order Reference in the "Enter bluDID" field to obtain the latest delivery status.</p>
	
				<p>Should you have any queries or need further assistance on your parcel’s delivery, feel free to give bluCare a ring at <span style="color:#0055b8"><a href="tel:+65%206817%203620" value="+6568173620" target="_blank">+65 6817 3620</a> </span> or send us an email at <span style="color:#0055b8"><a href="mailto:blucare@go.blu.today"> blucare@go.blu.today</a></span>.</p>

				<p>Hope to see you again soon!</p>
				<p>
				Love,<br>
				The blu Team
				</p>';
}			


$html .=	'</td>
			</tr>
			<tr>
				<td colspan="2">
		<a href="#"><img id="Image-Maps" src="'.BLU_PLUGIN_PATH.'/admin/images/mockup_05.png" border="0" width="600" height="471" orgWidth="600" orgHeight="471" usemap="#image-maps" alt="" /></a>
		<map name="image-maps" id="ImageMaps">
		<area  alt="" title="" href="https://go.blu.today/locations/" shape="rect" coords="230,172,388,214" style="outline:none;" target="_self" onclick=""    />
		<area  alt="Near you" title="" href="https://go.blu.today/bluGate" shape="rect" coords="476,263,575,298" style="outline:none;" target="_self"     />

		<area shape="rect" coords="120,370,176,385" alt="Learn more" style="outline:none;" href="https://go.blu.today/parcel/" />

		<area shape="rect" coords="470,426,499,452" alt="Track now" style="outline:none;" href="https://www.facebook.com/likeblutoday/" />

		<area shape="rect" coords="514,425,543,453"  style="outline:none;"  href="https://go.blu.today/" />
		<area shape="rect" coords="558,426,587,454"  style="outline:none;"  href="mailto:blucare@go.blu.today" />

		</map>

			</tr>
		</table>
		</body>
		</html>';

?>