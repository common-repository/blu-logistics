<?php
/**
 * Email Addresses
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-addresses.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include(BLU_PLUGIN_PATH.'/bluAPI.php');
$BluApi = new BluAPI();

?>
<?php

	$shipping_items = $order->get_items( 'shipping' );
	foreach($shipping_items as $el){
  		$order_shipping_method_id = $el['method_id'] ;
  		$order_shipping_method_name = $el['name'];
	}

?>
<table id="addresses" cellspacing="0" cellpadding="0" style="width:100%;vertical-align:top" border="0">
<tr>
<td class="td" style="text-align:left;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif" valign="top" width="50%">
<h3><?php _e( 'Billing address', 'woocommerce' ); ?></h3>
<p class="text"><?php echo $order->get_formatted_billing_address(); ?></p>
</td>
<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && ( $shipping = $order->get_formatted_shipping_address() ) ) : ?>
<td class="td" style="text-align:left;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif" valign="top" width="50%">
<?php if($order_shipping_method_id == 'blu_terminal_shipping_method'){?>
<h3><?php _e( 'Pickup location', 'woocommerce' ); ?></h3>
<p class="text">
<?php 
								$selected_location = $BluApi->blsmGetBluPortAddress($order->id);
								unset($selected_location[4]);
								foreach ($selected_location as $key) {
									echo $key.'<br>';
								}
						?></p>
<?php } else {?>
<h3><?php _e( 'Shipping address', 'woocommerce' ); ?></h3>
<p class="text"><?php echo $shipping; ?></p>
<?php } ?>
</td>
<?php endif; ?>
</tr>
</table>