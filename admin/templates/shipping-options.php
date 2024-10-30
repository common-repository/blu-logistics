<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function get_blu_shipping_option(){
   register_setting( 'shippingOption', 'blu_shipping_settings', 'shipping_validate_input' );

   add_settings_section(
      'blulogistics_section', 
      __( '', 'blulogistics' ), 
      'blu_shipping_settings_section_callback', 
      'shippingOption'
   ); 

   add_settings_field( 
      'blu_text_field_8', 
      __( 'Send order details to blu’s system:', 'blulogistics' ), 
      'blu_shipping_text_field_8_render', 
      'shippingOption', 
      'blulogistics_section' 
   ); 

   add_settings_field( 
      'blu_text_field_1', 
      __( 'Check this box to be enabled to drop-off your parcels at a bluPort:', 'blulogistics' ), 
      'blu_shipping_text_field_1_render', 
      'shippingOption', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_2', 
      __( 'blu Delivery Method', 'blulogistics' ), 
      'blu_shipping_text_field_2_render', 
      'shippingOption', 
      'blulogistics_section' 
   ); 

   add_settings_field( 
      'blu_text_field_3', 
      __( '<a href="admin.php?page=wc-settings&tab=shipping&section=blu_terminal_shipping_method">bluPort Parcel Terminal</a>', 'blulogistics' ), 
      'blu_shipping_text_field_3_render', 
      'shippingOption', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_4', 
      __( '<a href="admin.php?page=wc-settings&tab=shipping&section=blu_home_shipping_method">bluHome</a>', 'blulogistics' ), 
      'blu_shipping_text_field_4_render', 
      'shippingOption', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_5', 
      __( '<a href="admin.php?page=wc-settings&tab=shipping&section=blu_global_shipping_method">bluGlobal</a>', 'blulogistics' ), 
      'blu_shipping_text_field_5_render', 
      'shippingOption', 
      'blulogistics_section' 
   );


   add_settings_field( 
      'blu_text_field_6', 
      __( ' ', 'blulogistics' ), 
      'blu_shipping_settings_section_callback_info', 
      'shippingOption', 
      'blulogistics_section' 
   ); 

   add_settings_field( 
      'blu_text_field_7', 
      __( ' ', 'blulogistics' ), 
      'blu_shipping_settings_section_callback_info_for_pricing', 
      'shippingOption', 
      'blulogistics_section' 
   ); 
}

function blu_shipping_text_field_0_render(  ) { 

   $options = get_option( 'blu_shipping_settings' );
   ?>
   <select name="blu_shipping_settings[units]">
      <option value="">KGS</option>
      <option value="">GRAM</option>
   </select>
   <?php

}

function blu_shipping_text_field_1_render(  ) { 

   $options = get_option( 'blu_shipping_settings' );
   ?>
   <input type='checkbox' name='blu_shipping_settings[drop_off]' value='<?php echo $options['drop_off']; ?>' <?php if($options['drop_off']) echo 'checked="checked"'?> id="drop_off">
   <p class="info">(If you're unsure of what this function does, please contact blu)</p>
   <?php
}

function blu_shipping_text_field_8_render(  ) { 

   $options = get_option( 'blu_shipping_settings' );
   ?>
   <input type='checkbox' name='blu_shipping_settings[send_order]' value='<?php echo $options['send_order']; ?>' <?php if($options['send_order']) echo 'checked="checked"'?> id="send_order">
   <?php
}

function blu_shipping_text_field_2_render(  ) { ?>

   <div class="zone-heading">Description</div>
  <div class="zone-heading">Zone(s)</div>
  <div class="blu-heading">Get blu's pricing</div>
  <div class="markup-heading">Markup</div>
   <?php

}

function blu_shipping_text_field_3_render(  ) { 

   $options = get_option( 'blu_shipping_settings' );
   $get_country = get_option('woocommerce_blu_terminal_shipping_method_settings');
   $countries_list = $get_country['countries'];

   global $woocommerce;
   ?>
   <div class="zones"><?php echo $get_country['description']; ?></div>
   <div class="zones">
   <?php

   if($countries_list)
   {
      foreach ($countries_list as $key ) {
         echo '<label>'.WC()->countries->countries[ $key ].'</label><br>';
      }
   }
   else {
          echo '<label>Singapore</label>';
   } ?>
   </div>
<?php }

function blu_shipping_text_field_4_render(  ) { 

   $options = get_option( 'blu_shipping_settings' );
   $get_country = get_option('woocommerce_blu_home_shipping_method_settings');
   $countries_list = $get_country['countries'];

   global $woocommerce;
   ?>
   <div class="zones"><?php echo $get_country['description']; ?></div>
   <div class="zones">
   <?php

   if($countries_list)
   {
      foreach ($countries_list as $key ) {
         echo '<label>'.WC()->countries->countries[ $key ].'</label><br>';
      }
   }
   else {
          echo '<label>Singapore</label>';
   } ?>
</div>
<?php }

function blu_shipping_text_field_5_render(  ) { 

   global $woocommerce;
   $options = get_option( 'blu_shipping_settings' );
   $get_shipping_method = get_option('woocommerce_blu_global_shipping_method_settings');
   $get_country = get_option('blu_global_shipping_method_table_rates');
   foreach ($get_country as $key) {
      foreach ($key['countries'] as $val) {
          $zone_and_country[] = $val;
       } 
   }
   $zone_and_country = array_unique($zone_and_country);
   $zones = WC()->countries->get_continents();

   ?>
   <div class="zones"><?php echo $get_shipping_method['description']; ?></div>
   <div class="zones">
   <?php

   if($zone_and_country)
   {
      foreach ($zone_and_country as $key ) {
         if (($pos = strpos($key, "zone:")) !== FALSE) { 
               $zone_code = substr($key, $pos+5); 
               echo '<label>'.$zones[$zone_code]['name'].'</label><br>';
         } else {
               echo '<label>'.WC()->countries->countries[ $key ].'</label><br>';
         }
      }
   }
   else {
         echo '<label>No record(s)</label>';
   } 

   ?>
   </div>
   <div class="blu-pricing">
      <input type='checkbox' name='blu_shipping_settings[use_api]' value='<?php echo $options['use_api']; ?>' id="use_api" <?php if($options['use_api']) echo 'checked="checked"'?>>Get blu's pricing
      <p class="info">Use the 'Get blu's pricing' function, to obtain bluGlobal prices directly from blu to be shown on your shop's checkout page</p>
   </div>
   <div class="markup">
      <select name="blu_shipping_settings[mark_up]" class="enable-api">
         <option value="none" <?php if($options['mark_up'] == 'none') echo 'selected';?>>None</option>
         <option value="fixed" <?php if($options['mark_up'] == 'fixed') echo 'selected';?>>Fixed Amount</option>
         <option value="percent" <?php if($options['mark_up'] == 'percent') echo 'selected';?>>% of Shipping Price</option>
      </select>
      <input name="blu_shipping_settings[price]" value="<?php echo $options['price'] ?>" class="enable-api nocopy" type="number"/>
   </div>
   <?php

}

function blu_shipping_settings_section_callback_info( ){
   echo '<p class="info">To enable/disable a blu Delivery Method and to manage the Delivery Zones, click into each Delivery Method to access the shipping settings of your Woocommerce account. 
         Note that the Delivery Zone for bluPort and bluHome should always be Singapore only.</p>';
}

function blu_shipping_settings_section_callback_info_for_pricing( ){
   echo "<p class='info blu-global-api'>For bluGlobal, your shop's checkout page will reflect the price from blu plus your markup (if any). <br>
         Note that in order for this functionality to work, your products must have weight dimensions saved and updated. To add a product's weight, open the Products menu, click on 'Edit' next to the relevant product, and update the 'Weight (kg)' under the 'Shipping' section.</p>";
}

function blu_shipping_settings_section_callback(  ) { 

   $title = '<h3>SHIPPING PREFERENCES</h3>';
   echo __( $title, 'blulogistics' );
}


function blu_shipping_option_submit( ){ ?>
   <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Update"></p>
<?php }


function shipping_validate_input( $input ) {

   // Create our array for storing the validated options

   $valid = array();
   if(isset($input['drop_off']))
      $valid['drop_off'] = '1';
   else
      $valid['drop_off'] = '0';

   if(isset($input['use_api']))
      $valid['use_api'] = '1';
   else
      $valid['use_api'] = '0';

   if(isset($input['send_order']))
      $valid['send_order'] = '1';
   else
      $valid['send_order'] = '0';

   $valid['mark_up'] = $input['mark_up'];
   $valid['price'] = $input['price'];

   $prev_option = get_option('blu_shipping_settings');

   blsm_save_message( 'success', __( 'Settings Updated.') );

   return $valid;
 
}
?>