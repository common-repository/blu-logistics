<?php

/**
 * Blu Options Settings
 *
 * @author      Blu
 * @category    Admin
 * @package     Blu/Admin
 * @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once('templates/contact-merchant.php');
include_once('templates/authentication.php');
include_once('templates/label-printing.php');
include_once('templates/shipping-options.php');
include_once('templates/blu-order.php');

function blu_options_page( ) { 

   $blu_auth_result = get_option('blu_auth_result');
   $class = '';
   if(empty($blu_auth_result) || ($blu_auth_result == 'failed'))
      $class = "disable-select";
   ?>
   <?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'contact_merchant'; ?>
   <script type="text/javascript" src="<?php echo plugins_url( '/js/blu.min.js', __FILE__ ) ?>"></script>
   <form action='options.php' method='post' class="blu-options-wrap <?php if($active_tab == 'options_settings') echo 'module-settings'?>">

      <h2><img src="<?php echo BLU_PLUGIN_PATH ?>/admin/images/blu-logo.png" style="margin: 0 10px 0 0;">blu Logistics</h2>

      <?php  include_once('message.php'); ?>

      <h2 class="nav-tab-wrapper">
            <a href="?page=blulogistics&tab=contact_merchant" class="nav-tab <?php echo $active_tab == 'contact_merchant' ? 'nav-tab-active' : ''; ?>">Contact blu</a>
            <a href="?page=blulogistics&tab=authentication" class="nav-tab <?php echo $active_tab == 'authentication' ? 'nav-tab-active' : ''; ?>">Authentication</a>
            <a href="?page=blulogistics&tab=options_settings" class="nav-tab <?php if($class) echo $class; ?> <?php echo $active_tab == 'options_settings' ? 'nav-tab-active' : ''; ?>">Module Settings</a>
            <a href="?page=blulogistics&tab=label_printing" class="nav-tab <?php if($class) echo $class; ?> <?php echo $active_tab == 'label_printing' ? 'nav-tab-active' : ''; ?>">Label Generator</a>
            <a href="?page=blulogistics&tab=blu_order" class="nav-tab <?php if($class) echo $class; ?> <?php echo $active_tab == 'blu_order' ? 'nav-tab-active' : ''; ?>">blu Orders</a>
      </h2>

      <?php

        $messages = blsm_get_messages();
        if ( $messages ) { 
          if($_SESSION['messages']['error merchant']) {
            $error .= '<div id="message" class="notice notice-error is-dismissible"><p>'.$_SESSION['messages']['error merchant'].'</p></div>';
          }
          if($_SESSION['messages']['error key']) {
            $error .= '<div id="message" class="notice notice-error is-dismissible"><p>'.$_SESSION['messages']['error key'].'</p></div>';
          }
           if($error) {  
                echo $error; 
            } else { ?>
              <div id="message" class="updated notice notice-success is-dismissible">
                 <?php echo $messages; ?>
              </div>
           <?php } 
          blsm_clean_message();
        }

      ?>

      <?php

      /**
      * Contact to Merchant Tab
      **/
      if($active_tab == 'contact_merchant') :
            settings_fields( 'merchantID' );
            do_settings_sections( 'merchantID' );
            blu_contact_merchant_submit();
      endif;

      /**
      * Merchant Authentication Tab
      **/
      if($active_tab == 'authentication') :
            settings_fields( 'authentication' );
            do_settings_sections( 'authentication' );
            blu_authenticate_submit();
      endif;

      /**
      * Shipping Options Tab
      **/
      if($active_tab == 'options_settings') :
            settings_fields( 'shippingOption' );
            do_settings_sections( 'shippingOption' );
            blu_shipping_option_submit();
      endif;

      /**
      * Label Printing Tab
      **/
      if($active_tab == 'label_printing') :
            settings_fields( 'labelPrinting' );
            do_settings_sections( 'labelPrinting' );
            blu_label_printing_submit();
      endif;
      ?>

   </form>
   <?php

      /**
      * Blu Orders Tab
      **/
      if($active_tab == 'blu_order') :
          blsm_get_orders();
      endif;

   ?>
   <?php

}

function blsm_save_message( $type, $message = '' ) {
    $_SESSION['messages'][$type] = $message;
}

function blsm_get_messages() {
    $return = '';
    if ( isset( $_SESSION['messages'] ) && is_array( $_SESSION['messages'] ) ) {
        foreach( $_SESSION['messages'] as $type => $message ) {
            if($message)
               $return .= sprintf( '<p class="%1$s">%2$s</p>', $type, $message );
        }
    }

    if ( strlen( $return ) > 0 )
        return $return;

    return false;
}

function blsm_clean_message( $type = false ) {
    if ( ! $type )
        $_SESSION['messages'] = array();

    else
        unset( $_SESSION['messages'][$type] );
}

?>