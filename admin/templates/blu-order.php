<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function blsm_get_orders() { 

	global $wpdb, $paged;
	$months = $wpdb->get_results( $wpdb->prepare( "
		SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
		FROM $wpdb->posts
		WHERE post_type = %s
		$extra_checks
		ORDER BY post_date DESC
	", 'shop_order' ) );

	$month_count = count( $months );

	$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;

	$post_ids = wc_order_search( $_GET['s'] );

	if(empty($post_ids)){
		$meta_query[] = array(
		    'key' => '_bluOrder_id',
		    'value' => $_GET['s'],
		    'compare' => 'LIKE'
		);
		$meta_query[] = array(
		    'key' => '_bluOrder_Address',
		    'value' => $_GET['s'],
		    'compare' => 'LIKE'
		);

		//if there is more than one meta query 'or' them
		if(count($meta_query) > 1) {
		    $meta_query['relation'] = 'OR';
		}
	}

	$paged = ( $_GET['paged'] ) ? $_GET['paged'] : 1; 

	$args = wp_parse_args( $args, array(
		'status'   => array_keys( wc_get_order_statuses() ),
		'type'     => wc_get_order_types( 'view-orders' ),
		'parent'   => null,
		'customer' => null,
		'email'    => '',
		'offset'   => null,
		'exclude'  => array(),
		'orderby'  => 'date',
		'order'    => 'DESC',
		'return'   => 'objects',
		'paginate' => false,
	) );

	/**
	 * Generate WP_Query args. This logic will change if orders are moved to
	 * custom tables in the future.
	 */
	$order_meta = array('_bluOrder_id','_shipping_address_1','_bluOrder_Address','_shipping_first_name');//_bluOrder_id
	if(in_array($_GET['order'], $order_meta))
	{
		$meta_query = array('meta_key' => $_GET['order'], //setting the meta_key which will be used to order
        			  'orderby' => 'meta_value'); 

		$wp_query_args = array(
			'post_type'      => $args['type'] ? $args['type'] : 'shop_order',
			'post_status'    => $_GET['post_status'] ? $_GET['post_status'] : $args['status'],
			'posts_per_page' => get_option( 'posts_per_page' ),
			'orderby'        => $_GET['orderby'] ? $_GET['orderby'] : $args['orderby'],
			'order'          => $_GET['order'] ? $_GET['order'] : $args['order'],
			'paged'	 		 => $paged,
			'page' 			 => $paged,
			'meta_key' 		 => "_bluOrder_id"
		);
	}
	else{
			$wp_query_args = array(
				'post_type'      => $args['type'] ? $args['type'] : 'shop_order',
				'post_status'    => $_GET['post_status'] ? $_GET['post_status'] : $args['status'],
				'posts_per_page' => get_option( 'posts_per_page' ),
				'orderby'        => $_GET['orderby'] ? $_GET['orderby'] : $args['orderby'],
				'order'          => $_GET['order'] ? $_GET['order'] : $args['order'],
				'paged'	 		 => $paged,
				'page' 			 => $paged,
				'meta_key'       => "_bluOrder_id"
			);
	}

	/** search filter **/
	if ( $_GET['s'] ) {
			if($post_ids)
			{
				$wp_query_args['shop_order_search'] = true;
				$wp_query_args['post__in'] = $post_ids;
			}

			$wp_query_args['meta_query'] = $meta_query;
	}

	/** search filter by month and year **/
	if ( $_GET['m'] ) {
			$wp_query_args['date_query'] = array(
    								array(
       										 'year'  => substr($m, 0, 4),
        									 'month' => substr($m, 4, 2),
    										),
			); 
	}

	/** search filter by customer user **/
	if ( $_GET['_customer_user'] ) {
			$wp_query_args['meta_key']    = '_customer_user';
    	    $wp_query_args['meta_value']  = $_GET['_customer_user'];
	}

	if(($_GET['action'] || $_GET['action2']) && $_GET['post'])
	{ 
		$data = $_GET['action'] ? $_GET['action'] : $_GET['action2'];  
		$order_ids = $_GET['post'];

		if($data == 'trash')
		{
			foreach ((array)$order_ids as $key) {
					wp_trash_post($key);
			}
		}  
		else{
				$order_status = substr($data, strpos($data, "_") + 1);    

				foreach ((array)$order_ids as $key) {
					$order = new WC_Order($key);
					
					if (!empty($order)) {
					    $order->update_status( $order_status );
					}
				}
		}
	}

	$orders = new WP_Query( $wp_query_args );
	$wc_orders = $orders->posts;

	$options = get_option( 'blu_label_settings' ); 

	$BluApi = new BluAPI();

?>
<!-- end load scripts blu-order-wrap-->

<div class="wrap"> 

<div id="setting-error-blu_company_name_error" class="error settings-error notice is-dismissible alert-warning"> 
<p><strong>One of your orders highlighted below is not updated in blu's systems for delivery. Please re-send the details using the "Resend Parcel Details" button available in the Order Details page.</strong></p>
<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>

<h1 class="wp-heading-inline">blu Orders</h1>

<h2>
<div class="notice is-dismissible">
	<p><strong>Here's a list of all the orders processed using blu's Delivery Methods.</strong></p>
<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
</h2>

<hr class="wp-header-end">

<h2 class="screen-reader-text">Filter orders</h2>
<!-- Get Order Count -->
<ul class="subsubsub">
	<li class="all"><a href="admin.php?page=blulogistics&tab=blu_order" class="current">All <span class="count">(<?php echo (wc_orders_count('processing') + wc_orders_count('on-hold') + wc_orders_count('completed'))?>)</span></a></li>

	<?php if(wc_orders_count('processing')) : ?>
	| <li class="wc-processing"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=wc-processing">Processing <span class="count">(<?php echo wc_orders_count('processing')?>)</span></a></li>
	<?php endif; ?>

	<?php if(wc_orders_count('on-hold')) : ?>
	| <li class="wc-on-hold"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=wc-on-hold">On Hold <span class="count">(<?php echo wc_orders_count('on-hold')?>)</span></a></li>
	<?php endif; ?>

	<?php if(wc_orders_count('completed')) : ?>
	| <li class="wc-completed"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=wc-completed">Completed <span class="count">(<?php echo wc_orders_count('completed')?>)</span></a></li>
	<?php endif; ?>

</ul>
<!-- End Get Order Count -->

<form id="posts-filter" method="get" action="admin.php">
	<!-- search filters -->
		<input type="hidden" name="page" value="blulogistics">
		<input type="hidden" name="tab" value="blu_order">
		<p class="search-box">
			<label class="screen-reader-text" for="post-search-input">Search Orders:</label>
			<input type="search" id="post-search-input" name="s" value="<?php echo $_GET['s'] ?>">
			<input type="submit" id="search-submit" class="button serach-button" value="Search">
			<img id="clear-submit" src="<?php echo BLU_PLUGIN_PATH ?>/admin/images/clear.png" class="blu-order display-label">
		</p>

	<input type="hidden" name="post_status" class="post_status_page" value="all">
	<!-- end search filters -->

	<div class="tablenav top">

		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
			<select name="action" id="bulk-action-selector-top">
				<option value="-1">Bulk Actions</option>
				<option value="trash">Move to Trash</option>
			</select>

			<input type="submit" id="doaction" class="button action" value="Apply">
		</div>

		<div class="alignleft actions">

		<!-- date filters -->
		<label for="filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date' ); ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
				<?php
						foreach ( $months as $arc_row ) {
							if ( 0 == $arc_row->year )
								continue;

							$month = sprintf( '%0' . '2' . 's', $arc_row->month );
							$year = $arc_row->year;
							$date = '01-'.$month.'-'.$year;

							printf( "<option %s value='%s'>%s</option>\n",
								selected( $m, $year . $month, false ),
								esc_attr( $arc_row->year . $month ),
								/* translators: 1: month name, 2: 4-digit year */
								sprintf( __( '%1$s %2$d' ), date('F',strtotime($date)), $year )
							);
						}
				?>
		</select> 
		<!-- date filters -->

		<?php 

		$user_string = '';
		$user_id     = '';
		if ( ! empty( $_GET['_customer_user'] ) ) {
			$user_id     = absint( $_GET['_customer_user'] );
			$user        = get_user_by( 'id', $user_id );
			$user_string = esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')';
		}

		?>
<input type="hidden" class="wc-customer-search" name="_customer_user" data-placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', 'woocommerce' ); ?>" data-selected="<?php echo htmlspecialchars( $user_string ); ?>" value="<?php echo $user_id; ?>" data-allow_clear="true" />
<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">		</div>
<div class="tablenav-pages one-page"><span class="displaying-num"></span>
<span class="pagination-links"><span class="tablenav-pages-navspan" aria-hidden="true">«</span>
<span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">1</span></span></span>
<span class="tablenav-pages-navspan" aria-hidden="true">›</span>
<span class="tablenav-pages-navspan" aria-hidden="true">»</span></span></div>
		<br class="clear">
</div> 
<h2 class="screen-reader-text">Orders list</h2>

<table class="wp-list-table widefat fixed striped posts">
	<thead>
	<tr>
		<td id="cb" class="manage-column column-cb check-column td-width-2">
		 <label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">
	    </td>
		<th scope="col" id="order_status" class="manage-column td-width-5"><span class="status_head tips" data-tip="Status">Status</span></th>
		<th scope="col" id="order_title" class="manage-column column-primary order_title sortable desc td-width-10"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=ID&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == 'ID') ? 'desc' : 'asc'?>"><span>Order</span><span class="sorting-indicator"></span></a></th>

		<th scope="col" class="manage-column column-primary sortable desc td-width-15"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=_shipping_first_name&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == '_shipping_first_name') ? 'desc' : 'asc'?>"><span>Customer</span><span class="sorting-indicator"></span></a></th>
		<th scope="col" id="blu_did" class="manage-column column-primary sortable desc td-width-10"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=_bluOrder_id&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == '_bluOrder_id') ? 'desc' : 'asc'?>"><span>bluDID</span><span class="sorting-indicator"></span></a></th>
		<th scope="col" id="shipping_address" class="manage-column column-primary ship_to sortable desc td-width-15"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=ship&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == 'ship') ? 'desc' : 'asc'?>"><span>Ship To</span><span class="sorting-indicator"></span></a></th>
		<th scope="col" class="manage-column sortable desc td-width-15"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=_bluOrder_Address&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == '_bluOrder_Address') ? 'desc' : 'asc'?>"><span>Pickup Location</span><span class="sorting-indicator"></span></a></th>
	

		<th scope="col" id="label_printing" class="manage-column td-width-10">Delivery Status</th>
		<?php if($options['blu_enable_label'] == 'Enable') :?>
		<!-- <th scope="col" id="label_printing" class="manage-column column-order_actions">Label</th> -->
		<?php endif;?>
		<th scope="col" id="order_date" class="manage-column sortable desc td-width-8"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=date&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == 'date') ? 'desc' : 'asc'?>"><span>Date</span><span class="sorting-indicator"></span></a></th>

		<!-- <th scope="col" id="order_total" class="manage-column column-order_total sortable desc"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=order_total&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == 'order_total') ? 'desc' : 'asc'?>"><span>Total</span><span class="sorting-indicator"></span></a></th> -->
		<th scope="col" id="order_actions" class="manage-column td-width-10">Actions</th>	</tr>
	</thead>

	<tbody id="the-list">
		<?php 	
				if($wc_orders){
				foreach ((array) $wc_orders as $wc_order) { 
					$the_order = wc_get_order( $wc_order->ID );
					$if_response = get_post_meta($wc_order->ID, "_bluOrder_save_parcel_response", true);
				
					$class = '';
					$if_order = get_post_meta($wc_order->ID, "_bluOrder_order_not_send", true);
					if($if_order)
					{
						$class = '';
					}
					else
					{
						if($if_response != 'SUCCEEDED'){
	    					$class = 'resend-parcel';
	    				}
					}
	    			$shipping_items = $the_order->get_items( 'shipping' );
					foreach($shipping_items as $el){
					  $order_shipping_method_id = $el['method_id'] ;
					}
	    		?>
		<tr id="post-<?php echo $wc_order->ID?>" class="iedit author-self level-0 post-32 type-shop_order status-wc-processing post-password-required hentry <?php echo $class ?>">
			<th scope="row" class="check-column">
				<label class="screen-reader-text" for="cb-select-<?php echo $wc_order->ID ?>">Select <?php echo $wc_order->post_title?></label>
				<input id="cb-select-<?php echo $wc_order->ID?>" type="checkbox" name="post[]" value="<?php echo $wc_order->ID ?>">
				<div class="locked-indicator">
					<span class="locked-indicator-icon" aria-hidden="true"></span>
					<span class="screen-reader-text">"<?php echo $wc_order->post_title?>" is locked</span>
				</div>
			</th>
			<td class="order_status column-order_status" data-colname="Status">
				<mark class="<?php echo sanitize_title( $the_order->get_status() ) ?> tips" data-tip="<?php echo wc_get_order_status_name($wc_order->post_status) ?>"><?php echo wc_get_order_status_name($wc_order->post_status) ?></mark> 
			</td>
			<td class="order_title has-row-actions column-primary" data-colname="Order">

				<?php

				printf( _x( '%s', 'Order number by X', 'woocommerce' ), '<a href="' . admin_url( 'post.php?post=' . absint( $wc_order->ID ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $the_order->get_order_number() ) . '</strong></a>');

				?>

			<div class="row-actions"><span class="edit"><a href="<?php echo site_url()?>/wp-admin/post.php?post=<?php echo $wc_order->ID?>&action=edit" aria-label="Edit “<?php echo $wc_order->post_title?>”">Edit</a> | </span>
				<span class="trash"><a href="admin.php?page=blulogistics&tab=blu_order&post=<?php echo $wc_order->ID?>&action=trash" class="submitdelete" aria-label="Move “<?php echo $wc_order->post_title?>” to the Trash">Trash</a></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>

			</td>

			<td class="order_title has-row-actions column-primary" data-colname="Customer">
				<?php /** fetching user name **/
					if ( $the_order->user_id ) {
						$user_info = get_userdata( $the_order->user_id );
					}

					if ( ! empty( $user_info ) ) {

						$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

						if ( $user_info->first_name || $user_info->last_name ) {
							$username .= esc_html( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), ucfirst( $user_info->first_name ), ucfirst( $user_info->last_name ) ) );
						} else {
							$username .= esc_html( ucfirst( $user_info->display_name ) );
						}

						$username .= '</a>';

					} else {
						if ( $the_order->billing_first_name || $the_order->billing_last_name ) {
							$username = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $the_order->billing_first_name, $the_order->billing_last_name ) );
						} else if ( $the_order->billing_company ) {
							$username = trim( $the_order->billing_company );
						} else {
							$username = __( 'Guest', 'woocommerce' );
						}
					}

					echo $username;
					// add billing email to user
					if ( $the_order->billing_email ) {
						echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $the_order->billing_email ) . '">' . esc_html( $the_order->billing_email ) . '</a></small>';
					}
				?>
			</td>

			<td class="order_items" data-colname="bluDID">
				<?php echo get_post_meta($wc_order->ID, "_bluOrder_id", true);?>
			</td>

			<td class="billing_address column-billing_address hidden" data-colname="Billing">
				<?php 
						if ( $address = $the_order->get_formatted_shipping_address() ) {
							echo '<a target="_blank" href="' . esc_url( $the_order->get_shipping_address_map_url() ) . '">'. esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) .'</a>';
						} else {
							echo '&ndash;';
						}

				if ( $the_order->get_shipping_method() ) {
					echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_shipping_method() ) . '</small>';
				} ?>
			</td>

			<td class="shipping_address column-shipping_address" data-colname="Ship to">

			<?php
					if($order_shipping_method_id == 'blu_terminal_shipping_method'){
						$selected_location = $BluApi->blsmGetBluPortAddress($wc_order->ID);
						echo '<a target="_blank">'.$selected_location[0].'</a>';
					}
					else {
						if ( $address = $the_order->get_formatted_shipping_address() ) {
							echo '<a target="_blank" href="' . esc_url( $the_order->get_shipping_address_map_url() ) . '">'. esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) .'</a>';
						} else {
							echo '&ndash;';
						}
					}

					if ( $the_order->get_shipping_method() ) {
						echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_shipping_method() ) . '</small>';
					}

			?>
			</td>

			<td class="customer_message column-pickup_address" data-colname="Pickup Address">
				<?php if($order_shipping_method_id == 'blu_terminal_shipping_method'){
					  	$selected_location = $BluApi->blsmGetBluPortAddress($wc_order->ID);
					  	unset($selected_location[4]);
					  	foreach ($selected_location as $key) {
					  		echo $key.'<br>';
					  	}
					  }
					  else {
					  		echo 'Not Applicable';
					  } 
				?>
			</td>

			<td class="customer_message column-delivery_status" data-colname="Delivery Status">
				<?php $if_order = get_post_meta($wc_order->ID, "_bluOrder_order_not_send", true); 
						 echo $BluApi->blsmGetDeliveryStatus($wc_order->ID);
				?>
			</td>

			<?php if($options['blu_enable_label'] == 'Enable') :?>
			<!-- <td class="customer_message column-label" data-colname="Label">
				<a href="<?php echo BLU_PLUGIN_PATH ?>/admin/label/display_label.php?id=<?php echo $wc_order->ID ?>" target="_blank">
					<img src="<?php echo BLU_PLUGIN_PATH ?>/admin/images/label.png" class="blu-order display-label">
				</a>
			</td> -->
			<?php endif; ?>

			<td class="order_date column-order_date" data-colname="Date">
				<?php
				if ( '0000-00-00 00:00:00' == $wc_order->post_date ) {
					$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
				} else {
					$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $wc_order );
					$h_time = get_the_time( __( 'Y/m/d', 'woocommerce' ), $wc_order );
				}

				echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'post_date_column_time', $h_time, $wc_order ) ) . '</abbr>';
				?>
			</td>


			<td class="order_actions column-order_actions label-width" data-colname="Actions">
				<p>
					<?php
						do_action( 'woocommerce_admin_order_actions_start', $the_order );

						$actions = array();

						if ( $the_order->has_status( array( 'pending', 'on-hold' ) ) ) {
							$actions['processing'] = array(
								'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $wc_order->ID ), 'woocommerce-mark-order-status' ),
								'name'      => __( 'Processing', 'woocommerce' ),
								'action'    => "processing"
							);
						}

						if ( $the_order->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
							$actions['complete'] = array(
								'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $wc_order->ID ), 'woocommerce-mark-order-status' ),
								'name'      => __( 'Complete', 'woocommerce' ),
								'action'    => "complete"
							);
						}

						$actions['view'] = array(
							'url'       => admin_url( 'post.php?post=' . $wc_order->ID . '&action=edit' ),
							'name'      => __( 'View', 'woocommerce' ),
							'action'    => "view"
						);

						$actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $the_order );

						unset($actions['label']);
						foreach ( $actions as $action ) {
							printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
						}

						if($options['blu_enable_label'] == 'Enable') : 
						echo '<a href="admin-ajax.php?action=blsm_label_pdf&data='.$wc_order->ID.'" target="_blank" class="button tips label blsm-label" data-tip="Label">
						<img src="'.BLU_PLUGIN_PATH.'/admin/images/label.png" class="blu-order display-label"></a>';
						endif;

						do_action( 'woocommerce_admin_order_actions_end', $the_order );
					?>
				</p>
			</td>
							</tr>
		<?php } } else { 
			?>
			<tr class="no-items"><td class="colspanchange" colspan="10">No orders found.</td></tr>
		<?php } ?>
		</tbody>

	<tfoot>
	<tr>
		<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></td>
		<th scope="col" class="manage-column column-order_status"><span class="status_head tips" data-tip="Status">Status</span></th>
		<th scope="col" class="manage-column column-primary sortable desc"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=ID&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == 'ID') ? 'desc' : 'asc'?>"><span>Order</span><span class="sorting-indicator"></span></a></th>
		<th scope="col" class="manage-column column-primary sortable desc"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=_shipping_first_name&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == '_shipping_first_name') ? 'desc' : 'asc'?>"><span>Customer</span><span class="sorting-indicator"></span></a></th>
		<th scope="col" id="blu_did" class="manage-column column-primary sortable desc"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=_bluOrder_id&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == '_bluOrder_id') ? 'desc' : 'asc'?>"><span>bluDID</span><span class="sorting-indicator"></span></a></th>
		
		<th scope="col" id="shipping_address" class="manage-column column-primary sortable desc"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=_shipping_address_1&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == '_shipping_address_1') ? 'desc' : 'asc'?>"><span>Ship To</span><span class="sorting-indicator"></span></a></th>
		<th scope="col" class="manage-column column-order_total sortable desc"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=_bluOrder_Address&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == '_bluOrder_Address') ? 'desc' : 'asc'?>"><span>Pickup Location</span><span class="sorting-indicator"></span></a></th>
		<th scope="col" id="label_printing" class="manage-column column-order_actions">Delivery Status</th>
		<?php if($options['blu_enable_label'] == 'Enable') :?>
		<!-- <th scope="col" id="label_printing" class="manage-column column-order_actions">Label</th> -->
		<?php endif;?>
		<th scope="col" class="manage-column column-order_date sortable desc"><a href="admin.php?page=blulogistics&tab=blu_order&post_status=<?php echo $_GET['post_status']?>&orderby=date&order=<?php echo ($_GET['order'] == 'asc' && $_GET['orderby'] == 'date') ? 'desc' : 'asc'?>"><span>Date</span><span class="sorting-indicator"></span></a></th>
		<th scope="col" class="manage-column column-order_actions">Actions</th>	</tr>
	</tfoot>

</table>
<div class="tablenav bottom">


<div class="alignleft actions bulkactions">
<label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
	<select name="action2" id="bulk-action-selector-bottom">
	<option value="-1">Bulk Actions</option>
	<option value="trash">Move to Trash</option>
	</select>
	<input type="submit" id="doaction2" class="button action" value="Apply">
		</div>
		<br class="clear">
		<div class="alignleft actions">
			<?php

			$wp_query_args = array(
				'post_type'      => $args['type'] ? $args['type'] : 'shop_order',
				'post_status'    => $args['status'],
				'posts_per_page' => -1,
				'meta_key'       => "_bluOrder_id",
			);

			$total_orders = new WP_Query( $wp_query_args );
			$tc_orders = $total_orders->posts;

			include_once('pagination.php');
			$limit = get_option( 'posts_per_page' );
			$page = isset($_GET['paged']) ? ((int) $_GET['paged']) : 1;
			$paged =  ($page)?$limit*($page-1):$page;  

			$paged = ($page ==0)?$page+1:$page;

			$pagination = new Pagination();
			$pagination->setCurrent($paged);
			$pagination->setTotal(count($tc_orders));
			$pagination->setRPP($limit);
			$pagination->setCrumbs(10);

			echo $pagination_html =    '<div class="pagination">'.$pagination->parse().'</div>';

			?>

		</div>
	</div>

</form>

<div id="ajax-response"></div>
<br class="clear">
</div>

<script type="text/javascript">

jQuery(function() {
	jQuery('<option>').val('mark_processing').text('<?php _e( 'Mark processing', 'woocommerce' )?>').appendTo('select[name="action"]');
	jQuery('<option>').val('mark_processing').text('<?php _e( 'Mark processing', 'woocommerce' )?>').appendTo('select[name="action2"]');

	jQuery('<option>').val('mark_on-hold').text('<?php _e( 'Mark on-hold', 'woocommerce' )?>').appendTo('select[name="action"]');
	jQuery('<option>').val('mark_on-hold').text('<?php _e( 'Mark on-hold', 'woocommerce' )?>').appendTo('select[name="action2"]');

	jQuery('<option>').val('mark_completed').text('<?php _e( 'Mark complete', 'woocommerce' )?>').appendTo('select[name="action"]');
	jQuery('<option>').val('mark_completed').text('<?php _e( 'Mark complete', 'woocommerce' )?>').appendTo('select[name="action2"]');
});

</script>

<?php

}

?>