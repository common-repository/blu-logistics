<?php 
/*
Plugin Name: blu Logistics
Plugin URI: 
Description: Connect seamlessly with blu's retail logistics platform and the bluPort Parcel Terminal network!
Version: 1.0.0
Author: Blu Logistics Pte. Ltd.
*/

/**
 * Check if WooCommerce is activeana
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
if ( in_array( 'woocommerce/woocommerce.php', $active_plugins) ) {

  session_start();
  define( 'BLU_PLUGIN_PATH', plugins_url('', __FILE__ ) );

  $_SESSION['wpdb'] = $wpdb;

  $blu_auth_result = get_option('blu_auth_result'); 

  include_once('bluAPI.php'); 
  include_once('admin/settings.php');

  /** add custom menu in admin panel **/
  add_action( 'admin_menu', 'blsm_add_admin_menu' );
  add_action( 'admin_init', 'blsm_settings_init' );

  function blsm_add_admin_menu( ) { 
    global $page_hook_suffix;
    $page_hook_suffix = add_menu_page( 'blu Logistics', 'blu Logistics', 'manage_options', 'blulogistics', 'blu_options_page' );
  }

  function blsm_settings_init( ) { 
       get_contact_merchant();
       get_blu_authentication();
       get_blu_label_printing();
       get_blu_shipping_option();
  }

  /** load custom scripts in admin and customer**/
  function blsm_load_scripts($hook) {
  
    global $woocommerce; $page_hook_suffix;
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-core' );
    wp_enqueue_script('jquery-ui-sortable'); //load sortable
    wp_enqueue_script('jquery-ui-tabs'); //load tabs

    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
    {
      wp_enqueue_script( 'prettyPhoto', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
      wp_enqueue_script( 'prettyPhoto-init', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto.init' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
      wp_enqueue_style( 'woocommerce_prettyPhoto_css', $woocommerce->plugin_url() . '/assets/css/prettyPhoto.css' );
    }

      // if(!is_page())
      //   wp_enqueue_style( 'bootstrap', plugins_url( '  ', __FILE__ ) );

      wp_enqueue_script('fancybox', plugins_url( 'admin/js/jquery.fancybox.js', __FILE__ ), array( 'jquery' ), 1.1, true );
      wp_enqueue_style( 'fancybox', plugins_url( 'admin/css/fancybox.css', __FILE__ ) );
      wp_enqueue_script('custom-js', plugins_url( 'admin/js/blu.min.js', __FILE__ ), array( 'jquery' ), 1.1, true);

      if($_GET['tab'] == 'blu_order'){
          wp_localize_script( 'custom-js', 'wc_enhanced_select_params', array(
            'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
            'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
            'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
            'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
            'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
            'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
            'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
            'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
            'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
            'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
            'ajax_url'                  => admin_url( 'admin-ajax.php' ),
            'search_products_nonce'     => wp_create_nonce( 'search-products' ),
            'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
          ) );

        wp_enqueue_script('woocommerce-ui', plugins_url( 'woocommerce/assets/js/jquery-blockui/jquery.blockUI.min.js', 'woocommerce' ), array( 'jquery' ), 1.1, true);
        wp_enqueue_script('woocommerce-tooltip', plugins_url( 'woocommerce/assets/js/jquery-tiptip/jquery.tipTip.min.js','woocommerce' ), array( 'jquery' ), 1.1, true);
        wp_enqueue_script('woocommerce-admin', plugins_url( 'woocommerce/assets/js/admin/woocommerce_admin.min.js', 'woocommerce' ), array( 'jquery' ), 1.1, true);
        wp_enqueue_script('woocommerce-select2', plugins_url( 'woocommerce/assets/js/select2/select2.min.js', 'woocommerce' ), array( 'jquery' ), 1.1, true);
        wp_enqueue_script('woocommerce-enhanced', plugins_url( 'woocommerce/assets/js/admin/wc-enhanced-select.min.js', 'woocommerce' ), array( 'jquery' ), 1.1, true);
      }

      $scriptData = array();
      $scriptData['ajaxurl'] = admin_url( 'admin-ajax.php' );
      $scriptData['action'] = 'blsm_resend_parcel';

      $ship_to = array();
      $ship_to['ajaxurl'] = admin_url( 'admin-ajax.php' );
      $ship_to['action'] = 'blsm_if_ship_to_different_address';

      $label_pdf = array();
      $label_pdf['ajaxurl'] = admin_url( 'admin-ajax.php' );
      $label_pdf['action'] = 'blsm_label_pdf';

      $google_map = array();
      $google_map['ajaxurl'] = admin_url( 'admin-ajax.php' );
      $google_map['action'] = 'blsm_google_map';

      $search_radius = array();
      $search_radius['ajaxurl'] = admin_url( 'admin-ajax.php' );
      $search_radius['action'] = 'blsm_search_radius';

      $change_address = array();
      $change_address['ajaxurl'] = admin_url( 'admin-ajax.php' );
      $change_address['action'] = 'blsm_change_address';

      wp_localize_script( 'custom-js', 'blu_data', $scriptData );
      wp_localize_script( 'custom-js', 'blu_shipping', $ship_to );
      wp_localize_script( 'custom-js', 'blu_label_pdf', $label_pdf );
      wp_localize_script( 'custom-js', 'blu_search_radius', $search_radius );
      wp_localize_script( 'custom-js', 'blu_change_address', $change_address );
      wp_localize_script( 'custom-js', 'blu_google_map', $google_map );

      wp_enqueue_style( 'common-css', plugins_url( 'admin/css/blu.common.css', __FILE__ ) );

      if($hook != 'toplevel_page_blulogistics')
        return;

      wp_enqueue_style( 'woocommerce-light', plugins_url( 'admin/css/light.css', __FILE__  ));
      wp_enqueue_style( 'woocommerce-admin-css', $woocommerce->plugin_url() . '/assets/css/admin.css' );
      wp_enqueue_style( 'blu-font-awesome', plugins_url( 'admin/css/font-awesome.min.css', __FILE__  ) );

      wp_enqueue_style( 'custom-css', plugins_url( 'admin/css/blu.min.css', __FILE__ ) );
    }

    add_action('admin_enqueue_scripts', 'blsm_load_scripts', 20);
    add_action( 'wp_enqueue_scripts', 'blsm_load_scripts', 1000 , 1);

    if(empty($blu_auth_result) || ($blu_auth_result == 'failed')){
      return;
    }

    include_once('blu-shipping.php');
    include_once('admin/templates/blu-order.php');

    /** initialize shipping method **/
    add_filter( 'woocommerce_shipping_methods', 'add_blu_delivery_shipping_method' );
    function add_blu_delivery_shipping_method( $methods ) {
      $methods['blu_home_shipping_method'] = 'WC_Blu_Home_Delivery_Shipping_Method';
      $methods['blu_terminal_shipping_method'] = 'WC_Blu_Terminal_Delivery_Shipping_Method';
      $methods['blu_global_shipping_method'] = 'WC_Blu_Global_Delivery_Shipping_Method';
      return $methods;
    }

    /** initialize shipping **/
    add_action( 'woocommerce_shipping_init', 'blu_delivery_shipping_method_init' );
    function blu_delivery_shipping_method_init(){
       require_once 'class-blu-home-method.php';
       require_once 'class-blu-terminal-method.php';
       require_once 'class-blu-global-method.php';
    }

    /** set blu plugin url **/
    add_action('wp_head', 'blu_plugin_url');

    function blu_plugin_url() {
        ?>
      <input type="hidden" id="refresh" value="no">
            <script type="text/javascript">
              var plugin_url = "<?php echo plugin_dir_url( __FILE__ ); ?>";
              var site_admin_url = "<?php echo admin_url(); ?>";
              var note1 = "For bluPort delivery, if you wish to update the pickup location click the 'Select bluPort' button at the cart page or checkout page and select the appropriate bluPort location for collecting your parcel.";
              var note2 = "The delivery methods shown on the right will be with respect to your Billing Address, unless the ‘Ship to a different address’ option below has been selected.";
            </script>
        <?php
    }

    function blu_plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }
   
    /** override template path if it exists**/
    add_filter( 'woocommerce_locate_template', 'blsm_woocommerce_locate_template', 10, 3 );
   
    function blsm_woocommerce_locate_template( $template, $template_name, $template_path ) {
     
      global $woocommerce;
      $_template = $template;
     
      if ( ! $template_path ) $template_path = $woocommerce->template_url;
        $plugin_path  = blu_plugin_path() . '/woocommerce/';
     
      $template = locate_template(  array(
                    $template_path . $template_name,
                    $template_name));
     
      // Modification: Get the template from this plugin, if it exists
      if ( ! $template && file_exists( $plugin_path . $template_name ) )
        $template = $plugin_path . $template_name;

      // Use default template
     
      if ( ! $template )
        $template = $_template;
      return $template;
     
    }
} 