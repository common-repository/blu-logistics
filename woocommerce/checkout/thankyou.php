<?php
/**
 * Thankyou page
 *
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include(BLU_PLUGIN_PATH.'/bluAPI.php');
$BluApi = new BluAPI();

if ( $order ) : ?>

	<?php if ( $order->has_status( 'failed' ) ) : ?>

		<p class="woocommerce-thankyou-order-failed"><?php _e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

		<p class="woocommerce-thankyou-order-failed-actions">
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e( 'Pay', 'woocommerce' ) ?></a>
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php _e( 'My Account', 'woocommerce' ); ?></a>
			<?php endif; ?>
		</p>

	<?php else : ?>

		<p class="woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); ?></p>

		<?php 

		$shipping_items = $order->get_items( 'shipping' );
		foreach($shipping_items as $el){
		  $order_shipping_method_id = $el['method_id'] ;
		}

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
		<p class="woocommerce-thankyou-order-received blu-thankyou woocommerce-message">
			<span class="wc-forward-text">
				<?php echo $blu_thank_you_message ?>
			</span>
		</p>
		<ul class="woocommerce-thankyou-order-details order_details">
			<li class="order">
				<?php _e( 'Order Number:', 'woocommerce' ); ?>
				<strong><?php echo $order->get_order_number(); ?></strong>
			</li>
			<li class="date">
				<?php _e( 'Date:', 'woocommerce' ); ?>
				<strong><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></strong>
			</li>
			<li class="total">
				<?php _e( 'Total:', 'woocommerce' ); ?>
				<strong><?php echo $order->get_formatted_order_total(); ?></strong>
			</li>
			<?php if ( $order->payment_method_title ) : ?>
			<li class="method">
				<?php _e( 'Payment Method:', 'woocommerce' ); ?>
				<strong><?php echo $order->payment_method_title; ?></strong>
			</li>
			<?php endif; ?>
		</ul>
		<div class="clear"></div>

	<?php endif; ?>

	<?php do_action( 'woocommerce_thankyou_' . $order->payment_method, $order->id ); ?>
	<?php do_action( 'woocommerce_thankyou', $order->id ); ?>

<?php else : ?>

	<p class="woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), null ); ?></p>

<?php endif; ?>
