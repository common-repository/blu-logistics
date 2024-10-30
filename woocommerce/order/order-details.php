<?php
/**
 * Order details
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
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include(BLU_PLUGIN_PATH.'/bluAPI.php');
$BluApi = new BluAPI();

$order = wc_get_order( $order_id );

$show_purchase_note    = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
?>
<h2><?php _e( 'Order Details', 'woocommerce' ); ?></h2>
<table class="shop_table order_details">
	<thead>
		<tr>
			<th class="product-name"><?php _e( 'Product', 'woocommerce' ); ?></th>
			<th class="product-total"><?php _e( 'Total', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			foreach( $order->get_items() as $item_id => $item ) {
				$product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );

				wc_get_template( 'order/order-details-item.php', array(
					'order'			     => $order,
					'item_id'		     => $item_id,
					'item'			     => $item,
					'show_purchase_note' => $show_purchase_note,
					'purchase_note'	     => $product ? get_post_meta( $product->id, '_purchase_note', true ) : '',
					'product'	         => $product,
				) );
			}
		?>
		<?php do_action( 'woocommerce_order_items_table', $order ); ?>
	</tbody>
	<tfoot>
		<?php 
			$shipping_items = $order->get_items( 'shipping' );
			foreach($shipping_items as $el){
			  $order_shipping_method_id = $el['method_id'] ;
			}
			$blu_thank_you_message = '';
			if($order_shipping_method_id == 'blu_global_shipping_method'){
				$blu_thank_you_message = 'Thank you for choosing bluGlobal as your preferred delivery method';
			}
			else if($order_shipping_method_id == 'blu_home_shipping_method'){
				$blu_thank_you_message = "Thank you for choosing bluHome as your preferred delivery method. You can track your parcel's delivery status via https://go.blu.today/parcel - simply key in your order reference in the 'Enter bluDID' field.";
			}
			else if($order_shipping_method_id == 'blu_terminal_shipping_method'){
				$selected_location = $BluApi->blsmGetBluPortAddress($order->id);
				$blu_thank_you_message = "Thank you for choosing ".$selected_location[0]." for your parcel collection. Please proceed to the bluPort location after you have received an SMS/Email from blu indicating your parcel is ready for collection.";
			}
			else {
				// do nothing
			}
		?>
		<?php
			foreach ( $order->get_order_item_totals() as $key => $total ) {
				?>
				<tr>
					<th scope="row"><?php echo $total['label']; ?></th>
					<td><?php echo $total['value']; ?>
						<?php if ( ($total['label'] == 'Shipping:') && $blu_thank_you_message ) :?>
							<p><?php echo $blu_thank_you_message ?><p>
						<?php endif;?>
					</td>
				</tr>
				<?php if ($total['label'] == 'Shipping:') :?>
					<tr>
						<th scope="row">bluDID:</th>
						<td><?php echo get_post_meta($order_id, "_bluOrder_id", true);?></td>
					</tr>
					<tr>
						<th scope="row">Delivery Status:</th>
						<td><?php echo $BluApi->blsmGetDeliveryStatus($order_id);?></td>
					</tr>
				<?php endif;?>
				<?php
			}
		?>
	</tfoot>
</table>

<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>

<?php if ( $show_customer_details ) : ?>
	<?php wc_get_template( 'order/order-details-customer.php', array( 'order' =>  $order ) ); ?>
<?php endif; ?>
