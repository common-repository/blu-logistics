<?php
/**
 * Order Customer Details
 *
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
include(BLU_PLUGIN_PATH.'/bluAPI.php');
$BluApi = new BluAPI();

?>
<header><h2><?php _e( 'Customer Details', 'woocommerce' ); ?></h2></header>

<table class="shop_table customer_details">
	<?php if ( $order->customer_note ) : ?>
		<tr>
			<th><?php _e( 'Note:', 'woocommerce' ); ?></th>
			<td><?php echo wptexturize( $order->customer_note ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $order->billing_email ) : ?>
		<tr>
			<th><?php _e( 'Email:', 'woocommerce' ); ?></th>
			<td><?php echo esc_html( $order->billing_email ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $order->billing_phone ) : ?>
		<tr>
			<th><?php _e( 'Telephone:', 'woocommerce' ); ?></th>
			<td><?php echo esc_html( $order->billing_phone ); ?></td>
		</tr>
	<?php endif; ?>

	<?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
</table>

<?php

	$shipping_items = $order->get_items( 'shipping' );
	foreach($shipping_items as $el){
  		$order_shipping_method_id = $el['method_id'] ;
  		$order_shipping_method_name = $el['name'];
	}

?>

<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() ) : ?>

<div class="col2-set addresses">
	<div class="col-1">

<?php endif; ?>

<header class="title">
	<h3><?php _e( 'Billing Address', 'woocommerce' ); ?></h3>
</header>
<address>
	<?php echo ( $address = $order->get_formatted_billing_address() ) ? $address : __( 'N/A', 'woocommerce' ); ?>
</address>

<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() ) : ?>

	</div><!-- /.col-1 -->
	<?php if($order_shipping_method_id == 'blu_terminal_shipping_method'){ 
		$selected_location = $BluApi->blsmGetBluPortAddress($order->id);
		unset($selected_location[4]);?>

		<div class="col-2">
			<header class="title">
				<h3><?php _e( 'Pickup Location', 'woocommerce' ); ?></h3>
			</header>
			<address>
				<?php
					foreach ($selected_location as $key) {
						echo $key.'<br>';
					} 
				?>
			</address>
		</div><!-- /.col-2 -->
	<?php ?>

	<?php } else { ?>
	<div class="col-2">
		<header class="title">
			<h3><?php _e( 'Shipping Address', 'woocommerce' ); ?></h3>
		</header>
		<address>
			<?php echo ( $address = $order->get_formatted_shipping_address() ) ? $address : __( 'N/A', 'woocommerce' ); ?>
		</address>
	</div><!-- /.col-2 -->
	<?php } ?>
</div><!-- /.col2-set -->

<?php endif; ?>
